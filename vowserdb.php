<?php
/* vowserDB -  v2.8.0
 * by vantezzen (http://vantezzen.de)
 *
 * For documentation check http://github.com/vantezzen/vowserdb
 *
 * TODO:
 * Add function to add/remove columns
 * Caching system
 */

class vowserdb
{
    /*
   * Configuration
   * Edit these settings to your needs
   */
  public static $folder = 'vowserdb/';     // Change the folder, where the tables will be saved to (notice the leading "/")
  public static $dobackup = true;    // Do a backup of every table before editing it (e.g. UPDATE, ADD_COLUMN, etc.)
  public static $disablelock = false; // Disable the table lock*
  public static $respectrelationshipsrelationship = false; // Should relationships on the relationship table be repected?
  public static $encrypt = true; // Encrypt the tables
  public static $file_encryption_blocks = 10000;

  /*
   * Do not edit the constants below
   */
  const NEWLINE = '
';
  const RELATIONSHIPTABLE = "vowserdb-table-relationships";

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
      if (self::$encrypt && !defined('VOWSERDBENCRKEY')) {
          $error[] = 'Encryption is enabled but no custom encryption key is defined. Please define(\'VOWSERDBENCRKEY\') with a custom AES-128-CBC encryption key.';
      }

      return $error;
  }

   /**
    * Create a new vowserdb table.
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
       $file = fopen($folder.$name.'.vowserdb', 'w');
       fwrite($file, $content);
       fclose($file);
       self::removelock($name);
   }
   /**
    * Escape a string to remove forbidden characters.
    *
    * @param string
    *
    * @return Escaped String
    */
   public static function ESCAPE($string)
   {
       $forbidden = array(
       ';;',
       '
',
     );
       $string = str_replace($forbidden, '', $string);

       return $string;
   }

    /**
     * Insert data into a table.
     *
     * @param array of data to Insert
     * @param table to insert it to
     */
    public static function INSERT($data, $table)
    {
        self::checklock($table);
        self::setlock($table);
        $path = self::$folder.$table.'.vowserdb';
        $columns = self::GET_COLUMNS($table);
        $content = self::NEWLINE;
        foreach ($columns as $column) {
            if (isset($data[$column])) {
                $content .= self::ESCAPE($data[$column]).';;';
            } else {
                $content .= ';;';
            }
        }
        $file = fopen($path, 'a');
        fwrite($file, $content);
        self::removelock($table);
    }

    /**
     * Get the name of the columns of a table.
     *
     * @param Name of the table
     *
     * @return array with names of the columns
     */
    public static function GET_COLUMNS($table)
    {
        $path = self::$folder.$table.'.vowserdb';
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
     * Select data from a table.
     *
     * @param Name of the table
     * @param array of the requirements of the selections
     *
     * @return array with the selected rows
     */
    public static function SELECT($table, $requirements = array(), $ignorerelationships = false)
    {
        $path = self::$folder.$table.'.vowserdb';
        self::checklock($table);
        self::setlock($table);
        $columns = self::GET_COLUMNS($table);
        $content = file_get_contents($path);
        self::removelock($table);
        $items = explode(self::NEWLINE, $content);
        $items[0] = '';
        $array = array();
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
          if ($table !== self::RELATIONSHIPTABLE && $ignorerelationships !== true) {
            $relationships = self::getrelationships($table);
            if (!empty($relationships)) {
              foreach($relationships as $relationship) {
                $row = $relationship["row1"];
                $row2 = $relationship["row2"];
                $table2 = $relationship["table2"];
                foreach($array as $id => $entry) {
                  if (!is_array($array[$id][$row])) {
                    $array[$id][$row] = self::SELECT($table2, array($row2 => $array[$id][$row]), !self::$respectrelationshipsrelationship);
                  }
                }
              }
            }
          }
          return $array;
        }
        $select = array();
        $counter = 0;
        foreach ($requirements as $column => $value) {
            $value = self::ESCAPE($value);
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
                    } elseif ($mode == 'like') {
                        if (isset($row[$column]) && stristr($row[$column], (string) $value)) {
                            $select[] = $row;
                        }
                    } elseif ($mode == 'match') {
                        if (isset($row[$column]) && preg_match($value, $row[$column])) {
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
                        if (isset($row[$column]) && $row[$column] <= $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'smaller') {
                        if (isset($row[$column]) && $row[$column] >= $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'biggerequal') {
                        if (isset($row[$column]) && $row[$column] < $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'smallerequal') {
                        if (isset($row[$column]) && $row[$column] > $value) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'like') {
                        if (isset($row[$column]) && !stristr($row[$column], (string) $value)) {
                            unset($select[$key]);
                        }
                    } elseif ($mode == 'match') {
                        if (isset($row[$column]) && !preg_match($value, $row[$column])) {
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

        if ($table !== self::RELATIONSHIPTABLE && $ignorerelationships !== true) {
          $relationships = self::getrelationships($table);
          if (!empty($relationships)) {
            foreach($relationships as $relationship) {
              $row = $relationship["row1"];
              $row2 = $relationship["row2"];
              $table2 = $relationship["table2"];
              foreach($select as $id => $entry) {
                if (!is_array($array[$id][$row])) {
                  $select[$id][$row] = self::SELECT($table2, array($row2 => $select[$id][$row]), !self::$respectrelationshipsrelationship);
                }
              }
            }
          }
        }
        return $select;
    }

    /**
     * Update data in the table.
     *
     * @param Name of the table
     * @param array of data to Insert
     * @param Requirements of the row selections
     */
    public static function UPDATE($table, $data, $where = array())
    {
        self::checklock($table);
        self::setlock($table);
        $rows = self::SELECT($table, $where, true);
        $path = self::$folder.$table.'.vowserdb';
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
      * Rename a column in a table.
      *
      * @param Name of the table
      * @param Old name of the column
      * @param New name of the column
      *
      * @return true/false
      */
     public static function RENAME($table, $oldname, $newname)
     {
         self::checklock($table);
         self::setlock($table);
         $path = self::$folder.$table.'.vowserdb';
         if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
             self::removelock($table);

             return false;
         }
         $f = fopen($path, 'r');
         $line = fgets($f);
         fclose($f);
         if (strpos($line, ';;'.$oldname.';;') !== false) {
             $line = str_replace(';;'.$oldname.';;', ';;'.$newname.';;', $line);
         } elseif (strpos($line, ';;'.$oldname) !== false) {
             $line = str_replace(';;'.$oldname, ';;'.$newname, $line);
         } elseif (strpos($line, $oldname.';;') !== false) {
             $line = str_replace($oldname.';;', $newname.';;', $line);
         } else {
             self::removelock($table);

             return false;
         }

         $content = file_get_contents($path);
         $content = explode(self::NEWLINE, $content);
         $content[0] = $line;
         $content = implode(self::NEWLINE, $content);

         $file = fopen($path, 'w');
         fwrite($file, $content);
         fclose($file);

         self::removelock($table);

         return true;
     }

     /**
      * Add a column to a table.
      *
      * @param Name of the table
      * @param Name of the new column
      * @param Value of the column in all existing rows (optinal)
      */
     public static function ADD_COLUMN($table, $column, $value = '')
     {
         self::checklock($table);
         self::setlock($table);
         $path = self::$folder.$table.'.vowserdb';
         if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
             self::removelock($table);

             return false;
         }
         $f = fopen($path, 'r');
         $line = fgets($f);
         fclose($f);
         $line = str_replace(self::NEWLINE, '', $line);
         $line .= $column.';;';
         $content = file_get_contents($path);
         $content = explode(self::NEWLINE, $content);
         foreach ($content as $key => $l) {
             if ($l !== '') {
                 if ($key == 0) {
                     $content[$key] = $line;
                 } else {
                     $content[$key] .= $value.';;';
                 }
             }
         }
         $content = implode(self::NEWLINE, $content);

         $file = fopen($path, 'w');
         fwrite($file, $content);
         fclose($file);

         self::removelock($table);
     }
    public static function REMOVE_COLUMN($table, $column)
    {
        self::checklock($table);
        self::setlock($table);
        $path = self::$folder.$table.'.vowserdb';
        if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
            self::removelock($table);

            return false;
        }
        $f = fopen($path, 'r');
        $line = fgets($f);
        fclose($f);
        $line = str_replace(self::NEWLINE, '', $line);
        $columns = explode(';;', $line);
        foreach ($columns as $key => $c) {
            if ($column == $c) {
                $k = $key;
                break;
            }
        }
        if (!isset($k)) {
            self::removelock($table);

            return false;
        }
        $content = file_get_contents($path);
        $content = explode(self::NEWLINE, $content);
        foreach ($content as $key => $line) {
            $array = explode(';;', $line);
            unset($array[$k]);
            $line = implode(';;', $array);
            $content[$key] = $line;
        }
        $content = implode(self::NEWLINE, $content);

        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);

        self::removelock($table);

        return true;
    }

    /**
     * Delete data from the table.
     *
     * @param Name of the table
     * @param Requirements of the row selection
     */
    public static function DELETE($table, $where = array())
    {
        self::checklock($table);
        self::setlock($table);
        $rows = self::SELECT($table, $where, true);
        $path = self::$folder.$table.'.vowserdb';
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
     * Truncate a table.
     *
     * @param Name of the table
     */
    public static function TRUNCATE($table)
    {
        //Alias for DELETE *
     self::DELETE($table);
    }

    /**
     * Delete empty lines in the table file.
     *
     * @param Name of the table
     */
    public static function CLEAR($table)
    {
        self::checklock($table);
        self::setlock($table);
        $path = self::$folder.$table.'.vowserdb';
        $content = file_get_contents($path);
        $rows = explode(self::NEWLINE, $content);
        $newcontent = '';
        foreach ($rows as $key => $row) {
            if (!empty($row) && $row !== ' ') {
                $newcontent .= $row.self::NEWLINE;
            }
        }
        $file = fopen($path, 'w');
        fwrite($file, $newcontent);
        fclose($file);
        self::removelock($table);
    }

    /**
     * Drop/delete a table.
     *
     * @param Name of the table
     */
    public static function DROP($table)
    {
        self::checklock($table);
        self::setlock($table);
        if (file_exists(self::$folder.$table.'.vowserdb')) unlink(self::$folder.$table.'.vowserdb');
        if (file_exists(self::$folder.$table.'.encrypt.vowserdb')) unlink(self::$folder.$table.'.encrypt.vowserdb');
        if (file_exists(self::$folder.$table.'.backup.vowserdb')) unlink(self::$folder.$table.'.backup.vowserdb');
        if (file_exists(self::$folder.$table.'.backup.enrypt.vowserdb')) unlink(self::$folder.$table.'.backup.enrypt.vowserdb');
        self::removelock($table);
    }

    /**
     * Get a list of tables in the database.
     *
     * @return array with the names of all tables
     */
    public static function TABLES()
    {
        $tables = array();
        foreach (glob(self::$folder.'*.vowserdb') as $table) {
          if (!preg_match("/.backup.vowserdb$/", $table) && !preg_match("/.encrypt.vowserdb$/", $table) && !preg_match("/^vowserdb-/", $table)) {
            $tables[] = str_replace(array(self::$folder, '.vowserdb'), '', $table);
          }
        }

        return $tables;
    }

    /**
     * Restore the backup of a table (as long as it exists).
     *
     * @param Name of the table
     */
    public static function RESTORE_BACKUP($table)
    {
        $backupfile = self::$folder.$table.'.backup.vowserdb';
        $tablefile = self::$folder.$table.'.vowserdb';
        if (file_exists($backupfile)) {
            if (file_exists($tablefile)) {
                rename($tablefile, $tablefile.'.moving');
            }
            rename($backupfile, $tablefile);
            if (file_exists($tablefile.'.moving')) {
                rename($tablefile.'.moving', $backupfile);
            }

            return true;
        }

        return false;
    }

   /*
    * MySQL Table Migrating System.
    */
   /**
    * Migrate a MySQL table to vowserdb.
    *
    * @param Host of the MySQL Server
    * @param Username of the MySQL Server
    * @param Password of the MySQL Server
    * @param MySQL Database name
    * @param MySQL table name
    * @param MySQL WHERE statement
    *
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
     * Migrate a MySQL database to vowserdb.
     *
     * @param Host of the MySQL Server
     * @param Username of the MySQL Server
     * @param Password of the MySQL Server
     * @param MySQL Database name
     *
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
   * Relationships
   */

   public static function relationship($table1, $row1, $table2, $row2) {
     if (!file_exists(self::$folder.self::RELATIONSHIPTABLE.'.vowserdb')) {
       self::CREATE(self::RELATIONSHIPTABLE, array("table1", "row1", "table2", "row2"));
     }
     if (!empty(self::SELECT(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2)))) {
       return array("error" => "Relationship already exists");
     } else {
       self::INSERT(array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2), self::RELATIONSHIPTABLE);
       return true;
     }
   }
   public static function destroyrelationship($table1, $row1, $table2, $row2) {
     if (!file_exists(self::$folder.self::RELATIONSHIPTABLE.'.vowserdb')) {
       return array("error" => "Relationship not found");
     }
     if (empty(self::SELECT(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2)))) {
       return array("error" => "Relationship not found");
     } else {
       self::DELETE(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2));
       return true;
     }
   }
   private static function getrelationships($table) {
     if (!file_exists(self::$folder.self::RELATIONSHIPTABLE.'.vowserdb')) {
       return array();
     }
     return self::SELECT(self::RELATIONSHIPTABLE, array("table1" => $table));
   }

  /*
   * INTERNAL FUNCTIONS
   */
   /*
    * Lock mechanism.
    */
   /**
    * Set a lock for a table.
    *
    * @param Table name
    *
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
       if (self::$encrypt) {
         self::decrypt($table);
         self::decryptbackup($table);
       }
       if (self::$dobackup == true) {
           if (file_exists(self::$folder.$table.'.backup.vowserdb')) {
               unlink(self::$folder.$table.'.backup.vowserdb');
           }
           if (file_exists(self::$folder.$table.'.vowserdb')) {
             copy(self::$folder.$table.'.vowserdb', self::$folder.$table.'.backup.vowserdb');
           }
       }

       return true;
   }

    /**
     * Remove the lock of a database.
     *
     * @param Name of the table
     *
     * @return true
     */
    private static function removelock($table)
    {
        if (self::$disablelock == false) {
            $path = self::$folder.$table.'.lock';
            if (file_exists($path)) unlink($path);
        }
        if (self::$encrypt) {
          self::encrypt($table);
          self::encryptbackup($table);
        }

        return true;
    }

    /**
     * Wait for a table to get unlocked.
     *
     * @param Name of the table
     *
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

    /*
     * Encryption/Decryption of tables
     */
     public static function decryptall()
     {
       foreach(self::TABLES() as $table) {
         self::decrypt($table);
         self::decryptbackup($table);
       }
     }

     private static function encrypt($table)
     {
       $encryptfile = self::$folder.$table.'.encrypt.vowserdb';
       $originalfile = self::$folder.$table.'.vowserdb';
       $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
       if (!file_exists($originalfile)) {
         return false;
       }
       if (file_get_contents($originalfile) == "encr") {
         return array("error" => "Already encrypted");
       }
       self::encryptFile($originalfile, $key, $encryptfile);
       $f = fopen($originalfile, 'w');
       fwrite($f, 'encr');
       fclose($f);
     }

     private static function encryptbackup($table)
     {
       $encryptfile = self::$folder.$table.'.backup.encrypt.vowserdb';
       $originalfile = self::$folder.$table.'.backup.vowserdb';
       $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
       if (!file_exists($originalfile)) {
         return false;
       }
       if (file_get_contents($originalfile) == "encr") {
         return array("error" => "Already encrypted");
       }
       self::encryptFile($originalfile, $key, $encryptfile);
       $f = fopen($originalfile, 'w');
       fwrite($f, 'encr');
       fclose($f);
     }

     private static function decrypt($table)
     {
       $encryptfile = self::$folder.$table.'.encrypt.vowserdb';
       $originalfile = self::$folder.$table.'.vowserdb';
       $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
       if (!file_exists($encryptfile)) {
         return array("error" => "Already decrypted");
       }
       self::decryptFile($encryptfile, $key, $originalfile);
       unlink($encryptfile);
     }

     private static function decryptbackup($table)
     {
       $encryptfile = self::$folder.$table.'.backup.encrypt.vowserdb';
       $originalfile = self::$folder.$table.'.backup.vowserdb';
       $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
       if (!file_exists($encryptfile)) {
         return array("error" => "Already decrypted");
       }
       self::decryptFile($encryptfile, $key, $originalfile);
       unlink($encryptfile);
     }

     private static function encryptFile($source, $key, $dest)
     {
         $key = substr(sha1($key, true), 0, 16);
         $iv = openssl_random_pseudo_bytes(16);

         $error = false;
         if ($fpOut = fopen($dest, 'w')) {
             // Put the initialzation vector to the beginning of the file
             fwrite($fpOut, $iv);
             if ($fpIn = fopen($source, 'rb')) {
                 while (!feof($fpIn)) {
                     $plaintext = fread($fpIn, 16 * 10000);
                     $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                     // Use the first 16 bytes of the ciphertext as the next initialization vector
                     $iv = substr($ciphertext, 0, 16);
                     fwrite($fpOut, $ciphertext);
                 }
                 fclose($fpIn);
             } else {
                 $error = true;
             }
             fclose($fpOut);
         } else {
             $error = true;
         }

         return $error ? false : $dest;
     }
     private static function decryptFile($source, $key, $dest)
     {
         $key = substr(sha1($key, true), 0, 16);

         $error = false;
         if ($fpOut = fopen($dest, 'w')) {
             if ($fpIn = fopen($source, 'rb')) {
                 // Get the initialzation vector from the beginning of the file
                 $iv = fread($fpIn, 16);
                 while (!feof($fpIn)) {
                     $ciphertext = fread($fpIn, 16 * (10000 + 1)); // we have to read one block more for decrypting than for encrypting
                     $plaintext = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                     // Use the first 16 bytes of the ciphertext as the next initialization vector
                     $iv = substr($ciphertext, 0, 16);
                     fwrite($fpOut, $plaintext);
                 }
                 fclose($fpIn);
             } else {
                 $error = true;
             }
             fclose($fpOut);
         } else {
             $error = true;
         }

         return $error ? false : $dest;
     }
}
