<?php

class Utilities {

    static function startsWith($haystack, $needle)
    {
        return $needle === "" || stripos($haystack, $needle) === 0;
    }

    static function endsWith($haystack, $needle)
    {
        return $needle === "" || strtolower(substr($haystack, -strlen($needle))) === strtolower($needle);
    }

    public static function fixCSV ($line) {
        $line = rtrim($line, ';');
        $line = str_replace("\"", "", $line);
        $line = str_replace("\"\"", "\"", $line);
        return $line;
    }

    public static function tagTrim($s, $tagName) {
        $stag = "<$tagName>";
        $etag = "</$tagName>";
        if(self::startsWith($s, $stag) && self::endsWith($s, $etag)) {
            $s = substr($s, strlen($stag));
            $s = substr($s, 0, -strlen($etag));
        }

        return $s;
    }

    public static function stringSet()  {
        $set = array();
        /*foreach($strings as $string)
            array_push($set, $string);*/

        //php <= 5.5
        for($i = 0; $i < func_num_args(); $i++){
            array_push($set, func_get_arg($i));
        }
        return $set;
    }

    public static function saveStarState() {

    }
}
