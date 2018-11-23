<?php
/**
 * vowserDB : Standalone database software for PHP (https://vantezzen.github.io/vowserdb-docs/index.html)
 * Managing the database folder
 * Copyright (c) vantezzen (https://github.com/vantezzen/)
 *
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 * @link          https://vantezzen.github.io/vowserdb
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @version       4.1.0
 */

namespace vowserDB;

class Database {
    public static function getPath($folder) {
        if ($folder === false) {
            $folder = 'vowserdb/';
        }
        if ($folder instanceof Table) {
            $folder = dirname($folder->path);
        }
        
        $folder = realpath($folder);

        return $folder;
    }

    public static function tables($folder = false) {
        $path = self::getPath($folder);
        
        $files = glob($path . '/*.csv');

        // Get table name from absolute path
        foreach($files as $key => $file) {
            $files[$key] = basename($file, '.csv');
        }

        return $files;
    }

    public static function truncate($folder = false) {
        $tables = self::tables($folder);
        $folder = self::getPath($folder);

        foreach($tables as $table) {
            $columns = CSVFile::columns($folder . $table . '.csv');
            CSVFile::writeColumns(folder . $table . '.csv', $columns);
        }
    }
}