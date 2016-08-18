<?php

class Category {
    private $pattern;
    private $that;
    private $topic;
    private $template;
    private $filename;
    private $activationCnt;
    private $categoryNumber; // for loading order
    public static $categoryCnt = 0;
    private $matches;
    public $validationMessage="";

    public function __construct($activationCnt, $pattern, $that, $topic, $template, $filename){
        if (MagicBooleans::$fix_excel_csv)   {
            $pattern = Utilities::fixCSV($pattern);
            $that = Utilities::fixCSV($that);
            $topic = Utilities::fixCSV($topic);
            $template = Utilities::fixCSV($template);
            $filename = Utilities::fixCSV($filename);
        }

        $this->pattern = strtoupper(trim($pattern));
        $this->that = strtoupper(trim($that));
        $this->topic = strtoupper(trim($topic));
        $this->template = str_replace("& ", " and ", $template); // XML parser treats & badly
        $this->filename = $filename;
        $this->activationCnt = $activationCnt;
        $this->matches = null;
        $this->categoryNumber = self::$categoryCnt++;
    }

    public function getMatches($bot) {
        if ($this->matches != null)
        return $this->matches;
        else return new AIMLSet("No Matches", $bot);
    }

    public function getActivationCnt () {
        return $this->activationCnt;
    }

    public function getCategoryNumber () {
        return $this->categoryNumber;
    }

    public function getPattern () {
        if ($this->pattern == null) return "*";
        else return $this->pattern;
    }
  
    public function getThat () {
        if ($this->that == null) return "*";
        else return $this->that;
    }
  
    public function getTopic () {
        if ($this->topic == null) return "*";
        else return $this->topic;
    }
    
    public function getTemplate () {
        if ($this->template==null) return "";
        else
            return $this->template;
    }
   
    public function getFilename () {
        if ($this->filename==null) return MagicStrings::$unknown_aiml_file;
        else
            return $this->filename;
    }

    public function incrementActivationCnt() {
        $this->activationCnt++;
    }

    public function setActivationCnt($cnt) {
        $this->activationCnt = $cnt;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }
  
    public function setTemplate($template) {
        $this->template = $template;
    }

    public function setPattern($pattern) {
        $this->pattern = $pattern;
    }

    public function setThat($that) {
        $this->that = $that;
    }

    public function setTopic($topic) {
        $this->topic = $topic;
    }

    public function inputThatTopic() {
        return Graphmaster::inputThatTopic($this->pattern, $this->that, $this->topic);
    }

    public function addMatch ($input, $bot) {
        if ($this->matches == null) {
            $setName = str_replace("*", "STAR", $this->inputThatTopic());
            $setName = str_replace("_", "UNDERSCORE", $setName);
            $setName = str_replace(" ", "-", $setName);
            $setName = str_replace("<THAT>", "THAT", $setName);
            $setName = str_replace("<TOPIC>", "TOPIC", $setName);
            $this->matches = new AIMLSet($setName, $bot);
        }

        $this->matches->add($input);
    }

    public static function templateToLine ($template) {
        $result = $template;
        $result = str_replace(array("\r\n", "\n\r", "\r", "\n"), "\\#Newline", $result);
        $result = str_replace(MagicStrings::$aimlif_split_char, MagicStrings::$aimlif_split_char_name, $result);
        return $result;
    }

    private static function lineToTemplate($line) {
        $result = str_replace("\\#Newline","\n", $result);
        $result = str_replace(MagicStrings::$aimlif_split_char_name, MagicStrings::$aimlif_split_char, $result);
        return $result;
    }

    public static function IFToCategory($IF) {
        $pieces = explode(MagicStrings::$aimlif_split_char, $IF);
        return new Category($pieces[0], $pieces[1], $pieces[2], $pieces[3], self::lineToTemplate($pieces[4]), $pieces[5]);
     }

    public static function categoryToIF($category) {
        $c = MagicStrings::$aimlif_split_char;
        return $category->getActivationCnt().$c.$category->getPattern().$c.$category->getThat().$c.$category->getTopic().$c.self::templateToLine($category->getTemplate()).$c.$category->getFilename();
    }

    static function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    static function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

    public static function categoryToAIML($category) {
        $topicStart = "";
        $topicEnd = "";
        $thatStatement = "";
        $result = "";
        $pattern = $category->getPattern();

        if(strpos($pattern,'<SET>') !== false || strpos($pattern,'<BOT>') !== false) {
            $splitPattern = explode(" ", $pattern);
            $rpattern = "";
            foreach($splitPattern as $w) {
                if (self::startsWith($w, "<SET>") || self::startsWith($w, "<BOT>") || self::startsWith($w, "NAME=")) {$w = strtolower($w);}
                $rpattern = $rpattern." ".$w;
            }
            $pattern = trim($rpattern);
        }

        $NL = "\n";
        try {
            if (!$category->getTopic() === "*") { $topicStart = "<topic name=\"".$category->getTopic()."\">".$NL; $topicEnd = "</topic>".$NL;}
            if (!$category->getThat() === "*") { $thatStatement = "<that>".$category->getThat()."</that>";}
            $result = $topicStart."<category><pattern>".$pattern."</pattern>".$thatStatement.$NL."<template>".$category->getTemplate()."</template>".$NL."</category>".$topicEnd;
        } catch (Exception $ex) {
            echo($ex->getMessage());
        }
        return $result;
    }

    public function validPatternForm($pattern) {
        if (strlen($pattern) < 1) {$validationMessage .= "Zero length. "; return false; }
        $words = explode(" ", $pattern);
        foreach($words as $word) {
            /*if (!(word.matches("[\\p{Hiragana}\\p{Katakana}\\p{Han}\\p{Latin}]*+") || word.equals("*") || word.equals("_"))) {
                System.out.println("Invalid pattern word "+word);
                return false;
            }*/
        }
        return true;
    }

    public function validate () {
        $validationMessage = "";
        if (!$this->validPatternForm($pattern)) {$validationMessage .= "Badly formatted <pattern>"; return false;}
        if (!$this->validPatternForm($that)) {$validationMessage .= "Badly formatted <that>"; return false;}
        if (!$this->validPatternForm($topic)) {$validationMessage .= "Badly formatted <topic>"; return false;}
        if (!AIMLProcessor::validTemplate($template)) {$validationMessage .= "Badly formatted <template>"; return false;}
        if (!$this->endsWith($this->filename, ".aiml")) {$validationMessage .= "Filename suffix should be .aiml"; return false;}
        return true;
    }

    static function get_string_between($string, $start, $end){
        $string = " ".$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }


    public function CategoryShort($activationCnt, $patternThatTopic, $template, $filename){
        $this->Category($activationCnt,
                self::get_string_between($patternThatTopic, "<PATTERN>", "</PATTERN"),
                self::get_string_between($patternThatTopic, "<THAT>", "</THAT"),
                self::get_string_between($patternThatTopic, "<TOPIC>", "</TOPIC"),
                $template, $filename);
    }

    public static function ACTIVATION_COMPARATOR($c1, $c2) {
        return $c2->getActivationCnt() - $c1->getActivationCnt();
    }
   
    public static function PATTERN_COMPARATOR($c1, $c2) {
        return strcasecmp($c1->inputThatTopic(), $c2->inputThatTopic());
    }

    public static function CATEGORY_NUMBER_COMPARATOR($c1, $c2) {
        return $c1->getCategoryNumber() - $c2->getCategoryNumber();
    }
}