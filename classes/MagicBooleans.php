<?php

class MagicBooleans {
    public static $trace_mode = false;
    public static $enable_external_sets = true;
    public static $enable_external_maps = true;
    public static $jp_tokenize = false;
    public static $fix_excel_csv = false;
    public static $enable_network_connection = true;
    public static $cache_sraix = false;
    public static $qa_test_mode = false;
    public static $make_verbs_sets_maps = false;
    public static $debugcats = false;

    public static function trace($traceString) {
        if (self::$trace_mode) {
            echo($traceString . "<br/>");
        }
    }

    public static function webPrint($string) {
        /*$string = str_replace("<", "&lt;", $string);
        $string = str_replace(">", "&gt;", $string);
        echo($string . "<br/>");*/
    }

    public static function webArray($arr) {
       /* echo("<pre>");
        print_r($arr);
        echo("</pre>");*/
    }
}
