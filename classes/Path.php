<?php

class Path {

    public $word;
    public $next;
    public $length;

    public function __construct() {
        $this->next = null;
        $this->word = null;
        $this->length = 0;
    }

    public static function sentenceToPath($sentence) {
        //echo("calling sentence to path with sentence = " . $sentence);
        $sentence = trim($sentence);
        return self::arrayToPath(explode(" ", $sentence));
    }

    public static function pathToSentence ($path) {
        $result="";
        for ($p = $path; $p != null; $p = $p->next) {
            $result = $result . " " . $p->word;
        }
        return trim($result);
    }

    private static function arrayToPath($array) {
        $tail = null;
        $head = null;
        for ($i = sizeof($array)-1; $i >= 0; $i--) {
            $head = new Path();
            $head->word = $array[$i];
            $head->next = $tail;
            if ($tail == null) $head->length = 1;
            else $head->length = $tail->length + 1;
            $tail = $head;
        }
        return $head;
    }

    private static function arrayToPathI($array, $index)  {
        if ($index >= sizeof($array)) return null;
        else {
            $newPath = new Path();
            $newPath->word = $array[$index];
            $newPath->next = self::arrayToPathI($array, $index+1);
            if ($newPath->next == null) $newPath->length = 1;
            else $newPath->length = $newPath->next->length + 1;
            return $newPath;
        }
    }

    // renamed from print because php didnt like it.
    public function debug() {
        $result = "";
        for ($p = $this; $p != null; $p = $p->next) {
            $result .= $p->word.",";
        }
        $result = rtrim($result, ',');
        echo($result);
    }
}
