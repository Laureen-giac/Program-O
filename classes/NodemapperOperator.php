<?php

class NodemapperOperator {

    public static function size($node) {
        $set = array();
        if ($node->shortCut) array_push($set, "<THAT>");
        if ($node->key != null) array_push($set, $node->key);
        if (!empty($node->map)) $set = array_merge($set, array_keys($node->map));
        return sizeof($set);
    }

   public static function put($node, $key, $value) {
       if (!empty($node->map)) {
           $node->map[$key] = $value;
       }
       else { // node.type == unary_node_mapper
             $node->key = $key;
             $node->value = $value;

       }
   }

   public static function get($node, $key) {
       if ($node->map != null) {
           return $node->map[$key];
       }
       else {// node.type == unary_node_mapper
           if ($key === $node->key) return $node->value;
           else return null;
       }

   }

   public static function containsKey($node, $key)  {
       if (!empty($node->map)) {
           return array_key_exists($key, $node->map);
       }
       else {// node.type == unary_node_mapper
           if ($key === $node->key) return true;
           else return false;
       }
   }

    public static function printKeys ($node)  {
        $set = self::keySet($node);
        foreach($set as $k => $v) {
            echo($k);
        }
    }

    public static function keySet($node) {
        if ($node->map != null) {
            return array_keys($node->map);
        }
        else {// node.type == unary_node_mapper
            $set = array();
            if ($node->key != null) array_push($set, $node->key);
            return $set;
        }

    }

    public static function isLeaf($node) {
        return ($node->category != null);
    }

    public static function upgrade($node) {
        $node->map = array();
        $node->map[$node->key] = $node->value;
        $node->key = null;
        $node->value = null;
    }
}
