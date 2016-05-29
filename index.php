<?php
  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 3.0.0
   *
   * FILE: index.php
   * AUTHOR: Dave Morton and Elizabeth Perreau
   * DATE: 5/28/2016 - 8:48 AM
   * DETAILS: Root gateway for the Program O chatbot
   ***************************************/

  $cwd = dirname(__FILE__);

  require_once ('config/config.php');
  error_reporting(E_ALL);
  ini_set('log_errors', 1);
  ini_set('display_errors', 1);
  ini_set('error_log', LOG_PATH . 'base.error.log');

  
  $cfg = new Config();
  //$dbh = new DBobject();
  
  
  /**
   * Function __autoload
   *
   * * @param $class
   * @return void
   */
  function __autoload($class) {
    require_once (CLASS_PATH . "$class.php");
  }