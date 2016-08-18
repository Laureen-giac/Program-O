<?php

  /***************************************
  * http://www.program-o.com
  * PROGRAM O
  * Version: 3.0.0
  * FILE: Bot.php
  * AUTHOR: Elizabeth Perreau and Dave Morton
  * DATE: 08-14-2016
  * DETAILS: Bot class- The basic building block for the chatbot
  ***************************************/

  class Bot {

      // public vars

      public $name;
      public $brain;
      public $debugger;
      public $bot_path;
      public $aiml_path;
      public $maps_path;
      public $sets_path;
      public $cfg;

      public function __construct($name, $cfg, $debugger = null) {
          if (is_null($debugger)) $debugger = new Logger(LOG_PATH . "$name.debug.log", LOG_ALL);
          $this->debugger = $debugger;
          $this->name = $name;
          $this->bot_path = "bots/$name";
          $this->cfg = new Config('../config/config.ini');
          $pathsArray = $this->cfg->getConfig('paths');
          foreach ($pathsArray as $key => $value) {
              $this->{$key} = $this->bot_path . $value;
          }
          $this->debugger->logEntry(Logger::LOG_IMPORTANT, 'Building the brain.', __METHOD__, __LINE__);

          if(extension_loaded('apc') && ini_get('apc.enabled') && false) { //
              $bot_name = $this->name; // We want to keep chatbots separate from each other, so let's differentiate between them
              $this->debugger->logEntry(Logger::LOG_IMPORTANT, "Attempting to take advantage of APCu to increase performance.", __METHOD__, __LINE__);
              if(isset($this->post['message']) && $this->post['message'] == "reload") {
                  apc_delete("brain_$bot_name");
                  apc_clear_cache();
              }
              if(apc_exists("brain_$bot_name")) {
                  $this->brain = unserialize(apc_fetch("brain_$bot_name"));
                  $this->addAIMLSets();
                  $this->addAIMLMaps();
              }
              else {
                  $this->brain = new Graphmaster($name, 'test', $this->debugger);
                  $this->loadMemory();
                  $this->addAIMLSets();
                  $this->addAIMLMaps();
                  apc_store("brain_$bot_name", serialize($this->brain));
              }
          }
          else {
              $this->brain = new Graphmaster($name, 'test', $this->debugger);
              $this->addAIMLSets();
              $this->addAIMLMaps();
              $this->loadMemory();
          }
      }

      public function loadMemory() {
          $categories = array();
          $globSearch = $this->aiml_path . '/*.{aiml}';
          $files = glob($globSearch, GLOB_BRACE);

          foreach($files as $file) {
              $this->debugger->logEntry(Logger::LOG_TRIVIAL,"loading AIML file $file into the brain", __METHOD__, __LINE__);
              $cats = AIMLProcessor::AIMLToCategories("", $file);
              $categories = array_merge($categories, $cats);
          }

          foreach($categories as $category) {
            $this->brain->addCategory($category);
          }
          $this->debugger->logEntry(Logger::LOG_INFO, 'Memory loading complete.', __METHOD__, __LINE__);
      }

      function addAIMLSets() {
          $this->debugger->logEntry(Logger::LOG_INFO, 'Loading AIML sets.', __METHOD__, __LINE__);
          $cnt = 0;
          $globSearch = $this->sets_path . '/*.{txt}';
          $files = glob($globSearch, GLOB_BRACE);

          foreach($files as $file) {
              $setName = basename($file, ".txt");
              $aimlSet = new AIMLSet($setName, $this);
              $cnt .= $aimlSet->readAIMLSet($this);
              $this->setMap[$setName] = $aimlSet;
          }
          return $cnt;
      }

      function addAIMLMaps() {
          $this->debugger->logEntry(Logger::LOG_INFO, 'Loading AIML maps.', __METHOD__, __LINE__);
          $cnt = 0;
          $globSearch = $this->maps_path . '/*.{txt}';
          $files = glob($globSearch, GLOB_BRACE);

          foreach($files as $file) {
              $mapName = basename($file, ".txt");
              $aimlMap = new AIMLMap($mapName, $this);
              $cnt .= $aimlMap->readAIMLMap($this);
              $this->mapMap[$mapName] = $aimlMap;
          }
          return $cnt;
      }
  }

