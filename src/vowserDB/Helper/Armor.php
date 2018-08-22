<?php
namespace vowserDB\Helper;

class Armor {
    public static function armor($value) {
        if (is_array($value)) {
            $value = "vowserDBArray" . json_encode($value);
        }
        return $value;
    }

    public static function unarmore($value) {

    }
}