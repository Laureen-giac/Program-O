<?php

define('BASE_PATH', $cwd);
define('LOG_PATH', BASE_PATH . '/logs/');
define('CFG_PATH', BASE_PATH . '/config/');
define('CLASS_PATH', BASE_PATH . '/classes/');
file_put_contents(LOG_PATH . 'classpath.txt', CLASS_PATH);

