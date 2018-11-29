<?php
/**
 * vowserDB Creation Helper
 * Helper for the creation and initialization of tables and databases.
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

namespace vowserDB\Helper;

use Exception;
use vowserDB\CSVFile;
use vowserDB\Exception\DatabaseCreationException;
use vowserDB\Exception\PermissionException;
use vowserDB\Exception\UnknownColumnsException;

class Initialize
{
    /**
     * Pre-made templates that can be used when creating a new table.
     *
     * @var array
     */
    protected static $templates = [
        'users' => [
            'username',
            'uuid',
            'password',
            'mail',
            'data',
        ],
        'posts' => [
            'uuid',
            'post_id',
            'type',
            'data',
            'created_date',
        ],
    ];

    /**
     * Create and initialize database folder
     * If the database folder doesn't exist it will be created
     * If vowserDB doesn't have the needed rights it will throw an error.
     *
     * @param string $path Path to the database folder
     *
     * @throws vowserDB\Exception\DatabaseCreationException When the database folder does not exist and can not be created
     * @throws vowserDB\Exception\PermissionException       If the database folder can not be read or written to
     *
     * @return bool True, if initialization and creation was successful, false if not
     */
    public static function database(string $path): bool
    {
        $folder = dirname($path);
        if (!file_exists($folder)) {
            try {
                mkdir($folder);
            } catch (Exception $e) {
                throw new DatabaseCreationException("vowserDB database folder doesn't exist and couldn't be created.");
                throw new $e();
                return false;
            }
        }
        if (!is_readable($folder)) {
            throw new PermissionException('vowserDB database folder is not readable.');
            return false;
        }
        if (!is_writable($folder)) {
            throw new PermissionException('vowserDB database folder is not writable.');
            return false;
        }

        return true;
    }

    /**
     * Create and initialize a table with given column names
     * If the table doesn't exist it will be created and the column declreation row will be written
     * This function supports templates as columns.
     * If the table already exists, $columns won't be used and can be set to false, but it is highly
     * adviced to always supply columns.
     *
     * @param string $path              Path to the table
     * @param mixed  $columns           Column array or template to initialize table with
     * @param array  $additionalColumns Columns to add to a given template (optional)
     *
     * @throws vowserDB\Exception\UnknownColumnsException If the table does not exist yet and no columns have been provided
     *
     * @return bool Success state of the initialization
     */
    public static function table(string $path, string $table, $columns, $additionalColumns = false): bool
    {
        // Check if the database exists and can be accessed
        if (!self::database($path)) {
            return false;
        }

        // Create table if not exists
        if (!file_exists($path)) {
            if ((empty($columns) || $columns == false) && !isset(self::$templates[$table])) {
                throw new UnknownColumnsException('No columns for vowserDB table given.');
                return false;
            } elseif (!is_array($columns)) {
                if ($columns == false) {
                    // Apply table template if $table is table template name
                    $columns = self::$templates[$table];
                } else {
                    // Apply table template if $columns is table template name
                    $columns = self::$templates[$columns];
                }

                if ($additionalColumns !== false) {
                    // Merge with $additionalColumns if availible
                    $columns = array_merge($columns, $additionalColumns);
                }
            }

            // Write columns - this will automatically create the file
            CSVFile::writeColumns($path, $columns);
        }

        return true;
    }
}
