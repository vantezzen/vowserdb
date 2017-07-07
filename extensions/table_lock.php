<?php
class table_lock extends vowserdb
{
    // Init
  public static function init()
  {
      vowserdb::listen('onTableAccessEnd', function ($table) {
          $path = vowserdb::$folder.$table.'.lock';
          unlink($path);
      });
      vowserdb::listen('onTableAccessBegin', function ($table) {
          $path = self::$folder.$table.'.lock';
          $file = fopen($path, 'w');
          fwrite($file, 'LOCKED');
          fclose($file);
      });
  }
}
table_lock::init();
