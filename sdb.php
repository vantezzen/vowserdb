<?php
/* SDB - Simple Database - v2.0.0
 * by vantezzen (http://vantezzen.de)
 *
 * For documentation check http://github.com/vatezzen/sdb2
 */

class sdb {
  /*
   * Configuration
   * Edit these settings to your needs
   */
  public static $folder = "sdb/";     // Change the folder, where the tables will be saved to (notice the leading "/")
  public static $disablelock = false; // Disable the table lock*
  /*
   * * Table lock will protect a table when a script writes to it.
   *   This can prevent data loss when two scripts try to write
   *   to the same table at the same time. It will temporarely
   *   create a *.lock file named after the table name.
   */

  /**
   * Check requirements
   *
   * @return Errors
   */
  public static function check() {
    $error = array();
    if (!file_exists(self::$folder) || !is_readable(self::$folder) || !is_writable(self::$folder)) {
      $error[] = self::$folder . " is not readable, writable or does not exist";
    }

    return $error;
  }

  /**
   * Create a new sdb database
   *
   * @return Success
   */
   public static function CREATE($name, $columns) {
     self::checklock($name);
     self::setlock($name);
     $folder = self::$folder;
     $content = "";
     foreach($columns as $column) {
       $content .= $column . ";;";
     }
     $file = fopen($folder . $name . ".sdb", "w");
     fwrite($file, $content);
     fclose($file);
     self::removelock($name);
   }

   public static function INSERT($data, $database) {
     self::checklock($database);
     self::setlock($database);
     $path = self::$folder . $database . ".sdb";
     $columns = self::GET_COLUMNS($database);
     $content = "
";
     foreach($columns as $column) {
       if (isset($data[$column])) {
         $data[$column] = str_replace(";;", "", $data[$column]);
         $content .= $data[$column] . ";;";
       } else {
         $content .= ";;";
       }
     }
     $file = fopen($path, 'a');
     fwrite($file, $content);
     self::removelock($database);
   }

   public static function GET_COLUMNS($database) {
     $path = self::$folder . $database . ".sdb";
     if (!file_exists($path) || !is_readable($path) || !is_writable($path)) {
       return array();
     }
     $f = fopen($path, 'r');
     $line = fgets($f);
     fclose($f);
     $array = explode(";;", $line);
     array_pop($array);
     return $array;
   }

   public static function SELECT($database, $requirements = array()) {
     $path = self::$folder . $database . ".sdb";
     $columns = self::GET_COLUMNS($database);
     $content = file_get_contents($path);
     $items = explode("
", $content);
     $items[0] = "";
     $rows = array();
     foreach($items as $item) {
       if (!empty($item)) {
         $explode = explode(";;", $item);
         array_pop($explode);
         unset($row);
         $row = array();
         foreach($explode as $key => $e) {
           $row[$columns[$key]] = $e;
         }
         $array[] = $row;
       }
     }
     if($requirements == array() || empty($requirements)) {
       return $array;
     }
     $select = array();
     $counter = 0;
     foreach($requirements as $column => $value) {
       if (preg_match("/^BIGGER THAN/", $value)) {
         $mode = "bigger";
         $value = str_replace("BIGGER THAN ", "", $value);
       } else if (preg_match("/^SMALLER THAN/", $value)) {
         $mode = "smaller";
         $value = str_replace("SMALLER THAN ", "", $value);
       } else if (preg_match("/^BIGGER EQUAL/", $value)) {
         $mode = "biggerequal";
         $value = str_replace("BIGGER EQUAL ", "", $value);
       } else if (preg_match("/^SMALLER EQUAL/", $value)) {
         $mode = "smallerequal";
         $value = str_replace("SMALLER EQUAL ", "", $value);
       } else {
         $mode = "normal";
       }
       if ($counter == 0) {
         foreach($array as $row) {
           if ($mode == "normal") {
             if (isset($row[$column]) && $row[$column] == $value) {
               $select[] = $row;
             }
           } else if ($mode == "bigger") {
             if (isset($row[$column]) && $row[$column] > $value) {
               $select[] = $row;
             }
           } else if ($mode == "smaller") {
             if (isset($row[$column]) && $row[$column] < $value) {
               $select[] = $row;
             }
           } else if ($mode == "biggerequal") {
             if (isset($row[$column]) && $row[$column] >= $value) {
               $select[] = $row;
             }
           } else if ($mode == "smallerequal") {
             if (isset($row[$column]) && $row[$column] <= $value) {
               $select[] = $row;
             }
           }
         }
       } else {
         foreach($select as $key => $row) {
           if ($mode == "normal") {
             if (isset($row[$column]) && $row[$column] == $value) {
               unset($select[$key]);
             }
           } else if ($mode == "bigger") {
             if (isset($row[$column]) && $row[$column] > $value) {
               unset($select[$key]);
             }
           } else if ($mode == "smaller") {
             if (isset($row[$column]) && $row[$column] < $value) {
               unset($select[$key]);
             }
           } else if ($mode == "biggerequal") {
             if (isset($row[$column]) && $row[$column] >= $value) {
               unset($select[$key]);
             }
           } else if ($mode == "smallerequal") {
             if (isset($row[$column]) && $row[$column] <= $value) {
               unset($select[$key]);
             }
           }
         }
       }
       $counter++;
     }
     return $select;
   }

   public static function UPDATE($database, $data, $where = array()) {
     self::checklock($database);
     self::setlock($database);
     $rows = self::SELECT($database, $where);
     $path = self::$folder . $database . ".sdb";
     $content = file_get_contents($path);
     foreach($rows as $row) {
       $oldrow = "";
       $newrow = "";
       foreach($row as $column => $value) {
         $oldrow .= $value . ";;";
         if (isset($data[$column])) {
           $data[$column] = str_replace(";;", "", $data[$column]);
           $newrow .= $data[$column] . ";;";
         } else {
           $newrow .= $value . ";;";
         }
       }
       $content = str_replace($oldrow, $newrow, $content, $num);
     }
     $file = fopen($path, "w");
     fwrite($file, $content);
     fclose($file);
     self::removelock($database);
   }
   public static function DELETE($database, $where = array()) {
     self::checklock($database);
     self::setlock($database);
     $rows = self::SELECT($database, $where);
     $path = self::$folder . $database . ".sdb";
     $content = file_get_contents($path);
     foreach($rows as $row) {
       $oldrow = "";
       foreach($row as $column => $value) {
         $oldrow .= $value . ";;";
       }
       $content = str_replace($oldrow, "", $content, $num);
     }
     $file = fopen($path, "w");
     fwrite($file, $content);
     fclose($file);
     self::removelock($database);
     self::CLEAR($database);
   }

   public static function TRUNCATE($database) {
     //Alias for DELETE *
     self::DELETE($database);
   }

   public static function CLEAR($database) {
     self::checklock($database);
     self::setlock($database);
     $path = self::$folder . $database . ".sdb";
     $content = file_get_contents($path);
     $rows = explode("
", $content);
     $newcontent = "";
     foreach($rows as $key => $row) {
       if(!empty($row) && $row !== " ") {
         $newcontent .= $row . "
";
       }
     }
     $file = fopen($path, "w");
     fwrite($file, $newcontent);
     fclose($file);
     self::removelock($database);
   }

   public static function DROP($database) {
     self::checklock($database);
     self::setlock($database);
     $path = self::$folder . $database . ".sdb";
     unlink($path);
     self::removelock($database);
   }

   public static function TABLES() {
     $tables = array();
     foreach(glob(self::$folder . "*.sdb") as $table) {
       $tables[] = str_replace(array(self::$folder, ".sdb"), "", $table);
     }
     return $tables;
   }

   /**
    * MySQL Table Migrating System
    */

   function MIGRATE($host, $username, $password, $database, $table, $where = "1") {
      $db = mysqli_connect($host, $username, $password, $database);
      if (mysqli_connect_errno()) {
        return array("error" => mysqli_connect_error());
      }
      $command = "SELECT * FROM `".$table."` WHERE " . $where;
      $exe = mysqli_query($db, $command);
      if($exe == false) {
        return array("error" => "SELECT failed");
      }
      $columns = "";
      $rows = array();
      while($row = mysqli_fetch_assoc($exe)) {
        if (empty($columns)) {
          $columns = array_keys($row);
        }
        $rows[] = $row;
      }
      sdb::CREATE($table, $columns);
      foreach($rows as $row) {
        sdb::INSERT($row, $table);
      }
   }

   /**
    * Lock mechanism
    */
   private static function setlock($database) {
     if(self::$disablelock == false) {
       $path = self::$folder . $database . ".lock";
       $file = fopen($path, "w");
       fwrite($file, "LOCKED");
       fclose($file);
     }
     return true;
   }

   private static function removelock($database) {
     if(self::$disablelock == false) {
       $path = self::$folder . $database . ".lock";
       unlink($path);
     }
     return true;
   }

   private static function checklock($database) {
     if(self::$disablelock == false) {
       $lockfile = self::$folder . $database . ".lock";
       $i = 0;
       while(file_exists($lockfile) && $i < 1000) {
         usleep(10);
         $i++;
       }
     }
     return true;
   }
}

?>
