<?php
/**
 * vowserDB CSV File
 * Handle reading and writing to the table CSV files
 *
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 * @link          https://vantezzen.github.io/vowserdb
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @version       4.0.0
 */

namespace vowserDB;

use vowserDB\Helper\Armor;

class CSVFile {
    /**
     * Read a CSV file and remove the first row as it is used for column decleration
     * 
     * @param string $file      Path to the file that should be read
     * @param array $columns    Array of column names that will be applied to the data array
     * 
     * @return array Data from the file associated with the given column names
     */
    public static function read(string $file, array $columns): array {
        $f = fopen($file, 'r');
        $content = array();
        while (($data = fgetcsv($f)) !== false) {
            if (!empty($data) && array_filter($data, 'trim')) {
                $row = array();
                foreach ($data as $key => $e) {
                    $row[$columns[$key]] = Armor::unarmor($e);
                }
                $content[] = $row;
            }
        }
        fclose($f);

        array_shift($content);

        return $content;
    }

    /**
     * Get an array with the name of the columns in a given file
     * 
     * @param string $file  Path to a table file
     * @return array Array of the columns in the file
     */
    public static function columns(string $file): array {
        $f = fopen($file, 'r');
        $rows = fgetcsv($f);
        fclose($f);

        return $rows;
    }

    /**
     * Write the column decleration row (first row) to a table file.
     * 
     * @param string $file      Path to the table file
     * @param array $columns    Array of column names that should be written to the table
     * @param bool $dontCloseFile Don't close the file but rather return it
     * @return file Table file if $dontCloseFile is true
     */
    public static function writeColumns(string $file, array $columns, bool $dontCloseFile = false) {
        $file = fopen($file, 'w');
        fputcsv($file, $columns);
        if ($dontCloseFile) {
            return $file;
        } else {        
            fclose($file);
        }
    }

    /**
     * Save data from data array to the table file
     * 
     * @param string $file   Path to the table file
     * @param array $columns Array of column names of the table
     * @param array $data    Data that will be saved to the table
     */
    public static function save(string $file, array $columns, array $data) {
        $file = self::writeColumns($file, $columns, true);
        foreach ($data as $row) {
            $final = array();
            foreach ($columns as $column) {
                $final[] = isset($row[$column]) ? Armor::armor($row[$column]) : "";
            }
            fputcsv($file, $final);
        }
        fclose($file);
    }

    /**
     * Delete a table file
     * 
     * @param string $file  Path to the table file to delete
     */
    public static function delete(string $file) {
        unlink($file);
    }
}
?>