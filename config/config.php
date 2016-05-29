<?php

$cwd = str_replace('\\', '/', $cwd);
define('BASE_PATH', "$cwd/");
file_put_contents('logs/BASE_PATH.txt', BASE_PATH);
echo 'BASE_PATH = ' . BASE_PATH;
define('LOG_PATH', BASE_PATH . 'logs/');
define('CFG_PATH', BASE_PATH . 'config/');
define('CLASS_PATH', BASE_PATH . 'classes/');
file_put_contents(LOG_PATH . 'classpath.txt', CLASS_PATH);

