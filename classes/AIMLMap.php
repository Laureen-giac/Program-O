<?php

class AIMLMap {
    public $mapName;
    private $host; // for external maps
    private $botid; // for external maps
    private $isExternal = false;
    //$inflector = new Inflector();
    private $bot;

    private $data = array();

    public function __construct ($name, $bot) {
        //$this->bot = $bot;
        $this->mapName = $name;
    }

    public function get($key) {
        $value;
        if ($this->mapName === MagicStrings::$map_successor) {
            try {
                return $key+1;
            } catch (Exception $ex) {
                return MagicStrings::$default_map;
            }
        }
        else if ($this->mapName === MagicStrings::$map_predecessor) {
            try {
                return $key-1;
            } catch (Exception $ex) {
                return MagicStrings::$default_map;
            }
        }
        else if ($this->mapName === "singular") {
            return $key;//inflector.singularize(key).toLowerCase();
        }
        else if ($this->mapName === "plural") {
            return $key;//inflector.pluralize(key).toLowerCase();
        }
        else if ($this->isExternal && MagicBooleans::$enable_external_sets) {
            //TODO: NOT DOING EXTERNALS RIGHT NOW AS THEY ARE FOR PANDORABOTS
            //String[] split = key.split(" ");
            /*$query = strtoupper($this->mapName)." ".$key;
            $response = Sraix.sraix(null, query, MagicStrings.default_map, null, host, botid, null, "0");
            System.out.println("External "+mapName+"("+key+")="+response);
            value = response;*/
        }
        else $value = isset($this->data[$key]) ? $this->data[$key] : null;
        if ($value == null) $value = MagicStrings::$default_map;
        //System.out.println("AIMLMap get "+key+"="+value);
        return $value;
    }

    public function put($key, $value) {
        $this->data[$key] = $value;
        return $value;
    }


    public function writeAIMLMap () {
        //TODO: CONVERT THIS TO PHP
        /*System.out.println("Writing AIML Map "+mapName);
        try{
            // Create file
            FileWriter fstream = new FileWriter(bot.maps_path+"/"+mapName+".txt");
            BufferedWriter out = new BufferedWriter(fstream);
            for (String p : this.keySet()) {
                p = p.trim();
                //System.out.println(p+"-->"+this.get(p));
                out.write(p+":"+this.get(p).trim());
                out.newLine();
            }
            //Close the output stream
            out.close();
        }catch (Exception e){//Catch exception if any
            System.err.println("Error: " + e.getMessage());
        }*/
    }

    public function readAIMLMap($bot) {
        $cnt = 0;
        if (!file_exists($bot->maps_path."/".$this->mapName.".txt")) return $cnt;
        $lines = file($bot->maps_path."/".$this->mapName.".txt");
        $lines = array_values(array_filter($lines, "trim"));
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            $splitLine = explode(":", $line, 2);
            $this->put(strtoupper(trim($splitLine[0])), trim($splitLine[1]));
            $cnt++;
        }
        return $cnt;
    }
}
