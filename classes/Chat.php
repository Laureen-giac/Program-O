<?php

class Chat {
    public $bot;
    public $doWrites;
    public $customerId;
    public $thatHistory;
    public $requestHistory;
    public $responseHistory;
    public $inputHistory;
    public $predicates;
    public static $matchTrace;
    public static $locationKnown;
    public static $longitude;
    public static $latitude;
    public $tripleStore;

    public function __construct($bot, $doWrites = true, $customerId = "0") {
      DebugLogger::logDebug('Constructing a new chat object.', DebugLogger::DEBUG_TRIVIAL, __CLASS__, __METHOD__, __LINE__);
        $this->customerId = $customerId;
        $this->bot = $bot;
        $this->doWrites = $doWrites;
        $contextThatHistory = array();

        $this->thatHistory= array();
        $this->requestHistory= array();
        $this->responseHistory= array();
        $this->inputHistory= array();

        $this->predicates = new Predicates();
        self::$matchTrace = "";
        self::$locationKnown = false;
        $this->tripleStore = new TripleStore("anon", $this);

        /*array_push($contextThatHistory, MagicStrings::$default_that);
        array_push($this->thatHistory, $contextThatHistory);*/

        MemoryUtils::loadHistory($this);

        $this->addPredicates();
        //$this->addTriples(); commented so it can go on the server. It is exhausting memory
        //$this->tripleStore->printTriples();

        //TODO:: FIX THIS. This will overwrite what is in the session with unknown. Should topic carry on until changed?
        // if so, just check to make sure its not set first and then you can make it unknown.
        //$this->predicates->put("topic", MagicStrings::$default_topic);
        //$this->predicates->put("jsenabled", MagicStrings::$js_enabled);
        if (MagicBooleans::$trace_mode) echo("Chat Session Created for bot ". $this->bot->name);
    }

    function addPredicates() {
        try {
            $this->predicates->getPredicateDefaults($this->bot->config_path . '/predicates.txt') ;
        } catch (Exception $ex)  {
            echo($ex->getMessage());
        }
    }
    
    function addTriples() {
        if(isset($_SESSION['tripleStore']))
            $this->tripleStore = MemoryUtils::loadFromSession("tripleStore");
        else
        {
            $tripleCnt = 0;
            //if (MagicBooleans::$trace_mode) echo("Loading Triples from ".$this->bot->config_path."/triples.txt");
            if(file_exists($this->bot->config_path."/triples.txt")) {
                $lines = file($this->bot->config_path."/triples.txt");
                $lines = array_values(array_filter($lines, "trim"));
                foreach($lines as $line) {
                    $triple = explode(":", $line, 3);
                    $subject = $triple[0];
                    $predicate = $triple[1];
                    $object = $triple[2];
                    $this->tripleStore->addTriple($subject, $predicate, $object);
                    $tripleCnt++;
                }
                if (MagicBooleans::$trace_mode) echo("Loaded ".$tripleCnt." triples");
            }
            MemoryUtils::savetoSession("tripleStore", $this->tripleStore);
            return $tripleCnt;
        }
    }

    public function conversation () {
       /* $request="SET PREDICATES";
        $response = $this->multisentenceRespond($request);*/

        //$this->runTests(true);

        $request = $this->bot->post['message'];
        DebugLogger::logDebug("Starting conversation. input = '$request'.",DebugLogger::DEBUG_TRIVIAL, __CLASS__, __METHOD__, __LINE__);
        $response = $this->multisentenceRespond($request);
        DebugLogger::logDebug("Response generated: '$response'.", DebugLogger::DEBUG_TRIVIAL, __CLASS__, __METHOD__, __LINE__);

        $ret =  ("request: " . $request . "<br/>\n");
        $ret .= ("response: " . $response . "<br/>");

        $this->logChat($request, $response);

        return(json_encode(array("response" => $ret)));
        //$this->bot->brain->printgraph();
    }

    function logChat($request, $response) {
        $file = $this->bot->log_path . '/chatlog.txt';
        $current = (file_exists($file)) ?  file_get_contents($file) : '';
        $current .= "request: " . $request . "-->response: " . $response . "\n";
        file_put_contents($file, $current);
    }

    function runTests($verbose) {
        $overall = "<span style='color:green'>SUCCESS</span>";
        //Unknown topic tests -------------------------------------------------------------------------------------------------------------
        $tests = array(
                       "hi"                               => array("Hi back at ya"),
                       "fill in the blanks"               => array("filling in all da blanks"),
                       "bots are the shit"                => array("THE SHIT!!"),
                       "some sharp matching"              => array("Found a sharp match"),
                       "pick a number between 1 and 10"   => array("1", "2", "3", "4", "5", "6", "7", "8", "9", "10"),
                       "blue is my favorite color"        => array("MINE TOO"),
                       "red is my favorite color"         => array("I have no answer for that."),
                       "is your name lucy"                => array("Yup"),
                       "is your name Bob"                 => array("I have no answer for that."),
                       "what sound does a horse make"     => array("A LOUD ONE"),
                       "i like blue"                      => array("blue is a nice color."),
                       "i like black"                     => array("black is a nice color."),
                       "i like potatoes"                  => array("I have no answer for that."),
                       "do you like blue"                 => array("yes, blue is a nice color and star index 1 works"),
                       "do you like yager"                => array("I have no answer for that."),
                       "black and blue go together"       => array("black and blue go together nicely and multi index stars work"),
                       "does blue work with recursion"    => array("RECURSION and RECURSION works with recursion and sr/srai works"),
                       "yes"                              => array("YES WHAT"),
                       "what is your favorite video game" =>array("The lucy chat robot!")
            );

        foreach($tests as $request => $expected) {
            if(!$this->runTest($request, $expected, $verbose))
                $overall = "<span style='color:red'>FAILED</span>";
        }
        //-----------------------------------------------------------------------------------------------------------------------------------


        //PUPPY topic tests -----------------------------------------------------------------------------------------------------------------
        $this->predicates->put("topic", "PUPPIES");
        if($verbose)
            echo("<span style='color:blue'>SETTING TOPIC TO PUPPIES</span><br/>");
        $topic_tests = array("yes"      => array("SO DO I"),
                             "no"      => array("WHAT IS WRONG WITH YOU")
            );

        foreach($topic_tests as $request => $expected) {
            if(!$this->runTest($request, $expected, $verbose))
                $overall = "<span style='color:red'>FAILED</span>";
        }

        $this->predicates->put("topic", MagicStrings::$default_topic);
        //-----------------------------------------------------------------------------------------------------------------------------------

        echo("TESTS COMPLETE--OVERALL STATUS:" . $overall . "<br/><br/>");
        
    }

    function runTest($request, $expected, $verbose) {
        $response = $this->multisentenceRespond($request);
        if($verbose) {
            echo("REQUEST: " . $request . "<br/>");
            echo("RESPONSE: " . $response . "<br/>");
            echo("STATUS: " . (in_array($response, $expected) ? "<span style='color:green'>SUCCESS</span>" : "<span style='color:red'>FAILED</span>") . "<br/><br/>");
        }
        return (in_array($response, $expected));
    }

    function respondThatTopic($input, $that, $topic, &$contextThatHistory) {
        MagicBooleans::webPrint("calling respondThatTopic with " . $input);
        $repetition = true;
        for($i = 0; $i < MagicNumbers::$repetition_count; $i++) {
            if (!isset($this->inputHistory[$i]) || strtoupper($input) !== strtoupper($this->inputHistory[$i]) )
                $repetition = false;
        }
        if ($input === MagicStrings::$null_input) $repetition = false;
        array_push($this->inputHistory, $input);
        if ($repetition) {$input = MagicStrings::$repetition_detected;}

        $time_pre = microtime(true);
        $response = AIMLProcessor::respond($input, $that, $topic, $this);
        $time_post = microtime(true);
        $exec_time = $time_post - $time_pre;
        $normResponse = $this->bot->preProcessor->normalize(strip_tags($response));//$this->bot->preProcessor->normalize($response); //testing. i am gettnig things like lt br slash gt back in "that" and its messing up games
        //$normResponse .="(".$exec_time.")";

        if (MagicBooleans::$jp_tokenize) $normResponse = JapaneseUtils::tokenizeSentence($normResponse);
        $sentences = $this->bot->preProcessor->sentenceSplit($normResponse);
        foreach($sentences as $that) {
            if (trim($that) === "") $that = MagicStrings::$default_that;
                array_push($contextThatHistory, $that);
        }
        $result = trim($response)."  ";
        return $result;//." (".$exec_time.")";
    }

    function respond($input, &$contextThatHistory) {
        $hist = end($this->thatHistory);
        $that;
        if (empty($hist)) $that = MagicStrings::$default_that;
        else $that = end($hist);
        return $this->respondThatTopic($input, $that, $this->predicates->get("topic"), $contextThatHistory);
    }

    public function multisentenceRespond($request) {
        MagicBooleans::webPrint("calling multisentenceResonse with " . $request);
        $response="";
        $matchTrace="";
        try {
            $normalized = $this->bot->preProcessor->normalize($request);
            $normalized = JapaneseUtils::tokenizeSentence($normalized);
            $sentences = $this->bot->preProcessor->sentenceSplit($normalized);
            MagicBooleans::webPrint(sizeof($sentences) . " sentence(s)");
            $contextThatHistory = array();
            foreach($sentences as $sentence) {
                AIMLProcessor::$trace_count = 0;
                $reply = $this->respond($sentence, $contextThatHistory);
                MagicBooleans::webPrint("reply = " . $reply);
                $response .= "  ".$reply;
            }
            array_push($this->requestHistory, $request);
            array_push($this->responseHistory, $response);
            array_push($this->thatHistory, $contextThatHistory);
           
            MemoryUtils::saveHistory($this);
            $response = str_replace("[\n]+", "\n", $response);
            $response = trim($response);
        } catch (Exception $ex) {
            echo($ex->getMessage());
            return MagicStrings::$error_bot_response;
        }

        if ($this->doWrites) {
            $this->bot->writeLearnfIFCategories();
        }
        return $response;
    }

    public static function setMatchTrace($newMatchTrace) {
        self::$matchTrace = $newMatchTrace;
    }
}
