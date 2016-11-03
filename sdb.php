<?php
/* SDB - Simple Database
 * by vantezzen (http://vantezzen.de)
 *
 * For documentation check http://github.com/vatezzen/sdb2
 */

class sdb {
  public static $folder = "sdb/";

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
     $folder = self::$folder;
     $content = "";
     foreach($columns as $column) {
       $content .= $column . ";;";
     }
     $file = fopen($folder . $name . ".sdb", "w");
     fwrite($file, $content);
     fclose($file);
     return true;
   }

   public static function INSERT($data, $database) {
     $path = self::$folder . $database . ".sdb";
     $columns = self::GET_COLUMNS($database);
     $content = "
";
     foreach($columns as $column) {
       if (isset($data[$column])) {
         $content .= $data[$column] . ";;";
       } else {
         $content .= ";;";
       }
     }
     $file = fopen($path, 'a');
     fwrite($file, $content);
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
       if ($counter == 0) {
         foreach($array as $row) {
           if (isset($row[$column]) && $row[$column] == $value) {
             $select[] = $row;
           }
         }
       } else {
         foreach($select as $key => $row) {
           if ($row[$column] !== $value) {
             unset($select[$key]);
           }
         }
       }
       $counter++;
     }
     return $select;
   }

   public static function UPDATE($database, $data, $where = array()) {
     $rows = self::SELECT($database, $where);
     $path = self::$folder . $database . ".sdb";
     $content = file_get_contents($path);
     foreach($rows as $row) {
       $oldrow = "";
       $newrow = "";
       foreach($row as $column => $value) {
         $oldrow .= $value . ";;";
         if (isset($data[$column])) {
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
   }
   public static function DELETE($database, $where = array()) {
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
     self::CLEAR($database);
   }

   public static function TRUNCATE($database) {
     //Alias for DELETE *
     self::DELETE($database);
   }

   public static function CLEAR($database) {
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
   }

   public static function DROP($database) {
     $path = self::$folder . $database . ".sdb";
     unlink($path);
   }

   public static function TABLES() {
     $tables = array();
     foreach(glob(self::$folder . "*.sdb") as $table) {
       $tables[] = str_replace(array(self::$folder, ".sdb"), "", $table);
     }
     return $tables;
   }

   /**
    * Lock mechanism
    *
    * Not yet implemented: Locks a table for the time a script writes to it.
    * This should avoid data loss when two scripts try to update the table
    * at the same time.
    *
    */
   private static function setlock($database) {
     $path = self::$folder . $database . ".lock";
     $file = fopen($path, "w");
     fwrite($file, "LOCKED");
     fclose($file);
   }

   private static function removelock($database) {
     $path = self::$folder . $database . ".lock";
     unlink($path);
   }

   private static function checklock($database) {
     $lockfile = self::$folder . $database . ".lock";
     $i = 0
     while(file_exists($lockfile) && $i < 1000) {
       usleep(100);
       $i++;
     }
     return true;
   }
}

?>
