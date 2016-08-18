<?php
  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 3.0.0
   *
   * FILE: API.php
   * AUTHOR: Dave Morton and Elizabeth Perreau
   * DATE: 5/28/2016 - 8:48 AM
   * DETAILS: API gateway for the Program O chatbot
   ***************************************/

  $cwd = dirname(__FILE__);
  error_reporting(E_ALL);
  ini_set('log_errors', 1);
  ini_set('display_errors', 1);
  require_once ('config/config.php');
  ini_set('error_log', LOG_PATH . 'base.error.log');

  //$db = new DB($config->credentials);
  $referrer = '';
  apc_clear_cache('user');
  apc_clear_cache();
  if (isset($_SERVER['HTTP_REFERER'])) $referrer = $_SERVER['HTTP_REFERER'];
  $debugLogger = new Logger(LOG_PATH . 'debug.log', Logger::LOG_ALL);
  $PGO_Logger = new Logger(LOG_PATH . 'PGO.error.log', Logger::LOG_ALL);

  $debugLogger->logEntry(Logger::LOG_TRIVIAL, 'This is a test.', '[No class or method]', __LINE__);
  $bot_name = $cfg->getConfig('bot', 'name');
  $bot = new Bot($bot_name, $cfg, $debugLogger);
  //$brain = new Graphmaster($bot_name, 'test', $debugLogger);

/*
  $cfg->setConfig('Test', 'foo','bat');
  $cfg->saveConfig();
*/
  $debugLogger->logEntry(Logger::LOG_ALL, 'Script complete.', 'API.PHP:[no method or function]', __LINE__);
  header('content-type: text/plain');
  print_r($bot);


