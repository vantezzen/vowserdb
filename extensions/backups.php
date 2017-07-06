<?php
class backups extends vowserdb {
  // Set triggers
  public static function init()
  {
      vowserdb::listen('onLockSet', function () {
        if (vowserdb::$dobackup == true) {
            if (file_exists(self::$folder.$table.'.backup'.self::$file_extension)) {
                unlink(self::$folder.$table.'.backup'.self::$file_extension);
            }
            if (file_exists(self::$folder.$table.self::$file_extension)) {
                copy(self::$folder.$table.self::$file_extension, self::$folder.$table.'.backup'.self::$file_extension);
            }
        }
      });
  }
  /**
   * Restore the backup of a table (as long as it exists).
   *
   * @param Name of the table
   */
  public static function RESTORE($table)
  {
      $backupfile = self::$folder.$table.'.backup'.self::$file_extension;
      $tablefile = self::$folder.$table.self::$file_extension;
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
}

backups::init();
 ?>
