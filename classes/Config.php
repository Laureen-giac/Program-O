<?php
  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 2.4.2
   * Build: 1402028162
   * FILE: Config.php
   * AUTHOR: Elizabeth Perreau and Dave Morton
   * DATE: 5/28/2016 - 9:09 AM
   * DETAILS: ${DESCRIPTION}
   ***************************************/


  //namespace PGOv3;


  class Config
  {
    public $cfg;
    private $cfgArray;
    public function __construct() {
      $cfgArray = parse_ini_file(CFG_PATH . '/config.ini', true);
      file_put_contents(LOG_PATH . '/cfgArray.txt', print_r($cfgArray, true));
    }

  }