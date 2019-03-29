<?php
/**
 * vowserDB Storage abstract class
 * Abstract class for the creation of storage providers.
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
 * @version       4.1.1
 */

namespace vowserDB\Storage;

abstract class AbstractStorage implements StorageInterface
{
    /**
     * File extension used for table files.
     *
     * @var string
     */
    public $extension;

    /**
     * Read a CSV file and remove the first row as it is used for column decleration.
     *
     * @param string $file    Path to the file that should be read
     * @param array  $columns Array of column names that will be applied to the data array
     *
     * @return array Data from the file associated with the given column names
     */
    abstract public function read(string $file, array $columns): array;

    /**
     * Get an array with the name of the columns in a given file.
     *
     * @param string $file Path to a table file
     *
     * @return array Array of the columns in the file
     */
    abstract public function columns(string $file): array;

    /**
     * Save data from data array to the table file.
     *
     * @param string $file    Path to the table file
     * @param array  $columns Array of column names of the table
     * @param array  $data    Data that will be saved to the table
     */
    abstract public function save(string $file, array $columns, array $data);

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
