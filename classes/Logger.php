<?php

  /***************************************
   * http://www.program-o.com
   * PROGRAM O
   * Version: 3.0.0
   * FILE: Logger.php
   * AUTHOR: Elizabeth Perreau and Dave Morton
   * DATE: 08-13-2016
   * DETAILS: Program O Logger class
   * LONG DESCRIPTION: This logger class not only creates debugging log files,
   * but also other log files as well, such as SQL logs, more detailed error logs, etc.
   * Usage will be described here later, once more details become available.
   ***************************************/

  class Logger {
    // init variables
    public $log_path;
    protected $log_level;

    private  $firstTimestamp;
    private  $lastTimestamp;
    private  $debug_filename;
    private  $lastMethodCall = 'none';
    private  $debugLabels;
    private  $newFilePerVolley = true;
    private  $firstEntry = true;

    const LOG_ALL         = 1023; // room for expansion
    const LOG_FATAL_ERROR = 256;  // these will be handled later, once I get error handling figured out
    const LOG_EXCEPTION   = 128;  // these will be handled later, once I get EXCEPTION handling figured out
    const LOG_ERROR       = 64;   // NON-FATAL php ERRORS
    const LOG_WARNING     = 32;   // php WARNINGS
    const LOG_NOTICE      = 16;   // php NOTICES
    const LOG_IMPORTANT   = 8;    // Notices of major events in the script, such as class instantiations, etc.
    const LOG_SQL         = 4;    // Database related information (queries, empty results, etc.)
    const LOG_INFO        = 2;    // General information, for non-looping function/method/procedure calls
    const LOG_TRIVIAL     = 1;    // Unimportant information, included for completeness of tracing
    const LOG_NONE        = 0;    // Pretty self-explanitory: don't log anything!

    public function __construct($log_path, $log_level = LOG_ALL) {
      $this->log_level = $log_level;
      $this->log_path = $log_path;
      $this->firstTimestamp = microtime(true);
      $this->lastTimestamp = microtime(true);
      $this->debug_filename = $log_path;
      $this->debugLabels = array(
        self::LOG_ALL         => 'LOG_ALL',
        self::LOG_FATAL_ERROR => 'LOG_FATAL_ERROR',
        self::LOG_EXCEPTION   => 'LOG_EXCEPTION',
        self::LOG_ERROR       => 'LOG_ERROR',
        self::LOG_WARNING     => 'LOG_WARNING',
        self::LOG_NOTICE      => 'LOG_NOTICE',
        self::LOG_IMPORTANT   => 'LOG_IMPORTANT',
        self::LOG_SQL         => 'LOG_SQL',
        self::LOG_INFO        => 'LOG_INFO',
        self::LOG_TRIVIAL     => 'LOG_TRIVIAL',
        self::LOG_NONE        => 'LOG_NONE', // this one should never see the light of day in a log file.
      );
    }

    public function logError($error_num, $error_message, $method, $line) {
      $err_label = $this->debugLabels[$error_num];
      $out = "[ts] Error #$error_num($err_label) logged from $method, line #$line. The message was:\n$error_message\n";
      // create the timestamp
      $time = microtime();
      list($uSec, $now) = explode(' ', $time);
      $us = round($uSec * 1000);
      $ts = date('m/d/Y h:i:s.[\u\s]', (int)$now);
      $out = str_replace('[ts]', $ts, $out);
      $out = str_replace('[us]', $us, $out);
      error_log($out, 3, $this->log_path);
    }

    public function logEntry($debugLevel, $message, $method, $line, $deferred = false) {
      $log_level = $this->log_level;
      if ($this->newFilePerVolley && $this->firstEntry) file_put_contents($this->debug_filename, '');
      $this->firstEntry = false;
      $allowLogging = ($debugLevel & $this->log_level);
      //error_log("allowLogging = $allowLogging, debugLevel = $debugLevel, log_level = $log_level.\n", 3, $this->debug_filename);
      if (!$allowLogging) {
        error_log("Logging failed. allowLogging = $allowLogging, debugLevel = $debugLevel, log_level = $log_level.\n", 3, 'logs/test.log');
        return false;
      }
      $currentTS = microtime(true);
      $prCTS = print_r($currentTS, true);
      $tsLabel = $this->getTSLabel($currentTS);
      $elapsed = $this->getElapsed($this->lastTimestamp, $currentTS);
      $totalElapsed = $this->getElapsed($this->firstTimestamp, $currentTS);
      $this->lastTimestamp = $currentTS;
      $debug = array(
        'last method call' => $this->lastMethodCall,
        'timestamp' => $tsLabel,
        'elapsed' => $elapsed,
        'total elapsed' => $totalElapsed,
        'location' => "$method, $line",
        'Called Debug Level' => $this->debugLabels[$debugLevel],
        'message' => $message,
      );
      $dbgTxt = print_r($debug, true);
      $dbgTxt = str_replace("Array\n(", "[$tsLabel] (", $dbgTxt);
      if ($deferred) {
        $_SESSION['deferred_debug'] .= $dbgTxt;
      }
      elseif (isset($_SESSION['deferred_debug'])) {
        file_put_contents($this->debug_filename, '');
        error_log($_SESSION['deferred_debug'], 3, $this->debug_filename);
        unset($_SESSION['deferred_debug']);
        error_log($dbgTxt, 3, $this->debug_filename);
      }
      else error_log($dbgTxt, 3, $this->debug_filename);

      $this->lastMethodCall = "$method, $line";
    }

    private static function getTSLabel($ts) {
      $label = date('m/d/Y H:i:s.[*]', $ts);
      $tsUs = round(round($ts - (int)$ts, 3) + 0.00001, 3); // PHP's round() function isn't always accurate, so add a "fudge factor"
      $tsUs = str_replace('0.', '', $tsUs);
      return str_replace('[*]', $tsUs, $label);
    }

    private static function getElapsed($start, $end) {
      $elapsed = ($end - $start) * 1000000;
      switch (true) {
        case ($elapsed > 1000000):
          $etUnits = 'seconds';
          $etVal = $elapsed / 1000000;
        break;
        case ($elapsed > 1000):
          $etUnits = '(MILLI)seconds';
          $etVal = $elapsed / 1000;
        break;
        default:
          $etUnits = '(MICRO)seconds';
          $etVal = $elapsed;
      }
      $etVal = round(round($etVal, 3) + 0.00001, 3); // PHP's round() function isn't always accurate, so add a "fudge factor"
      return "$etVal $etUnits";
    }
  }

