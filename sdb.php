<?php
/* SDB - Simple Database - v2.3.0
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
    * Create a new sdb table.
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
    * Insert data into a table
    *
    * @param Array of data to Insert
    * @param table to insert it to
    */
    public static function INSERT($data, $table)
    {
        self::checklock($table);
        self::setlock($table);
        $path = self::$folder.$table.'.sdb';
        $columns = self::GET_COLUMNS($table);
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
        self::removelock($table);
    }

    /**
      * Get the name of the columns of a table
      *
      * @param Name of the table
      * @return Array with names of the columns
      */
    public static function GET_COLUMNS($table)
    {
        $path = self::$folder.$table.'.sdb';
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
      * Select data from a table
      *
      * @param Name of the table
      * @param Array of the requirements of the selections
      * @return Array with the selected rows
      */
    public static function SELECT($table, $requirements = array())
    {
        $path = self::$folder.$table.'.sdb';
        $columns = self::GET_COLUMNS($table);
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
      * Update data in the table
      *
      * @param Name of the table
      * @param Array of data to Insert
      * @param Requirements of the row selections
      */
    public static function UPDATE($table, $data, $where = array())
    {
        self::checklock($table);
        self::setlock($table);
        $rows = self::SELECT($table, $where);
        $path = self::$folder.$table.'.sdb';
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
        self::removelock($table);
    }

    /**
      * Delete data from the table
      *
      * @param Name of the table
      * @param Requirements of the row selection
      */
    public static function DELETE($table, $where = array())
    {
        self::checklock($table);
        self::setlock($table);
        $rows = self::SELECT($table, $where);
        $path = self::$folder.$table.'.sdb';
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
        self::removelock($table);
        self::CLEAR($table);
    }

    /**
      * Truncate a table
      *
      * @param Name of the table
      */
    public static function TRUNCATE($table)
    {
        //Alias for DELETE *
     self::DELETE($table);
    }

    /**
      * Delete empty lines in the table file
      *
      * @param Name of the table
      */
    public static function CLEAR($table)
    {
        self::checklock($table);
        self::setlock($table);
        $path = self::$folder.$table.'.sdb';
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
        self::removelock($table);
    }

    /**
      * Drop/delete a table
      *
      * @param Name of the table
      */
    public static function DROP($table)
    {
        self::checklock($table);
        self::setlock($table);
        $path = self::$folder.$table.'.sdb';
        unlink($path);
        self::removelock($table);
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
    /**
      * Set a lock for a table
      *
      * @param Table name
      * @return true
      */
   private static function setlock($table)
   {
       if (self::$disablelock == false) {
           $path = self::$folder.$table.'.lock';
           $file = fopen($path, 'w');
           fwrite($file, 'LOCKED');
           fclose($file);
       }

       return true;
   }

  /**
    * Remove the lock of a database
    *
    * @param Name of the table
    * @return true
    */
    private static function removelock($table)
    {
        if (self::$disablelock == false) {
            $path = self::$folder.$table.'.lock';
            unlink($path);
        }

        return true;
    }

    /**
      * Wait for a table to get unlocked
      *
      * @param Name of the table
      * @return true
      */
    private static function checklock($table)
    {
        if (self::$disablelock == false) {
            $lockfile = self::$folder.$table.'.lock';
            $i = 0;
            while (file_exists($lockfile) && $i < 1000) {
                usleep(10);
                ++$i;
            }
        }

        return true;
    }
}
