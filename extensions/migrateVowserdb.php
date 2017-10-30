<?php
// This function is used to update old .vowserdb tables from vowserDB 2.X.X
class migrateVowserdb extends vowserdb {
  public static function migrate($table) {
    $file = vowserdb::$folder.$table.'.vowserdb';
    if (!file_exists($file)) {
      echo('Unknown table ' . $file);
      return false;
    }
    $migrated = vowserdb::$folder.$table.vowserdb::$file_extension;
    if (file_exists($migrated)) {
      echo('Migrated table '.$migrated.' already exists');
      return false;
    }
    $f = fopen($migrated, 'w');
    $original = fopen($file, 'r');
    while (($line = fgets($original)) !== false) {
      $data = explode(';;', $line);
      array_pop($data);
      fputcsv($f, $data);
    }
    fclose($f);
    fclose($original);
    return true;
  }

  public static function migrateAll() {
    $folder = self::$folder;
    foreach (glob(self::$folder.'*.vowserdb') as $table) {
      $tablename = str_replace(array('.vowserdb', 'vowserdb/'), '', $table);
      self::migrate($tablename);
    }
  }

}
 ?>
