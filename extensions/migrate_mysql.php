<?php
class migrate_mysql extends vowserdb {
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
}

?>
