<?php
/* vowserDB -  v4.0.0 Alpha 3
 * by vantezzen (http://vantezzen.de)
 *
 * For documentation check http://github.com/vantezzen/vowserdb
 *
 * TODO:
 */
class vowserdb
{
    /*
   * Configuration
   * Edit these settings to your needs
   */
  public static $folder = 'vowserdb/';     // Change the folder, where the tables will be saved to (notice the leading "/")
  public static $respectrelationshipsrelationship = false; // Should relationships on the relationship table be repected?
  public static $productionmode = false; // Change to true to enable production mode ()
  public static $file_extension = '.csv';
  public static $seperation_char = ',';

  /*
   * Do not edit the constants and variables below
   */
  public static $version = '4.0.0';
  private static $events = []; // Trigger events (used in extensions)
  private static $file_postfixes = array(''); // Possible file postfixes (e.g .encrypt or .backup)
  private static $loaded_extensions =  [];
  private static $uncompatible_extensions = [];
  const NEWLINE = PHP_EOL;
  const RELATIONSHIPTABLE = "vowserdb-table-relationships";

  /**
   * Initiate vowserDB by creating a database
   */
  public static function initiate($disableecho = false)
  {
      $errors = array();
      if (!file_exists(self::$folder)) {
          if (!mkdir('./test')) {
              $error = "We could not find and create the database server (\"" . self::$folder . "\"). Please create it and give PHP/www-data enough file permissions to read and write to it.";
              if (!$disableecho) {
                  echo($error . "<br />");
              }
              $errors[] = $error;
          }
      }
      if (!is_readable(self::$folder)) {
          $error = "The table folder (" . realpath(dirname(__FILE__)).'/'.self::$folder . ") is not readable for PHP. Please give PHP (www-data) enough rights to read the folder.";
          if (!$disableecho) {
              echo($error . "<br />");
          }
          $errors[] = $error;
      }
      if (!is_writable(self::$folder)) {
          $error = "The table folder (" . realpath(dirname(__FILE__)).'/'.self::$folder . ") is not writable for PHP. Please give PHP (www-data) enough rights to write the folder.";
          if (!$disableecho) {
              echo($error . "<br />");
          }
          $errors[] = $error;
      }

      if (!file_exists(self::$folder.'.htaccess')) {
          try {
              $htaccess = fopen(self::$folder.'.htaccess', 'w');
              if (!$htaccess) {
                  throw new Exception('File open failed.');
              }
              fwrite($htaccess, 'deny from all');
              fclose($htaccess);
          } catch (Exception $e) {
              $error = "We have tried to create an .htaccess file in your database folder (" . self::$folder . ") but  the access was denied.";
              if (!$disableecho) {
                  echo($error . "<br />");
              }
              $errors[] = $error;
          }
      }

      if (!file_exists(realpath(dirname(__FILE__)).'/extensions/')) {
          $error = 'The default extensions folder (\'' . realpath(dirname(__FILE__)).'/extensions/' . '\') does not exist. Please copy it from the GitHub repo if you want to use vowserDB\'s extensions.';
          if (!$disableecho) {
              echo($error . "<br />");
          }
          $errors[] = $error;
      }

      self::trigger('onInitDone', $errors);

      if (empty($errors)) {
        if (!$disableecho) {
            echo("Initiated vowserDB sucessfully<br />");
        }
        return true;
      }
      return $errors;
  }

   /**
    * Create a new vowserdb table.
    *
    * @return Success
    */
   public static function CREATE($name, $columns)
   {
       self::beginTableAccess($name);
       if (file_exists(self::get_table_path($name))) {
           return false;
       }

       $file = fopen(self::$folder.$name.self::$file_extension, 'w');
       fputcsv($file, $columns);
       fclose($file);
       self::endTableAccess($name);
       self::trigger('onTableCreate', $name);
       return true;
   }

    /**
     * Insert data into a table.
     *
     * @param array of data to Insert
     * @param table to insert it to
     */
    public static function INSERT($table, $data)
    {
        self::beginTableAccess($table);
        $path = self::get_table_path($table);
        $columns = self::GET_COLUMNS($table);
        $columndata = array();
        foreach ($columns as $column) {
            if (isset($data[$column])) {
                $columndata[] = $data[$column];
            } else {
                $columndata[] = "";
            }
        }
        $file = fopen($path, 'a');
        fwrite($file, self::NEWLINE);
        fputcsv($file, $columndata);
        fclose($file);

        self::trigger('onInsert', array('name' => $table, 'data' => $data));
        self::endTableAccess($table);
    }

    /**
     * Get the name of the columns of a table.
     *
     * @param Name of the table
     *
     * @return array with names of the columns
     */
    public static function GET_COLUMNS($table, $tableaccessinitiated = true)
    {
        if (!$tableaccessinitiated) {
            self::beginTableAccess($table);
        }
        $path = self::get_table_path($table);
        if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
            return array();
        }
        $f = fopen($path, 'r');
        $rows = fgetcsv($f);
        fclose($f);

        if (!$tableaccessinitiated) {
            self::endTableAccess($table);
        }

        return $rows;
    }

    /**
     * Select data from a table.
     *
     * @param Name of the table
     * @param array of the requirements of the selections
     *
     * @return array with the selected rows
     */
    public static function SELECT($table, $requirements = array(), $ignorerelationships = false, $tableaccessinitiated = false)
    {
        if (!$tableaccessinitiated) {
            self::beginTableAccess($table);
        }
        $path = self::get_table_path($table);
        $columns = self::GET_COLUMNS($table);
        $array = self::read_table($table);

        self::trigger('onSelect', array('name' => $table, 'requirements' => $requirements));

        if ($requirements == array() || empty($requirements)) {
            if ($table !== self::RELATIONSHIPTABLE && $ignorerelationships !== true) {
                $relationships = self::getrelationships($table);
                if (!empty($relationships)) {
                    foreach ($relationships as $relationship) {
                        $row = $relationship["row1"];
                        $row2 = $relationship["row2"];
                        $table2 = $relationship["table2"];
                        foreach ($array as $id => $entry) {
                            $array[$id][$row] = self::SELECT($table2, array($row2 => $array[$id][$row]), !self::$respectrelationshipsrelationship);
                        }
                    }
                }
            }
            if (!$tableaccessinitiated) {
                self::endTableAccess($table);
            }
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
                foreach ($relationships as $relationship) {
                    $row = $relationship["row1"];
                    $row2 = $relationship["row2"];
                    $table2 = $relationship["table2"];
                    foreach ($select as $id => $entry) {
                        $select[$id][$row] = self::SELECT($table2, array($row2 => $select[$id][$row]), !self::$respectrelationshipsrelationship);
                    }
                }
            }
        }
        if (!$tableaccessinitiated) {
            self::endTableAccess($table);
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
        self::beginTableAccess($table);
        $rows = self::SELECT($table, $where, true, true);
        $path = self::get_table_path($table);
        $content = file_get_contents($path);
        foreach ($rows as $row) {
            $oldrow = self::str_putcsv($row);
            $newrow = array();
            foreach ($row as $column => $value) {
                if (isset($data[$column])) {
                    $data[$column] = str_replace(self::$seperation_char, '', $data[$column]);
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
                }
                $newrow[] = $value;
            }
            $newrow = self::str_putcsv($newrow);
            $content = str_replace($oldrow, $newrow, $content, $num);
        }
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);

        self::trigger('onUpdate', array('name' => $table, 'data' => $data, 'where' => $where));

        self::endTableAccess($table);
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
         self::beginTableAccess($table);
         $path = self::get_table_path($table);
         $content = explode(self::NEWLINE, file_get_contents($path));

         $columns = str_getcsv($content[0]);

         foreach ($columns as $key => $column) {
             if ($column == $oldname) {
                 $columns[$key] = $newname;
             }
         }

         $content[0] = self::str_putcsv($columns);

         $content = implode(self::NEWLINE, $content);
         $file = fopen($path, 'w');
         fwrite($file, $content);
         fclose($file);

         self::trigger('onRename', array('name' => $table, 'oldname' => $oldname, 'newname' => $newname));

         self::endTableAccess($table);
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
         self::beginTableAccess($table);
         $path = self::get_table_path($table);
         $content = explode(self::NEWLINE, file_get_contents($path));

         // Add column to columns
         $columns = str_getcsv($content[0]);
         $columns[] = $column;
         $content[0] = self::str_putcsv($columns);

         $content = implode(self::NEWLINE, $content);
         $file = fopen($path, 'w');
         fwrite($file, $content);
         fclose($file);

         self::trigger('onColumnAdd', array('name' => $table, 'column' => $column));

         self::endTableAccess($table);
     }
    public static function REMOVE_COLUMN($table, $column)
    {
        self::beginTableAccess($table);
        $path = self::get_table_path($table);
        $content = explode(self::NEWLINE, file_get_contents($path));
        $columns = str_getcsv($content[0]);

        $found = false;

        foreach ($columns as $key => $columnname) {
            if ($columnname == $column) {
                unset($columns[$key]);
            }
        }

        $content[0] = self::str_putcsv($columns);

        $content = implode(self::NEWLINE, $content);
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);

        self::trigger('onColumnRemove', array('name' => $table, 'column' => $column));

        self::endTableAccess($table);

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
        self::beginTableAccess($table);
        $rows = self::SELECT($table, $where, true, true);
        $path = self::get_table_path($table);
        $content = file_get_contents($path);
        foreach ($rows as $row) {
            $oldrow = self::str_putcsv($row);
            $content = str_replace($oldrow, '', $content);
        }
        $file = fopen($path, 'w');
        fwrite($file, $content);
        fclose($file);
        self::trigger('onDelete', array('name' => $table, 'select' => $where));
        self::endTableAccess($table);
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
        self::trigger('onTruncate', $table);
    }

    /**
     * Delete empty lines in the table file.
     *
     * @param Name of the table
     */
    public static function CLEAR($table)
    {
        self::beginTableAccess($table);
        $path = self::get_table_path($table);
        $content = file_get_contents($path);
        $rows = explode(self::NEWLINE, $content);
        $newcontent = '';
        foreach ($rows as $key => $row) {
            if (!empty(trim($row))) {
                $newcontent .= $row.self::NEWLINE;
            }
        }
        $file = fopen($path, 'w');
        fwrite($file, $newcontent);
        fclose($file);
        self::trigger('onClear', $table);
        self::endTableAccess($table);
    }

    /**
     * Drop/delete a table.
     *
     * @param Name of the table
     */
    public static function DROP($table)
    {
        self::beginTableAccess($table);
        foreach (self::$file_postfixes as $postfix) {
            $path = self::get_table_path($table . $postfix);
            if (file_exists($path)) {
                unlink($path);
            }
        }
        self::trigger('onDrop', $table);
        self::endTableAccess($table);
    }

    /**
     * Get a list of tables in the database.
     *
     * @return array with the names of all tables
     */
    public static function TABLES()
    {
        $tables = array();
        foreach (glob(self::$folder.'*'.self::$file_extension) as $table) {
            $tables[] = str_replace(array(self::$folder, self::$file_extension), '', $table);
        }

        return $tables;
    }

  /*
   * Relationships
   */

   public static function relationship($table1, $row1, $table2, $row2)
   {
       if (!file_exists(self::$folder.self::RELATIONSHIPTABLE.self::$file_extension)) {
           self::CREATE(self::RELATIONSHIPTABLE, array("table1", "row1", "table2", "row2"));
       }
       if (!empty(self::SELECT(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2)))) {
           return array("error" => "Relationship already exists");
       } else {
           self::INSERT(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2));
           return true;
       }
   }
    public static function destroyrelationship($table1, $row1, $table2, $row2)
    {
        if ((!file_exists(self::$folder.self::RELATIONSHIPTABLE.self::$file_extension)) || (empty(self::SELECT(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2))))) {
            return array("error" => "Relationship not found");
        } else {
            self::DELETE(self::RELATIONSHIPTABLE, array("table1" => $table1, "row1" => $row1, "table2" => $table2, "row2" => $row2));
            return true;
        }
    }
    private static function getrelationships($table)
    {
        if (!file_exists(self::$folder.self::RELATIONSHIPTABLE.self::$file_extension)) {
            return array();
        }
        return self::SELECT(self::RELATIONSHIPTABLE, array("table1" => $table));
    }

  /*
   * INTERNAL FUNCTIONS
   */
   /*
    * Table access triggers
    */
   /**
    * Execute triggers at the beggining of the table access
    *
    * @param Table name
    *
    * @return true
    */
   private static function beginTableAccess($table)
   {
       self::trigger('beforeTableAccess', $table);
       self::trigger('onTableAccessBegin', $table);

       return true;
   }

    /**
     * Execute triggers at the end of the table access
     *
     * @param Name of the table
     *
     * @return true
     */
    private static function endTableAccess($table)
    {
        self::trigger('onTableAccessEnd', $table);
        self::trigger('afterTableAccess', $table);

        return true;
    }

    private static function read_table($table)
    {
        $path = self::get_table_path($table);
        $columns = self::GET_COLUMNS($table);
        $f = fopen($path, 'r');
        $array = array();
        while (($data = fgetcsv($f)) !== false) {
            if (!empty($data) && array_filter($data, 'trim')) {
                $row = array();
                foreach ($data as $key => $e) {
                    $row[$columns[$key]] = $e;
                }
                $array[] = $row;
            }
        }
        fclose($f);

        array_shift($array);

        self::trigger('onTableRead', $table);

        return $array;
    }

    public static function get_table_path($table)
    {
        return realpath(dirname(__FILE__)).'/'.self::$folder.$table.self::$file_extension;
    }

    // Source: https://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
    public static function in_array_r($needle, $haystack, $strict = false)
    {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::in_array_r($needle, $item, $strict))) {
                return true;
            }
        }

        return false;
    }

    // Source: https://gist.github.com/johanmeiring/2894568
    private static function str_putcsv($input, $delimiter = ',', $enclosure = '"')
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $input, $delimiter, $enclosure);
        rewind($fp);
        $data = fread($fp, 1048576);
        fclose($fp);
        return rtrim($data, "\n");
    }

    // Functions for extensions

    // Source: https://gist.github.com/im4aLL/548c11c56dbc7267a2fe96bda6ed348b
    public static function listen($name, $callback)
    {
        self::$events[$name][] = $callback;
    }
    public static function trigger($name, $param = '')
    {
        if (isset(self::$events[$name]) && !empty(self::$events[$name])) {
            foreach (self::$events[$name] as $event => $callback) {
                call_user_func($callback, $param);
            }
        }
    }

    public static function register_postfix($postfix)
    {
        self::$file_postfixes[] = $postfix;
    }

    public static function get_extension_path($ext, $folder = 'extensions/', $type = 'php')
    {
        return realpath(dirname(__FILE__) . '/' . $folder) . '/' . $ext . '.' . $type;
    }

    public static function load_extension($extension, $folder = 'extensions/')
    {
        if (strpos($extension, '_') !== false) {
            self::handle('vowserDB Deprecated error', 'Please convert extension names to camelCase', E_USER_NOTICE);
            if (file_exists(get_extension_path($extension, $folder))) {
              self::handle('vowserDB Deprecated error', 'Please update your extension folder (' . $folder . ') by using the extension folder from the vowserDB GitHub repository.', E_USER_NOTICE);
            } else {
              $extension = str_replace('_', '', lcfirst(ucwords($extension, '_')));
            }
        }
        if (in_array($extension, self::$loaded_extensions)) {
            self::handle('Info', '"' . $extension . '" is already loaded and won\'t be loaded again.');
            return false;
        }
        if (!file_exists(realpath(dirname(__FILE__)).'/'.$folder)) {
            self::handle('Warning', 'The provided extension folder (\'' . realpath(dirname(__FILE__)).'/'.$folder . '\') does not exist. Please create it or provide the path to another folder.');
            return false;
        }
        if (self::in_array_r($extension, self::$uncompatible_extensions)) {
            $uncompatible_with = '';
            foreach (self::$uncompatible_extensions as $item) {
                if ($item === $extension || (is_array($item) && self::in_array_r($extension, $item))) {
                    $uncompatible_with = $item[0];
                }
            }
            if (!empty($uncompatible_with)) {
                self::handle('Warning', '"' . $uncompatible_with . '" is not compatible with the extension "' . $extension . '" and thus "' . $extension . '" hasn\'t been loaded.');
                return false;
            } else {
                self::handle('Warning', '"' . $extension . '" is not compatible with another loaded extension and thus hasn\'t been loaded.');
                return false;
            }
        }

        if (file_exists(self::get_extension_path($extension, $folder, 'json'))) {
            $conf = json_decode(file_get_contents(self::get_extension_path($extension, $folder, 'json')), true);
            if (isset($conf['uncompatible_with'])) {
                foreach ($conf['uncompatible_with'] as $uncompatible_extension) {
                    if (in_array($uncompatible_extension, self::$loaded_extensions)) {
                        self::handle('Warning', '"' . $extension . '" is not compatible with the extension "' . $uncompatible_extension . '" and thus hasn\'t been loaded.');
                        return false;
                    }
                    self::$uncompatible_extensions[] = array($extension, $uncompatible_extension);
                }
            }
            if (isset($conf['vowserdb_min'])) {
                if (!version_compare(self::$version, $conf['vowserdb_min'], '>=')) {
                    self::handle('Warning', '"' . $extension . '" requires at least vowserDB v' . $conf['vowserdb_min'] . ' to run. The extension won\'t be loaded.');
                    return false;
                }
            }
            if (isset($conf['vowserdb_max'])) {
                if (!version_compare(self::$version, $conf['vowserdb_max'], '<=')) {
                    self::handle('Warning', '"' . $extension . '" won\'t run with vowserDB versions over ' . $conf['vowserdb_max'] . '. The extension won\'t be loaded.');
                    return false;
                }
            }

            if (isset($conf['postfixes'])) {
                foreach ($conf['postfixes'] as $postfix) {
                    self::register_postfix($postfix);
                }
            }
        }

        include(self::get_extension_path($extension, $folder));
        if (method_exists($extension, 'init') && is_callable(array($extension, 'init'))) {
            call_user_func(array($extension, 'init'));
        }
        self::$loaded_extensions[] = $extension;
        return true;
    }

    /*
     * VowserDB error/info handler
     */
    private static function handle($type, $data)
    {
        if (self::$productionmode == true) {
            $text = 'vowserDB '.$type.': ';
            if (is_array($data)) {
                $text .= var_export($data, true);
            } else {
                $text .= $data;
            }
            $f = fopen(self::$folder . 'vowserdb-production.txt', 'a');
            fwrite($f, $text);
            fclose($f);
        } else {
            echo '<br />vowserDB '.$type.': ';
            if (is_array($data)) {
                print_r($data);
            } else {
                echo $data;
            }
            echo '<br />';
        }
    }
}
