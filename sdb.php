<?php
/* SDB - Simple Database - v2.2.0
 * by vantezzen (http://vantezzen.de)
 *
 * For documentation check http://github.com/vantezzen/sdb
 */

class sdb
{
    /*
   * Configuration
   * Edit these settings to your needs
   */
  public static $folder = 'sdb/';     // Change the folder, where the tables will be saved to (notice the leading "/")
  public static $disablelock = false; // Disable the table lock*
  /*
   * * Table lock will protect a table when a script writes to it.
   *   This can prevent data loss when two scripts try to write
   *   to the same table at the same time. It will temporarely
   *   create a *.lock file named after the table name.
   */

  /**
   * Check requirements.
   *
   * @return Errors
   */
  public static function check()
  {
      $error = array();
      if (!file_exists(self::$folder) || !is_readable(self::$folder) || !is_writable(self::$folder)) {
          $error[] = self::$folder.' is not readable, writable or does not exist';
      }
      if (!file_exists(self::$folder.'.htaccess') || file_get_contents(self::$folder.'.htaccess') !== 'deny from all') {
          $error[] = self::$folder.'.htaccess does not exists or may not have the right content';
      }

      return $error;
  }

   /**
    * Create a new sdb database.
    *
    * @return Success
    */
   public static function CREATE($name, $columns)
   {
       self::checklock($name);
       self::setlock($name);
       $folder = self::$folder;
       $content = '';
       foreach ($columns as $column) {
           $content .= $column.';;';
       }
       $file = fopen($folder.$name.'.sdb', 'w');
       fwrite($file, $content);
       fclose($file);
       self::removelock($name);
   }

   /**
    * Insert data into a database
    *
    * @param Array of data to Insert
    * @param Database to insert it to
    */
    public static function INSERT($data, $database)
    {
        self::checklock($database);
        self::setlock($database);
        $path = self::$folder.$database.'.sdb';
        $columns = self::GET_COLUMNS($database);
        $content = '
';
        foreach ($columns as $column) {
            if (isset($data[$column])) {
                $data[$column] = str_replace(';;', '', $data[$column]);
                $content .= $data[$column].';;';
            } else {
                $content .= ';;';
            }
        }
        $file = fopen($path, 'a');
        fwrite($file, $content);
        self::removelock($database);
    }

    /**
      * Get the name of the columns of a database
      *
      * @param Name of the database
      * @return Array with names of the columns
      */
    public static function GET_COLUMNS($database)
    {
        $path = self::$folder.$database.'.sdb';
        if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
            return array();
        }
        $f = fopen($path, 'r');
        $line = fgets($f);
        fclose($f);
        $array = explode(';;', $line);
        array_pop($array);

        return $array;
    }

    /**
      * Select data from a database
      *
      * @param Name if the database
      * @param Array of the requirements of the selections
      * @return Array with the selected rows
      */
    public static function SELECT($database, $requirements = array())
    {
        $path = self::$folder.$database.'.sdb';
        $columns = self::GET_COLUMNS($database);
        $content = file_get_contents($path);
        $items = explode('
', $content);
        $items[0] = '';
        $rows = array();
        foreach ($items as $item) {
            if (!empty($item)) {
                $explode = explode(';;', $item);
                array_pop($explode);
                unset($row);
                $row = array();
                foreach ($explode as $key => $e) {
                    $row[$columns[$key]] = $e;
                }
                $array[] = $row;
            }
        }
        if ($requirements == array() || empty($requirements)) {
            return $array;
        }
        $select = array();
        $counter = 0;
        foreach ($requirements as $column => $value) {
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
            } else {
                $mode = 'normal';
            }
            if ($counter == 0) {
                foreach ($array as $row) {
                    if ($mode == 'normal') {
                        if (isset($row[$column]) && $row[$column] == $value) {
                            $select[] = $row;
                        }
                    } elseif ($mode == 'bigger') {
                        if (isset($row[$column]) && $row[$column] > $value) {
                            $select[] = $row;
                        }
                    } elseif ($mode == 'smaller') {
                        if (isset($row[$column]) && $row[$column] < $value) {
                            $select[] = $row;
                        }
                    } elseif ($mode == 'biggerequal') {
                        if (isset($row[$column]) && $row[$column] >= $value) {
                            $select[] = $row;
                        }
                    } elseif ($mode == 'smallerequal') {
                        if (isset($row[$column]) && $row[$column] <= $value) {
                            $select[] = $row;
                        }
                    } elseif ($mode == 'isnot') {
                        if (isset($row[$column]) && $row[$column] !== $value) {
                            $select[] = $row;
                        }
                    }
                }
            } else {
                foreach ($select as $key => $row) {
                    if ($mode == 'normal') {
                        if (isset($row[$column]) && $row[$column] !== $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'bigger') {
                        if (isset($row[$column]) && $row[$column] < $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'smaller') {
                        if (isset($row[$column]) && $row[$column] > $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'biggerequal') {
                        if (isset($row[$column]) && $row[$column] <= $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'smallerequal') {
                        if (isset($row[$column]) && $row[$column] >= $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'isnot') {
                        if (isset($row[$column]) && $row[$column] == $value) {
                            unset($select[$key]);
                        }
                    }
                }
            }
            ++$counter;
        }

        return $select;
    }

    /**
      * Update data in the database
      *
      * @param Name of the database
      * @param Array of data to Insert
      * @param Requirements of the row selections
      */
    public static function UPDATE($database, $data, $where = array())
    {
        self::checklock($database);
        self::setlock($database);
        $rows = self::SELECT($database, $where);
        $path = self::$folder.$database.'.sdb';
        $content = file_get_contents($path);
        foreach ($rows as $row) {
            $oldrow = '';
            $newrow = '';
            foreach ($row as $column => $value) {
                $oldrow .= $value.';;';
                if (isset($data[$column])) {
                    $data[$column] = str_replace(';;', '', $data[$column]);
                    if (preg_match('/^INCREASE BY/', $data[$column])) {
                        $data[$column] = str_replace('INCREASE BY ', '', $data[$column]);
                        $value = $value + $data[$column];
                    } elseif (preg_match('/^DECREASE BY/', $data[$column])) {
                        $data[$column] = str_replace('DECREASE BY ', '', $data[$column]);
                        $value = $value - $data[$column];
                    } elseif (preg_match('/^MULTIPLY BY/', $data[$column])) {
                        $data[$column] = str_replace('MULTIPLY BY ', '', $data[$column]);
                        $value = $value * $data[$column];
                    } elseif (preg_match('/^DIVIDE BY/', $data[$column])) {
                        $data[$column] = str_replace('DIVIDE BY ', '', $data[$column]);
                        $value = $value / $data[$column];
                    } else {
                        $value = $data[$column];
                    }
                    $newrow .= $value.';;';
                } else {
                    $newrow .= $value.';;';
                }
            }
            $content = str_replace($oldrow, $newrow, $content, $num);
        }
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);
        self::removelock($database);
    }

    /**
      * Delete data from the database
      *
      * @param Name of the database
      * @param Requirements of the row selection
      */
    public static function DELETE($database, $where = array())
    {
        self::checklock($database);
        self::setlock($database);
        $rows = self::SELECT($database, $where);
        $path = self::$folder.$database.'.sdb';
        $content = file_get_contents($path);
        foreach ($rows as $row) {
            $oldrow = '';
            foreach ($row as $column => $value) {
                $oldrow .= $value.';;';
            }
            $content = str_replace($oldrow, '', $content, $num);
        }
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);
        self::removelock($database);
        self::CLEAR($database);
    }

    /**
      * Truncate a database
      *
      * @param Name of the database
      */
    public static function TRUNCATE($database)
    {
        //Alias for DELETE *
     self::DELETE($database);
    }

    /**
      * Delete empty lines in the database file
      *
      * @param Name of the database
      */
    public static function CLEAR($database)
    {
        self::checklock($database);
        self::setlock($database);
        $path = self::$folder.$database.'.sdb';
        $content = file_get_contents($path);
        $rows = explode('
', $content);
        $newcontent = '';
        foreach ($rows as $key => $row) {
            if (!empty($row) && $row !== ' ') {
                $newcontent .= $row.'
';
            }
        }
        $file = fopen($path, 'w');
        fwrite($file, $newcontent);
        fclose($file);
        self::removelock($database);
    }

    /**
      * Drop/delete a database
      *
      * @param Name of the database
      */
    public static function DROP($database)
    {
        self::checklock($database);
        self::setlock($database);
        $path = self::$folder.$database.'.sdb';
        unlink($path);
        self::removelock($database);
    }

    /**
      * Get a list of tables in the database
      *
      * @return Array with the names of all tables
      */
    public static function TABLES()
    {
        $tables = array();
        foreach (glob(self::$folder.'*.sdb') as $table) {
            $tables[] = str_replace(array(self::$folder, '.sdb'), '', $table);
        }

        return $tables;
    }

   /*
    * MySQL Table Migrating System.
    */
    /**
      * Migrate a MySQL table to sdb
      *
      * @param Host of the MySQL Server
      * @param Username of the MySQL Server
      * @param Password of the MySQL Server
      * @param MySQL Database name
      * @param MySQL table name
      * @param MySQL WHERE statement
      * @return Errors
      */
   public static function MIGRATE($host, $username, $password, $database, $table, $where = '1')
   {
       $db = mysqli_connect($host, $username, $password, $database);
       if (mysqli_connect_errno()) {
           return array('error' => mysqli_connect_error());
       }
       $command = 'SELECT * FROM `'.$table.'` WHERE '.$where;
       $exe = mysqli_query($db, $command);
       if ($exe == false) {
           return array('error' => 'SELECT failed');
       }
       $columns = '';
       $rows = array();
       while ($row = mysqli_fetch_assoc($exe)) {
           if (empty($columns)) {
               $columns = array_keys($row);
           }
           $rows[] = $row;
       }
       self::CREATE($table, $columns);
       foreach ($rows as $row) {
           self::INSERT($row, $table);
       }
   }

   /**
     * Migrate a MySQL database to sdb
     *
     * @param Host of the MySQL Server
     * @param Username of the MySQL Server
     * @param Password of the MySQL Server
     * @param MySQL Database name
     * @return Errors
     */
    public static function MIGRATE_DB($host, $username, $password, $database)
    {
        $db = mysqli_connect($host, $username, $password, $database);
        if (mysqli_connect_errno()) {
            return array('error' => mysqli_connect_error());
        }
        $command = 'SHOW TABLES';
        $exe = mysqli_query($db, $command);
        if ($exe == false) {
            return array('error' => 'SELECT failed');
        }
        $columnname = 'Tables_in_'.$database;
        while ($row = mysqli_fetch_assoc($exe)) {
            self::MIGRATE($host, $username, $password, $database, $row[$columnname]);
        }
    }

   /*
    * Lock mechanism.
    */
   private static function setlock($database)
   {
       if (self::$disablelock == false) {
           $path = self::$folder.$database.'.lock';
           $file = fopen($path, 'w');
           fwrite($file, 'LOCKED');
           fclose($file);
       }

       return true;
   }

    private static function removelock($database)
    {
        if (self::$disablelock == false) {
            $path = self::$folder.$database.'.lock';
            unlink($path);
        }

        return true;
    }

    private static function checklock($database)
    {
        if (self::$disablelock == false) {
            $lockfile = self::$folder.$database.'.lock';
            $i = 0;
            while (file_exists($lockfile) && $i < 1000) {
                usleep(10);
                ++$i;
            }
        }

        return true;
    }
}
