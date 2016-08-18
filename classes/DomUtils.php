<?php

class DomUtils {

    public static function parseFile($fileName) {
        $file = file_get_contents($fileName);
        $domdoc = new DOMDocument();
        $domdoc->loadXML($file);
        $root = $domdoc->getElementsByTagName('aiml')->item(0);
        return $root;
    }

    public static function parseString($string) {
        $root = new DOMDocument();
        $root->loadXML($string);
        return $root;
    }

    public static function nodeToString($node) {
        return $node->ownerDocument->saveXML($node);
    }
}
