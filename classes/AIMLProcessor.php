<?php

class AIMLProcessor {

    static $DEBUG = false;
    public static $extension;

    public static $sraiCount = 0;
    public static $repeatCount = 0;
    public static $trace_count = 0;

  /**
   * Function printShit
   *
   * Sends incoming text to an "error log"
   *
   * @param $shit
   * @return void
   */
  public static function printShit($shit) {
        error_log($shit . "<br/>", 3, Conversation::$logs_path . '/echo.txt');
    }

  /**
   * Function categoryProcessor
   *
   * Processes AIML Categories
   *
   * @param $n
   * @param $categories
   * @param $topic
   * @param $aimlFile
   * @param $language
   * @return void
   */
  private static function categoryProcessor($n, &$categories, $topic, $aimlFile, $language) {
        $pattern = "*";
        $that = "*";
        $template="";

        $children = $n->childNodes;
        foreach($children as $m) {
            $mName = $m->nodeName;
            
            if ($mName === "#text") {/*skip*/}
            else if ($mName === "pattern") $pattern = $m->ownerDocument->saveXML($m);
            else if ($mName === "that") $that = $m->ownerDocument->saveXML($m);
            else if ($mName === "topic") $topic = $m->ownerDocument->saveXML($m);
            else if ($mName === "template") $template = $m->ownerDocument->saveXML($m);
            else error_log("categoryProcessor: unexpected ".$mName." in ".$m->ownerDocument->saveXML($m), 3, Conversation::$logs_path . '/echo.txt');
        }

        $pattern    = self::cleanPattern(self::trimTag($pattern, "pattern"));
        $that       = self::cleanPattern(self::trimTag($that, "that"));
        $topic      = self::cleanPattern(self::trimTag($topic, "topic"));
        $template   = self::trimTag(self::cleanPattern($template), "template");

       /* if (stripos($template,'<learn>') !== false) {
            error_log("template = " . $template, 3, Conversation::$logs_path . '/echo.txt');
        }*/


        if (MagicBooleans::$jp_tokenize) {
            $morphPattern = JapaneseUtils::tokenizeSentence($pattern);
            $pattern = $morphPattern;
            $morphThatPattern = JapaneseUtils::tokenizeSentence($that);
            $that = $morphThatPattern;
            $morphTopicPattern = JapaneseUtils::tokenizeSentence($topic);
            $topic = $morphTopicPattern;
        }

        $c = new Category(0, $pattern, $that, $topic, $template, $aimlFile);

        if ($template == null || strlen($template) == 0) {
            error_log("Category ".$c->inputThatTopic()." discarded due to blank or missing <template>. for pattern " . $pattern, 3, Conversation::$logs_path . '/echo.txt');
        }
        else {
            array_push($categories, $c);
        }
    }

    static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    public static function cleanPattern($pattern) {
        return trim(str_replace(array("\r\n", "\n\r", "\r", "\n", "  "), " ", $pattern));
    }

    public static function trimTag($s, $tagName) {
        return trim(Utilities::tagTrim($s, $tagName));
    }

    public static function AIMLToCategories ($directory, $aimlFile) {
        try {
            $categories = array();

            $root = DomUtils::parseFile($directory."".$aimlFile);      // <aiml> tag

            $language = MagicStrings::$default_language;

            if (count($root->attributes)) {
                $XMLAttributes = $root->attributes;
                foreach($XMLAttributes as $attribute) {
                    if($attribute->nodeName === "language")
                        $language = $attribute;
                }
            }
            $nodelist = $root->childNodes;
            foreach($nodelist as $n){
                if ($n->nodeName === "category") {
                    self::categoryProcessor($n, $categories, "*", $aimlFile, $language);
                }
                else if ($n->nodeName === "topic") {
                    $topic = $n->getAttribute("name");
                    $children = $n->childNodes;
                    foreach($children as $m) {
                        if ($m->nodeName === "category") {
                            self::categoryProcessor($m, $categories, $topic, $aimlFile, $language);
                        }
                    }
                }
            }
            return $categories;
        }
        catch (Exception $ex) {
            error_log("AIMLToCategories: ".$ex, 3, Conversation::$logs_path . '/echo.txt');
            error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
            return null;
        }
    }

    public static function checkForRepeat($input, $chatSession) {
        if ($input == $chatSession->inputHistory->get(1)) {
            return 1;
        }
        else return 0;
    }

    public static function respond($input, $that, $topic, $chatSession, $dummy) {
        //MagicBooleans::$webPrint("calling AIMLProcessor:respond with " . $input);
        if (false /*self::checkForRepeat($input, $chatSession) > 0*/) return "Repeat!";
        else {
            return self::respondCount($input, $that, $topic, $chatSession, 0);
        }
    }

    public static function respondCount($input, $that, $topic, $chatSession, $srCnt) {
        //MagicBooleans::webPrint("calling AIMLProcessor:respondCount with " . $input);
        //MagicBooleans::trace("input: " . $input . ", that: " . $that . ", topic: " . $topic . ", srCnt: " . $srCnt);
        if ($input == null || strlen($input)==0) $input = MagicStrings::$null_input;
        self::$sraiCount = $srCnt;
        $response = MagicStrings::$default_bot_response;
         try {
            $leaf = $chatSession->bot->brain->match($input, $that, $topic);

            if ($leaf == null) {return($response);}
            $ps = new ParseState(0, $chatSession, $input, $that, $topic, $leaf);
            $template = $leaf->category->getTemplate();
            $response = self::evalTemplate($template, $ps);
            //System.out.println("That="+that);
        } catch (Exception $ex) {
            error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
        }
        return $response;
    }

    private static function capitalizeString($string) {
        return ucwords(strtolower($string));
    }

    private static function explodeSpaces($input) {
        return str_replace("  ", " ", trim(implode(' ',str_split($input))));
    }
    
    public static function evalTagContent($node, $ps, $ignoreAttributes) {
        $result = "";
        try {
            $childList = $node->childNodes;
            foreach($childList as $child) {
                if ($ignoreAttributes == null || !in_array($child->nodeName, $ignoreAttributes))  {
                    $result .= self::recursEval($child, $ps);
                }
            }
        } catch (Exception $ex) {
            error_log("Something went wrong with evalTagContent", 3, Conversation::$logs_path . '/echo.txt');
            error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
        }
        return $result;
    }
    
    public static function genericXML($node, $ps) {
        $evalResult = self::evalTagContent($node, $ps, null);
        $result = self::unevaluatedXML($evalResult, $node, $ps);
        return $result;
    }

    private static function unevaluatedXML($resultIn, $node, $ps) {
        $nodeName = $node->nodeName;
        $attributes = "";
        if (count($node->attributes)) {
            $XMLAttributes = $node->attributes;
            foreach($XMLAttributes as $attribute)

            {
                $attributes .= " ".$attribute->nodeName."=\"".$attribute->nodeValue."\"";
            }
        }
        $result = "<".$nodeName.$attributes."/>";
        if ($resultIn != "")
            $result = "<".$nodeName.$attributes.">".$resultIn."</".$nodeName.">";
        return $result;
    }

    private static function srai($node, $ps) {
        self::$sraiCount++;
        if (self::$sraiCount > MagicNumbers::$max_recursion_count || $ps->depth > MagicNumbers::$max_recursion_depth) {
            return MagicStrings::$too_much_recursion;
        }
        $response = MagicStrings::$default_bot_response;
        try {
            $result = self::evalTagContent($node, $ps, null);
            $result = trim($result);
            $result = str_replace(array("\r\n", "\n\r", "\r", "\n"), " ", $result);
            $result = $ps->chatSession->bot->preProcessor->normalize($result);
            $result = JapaneseUtils::tokenizeSentence($result);
            $topic = $ps->chatSession->predicates->get("topic");     // the that stays the same, but the topic may have changed
            if (MagicBooleans::$trace_mode) {
                error_log(self::$trace_count.". <srai>".$result."</srai> from ".$ps->leaf->category->inputThatTopic()." topic=".$topic." ", 3, Conversation::$logs_path . '/echo.txt');
                self::$trace_count++;
            }
            $leaf = $ps->chatSession->bot->brain->match($result, $ps->that, $topic);
            if ($leaf == null) {return($response);}
            $response = self::evalTemplate($leaf->category->getTemplate(), new ParseState($ps->depth+1, $ps->chatSession, $ps->input, $ps->that, $topic, $leaf));
        } catch (Exception $ex) {
            error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
        }
        $result = trim($response);
        return $result;
    }

    private static function getAttributeOrTagValue($node, $ps, $attributeName) {        // AIML 2.0
        $result = "";
        $m = $node->getAttribute($attributeName);
        if ($m == null) {
            $childList = $node->childNodes;
            $result = null;         // no attribute or tag named attributeName
            foreach($childList as $child){
                if ($child->nodeName == $attributeName) {
                    $result = self::evalTagContent($child, $ps, null);
                }
            }
        }
        else {
            $result = $m;
        }
        return $result;
    }

    private static function sraix($node, $ps) {
        $attributeNames = Utilities::stringSet("botid", "host");
        $host = self::getAttributeOrTagValue($node, $ps, "host");
        $botid = self::getAttributeOrTagValue($node, $ps, "botid");
        $hint = self::getAttributeOrTagValue($node, $ps, "hint");
        $limit = self::getAttributeOrTagValue($node, $ps, "limit");
        $defaultResponse = self::getAttributeOrTagValue($node, $ps, "default");
        $evalResult = self::evalTagContent($node, $ps, $attributeNames);
        //TODO::see if i need to hook this to something. it looks like it is currently is going to pannous and pandorabots stuff
        $result = "";//Sraix::sraix($ps->chatSession, $evalResult, $defaultResponse, $hint, $host, $botid, null, $limit);
        return $result;
    }

    private static function map($node, $ps) {
        $result = MagicStrings::$default_map;
        $attributeNames = Utilities::stringSet("name");
        $mapName = self::getAttributeOrTagValue($node, $ps, "name");
        $contents = self::evalTagContent($node, $ps, $attributeNames);
        $contents = trim($contents);
        if ($mapName == null) $result = "<map>".$contents."</map>"; // this is an OOB map tag (no attribute)
        else {
            $map = $ps->chatSession->bot->mapMap[$mapName];
            if ($map != null) $result = $map->get(strtoupper($contents));
            if ($result == null) $result = MagicStrings::$default_map;
            $result = trim($result);
        }
        return $result;
    }

    private static function set($node, $ps) {                    // add pronoun check
      DebugLogger::logDebug('Parsing a SET tag...', DebugLogger::DEBUG_TRIVIAL, __CLASS__, __METHOD__, __LINE__);
        $attributeNames = Utilities::stringSet("name", "var");
        $predicateName = self::getAttributeOrTagValue($node, $ps, "name");
        $varName = self::getAttributeOrTagValue($node, $ps, "var");
        $result = trim(self::evalTagContent($node, $ps, $attributeNames));
        $result = str_replace(array("\r\n", "\n\r", "\r", "\n"), " ", $result);
        $value = trim($result);
        if ($predicateName != null) {
            $ps->chatSession->predicates->put($predicateName, $result);
            MagicBooleans::trace("Set predicate ".$predicateName." to ".$result." in ".$ps->leaf->category->inputThatTopic());
        }
        if ($varName != null) {
            $ps->vars[$varName] = $result;
            MagicBooleans::trace("Set var ".$varName." to ".$value." in ".$ps->leaf->category->inputThatTopic());
        }
        if (in_array($predicateName, $ps->chatSession->bot->pronounSet)) {
            $result = $predicateName;
        }
        return $result;
    }

    private static function email($node, $ps) { 
        $attributeNames = Utilities::stringSet("name", "var");
        $predicateName = self::getAttributeOrTagValue($node, $ps, "address");
        $varName = self::getAttributeOrTagValue($node, $ps, "var");
        $result = trim(self::evalTagContent($node, $ps, $attributeNames));
        $result = str_replace(array("\r\n", "\n\r", "\r", "\n"), " ", $result);
        $value = trim($result);

        if ($predicateName != null) {
            mail($predicateName, 'Email From IRIS', $value);
            return "Email Sent.";
        }
       
        return $result;
    }

    private static function get($node, $ps) {
        $result = MagicStrings::$default_get;
        $predicateName = self::getAttributeOrTagValue($node, $ps, "name");
        $varName = self::getAttributeOrTagValue($node, $ps, "var");
        $tupleName = self::getAttributeOrTagValue($node, $ps, "tuple");
        if ($predicateName != null) {
           $result = trim($ps->chatSession->predicates->get($predicateName));
       }
        else if ($varName != null && $tupleName != null) {
               $result = self::tupleGet($tupleName, $varName);

           }
        else if ($varName != null) {
           $result = trim($ps->vars[$varName]);
        }
        return $result;
    }

    public static function tupleGet ($tupleName, $varName) {
        $result = MagicStrings::$default_get;
        $tuple = isset(Tuple::$tupleMap[$tupleName]) ? Tuple::$tupleMap[$tupleName] : null;
        if ($tuple == null) $result = MagicStrings::$default_get;
        else $result = $tuple->getValue($varName);
        return $result;
    }

    private static function bot($node, $ps) {
        $result = MagicStrings::$default_property;
        $propertyName = self::getAttributeOrTagValue($node, $ps, "name");
        if ($propertyName != null)
           $result = array_key_exists($propertyName, $ps->chatSession->bot->properties) ? trim($ps->chatSession->bot->properties[$propertyName]) : "UNKNOWNPROP(".$propertyName.")";
        return $result;
    }

    private static function date($node, $ps)  {
        $format = self::getAttributeOrTagValue($node, $ps, "format");      // AIML 2.0
        $locale = self::getAttributeOrTagValue($node, $ps, "locale");
        $timezone = self::getAttributeOrTagValue($node, $ps, "timezone");
        setlocale(LC_TIME, $locale);
        $dateAsString = strftime($format);//CalendarUtils::date($jformat, $locale, $timezone);
        return $dateAsString;
    }

    private static function interval($node, $ps)  {
        $style = self::getAttributeOrTagValue($node, $ps, "style");      // AIML 2.0
        $pformat = self::getAttributeOrTagValue($node, $ps, "pformat");      // AIML 2.0
        $from = self::getAttributeOrTagValue($node, $ps, "from");
        $to = self::getAttributeOrTagValue($node, $ps, "to");

        $from = strtotime($from);
        $to = strtotime($to);
        
        if ($style == null) $style = "years";
        if ($pformat == null) $pformat = "F d Y";
        if ($from == "") $from = time();
        if ($to == "") {
            $to = time();
        }

        $datediff = abs($from - $to);
      
        $result = "unknown";
        if ($style === "years") $result = floor($datediff/(60*60*24*30*12));
        if ($style === "months") $result = floor($datediff/(60*60*24*30));
        if ($style === "days") $result = floor($datediff/(60*60*24));
        if ($style === "hours") $result = floor($datediff/(60*60));
        return $result;
    }

    private static function getIndexValue($node, $ps) {
        $index=0;
        $value = self::getAttributeOrTagValue($node, $ps, "index");
        if ($value != null) try {$index = $value-1;} catch (Exception $ex) {
          error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
        }
        return $index;
    }

    private static function inputStar($node, $ps) {
        $result="";
        $index=self::getIndexValue($node, $ps);
        if ($ps->starBindings->inputStars->star($index)==null) $result = "";
        else $result = trim($ps->starBindings->inputStars->star($index));
        return $result;
    }

    private static function thatStar($node, $ps) {
        $index=self::getIndexValue($node, $ps);
        if ($ps->starBindings->thatStars->star($index)==null) return "";
        else return trim($ps->starBindings->thatStars->star($index));
    }

    private static function topicStar($node, $ps) {
        $index=self::getIndexValue($node, $ps);
        if ($ps->starBindings->topicStars->star($index)==null) return "";
        else return trim($ps->starBindings->topicStars->star($index));
    }

    private static function id($node, $ps) {
        return $ps->chatSession->customerId;
    }
    
    private static function size($node, $ps) {
        return sizeof($ps->chatSession->bot->brain->getCategories());
    }
    
    private static function vocabulary($node, $ps) {
        return sizeof($ps->chatSession->bot->brain->getVocabulary());
    }
    
    private static function program($node, $ps) {
        return MagicStrings::$program_name_version;
    }

    private static function that($node, $ps) {
        $index=0;
        $jndex=0;
        $value = self::getAttributeOrTagValue($node, $ps, "index");
        if ($value != null)
            try {
                $pair = $value;
                $spair = explode( ',', $pair);
                $index = $spair[0]-1;
                $jndex = $spair[1]-1;
            }
            catch (Exception $ex) {
              error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
            }
        $that = MagicStrings::$unknown_history_item;
         $hist = $ps->chatSession->thatHistory[count($ps->chatSession->thatHistory)-$index-1];
        if ($hist != null) $that = $hist[count($hist)-$jndex-1];
       /* $hist = $ps->chatSession->thatHistory[$index];
        if ($hist != null) $that = $hist[$jndex];*/
        return trim($that);
    }
    //TODO:: These "stacks" need to be fixed. Either use a stack class or write a utility that will use the array right for this.
    // or i could have it so that it always pushes to the end of the array and then just call array[$index-1] so that that index="1" looks for 0
    private static function input($node, $ps) {
        $index=self::getIndexValue($node, $ps);
        return $ps->chatSession->inputHistory[count($ps->chatSession->inputHistory)-$index-1];
    }

    private static function request($node, $ps) {             // AIML 2.0
        $index=self::getIndexValue($node, $ps);
        return trim($ps->chatSession->requestHistory[count($ps->chatSession->requestHistory)-$index-1]);
    }

    private static function response($node, $ps) {            // AIML 2.0
        $index=self::getIndexValue($node, $ps);
        return trim($ps->chatSession->responseHistory[count($ps->chatSession->responseHistory)-$index-1]);
    }
   
    private static function system($node, $ps) {
        $attributeNames = Utilities::stringSet("timeout");
        $evaluatedContents = self::evalTagContent($node, $ps, $attributeNames);
        $retval = null;
        $result = exec($evaluatedContents, $var);
        $ret = "";
        foreach($var as $v) {
            $ret .= $v."<br/>";
        }
        return $ret;
    }
  
    private static function think($node, $ps) {
        self::evalTagContent($node, $ps, null);
        return "";
    }

    private static function explode($node, $ps) {              // AIML 2.0
        $result = self::evalTagContent($node, $ps, null);
        return self::explodeSpaces($result);
    }

    private static function normalize($node, $ps) {            // AIML 2.0
        $result = self::evalTagContent($node, $ps, null);
        $returning = $ps->chatSession->bot->preProcessor->normalize($result);
        return $returning;
    }
    
    private static function denormalize($node, $ps) {            // AIML 2.0
        $result = self::evalTagContent($node, $ps, null);
        return $ps->chatSession->bot->preProcessor->denormalize($result);
    }
    
    private static function uppercase($node, $ps) {
        $result = self::evalTagContent($node, $ps, null);
        return strtoupper($result);
    }
    
    private static function lowercase($node, $ps) {
        $result = self::evalTagContent($node, $ps, null);
        return strtolower($result);
    }
    
     private static function formal($node, $ps) {
        $result = self::evalTagContent($node, $ps, null);
        return self::capitalizeString($result);
    }
    
    private static function sentence($node, $ps) {
        $result = self::evalTagContent($node, $ps, null);
        if (strlen($result) > 1) return ucfirst($result);
        else return "";
    }
    
    private static function person($node, $ps) {
        $result;
        //TODO::FIX THIS HACK
        $actuallyHasChildren = false;
        foreach($node->childNodes as $childNode) {
            $actuallyHasChildren = true;
        }
        if ($actuallyHasChildren)
          $result = self::evalTagContent($node, $ps, null);
        else $result = $ps->starBindings->inputStars->star(0);   // for <person/>
        $result = " ".$result." ";
        $result = $ps->chatSession->bot->preProcessor->person($result);
        return trim($result);
    }
    
    private static function person2($node, $ps) {
        $result;
        //TODO::FIX THIS HACK
        $actuallyHasChildren = false;
        foreach($node->childNodes as $childNode) {
            $actuallyHasChildren = true;
        }
        if ($actuallyHasChildren)
            $result = self::evalTagContent($node, $ps, null);
        else $result = $ps->starBindings->inputStars->star(0);   // for <person2/>
        $result = " ".$result." ";
        $result = $ps->chatSession->bot->preProcessor->person2($result);
        return trim($result);
    }
    
    private static function gender($node, $ps) {
        $result = self::evalTagContent($node, $ps, null);
        $result = " ".$result." ";
        $result = $ps->chatSession->bot->preProcessor->gender($result);
        return trim($result);
    }

    private static function random($node, $ps) {

        $childList = $node->childNodes;
        $liList = array();
        $setName = self::getAttributeOrTagValue($node, $ps, "set");
        foreach($childList as $child) {
            if ($child->nodeName === "li") {
                array_push($liList, $child);
            }
        }
        return self::evalTagContent($liList[array_rand($liList)], $ps, null);
    }

    private static function unevaluatedAIML($node, $ps) {
        $result = self::learnEvalTagContent($node, $ps);
        return self::unevaluatedXML($result, $node, $ps);
    }

    private static function recursLearn($node, $ps) {
        $nodeName = $node->nodeName;
        if ($nodeName === "#text") return $node->textContent;
        else if ($nodeName === "eval") return self::evalTagContent($node, $ps, null);                // AIML 2.0
        else return self::unevaluatedAIML($node, $ps);
    }

    private static function learnEvalTagContent($node, $ps) {
        $result = "";
        $childList = $node->childNodes;
        foreach($childList as $child) {
            $result .= self::recursLearn($child, $ps);
        }
        return $result;
    }

    private static function learn($node, $ps)   {                 // learn, learnf AIML 2.0
        $children = $node->childNodes;
        $pattern = "";
        $that="*";
        $template = "";

        foreach($children as $child) {
            if ($child->nodeName === "category") {
                $grandChildList = $child->childNodes;
                foreach($grandChildList as $grandChild) {
                    if ($grandChild->nodeName === "pattern") {
                        $pattern = self::recursLearn($grandChild, $ps);
                    }
                    else if ($grandChild->nodeName === "that") {
                        $that = self::recursLearn($grandChild, $ps);
                    }
                    else if ($grandChild->nodeName === "template") {
                        $template = self::recursLearn($grandChild, $ps);
                    }
                }

                $pattern = self::trimTag($pattern, "pattern");
                if (MagicBooleans::$trace_mode) error_log("Learn Pattern = ".$pattern, 3, Conversation::$logs_path . '/echo.txt');
                if (strlen($template) >= strlen("<template></template>")) $template = self::trimTag($template, "template");
                if (strlen($that) >= strlen("<that></that>")) $that = self::trimTag($that, "that");
                $pattern = strtoupper($pattern);
                $pattern = str_replace("\n"," ", $pattern);
                $pattern = str_replace("[ ]+"," ", $pattern);
                $that = strtoupper($that);
                $that = str_replace("\n"," ", $that);
                $that = str_replace("[ ]+"," ", $that);
                if (MagicBooleans::$trace_mode) {
                    error_log("Learn Pattern = ".$pattern, 3, Conversation::$logs_path . '/echo.txt');
                    error_log("Learn That = ".$that, 3, Conversation::$logs_path . '/echo.txt');
                    error_log("Learn Template = ".$template, 3, Conversation::$logs_path . '/echo.txt');
                }
                $c;
                if ($node->nodeName === "learn") {
                    $c = new Category(0, $pattern, $that, "*", $template, MagicStrings::$null_aiml_file);
                    $ps->chatSession->bot->learnGraph->addCategory($c);
                }
                else {// learnf
                    $c = new Category(0, $pattern, $that, "*", $template, MagicStrings::$learnf_aiml_file);
                    $ps->chatSession->bot->learnfGraph->addCategory($c);
                }

                $ps->chatSession->bot->brain->addCategory($c);
                if(extension_loaded('apc') && ini_get('apc.enabled'))
                {
                    //need to recache the brain or it will load without this next time
                    apc_store("brain", serialize($ps->chatSession->bot->brain));
                }
            }
        }
        return "";
    }

    private static function loopCondition($node, $ps) {
        $loop = true;
        $result="";
        $loopCnt = 0;
        while ($loop && $loopCnt < MagicNumbers::$max_loops) {
            $loopResult = self::condition($node, $ps);
            if (trim($loopResult) == MagicStrings::$too_much_recursion) return MagicStrings::$too_much_recursion;
            if (strpos($loopResult,'<loop/>') !== false) {
                $loopResult = str_replace("<loop/>", "", $loopResult);
                $loop = true;
            }
            else $loop = false;
            $result .= $loopResult;
        }
        if ($loopCnt >= MagicNumbers::$max_loops) $result = MagicStrings::$too_much_looping;
        return $result;
    }

    private static function condition($node, $ps) {

        $result="";
        $childList = $node->childNodes;
        $liList = array();
        $predicate=null;
        $varName=null;
        $value=null; //Node p=null, v=null;
        $attributeNames = Utilities::stringSet("name", "var", "value");
        $predicate = self::getAttributeOrTagValue($node, $ps, "name");
        $varName = self::getAttributeOrTagValue($node, $ps, "var");
        foreach($childList as $child)
            if ($child->nodeName === "li") array_push($liList, $child);
        if (sizeof($liList) == 0 && ($value = self::getAttributeOrTagValue($node, $ps, "value")) != null   &&
                   $predicate != null  &&
                   !strcasecmp($ps->chatSession->predicates->get($predicate), $value))  {
                   return self::evalTagContent($node, $ps, $attributeNames);
        }
        else if (sizeof($liList) == 0 && ($value = self::getAttributeOrTagValue($node, $ps, "value")) != null   &&
                $varName != null  &&
                !strcasecmp($ps->vars[$varName], $value))  {
            return self::evalTagContent($node, $ps, $attributeNames);
        }
        else {
            for ($i = 0; $i < sizeof($liList) && $result == ""; $i++) {
                $n = $liList[$i];
                $liPredicate = $predicate;
                $liVarName = $varName;
                if ($liPredicate == null) $liPredicate = self::getAttributeOrTagValue($n, $ps, "name");
                if ($liVarName == null) $liVarName = self::getAttributeOrTagValue($n, $ps, "var");
                $value = self::getAttributeOrTagValue($n, $ps, "value");
                if ($value != null) {
                    if ($liPredicate != null && $value != null && (!strcasecmp($ps->chatSession->predicates->get($liPredicate), $value)) ||
                            (array_key_exists($liPredicate, $ps->chatSession->predicates) && $value === "*")) 
                        return self::evalTagContent($n, $ps, $attributeNames);
                    else if ($liVarName != null && $value != null && (!strcasecmp($ps->vars[$liVarName], $value)) ||
                            (array_key_exists($liPredicate, $ps->vars) && $value === "*"))
                        return self::evalTagContent($n, $ps, $attributeNames);

               }
                else
                    return self::evalTagContent($n, $ps, $attributeNames);
            }
        }
        return "";

    }

    public static function evalTagForLoop($node) {
        $childList = $node->childNodes;
        foreach($childList as $child) {
            if ($child->nodeName === "loop") return true;
        }
        return false;
    }

    private static function deleteTriple($node, $ps) {
        $subject = self::getAttributeOrTagValue($node, $ps, "subj");
        $predicate = self::getAttributeOrTagValue($node, $ps, "pred");
        $object = self::getAttributeOrTagValue($node, $ps, "obj");
        return $ps->chatSession->tripleStore->deleteTriple($subject, $predicate, $object);
    }

    private static function addTriple($node, $ps) {
        $subject = self::getAttributeOrTagValue($node, $ps, "subj");
        $predicate = self::getAttributeOrTagValue($node, $ps, "pred");
        $object = self::getAttributeOrTagValue($node, $ps, "obj");
        return $ps->chatSession->tripleStore->addTriple($subject, $predicate, object);
    }

    public static function uniq($node, $ps) {
        $vars = array();
        $visibleVars = array();
        $subj = "?subject";
        $pred = "?predicate";
        $obj = "?object";
        $childList = $node->childNodes;
        foreach($childList as $childNode) {
            $contents = self::evalTagContent($childNode, $ps, null);
            if ($childNode->nodeName === "subj") $subj = $contents;
            else if ($childNode->nodeName === "pred") $pred = $contents;
            else if ($childNode->nodeName === "obj") $obj = $contents;
            if (self::startsWith($contents, "?")) {
                array_push($visibleVars, $contents);
                array_push($vars, $contents);
            }
        }
        $partial = Tuple::TupleBySets($vars, $visibleVars);
        $clause = new Clause($subj, $pred, $obj);
        $tuples = $ps->chatSession->tripleStore->selectFromSingleClause($partial, $clause, true);
        $tupleList = "";

        //exit("foreach tuples " . var_dump($tuples));
        foreach($tuples as $tuple) {
            $tupleList = $tuple->name." ".$tupleList;
        }
        //exit("tupleList = " . $tupleList);
        $tupleList = trim($tupleList);
        if (strlen($tupleList)==0) $tupleList = "NIL";
        $var = "";
        foreach($visibleVars as $x){
           $var = $x;
        }
        $firstTuple = self::firstWord($tupleList);
        $result = self::tupleGet($firstTuple, $var);
        return $result;
    }

    public static function select($node, $ps) {
        $clauses = array();
        $childList = $node->childNodes;
        $vars = array();
        $visibleVars = array();
        foreach($childList as $childNode) {
            if ($childNode->nodeName === "vars") {
                $contents = self::evalTagContent($childNode, $ps, null);
                $splitVars = explode( ' ', $contents);
                foreach($splitVars as $var) {
                    $var = trim($var);
                    if (strlen($var) > 0) array_push($visibleVars, $var);
                }
            }

            else if ($childNode->nodeName === "q" || $childNode->nodeName === "notq") {
                $affirm = $childNode->nodeName !== "notq";
                $grandChildList = $childNode->childNodes;
                $subj = null;
                $pred = null;
                $obj = null;
                foreach($grandChildList as $grandChildNode) {
                    $contents = self::evalTagContent($grandChildNode, $ps, null);
                    if ($grandChildNode->nodeName === "subj") $subj = $contents;
                    else if ($grandChildNode->nodeName === "pred") $pred = $contents;
                    else if ($grandChildNode->nodeName === "obj") $obj = $contents;
                    if (self::startsWith($contents, "?")) array_push($vars, $contents);

                }
                $clause = new Clause($subj, $pred, $obj, $affirm);
                array_push($clauses, $clause);
            }
        }
        $tuples = $ps->chatSession->tripleStore->select($vars, $visibleVars, $clauses);
        $result = "";
        foreach($tuples as $tuple) {
            $result = $tuple->name." ".$result;
        }
        $result = trim($result);
        if (strlen($result)==0) $result = "NIL";
        return $result;
    }

    public static function subject($node, $ps) {
        $id = self::evalTagContent($node, $ps, null);
        $ts = $ps->chatSession->tripleStore;
        $subject = "unknown";
        if(array_key_exists($id, $ts->idTriple))
            $subject = $ts->idTriple->get($id)->subject;
        return $subject;
    }

    public static function predicate($node, $ps) {
        $id = self::evalTagContent($node, $ps, null);
        $ts = $ps->chatSession->tripleStore;
        if(array_key_exists($id, $ts->idTriple))
            return $ts->idTriple->get($id)->predicate;
        else return "unknown";
    }
    public static function object($node, $ps) {
        $id = self::evalTagContent($node, $ps, null);
        $ts = $ps->chatSession->tripleStore;
        if(array_key_exists($id, $ts->idTriple))
            return $ts->idTriple->get($id)->object;
        else return "unknown";
    }

    public static function javascript($node, $ps) {
        $result = MagicStrings::$bad_javascript;
        $script = self::evalTagContent($node, $ps, null);

        try {
            $result = IOUtils::evalScript("JavaScript", $script);
        } catch (Exception $ex) {
           error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
        }
        MagicBooleans::trace("in AIMLProcessor.javascript, returning result: " .$result);
        return $result;
    }

    public static function firstWord($sentence) {
        $content = ($sentence == null ? "" : $sentence);
        $content = trim($content);
        if(strlen($sentence) > 0) {
            $arr = explode(' ',trim($content));
            return $arr[0];
        }
        else return MagicStrings::$default_list_item;
    }

    public static function restWords($sentence) {
        $content = ($sentence == null ? "" : $sentence);
        $content = trim($content);
        if(strlen($sentence) > 0) {
            $arr = explode(' ',trim($content), 2);
            return $arr[1];
        }
        else return MagicStrings::$default_list_item;
    }

    public static function first($node, $ps) {
        $content = self::evalTagContent($node, $ps, null);
        return self::firstWord($content);

    }

    public static function rest($node, $ps) {
        $content = self::evalTagContent($node, $ps, null);
        $content = $ps->chatSession->bot->preProcessor->normalize($content);
        return self::restWords($content);

    }

    public static function resetlearnf($node, $ps) {
       $ps->chatSession->bot->deleteLearnfCategories();
       return "Deleted Learnf Categories";

    }

    public static function resetlearn($node, $ps) {
        $ps->chatSession->bot->deleteLearnCategories();
        return "Deleted Learn Categories";

    }

    private static function recursEval($node, $ps) {
        try {
            $extension = false;
            $nodeName = $node->nodeName;
        if($nodeName === "#text") {
            return $node->textContent;
        }
        else if($nodeName === "#document") {
            foreach($node->childNodes as $child) {
                return self::recursEval($child, $ps);
            }
        }
        else if ($nodeName === "#comment") {
            return "";
        }
        else if ($nodeName === "template") {
            return self::evalTagContent($node, $ps, null);
        }
        else if ($nodeName === "random")
            return self::random($node, $ps);
        else if ($nodeName === "condition")
            return self::loopCondition($node, $ps);
        else if ($nodeName === "srai")
            return self::srai($node, $ps);
        else if ($nodeName === "sr")
              return self::respond($ps->starBindings->inputStars->star(0), $ps->that, $ps->topic, $ps->chatSession, self::$sraiCount);
        else if ($nodeName === "sraix")
            return self::sraix($node, $ps);
        else if ($nodeName === "set")
            return self::set($node, $ps);
        else if ($nodeName === "email")
            return self::email($node, $ps);
        else if ($nodeName === "get")
            return self::get($node, $ps);
        else if ($nodeName === "map")  // AIML 2.0 -- see also <set> in pattern
            return self::map($node, $ps);
        else if ($nodeName === "bot")
            return self::bot($node, $ps);
        else if ($nodeName === "id")
            return self::id($node, $ps);
        else if ($nodeName === "size")
            return self::size($node, $ps);
        else if ($nodeName === "vocabulary") // AIML 2.0
            return self::vocabulary($node, $ps);
        else if ($nodeName === "program")
            return self::program($node, $ps);
        else if ($nodeName === "date")
            return self::date($node, $ps);
        else if ($nodeName === "interval")
            return self::interval($node, $ps);
        else if ($nodeName === "think")
            return self::think($node, $ps);
        else if ($nodeName === "system")
            return self::system($node, $ps);
        else if ($nodeName === "explode")
            return self::explode($node, $ps);
        else if ($nodeName === "normalize")
            return self::normalize($node, $ps);
        else if ($nodeName === "denormalize")
            return self::denormalize($node, $ps);
        else if ($nodeName === "uppercase")
            return self::uppercase($node, $ps);
        else if ($nodeName === "lowercase")
            return self::lowercase($node, $ps);
        else if ($nodeName === "formal")
            return self::formal($node, $ps);
        else if ($nodeName === "sentence")
            return self::sentence($node, $ps);
        else if ($nodeName === "person")
            return self::person($node, $ps);
        else if ($nodeName === "person2")
            return self::person2($node, $ps);
        else if ($nodeName === "gender")
            return self::gender($node, $ps);
        else if ($nodeName === "star")
            return self::inputStar($node, $ps);
        else if ($nodeName === "thatstar")
            return self::thatStar($node, $ps);
        else if ($nodeName === "topicstar")
            return self::topicStar($node, $ps);
        else if ($nodeName === "that")
            return self::that($node, $ps);
        else if ($nodeName === "input")
            return self::input($node, $ps);
        else if ($nodeName === "request")
            return self::request($node, $ps);
        else if ($nodeName === "response")
            return self::response($node, $ps);
        else if ($nodeName === "learn" || $nodeName === "learnf")
            return self::learn($node, $ps);
        else if ($nodeName === "addtriple") //No Documentation, probably created for testing and is not 2.0 standard. Looks like it just adds to the triples array
            return self::addTriple($node, $ps);
        else if ($nodeName === "deletetriple") //No Documentation, probably created for testing and is not 2.0 standard. Looks like it just removes from the triples array
            return self::deleteTriple($node, $ps);
        else if ($nodeName === "javascript") //No Documentation, Not required for 2.0 compliance. Should fire javascript code on response
            return self::javascript($fnode, $ps);
        else if ($nodeName === "select") //No Documentation, probably created for testing and is not 2.0 standard. Looks like a database query.
            return self::select($node, $ps);
        else if ($nodeName === "uniq") //No Documentation, probably created for testing and is not 2.0 standard. Im not even sure
            return self::uniq($node, $ps);
        else if ($nodeName === "first") //No Documentation, probably created for testing and is not 2.0 standard. Looks like it returns the first word matched
            return self::first($node, $ps);
        else if ($nodeName === "rest") //No Documentation, probably created for testing and is not 2.0 standard. Looks like it returns everything except the first word matched
            return self::rest($node, $ps);
        else if ($nodeName === "resetlearnf") //No Documentation, probably created for testing and is not 2.0 standard.just clearns learnf
            return self::resetlearnf($node, $ps);
        else if ($nodeName === "resetlearn") //No Documentation, probably created for testing and is not 2.0 standard.just clearns learn
            return self::resetlearn($node, $ps);
        else if ($extension != null && array_key_exists($nodeName, $extension->extensionTagSet())) return $extension->recursEval($node, $ps) ;
        else return (self::genericXML($node, $ps));
        }
        catch (Exception $ex) {
          error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
          return "";
        }
    }

    public static function evalTemplate($template, $ps) {
        $response = MagicStrings::$template_failed;
        try {
            $template = "<template>".$template."</template>";
            $root = DomUtils::parseString($template);
            $response = self::recursEval($root, $ps);
        }
        catch (Exception $ex) {
          error_log($ex->getMessage(), 3, Conversation::$logs_path . '/echo.txt');
        }
        return $response;
    }
   
    public static function validTemplate($template) {
        MagicBooleans::trace("AIMLProcessor.validTemplate(template: " . $template . ")");
        try {
            $template = "<template>".$template."</template>";
            DomUtils::parseString($template);
            return true;
        }
        catch (Exception $ex) {
          error_log("Invalid Template ".$template, 3, Conversation::$logs_path . '/echo.txt');
          return false;
        }

    }

}
