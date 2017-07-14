<?php
/* vowserDB -  v3.0.0 Beta 1
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
  public static $file_extension = '.csv';
  public static $seperation_char = ',';
  private static $events = []; // Trigger events (used in extensions)
  private static $file_postfixes = array(''); // Possible file postfixes (e.g .encrypt or .backup)
  private static $loaded_extensions =  [];
  private static $uncompatible_extensions = [];

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
      if (!file_exists(realpath(dirname(__FILE__)).'/extensions/')) {
          $error[] = 'The default extensions folder (\'' . realpath(dirname(__FILE__)).'/extensions/') . '\') does not exist. Please copy it from the GitHub repo.';
      }

      self::trigger('onCheckDone', $error);

      return $error;
  }

   /**
    * Create a new vowserdb table.
    *
    * @return Success
    */
   public static function CREATE($name, $columns)
   {
       self::beforeTableAccess($name);
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
        self::beforeTableAccess($table);
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
    public static function GET_COLUMNS($table)
    {
        $path = self::get_table_path($table);
        if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
            return array();
        }
        $f = fopen($path, 'r');
        $rows = fgetcsv($f);
        fclose($f);

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
            self::beforeTableAccess($table);
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
        self::beforeTableAccess($table);
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
         self::beforeTableAccess($table);
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
         self::beforeTableAccess($table);
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
        self::beforeTableAccess($table);
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
        self::beforeTableAccess($table);
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
        self::beforeTableAccess($table);
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
        self::beforeTableAccess($table);
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

    /**
     * Execute triggers before a table is being accessed.
     *
     * @param Name of the table
     *
     * @return true
     */
    private static function beforeTableAccess($table)
    {
        self::trigger('beforeTableAccess', $table);

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


    public static function load_extension($extension, $folder = 'extensions/')
    {
        if (!file_exists(realpath(dirname(__FILE__)).'/'.$folder)) {
          echo 'The provided extension folder (\'' . realpath(dirname(__FILE__)).'/'.$folder) . '\') does not exist. Please create it or provide the path to another folder.';
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
              echo('<br /><b>"' . $uncompatible_with . '" is not compatible with the extension "' . $extension . '" and thus "' . $extension . '" hasn\'t been loaded.</b><br />');
              return false;
            } else {
              echo('<br /><b>"' . $extension . '" is not compatible with another loaded extension and thus hasn\'t been loaded.</b><br />');
              return false;
            }

        }

        if (file_exists(realpath(dirname(__FILE__)).'/'.$folder.$extension.'.json')) {
            $conf = json_decode(file_get_contents(realpath(dirname(__FILE__)).'/extensions/'.$extension.'.json'), true);
            if (isset($conf['uncompatible_with'])) {
                foreach ($conf['uncompatible_with'] as $uncompatible_extension) {
                    if (in_array($uncompatible_extension, self::$loaded_extensions)) {
                        echo('<br /><b>"' . $extension . '" is not compatible with the extension "' . $uncompatible_extension . '" and thus hasn\'t been loaded.</b><br />');
                        return false;
                    }
                    self::$uncompatible_extensions[] = array($extension, $uncompatible_extension);
                }
            }
            if (isset($conf['postfixes'])) {
              foreach($conf['postfixes'] as $postfix) {
                self::$file_postfixes[] = $postfix;
              }
            }
        }

        include(realpath(dirname(__FILE__)).'/'.$folder.$extension.'.php');
        if (method_exists($extension, 'init') && is_callable(array($extension, 'init'))) {
            call_user_func(array($extension, 'init'));
        }
        self::$loaded_extensions[] = $extension;
        return true;
    }
}
