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
     * 
     * @return array Filtered data
     */
    public function applySelection($data, $selection, $columns) {
        if ($selection == array() || empty($selection) || $selection == '*') {
            return $data;
        }
        $select = array();
        $counter = 0;
        foreach ($selection as $column => $value) {
            if (preg_match('/^BIGGER THAN/', $value)) {
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
            foreach ($data as $row) {
              if (
                ($mode == 'normal'        && isset($row[$column]) && $row[$column] == $value)                 ||
                ($mode == 'bigger'        && isset($row[$column]) && $row[$column] > $value)                  ||
                ($mode == 'smaller'       && isset($row[$column]) && $row[$column] < $value)                  ||
                ($mode == 'biggerequal'   && isset($row[$column]) && $row[$column] >= $value)                 ||
                ($mode == 'smallerequal'  && isset($row[$column]) && $row[$column] <= $value)                 ||
                ($mode == 'like'          && isset($row[$column]) && stristr($row[$column], (string) $value)) ||
                ($mode == 'match'         && isset($row[$column]) && preg_match($value, $row[$column]))       ||
                ($mode == 'isnot'         && isset($row[$column]) && $row[$column] !== $value)
              ) {
                  if ($counter == 0) {
                      $select[] = $row;
                  } else {
                    unset($select[$key]);
                  }
                }
              }
              ++$counter;
            }
        return $select;
    }

    /**
     * Update selected rows in the data array
     * 
     * @param array $selection  Selection that will be updated
     * @param array $data       Data array that will be updated
     * @param array $update     Update that will be done to the selected rows
     * @return array Updated $data array
     */
    public static function update($selection, $data, $update) {
        $selected = $selection;
        foreach ($selection as $key => $row) {
            foreach ($row as $column => $value) {
                if (isset($update[$column])) {
                    if (preg_match('/^INCREASE BY/', $update[$column])) {
                        $value = $value + str_replace('INCREASE BY ', '', $update[$column]);
                    } elseif (preg_match('/^DECREASE BY/', $update[$column])) {
                        $value = $value - str_replace('DECREASE BY ', '', $update[$column]);
                    } elseif (preg_match('/^MULTIPLY BY/', $update[$column])) {
                        $value = $value * str_replace('MULTIPLY BY ', '', $update[$column]);
                    } elseif (preg_match('/^DIVIDE BY/', $update[$column])) {
                        $value = $value / str_replace('DIVIDE BY ', '', $update[$column]);
                    } else {
                        $value = $update[$column];
                    }
                    $selection[$key][$column] = $value;
                }
            }
        }
        var_dump($selection);

        // Replace updated data in data array
        foreach ($selected as $skey => $selected_row) {
            foreach ($data as $dkey => $d) {
                if ($selected_row == $d) {
                    $data[$dkey] = $selection[$skey];
                }
            }
        }

        return $data;
    }

    /**
     * Delete a given selection from a data array
     * 
     * @param array $selection  Selection to delete
     * @param array $data       Data array to delete the selection from
     */
    public function delete($selection, $data) {
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