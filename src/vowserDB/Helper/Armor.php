<?php
/**
 * vowserDB Armor Helper
 * Armor values (especially arrays) before inserting into table and unarmor values
 * 
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 * @link          https://vantezzen.github.io/vowserdb-docs/index.html vowserDB
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @version       4.0.0 - Alpha 1
 */

namespace vowserDB\Helper;

class Armor {
    /**
     * Armor value to be inserted into the table file
     * 
     * @param mixed $value Value to armor
     * @return string Armored value
     */
    public static function armor($value): string {
        if (is_array($value)) {
            $value = "vowserDBArray" . json_encode($value);
        }
        return $value;
    }

    /**
     * Unarmor value to be used in script
     * 
     * @param string $value Armored value
     * @return mixed Unarmored value
     */
    public static function unarmor(string $value) {
        if (preg_match('/^vowserDBArray.*/', $value)) {
            $value = str_replace("vowserDBArray", "", $value);
            $value = json_decode($value, true);
        }
        return $value;
    }
}