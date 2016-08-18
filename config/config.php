<?php

$cwd = str_replace(DIRECTORY_SEPARATOR, '/', $cwd);
define('BASE_PATH', "$cwd/");
define('LOG_PATH', BASE_PATH . 'logs/');
define('CFG_PATH', BASE_PATH . 'config/');
define('CLASS_PATH', BASE_PATH . 'classes/');
define('CONFIG_PATH', BASE_PATH . 'config/');
define('TEMPLATE_PATH', BASE_PATH . 'templates/');
define('BOTS_PATH', BASE_PATH . 'bots/');

// More constant definitions here, as needed

$cfg = new Config(CONFIG_PATH . 'config.ini');

  /**
   * Function __autoload
   *
   * * @param $class
   * @return void
   */
  function __autoload($class) {
    require_once (CLASS_PATH . "$class.php");
  }