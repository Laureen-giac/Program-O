<?php
  /***************************************
   * http://www.program-o.com
   * Program-O
   * Version: 3.0.0
   * Build: 1402028162
   * FILE: Config.php
   * AUTHOR: Elizabeth Perreau and Dave Morton
   * DATE: 5/28/2016 - 9:09 AM
   * DETAILS: ${DESCRIPTION}
   ***************************************/



  class Config {
    public $cfg;
    public $credentials;
    private $cfgArray;
    public function __construct($iniFile) {
        $cfgArray = parse_ini_file(CFG_PATH . '/config.ini', true);
        //file_put_contents(LOG_PATH . '/cfgArray.txt', print_r($cfgArray, true));
        $this->cfg = $cfgArray;
        $this->credentials = $this->cfg['DB'];
    }

    public function getConfig($section = null, $key = null) {
        if(is_null($section)) return $this->cfg;
        if(is_null($key)) return $this->cfg[$section];
        return $this->cfg[$section][$key];
    }

    public function setConfig($section, $key, $value) {
        $this->cfg[$section][$key] = $value;
    }

    public function saveConfig() {
        $configContents = '';
        foreach ($this->cfg as $index => $section) {
            $configContents .= "[$index]\n";
            foreach ($section as $key => $value) {
                $configContents .= "$key=$value\n";
            }
            $configContents .= "\n";
            file_put_contents(CFG_PATH . '/config.ini', $configContents);
        }
    }
  }




