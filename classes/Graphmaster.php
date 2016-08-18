<?php

class Graphmaster {

    public $bot;
    public $root;
    public $name;
    public $vocabulary;
    public $verbose;
    public $bot_setMap;
    public $config_path;
    public $bot_properties;

    public $DEBUG = false;

    public $matchCount = 0;
    public $upgradeCnt = 0;
    public $resultNote = "";
    public $categoryCnt = 0;
    public $enableShortCuts = false;

    public $leafCnt;
    public $nodeCnt;
    public $nodeSize;
    public $singletonCnt;
    public $shortCutCnt;
    public $naryCnt;

    private $go;
    private $debugger;
   
    public function __construct($bot, $name = "brain", $debugger = null) {
        if (is_null($debugger)) $debugger = new Logger(LOG_PATH . "$bot.debug.log", LOG_ALL);
        $this->debugger = $debugger;
        $this->root = new Nodemapper();
        $this->bot_setMap = $bot->setMap;
        $this->config_path = $bot->config_path;
        $this->name = $name;
        $this->vocabulary = array();
        $this->verbose = false;

        $this->go = false;
    }

/*
    public function __sleep() {
      $root = serialize($this->root);
      file_put_contents($this->bot->config_path . '/root.dat', $root);
      $this->root = null;
      return array('root');
    }

    public function __wakeup() {
      $root = file_get_contents($this->bot->config_path . '/root.dat');
      $this->root = unserialize($root);
    }

*/
    public static function inputThatTopic ($input, $that, $topic)  {
        return trim($input) . " <THAT> " . trim($that) . " <TOPIC> " . trim($topic);
    }

    public function replaceBotProperties ($pattern) {

        if (strpos($pattern,'<B') !== false) {
            $regex = '<BOT NAME=\"(.*?)\"/>';

            $key = preg_match($regex, $pattern, $matches) ? strtolower($matches[1]) : 0;

            if(!$key)
            {
                if(strpos($pattern,'<BOT>') !== false) {
                    $regex = "/<BOT><NAME>(.*?)<\/NAME><\/BOT>/";
                    $key = preg_match($regex, $pattern, $matches) ? strtolower($matches[1]) : 0;
                    if($key && array_key_exists($key, $this->bot->properties)) {
                        $pattern = str_replace($matches[0], strtoupper($this->bot->properties[$key]), $pattern);
                    }
                }
            }
            if($key && array_key_exists($key, $this->bot->properties)) {
                $pattern = str_replace("<".$matches[0].">", strtoupper($this->bot->properties[$key]), $pattern);
            }
        }
        return $pattern;
    }

    public function addCategory ($category) {
        $inputThatTopic = $this->inputThatTopic($category->getPattern(), $category->getThat(), $category->getTopic());
        $inputThatTopic = $this->replaceBotProperties($inputThatTopic);
        $p = Path::sentenceToPath($inputThatTopic);

        $this->addPath($p, $category);
        $this->categoryCnt++;
    }

    function thatStarTopicStar($path) {
        $tail = trim(Path::pathToSentence($path));
        return tail == ("<THAT> * <TOPIC> *");
    }

    function addSets ($type, $setMap, $node, $filename, $bot_name) {
        $setName = strtolower(Utilities::tagTrim($type, "set"));
        if (array_key_exists($setName, $setMap)) {
            if ($node->sets == null) $node->sets = array();
            if (!array_key_exists($setName, $node->sets)) array_push($node->sets, $setName);
        }
        else {
            trigger_error("No AIML Set found for <set>".$setName."</set> in ".$bot_name." ".$filename);
        }
    }

    function addPath($path, $category) {
        $this->addPathByNode($this->root, $path, $category);
    }

    function addPathByNode($node, $path, $category) {
        if ($path == null) {
            $node->category = $category;
            $node->height = 0;
        }
        else if ($this->enableShortCuts && $this->thatStarTopicStar($path)) {
            $node->category = $category;
            $node->height = min(4, $node->height);
            $node->shortCut = true;
        }
        else if (Nodemapper::containsKey($node, $path->word)) {
            if (substr( $path->word, 0, 5 ) === "<SET>") $this->addSets($path->word, $this->setMap, $node, $category->getFilename());
            $nextNode = Nodemapper::get($node, $path->word);
            $this->addPathByNode($nextNode, $path->next, $category);
            $offset = 1;
            if ($path->word === "#" || $path->word === "^") $offset = 0;
            $node->height = min($offset + $nextNode->height, $node->height);
        }
        else {
            $nextNode = new Nodemapper();
            if (substr( $path->word, 0, 5 ) === "<SET>") {
                $this->addSets($path->word, $this->bot, $node, $category->getFilename());
            }
            if ($node->key != null)  {
                Nodemapper::upgrade($node);
                $this->upgradeCnt++;
            }
            Nodemapper::put($node, $path->word, $nextNode);
            $this->addPathByNode($nextNode, $path->next, $category);
            $offset = 1;
            if ($path->word === "#" || $path->word === "^") $offset = 0;
            $node->height = min($offset + $nextNode->height, $node->height);
        }
    }

    public function existsCategory($c) {
       return ($this->findNode($c) != null);
    }

    public function findNode($c) {
        return $this->findNodeByInputThatTopic($c->getPattern(), $c->getThat(), $c->getTopic());
    }

    public function findNodeByInputThatTopic($input, $that, $topic) {
        $result = $this->findNodeByNodePath($this->root, Path::sentenceToPath($this->inputThatTopic($input, $that, $topic)));
        if ($this->verbose) echo("findNode ".$this->inputThatTopic($input, $that, $topic)." ".$result);
        return $result;
    }

    function findNodeByNodePath($node, $path) {
        if ($path == null && $node != null) {
            if ($this->verbose) echo("findNode: path is null, returning node ".$node->category->inputThatTopic());
            return $node;
        }
        else if (trim(Path::pathToSentence($path)) === "<THAT> * <TOPIC> *" && $node->shortCut && $path->word === "<THAT>") {
            if ($this->verbose) echo("findNode: shortcut, returning ".$node->category->inputThatTopic());
            return $node;
        }
        else if (Nodemapper::containsKey($node, $path->word)) {
            if ($this->verbose) echo("findNode: node contains ".$path->word);
            $nextNode = Nodemapper::get($node, strtoupper($path->word));
            return $this->findNodeByNodePath($nextNode, $path->next);
        }

        else {
            if ($this->verbose) echo("findNode: returning null");
            return null;
        }
    }

    public function match($input, $that, $topic) {
      $this->debugger->logEntry("Trying to match input($input), that($that) and topic($topic) within the AIML", Logger::LOG_TRIVIAL, __METHOD__, __LINE__);
        $n = null;
        try {
         $inputThatTopic = $this->inputThatTopic($input, $that, $topic);
         $p = Path::sentenceToPath($inputThatTopic);
         $n = $this->matchByPathITT($p, $inputThatTopic);
        }
        catch (Exception $ex) {
            echo($ex->getMessage());
            $n = null;
        }
        return $n;
    }

    function matchByPathITT($path, $inputThatTopic) {
        try {
            $inputStars = array();
            $thatStars = array();
            $topicStars = array();
            $starState = "inputStar";
            $matchTrace = "";
            $n = $this->matchWithStars($path, $this->root, $inputThatTopic, $starState, 0, $inputStars, $thatStars, $topicStars, $matchTrace);
            if ($n != null) {
                $sb = new StarBindings();
                for ($i=0; isset($inputStars[$i]) && $i < MagicNumbers::$max_stars; $i++) $sb->inputStars->add($inputStars[$i]);
                for ($i=0; isset($thatStars[$i]) && $i < MagicNumbers::$max_stars; $i++) $sb->thatStars->add($thatStars[$i]);
                for ($i=0; isset($topicStars[$i]) && $i < MagicNumbers::$max_stars; $i++) $sb->topicStars->add($topicStars[$i]);
                $n->starBindings = $sb;
            }
            if ($n != null) $n->category->addMatch($inputThatTopic, $this->bot);
            return $n;
        } catch (Exception $ex) {
            echo($ex->getMessage());
            return null;
        }
    }

    function matchWithStars($path, $node, $inputThatTopic, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        $matchedNode;
        $this->matchCount++;
        if (($matchedNode = $this->nullMatch($path, $node, $matchTrace)) != null) return $matchedNode;
        else if ($path->length < $node->height) {
           return null;}

        else if (($matchedNode = $this->dollarMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->sharpMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->underMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->wordMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->setMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->shortCutMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->caretMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else if (($matchedNode = $this->starMatch($path, $node, $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) return $matchedNode;
        else {
            //echo("Match failed (".$matchTrace.")");
            return null;
        }
    }

    function fail ($mode, $trace) {
       //echo("Match failed (".$mode.") ".$trace);
    }

    function nullMatch($path, $node, $matchTrace) {
        if ($path == null && $node != null && Nodemapper::isLeaf($node) && $node->category != null) return $node;
        else {
            $this->fail("null", $matchTrace);
            return null;
        }
    }

    function shortCutMatch($path, $node, $inputThatTopic, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        if ($node != null && $node->shortCut && $path->word === "<THAT>" && $node->category != null) {
            $tail = trim(Path::pathToSentence($path));
            preg_match('~<THAT>(.*?)<TOPIC>~', $tail, $output);
            $that = trim($output[1]);
            preg_match('~<TOPIC>(.*?)$~', $tail, $output);
            $topic = trim($output[1]);
            
            $thatStars[0] = $that;
            $topicStars[0] = $topic;
            return $node;
        }
        else {
            $this->fail("shortCut", $matchTrace);
            return null;
        }
    }

    function wordMatch($path, $node, $inputThatTopic, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        $matchedNode;
        try {
            $uword = strtoupper($path->word);
            if ($uword === "<THAT>") {$starIndex = 0; $starState = "thatStar";}
            else if ($uword === "<TOPIC>") {$starIndex = 0; $starState = "topicStar";}

            $matchTrace .= "[word,".$uword."]";
            if ($path != null && Nodemapper::containsKey($node, $uword) &&
                    ($matchedNode = $this->matchWithStars($path->next, Nodemapper::get($node, $uword), $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null)  {
                 return $matchedNode;
            } else {
                $this->fail("word", $matchTrace);
                return null;
            }
        } catch (Exception $ex) {
            echo("wordMatch: ".Path::pathToSentence($path).": ".$ex);
            echo($ex->getMessage());
            return null;
        }
    }

    function dollarMatch($path, $node, $inputThatTopic, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        $uword = "$".strtoupper($path->word);
        $matchedNode;
        if ($path != null && Nodemapper::containsKey($node, $uword) && ($matchedNode = $this->matchWithStars($path->next, Nodemapper::get($node, $uword), $inputThatTopic, $starState, $starIndex, $inputStars, $thatStars, $topicStars, $matchTrace)) != null)  {
            return $matchedNode;
        } else {
            $this->fail("dollar", $matchTrace);
            return null;
        }
    }

    function starMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        return $this->wildMatch($path, $node, $input, $starState, $starIndex, $inputStars, $thatStars, $topicStars, "*", $matchTrace);
    }

    function underMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        return $this->wildMatch($path, $node, $input, $starState, $starIndex, $inputStars, $thatStars, $topicStars, "_", $matchTrace);
    }

    function caretMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        $matchedNode;
        $matchedNode = $this->zeroMatch($path, $node, $input, $starState, $starIndex, $inputStars, $thatStars, $topicStars, "^", $matchTrace);
        if ($matchedNode != null) return $matchedNode;
        else return $this->wildMatch($path, $node, $input, $starState, $starIndex, $inputStars, $thatStars, $topicStars, "^", $matchTrace);
    }

    function sharpMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        $matchedNode;
        $matchedNode = $this->zeroMatch($path, $node, $input, $starState, $starIndex, $inputStars, $thatStars, $topicStars, "#", $matchTrace);
        if ($matchedNode != null) return $matchedNode;
        else
        return $this->wildMatch($path, $node, $input, $starState, $starIndex, $inputStars, $thatStars, $topicStars, "#", $matchTrace);
    }

    function zeroMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, $wildcard, &$matchTrace) {
        $matchTrace .= "[".$wildcard.",]";
        if ($path != null && Nodemapper::containsKey($node, $wildcard)) {
            $this->setStars($this->bot->properties[MagicStrings::$null_star], $starIndex, $starState, $inputStars, $thatStars, $topicStars);
            $nextNode = Nodemapper::get($node, $wildcard);
            return $this->matchWithStars($path, $nextNode, $input, $starState, $starIndex+1, $inputStars, $thatStars, $topicStars, $matchTrace);
        }
        else {
            $this->fail("zero ".$wildcard, $matchTrace);
            return null;
        }

    }

    function wildMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, $wildcard, &$matchTrace) {
        $matchedNode;
        if ($path->word === "<THAT>" || $path->word === "<TOPIC>") {
            $this->fail("wild1 ".$wildcard, $matchTrace);
            return null;
        }
        try {
            if ($path != null && Nodemapper::containsKey($node, $wildcard)) {
                $matchTrace .= "[".$wildcard.",".$path->word."]";
                $currentWord;
                $starWords;
                $pathStart;
                $currentWord = $path->word;
                $starWords = $currentWord." ";
                $pathStart = $path->next;
                $nextNode = Nodemapper::get($node, $wildcard);
                if (Nodemapper::isLeaf($nextNode) && !$nextNode->shortCut) {
                    $matchedNode = $nextNode;
                    $starWords = Path::pathToSentence($path);
                    $this->setStars($starWords, $starIndex, $starState, $inputStars, $thatStars, $topicStars);
                    return $matchedNode;
                }
                else {
                    for ($path = $pathStart; $path != null && $currentWord != "<THAT>" && $currentWord != "<TOPIC>"; $path = $path->next) {
                        $matchTrace .= "[".$wildcard.",".$path->word."]";
                        if (($matchedNode = $this->matchWithStars($path, $nextNode, $input, $starState, $starIndex + 1, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) {
                            $this->setStars($starWords, $starIndex, $starState, $inputStars, $thatStars, $topicStars);
                            return $matchedNode;
                        }
                        else {
                            $currentWord = $path->word;
                            $starWords .= $currentWord . " ";
                        }
                    }
                    $this->fail("wild2 ".$wildcard, $matchTrace);
                    return null;
                }
            }
        } catch (Exception $ex) {
            echo("wildMatch: ".Path::pathToSentence($path).": ".$ex);
        }
        $this->fail("wild3 ".$wildcard, $matchTrace);
        return null;
    }

    function setMatch($path, $node, $input, $starState, $starIndex, &$inputStars, &$thatStars, &$topicStars, &$matchTrace) {
        if ($this->DEBUG) echo("Graphmaster.setMatch(path: " . $path . ", node: " . $node . ", input: " . $input . ", starState: " . $starState . ", starIndex: " . $starIndex . ", inputStars, thatStars, topicStars, matchTrace: " . $matchTrace . ", )");
        if ($node->sets == null || $path->word === "<THAT>" || $path->word === "<TOPIC>") return null;
        if ($this->DEBUG) echo("in Graphmaster.setMatch, setMatch sets =".$node->sets);
        foreach ($node->sets as $setName) {
            if ($this->DEBUG) echo("in Graphmaster.setMatch, setMatch trying type ".$setName);
            $nextNode = Nodemapper::get($node, "<SET>".strtoupper($setName)."</SET>");
            $aimlSet = $this->bot->setMap[$setName];

            $matchedNode;
            $bestMatchedNode = null;
            $currentWord = $path->word;
            $starWords = $currentWord." ";
            $length = 1;
            $matchTrace .= "[<set>".$setName."</set>,".$path->word."]";
            if ($this->DEBUG) echo("in Graphmaster.setMatch, setMatch starWords =\"".$starWords."\"");
            for ($qath = $path->next; $qath != null && $currentWord != "<THAT>" && $currentWord != "<TOPIC>" && $length <= count($aimlSet); $qath = $qath->next) {
                if ($this->DEBUG) echo("in Graphmaster.setMatch, qath.word = ".$qath->word);
                $phrase = strtoupper($this->bot->preProcessor->normalize(trim($starWords)));
                if ($this->DEBUG) echo("in Graphmaster.setMatch, setMatch trying \"".$phrase."\" in ".$setName);
                if ($aimlSet->contains($phrase) && ($matchedNode = $this->matchWithStars($qath, $nextNode, $input, $starState, $starIndex + 1, $inputStars, $thatStars, $topicStars, $matchTrace)) != null) {
                    $this->setStars($starWords, $starIndex, $starState, $inputStars, $thatStars, $topicStars);
                    if ($this->DEBUG) echo("in Graphmaster.setMatch, setMatch found ".$phrase." in ". $setName);
                    $bestMatchedNode = $matchedNode;
                }
             

                $length = $length + 1;
                $currentWord = $qath->word;
                $starWords .= $currentWord . " ";

            }
            if ($bestMatchedNode != null) return $bestMatchedNode;
        }
        $this->fail("set", $matchTrace);
        return null;
    }

    public function setStars($starWords, $starIndex, $starState, &$inputStars, &$thatStars, &$topicStars) {
        if ($starIndex < MagicNumbers::$max_stars) {
            $starWords = trim($starWords);
            if ($starState === "inputStar") $inputStars[$starIndex] = $starWords;
            else if ($starState === "thatStar") $thatStars[$starIndex] = $starWords;
            else if ($starState === "topicStar") $topicStars[$starIndex] = $starWords;
        }
    }

    public function printgraph () {
        $this->printgraphByNode($this->root, "");
    }

    function printgraphByNode($node, $partial) {
        if ($node == null) echo("Null graph");
        else {
            $template = "";
            if (Nodemapper::isLeaf($node) || $node->shortCut) {
                $template = Category::templateToLine($node->category->getTemplate());
                $template = substr($template, 0, min(16, strlen($template)));
                if ($node->shortCut) echo($partial."(".Nodemapper::size($node)."[".$node->height."])--<THAT>-->X(1)--*-->X(1)--<TOPIC>-->X(1)--*-->".template."...");
                else echo($partial."(".Nodemapper::size($node)."[".$node->height."]) ".$template."...");
            }
            foreach (Nodemapper::keySet($node) as $key) {
                $this->printgraphByNode(Nodemapper::get($node, $key), $partial."(".Nodemapper::size($node)."[".$node->height."])--".$key."-->");
            }
        }
    }

    public function getCategories() {
        $categories = array();
        $this->getCategoriesByNode($this->root, $categories);
        return $categories;
    }

    function getCategoriesByNode($node, &$categories) {
        if ($node == null) return;

        else {
            if (Nodemapper::isLeaf($node) || $node->shortCut) {
                if ($node->category != null) array_push($categories, $node->category);   // node.category == null when the category is deleted.
            }
            foreach (Nodemapper::keySet($node) as $key) {
                $this->getCategoriesByNode(Nodemapper::get($node, $key), $categories);
            }
        }
    }

    public function nodeStats() {
        $this->leafCnt = 0;
        $this->nodeCnt = 0;
        $this->nodeSize = 0;
        $this->singletonCnt = 0;
        $this->shortCutCnt = 0;
        $this->naryCnt = 0;
        $this->nodeStatsGraph($this->root);
        $resultNote = $bot->name." (".name."): ".count($this->getCategories())." categories ".$nodeCnt." nodes ".$singletonCnt." singletons ".$leafCnt." leaves ".$shortCutCnt." shortcuts ".$naryCnt." n-ary ".$nodeSize." branches ".$nodeSize/$nodeCnt." average branching ";
    }

    public function nodeStatsGraph($node) {
        if ($node != null) {
            $nodeCnt++;
            $nodeSize += Nodemapper::size($node);
            if (Nodemapper::size($node) == 1) $singletonCnt += 1;
            if (Nodemapper::isLeaf($node) && !$node->shortCut) {
                $leafCnt++;
            }
            if (Nodemapper::size($node) > 1) $naryCnt += 1;
            if ($node->shortCut) {$shortCutCnt += 1;}
            foreach (Nodemapper::keySet($node) as $key) {
                $this->nodeStatsGraph(Nodemapper::get($node, $key));
            }
        }
    }

    public function getVocabulary () {
        $this->getBrainVocabulary($this->root);
        foreach (array_keys($this->bot->setMap) as $set)
            array_push($this->vocabulary, $this->bot->setMap[$set]);
        return $this->vocabulary;
    }

    public function getBrainVocabulary($node) {
        if ($node != null) {
            foreach (Nodemapper::keySet($node) as $key) {
                array_push($this->vocabulary, $key);
                $this->getBrainVocabulary(Nodemapper::get($node, $key));
            }
        }
    }
}