<?php
/**
 * vowserDB JSON File storage
 * Handle reading and writing to the table using JSON files.
 *
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 *
 * @link          https://vantezzen.github.io/vowserdb
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * @version       4.1.0
 */

namespace vowserDB\Storage;

use vowserDB\Helper\Armor;

class JSON extends AbstractStorage
{
    /**
     * File extension used for table files
     *
     * @var String
     */
    public $extension = 'json';

    /**
     * Read data from the table file.
     *
     * @param string $file    Path to the file that should be read
     * @param array  $columns Array of column names that will be applied to the data array
     *
     * @return array Data from the file associated with the given column names
     */
    public function read(string $file, array $columns): array
    {
        $f = fopen($file, 'r');
        $json = fread($f , filesize($file));
        fclose($f);

        $content = \json_decode($json, true);
        $data = $content['data']; 
        
        return $data;
    }

    /**
     * Get an array with the name of the columns in a given file.
     *
     * @param string $file Path to a table file
     *
     * @return array Array of the columns in the file
     */
    public function columns(string $file): array
    {
        $f = fopen($file, 'r');
        $json = fread($f , filesize($file));
        fclose($f);

        $content = \json_decode($json, true);
        $columns = $content['columns']; 
        
        return $columns;
    }

    /**
     * Save data from data array to the table file.
     *
     * @param string $file    Path to the table file
     * @param array  $columns Array of column names of the table
     * @param array  $data    Data that will be saved to the table
     */
    public function save(string $file, array $columns, array $data)
    {
        // Make sure all rows contain all columns
        foreach($data as $key => $row) {
            foreach($columns as $column) {
                if (!isset($row[$column])) {
                    $data[$key][$column] = '';
                }
            }
        }

        // Write to file
        $content = [
            "columns" => $columns,
            "data" => $data
        ];
        $f = fopen($file, 'w');
        $json = fwrite($f , json_encode($content));
        fclose($f);
    }

    /**
     * Delete a table file.
     *
     * @param string $file Path to the table file to delete
     */
    public function delete(string $file)
    {
        unlink($file);
    }
}
