<?php

class MagicStrings {
    // General global strings
    public static $program_name_version        = "Program Brents Fucking Program";
    public static $comment                     = "Added repetition detection.";
    public static $aimlif_split_char           = ",";
    public static $default_bot                 = "alice2";
    public static $default_language            = "EN";
    public static $aimlif_split_char_name      = "\\#Comma";
    public static $aimlif_file_suffix          = ".csv";
    public static $ab_sample_file              = "sample.txt";
    public static $text_comment_mark           = ";;";
    // <sraix> defaults                        
    public static $pannous_api_key             = "guest";
    public static $pannous_login               = "test-user";
    public static $sraix_failed                = "SRAIXFAILED";
    public static $repetition_detected         = "REPETITIONDETECTED";
    public static $sraix_no_hint               = "nohint";
    public static $sraix_event_hint            = "event";
    public static $sraix_pic_hint              = "pic";
    public static $sraix_shopping_hint         = "shopping";
    // AIML files
    public static $unknown_aiml_file           = "unknown_aiml_file.aiml";
    public static $deleted_aiml_file           = "deleted.aiml";
    public static $learnf_aiml_file            = "learnf.aiml";
    public static $null_aiml_file              = "null.aiml";
    public static $inappropriate_aiml_file     = "inappropriate.aiml";
    public static $profanity_aiml_file         = "profanity.aiml";
    public static $insult_aiml_file            = "insults.aiml";
    public static $reductions_update_aiml_file = "reductions_update.aiml";
    public static $predicates_aiml_file        = "client_profile.aiml";
    public static $update_aiml_file            = "update.aiml";
    public static $personality_aiml_file       = "personality.aiml";
    public static $sraix_aiml_file             = "sraix.aiml";
    public static $oob_aiml_file               = "oob.aiml";
    public static $unfinished_aiml_file        = "unfinished.aiml";
    // filter responses
    public static $inappropriate_filter        = "FILTER INAPPROPRIATE";
    public static $profanity_filter            = "FILTER PROFANITY";
    public static $insult_filter               = "FILTER INSULT";
    // default templates
    public static $deleted_template            = "deleted";
    public static $unfinished_template         = "unfinished";
    // AIML defaults
    public static $bad_javascript              = "JSFAILED";
    public static $js_enabled                  = "true";
    public static $unknown_history_item        = "unknown";
    public static $default_bot_response        = "I'm sorry. My responses are limited. You must ask the right questions.";
    public static $error_bot_response          = "Something is wrong with my brain.";
    public static $schedule_error              = "I'm unable to schedule that event.";
    public static $system_failed               = "Failed to execute system command.";
    public static $default_get                 = "unknown";
    public static $default_property            = "unknown";
    public static $default_map                 = "unknown";
    public static $default_Customer_id         = "unknown";
    public static $default_bot_name            = "unknown";
    public static $default_that                = "unknown";
    public static $default_topic               = "unknown";
    public static $default_list_item           = "NIL";
    public static $undefined_triple            = "NIL";
    public static $unbound_variable            = "unknown";
    public static $template_failed             = "Template failed.";
    public static $too_much_recursion          = "Too much recursion in AIML";
    public static $too_much_looping            = "Too much looping in AIML";
    public static $blank_template              = "blank template";
    public static $null_input                  = "NORESP";
    public static $null_star                   = "nullstar";
    // sets and maps
    public static $set_member_string           = "ISA";
    public static $remote_map_key              = "external";
    public static $remote_set_key              = "external";
    public static $natural_number_set_name     = "number";
    public static $map_successor               = "successor";
    public static $map_predecessor             = "predecessor";
    public static $map_singular                = "singular";
    public static $map_plural                  = "plural";
    // paths
    //public static $root_path = "c:/ab";
    public static $root_path = "";
    public static function setRootPath($newRootPath) {
        self::$root_path = $newRootPath;
    }
    public static function setRootPathDefault() {self::setRootPath("/");}

    /**
     * function setBotDefaults
     *
     * Iterates through an array to set default values for the chatbot
     *
     * @param (array) params
     *
     * @return void
     */
    public static function setBotDefaults($params) {
      foreach ($params as $line) {
        list ($key, $value) = explode(':', $line, 2);
        self::$$key = $value;
      }
    }

}

