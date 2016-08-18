<?php

class AIMLSet {
    private $setName;
    private $maxLength = 1; // there are no empty sets
    private $host; // for external sets
    private $botid; // for external sets
    private $isExternal = false;
    private $bot;
    private $inCache = array();
    private $outCache = array();

    private $data = array();

    public function add($val) {
        array_push($this->data, $val);
    }

    public function __construct ($name, $bot) {
        //$this->bot = $bot;
        $this->setName = strtolower($name);
        if ($this->setName === MagicStrings::$natural_number_set_name)  $this->maxLength = 1;
    }

    public function contains($s) {
        if ($this->isExternal && MagicBooleans::$enable_external_sets) {
            if (in_array($s, $this->inCache)) return true;
            if (in_array($s, $this->outCache)) return false;
            $split = explode(" ", $s);
            if (sizeof($split) > $this->maxLength) return false;
            $query = MagicStrings::$set_member_string.strtoupper($setName)." ".$s;
            $response = Sraix::sraix(null, $query, "false", null, $this->host, $this->botid, null, "0");
            if ($response == "true") {array_push($this->inCache, $s); return true;}
            else { array_push($this->outCache, $s); return false; }
        } else if ($this->setName == MagicStrings::$natural_number_set_name) {
           
            return preg_match('/^[0-9]+$/', $s);
        }
        else return in_array($s, $this->data);
    }

    public function writeAIMLSet () {

    }


    public function readAIMLSet ($bot) {
        $cnt = 0;
        $lines = file($bot->sets_path."/".$this->setName.".txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_values(array_filter($lines, "trim"));
        foreach ($lines as $line_num => $line) {
            if ($this->startsWith($line, "external")) {
                $splitLine = explode(":", $line);
                if (sizeof($splitLine) >= 4) {
                    $this->host = $splitLine[1];
                    $this->botid = $splitLine[2];
                    $this->maxLength = $splitLine[3];
                    $this->isExternal = true;
                    error_log("Created external set at ".$this->host." ".$botid, 3, $bot->log_path . '/echo.txt');
                }
            }
            else {
                $line = trim(strtoupper($line));
                $splitLine = explode(" ", $line);
                $length = sizeof($splitLine);
                if ($length > $this->maxLength) $this->maxLength = $length;
                $this->add(trim($line));
                $cnt++;
            }
        }
       
        return $cnt;

    }

    function startsWith($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }

    function endsWith($haystack, $needle)
    {
        return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
    }

}
