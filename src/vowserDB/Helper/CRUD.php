<?php
/**
 * vowserDB CRUD Helper
 * Helper for CRUD manipulation of table arrays
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

class CRUD {
    /**
     * Apply a given selection to a data array
     * 
     * @param array $data       Data that should be filtered
     * @param mixed $selection  Selection arguments that will be applied to the $data array
     * @param array $columns    Name of the columns in the array
     * @param bool $partialArrayMatch Wheather partial matches of arrays should be accepted
     * 
     * @return array Filtered data
     */
    public function applySelection(array $data, $selection, array $columns, bool $particalArrayMatch): array {
        // If should select all, just return the data
        if ($selection == array() || empty($selection) || $selection == '*') {
            return $data;
        }

        // Array that will hold the final selection
        $select = [];

        // Counter of selections already applied
        $counter = 0;

        foreach ($selection as $column => $value) {
            if ($counter == 0) {
                // In first round, use all data
                $iterate = $data;
            } else {
                // In following rounds, only use already selected rows
                $iterate = $select;
            }
            foreach ($iterate as $key => $row) {

                $field = $row[$column];

                // Get, weather the row matches the selection
                $matches = self::matchesSelection($field, $value, $particalArrayMatch);
                
                if ($matches && $counter == 0) {
                    // On first round, add all rows that match to the $select array
                    array_push($select, $row);
                } else if (!$matches && $counter > 0) {
                    // On all other rounds remove all rows that don't match
                    unset($select[$key]);
                }
            }
            $counter++;
        }
        return $select;
    }

    /**
     * Get the mode of the selector.
     * This will resolve vowserDB selection arguments and get the associated value
     * 
     * @param string $value Single selection argument
     * @return array 0 => Mode it should operate in, 1 => Value for the selection
     */
    private static function getMode($value): array {
        if (is_array($value)) {
            $mode = 'array';
        } else if (preg_match('/^BIGGER THAN/', $value)) {
            $mode = 'bigger';
            $value = str_replace('BIGGER THAN ', '', $value);
        } elseif (preg_match('/^SMALLER THAN/', $value)) {
            $mode = 'smaller';
            $value = str_replace('SMALLER THAN ', '', $value);
        } elseif (preg_match('/^BIGGER EQUAL/', $value)) {
            $mode = 'biggerequal';
            $value = str_replace('BIGGER EQUAL ', '', $value);
        } elseif (preg_match('/^SMALLER EQUAL/', $value)) {
            $mode = 'smallerequal';
            $value = str_replace('SMALLER EQUAL ', '', $value);
        } elseif (preg_match('/^IS NOT/', $value)) {
            $mode = 'isnot';
            $value = str_replace('IS NOT ', '', $value);
        } elseif (preg_match('/^LIKE/', $value)) {
            $mode = 'like';
            $value = str_replace('LIKE ', '', $value);
        } elseif (preg_match('/^MATCH/', $value)) {
            $mode = 'match';
            $value = str_replace('MATCH ', '', $value);
        } else {
            $mode = 'normal';
        }
        return [ $mode, $value ];
    }

    /**
     * Check if a field matches a selection
     * 
     * @param string $field Value of the field
     * @param string $selection Selection argument for field
     * @param bool $particalArrayMatch Allow array to only partially match
     * @return bool Wheather the field matches the selection
     */
    private static function matchesSelection($field, $selection, bool $particalArrayMatch): bool {
        $modeInfo = self::getMode($selection);
        $mode = $modeInfo[0];
        $value = $modeInfo[1];
        $matches = (
            ($mode == 'normal'        && $field === $value)                ||
            ($mode == 'bigger'        && $field > $value)                  ||
            ($mode == 'smaller'       && $field < $value)                  ||
            ($mode == 'biggerequal'   && $field >= $value)                 ||
            ($mode == 'smallerequal'  && $field <= $value)                 ||
            ($mode == 'like'          && stristr($field, (string) $value)) ||
            ($mode == 'match'         && preg_match($value, $field))       ||
            ($mode == 'isnot'         && $field !== $value)                ||
            
            ($mode == 'array'         && $particalArrayMatch === false && $field == $value) ||
            ($mode == 'array'         && $particalArrayMatch === true  && self::particalArrayMatch($field, $value))
        );
        return $matches;
    }

    /**
     * Check if there is a partial array match between the $fullArray and $particalArray
     * 
     * @param mixed $fullArray Full array with all data, this can also be of another type which will return false as there is no match
     * @param array $partialArray Array with partial data
     * 
     * @return bool If the fullArray partially matches
     */
    private static function particalArrayMatch($fullArray, array $partialArray): bool {
        if (!is_array($fullArray)) {
            return false;
        }
        $isAssociative = (array_keys($partialArray) !== range(0, count($partialArray) - 1));
        if ($isAssociative) {
            foreach($partialArray as $key => $value) {
                if (!isset($fullArray[$key]) || $fullArray[$key] !== $value) {
                    return false;
                }
            }
        } else {
            foreach($partialArray as $key => $value) {
                if (!in_array($value, $fullArray)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Update selected rows in the data array
     * 
     * @param array $selection  Selection that will be updated
     * @param array $data       Data array that will be updated
     * @param array $update     Update that will be done to the selected rows
     * @return array Updated $data array
     */
    public static function update(array $selection, array $data, array $update): array {
        foreach($selection as $key => $row) {
            // Apply update to row
            $updated = $row;

            foreach($row as $column => $value) {
                if (isset($update[$column])) {
                    $value = self::getUpdatedValue($value, $update[$column]);
                    $updated[$column] = $value;
                }
            }

            // Transfer updated row to data array
            foreach($data as $dataKey => $dataRow) {
                if ($row === $dataRow) {
                    $data[$dataKey] = $updated;
                }
            }
        }

        return $data;
    }

    /**
     * Apply an update to a single value
     * 
     * @param mixed $value Value that should be updated
     * @param string $update Update that should be applied
     * 
     * @return mixed Updated $value
     */
    private static function getUpdatedValue($value, string $update) {
        if (preg_match('/^INCREASE BY/', $update)) {
            $value = $value + str_replace('INCREASE BY ', '', $update);
        } elseif (preg_match('/^DECREASE BY/', $update)) {
            $value = $value - str_replace('DECREASE BY ', '', $update);
        } elseif (preg_match('/^MULTIPLY BY/', $update)) {
            $value = $value * str_replace('MULTIPLY BY ', '', $update);
        } elseif (preg_match('/^DIVIDE BY/', $update)) {
            $value = $value / str_replace('DIVIDE BY ', '', $update);
        } elseif (preg_match('/^ARRAY PUSH/', $update)) {
            array_push($value, str_replace('ARRAY PUSH ', '', $update));
        } elseif (preg_match('/^ARRAY REMOVE/', $update)) {
            $remove = str_replace('ARRAY REMOVE ', '', $update);
            $value = array_filter($value, function($val) use ($remove) {
                return $val !== $remove;
            });
        } else {
            $value = $update;
        }
        return $value;
    }

    /**
     * Delete a given selection from a data array
     * 
     * @param array $selection  Selection to delete
     * @param array $data       Data array to delete the selection from
     * @return array $data with $selection removed
     */
    public function delete(array $selection, array $data) {
        // Remove selection from data array
        foreach ($selection as $skey => $selected) {
            foreach ($data as $dkey => $d) {
                if ($selected == $d) {
                    unset($data[$dkey]);
                }
            }
        }
        
        // Clean the data array to avoid blank keys
        $clean = array();
        foreach ($data as $row) {
            $clean[] = $row;
        }

        return $clean;
    }
}