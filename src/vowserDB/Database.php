<?php
/**
 * vowserDB : Standalone database software for PHP (https://vantezzen.github.io/vowserdb-docs/index.html)
 * Managing the database folder
 * Copyright (c) vantezzen (https://github.com/vantezzen/).
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

namespace vowserDB;

use vowserDB\Storage\CSV;

class Database
{
    public static function getPath($folder)
    {
        if ($folder === false) {
            $folder = 'vowserdb/';
        }
        if ($folder instanceof Table) {
            $folder = dirname($folder->path);
        }

        $folder = realpath($folder);

        return $folder;
    }

    public static function tables($storage = false, $folder = false)
    {
        $path = self::getPath($folder);

        if ($storage == false) {
            $storage = new CSV;
        }

        $files = glob($path.'/*.' . $storage->extension);

        // Get table name from absolute path
        foreach ($files as $key => $file) {
            $files[$key] = basename($file, '.'. $storage->extension);
        }

        return $files;
    }

    public static function truncate($storage = false, $folder = false)
    {
        if ($storage == false) {
            $storage = new CSV;
        }

        $tables = self::tables($storage, $folder);
        $folder = self::getPath($folder);

        foreach ($tables as $table) {
            $columns = $storage->columns($folder.$table.'.'. $storage->extension);
            $storage->save($folder.$table.'.'. $storage->extension, $columns, []);
        }
    }
}
