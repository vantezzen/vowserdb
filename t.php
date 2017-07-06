<?php
error_reporting(E_ALL);
include("vowserdb.php");
// vowserdb::GET_COLUMNS('test');

vowserdb::CREATE("test", array("spalte 1", "spalte 2", "spalte 3"));
vowserdb::INSERT("test", array(
  "spalte 3" => "spalte drai",
  "spalte 2" => "data spalte 2"
));

// $columns = array("spalte 1", "spalte 2", "lol");
// $data = array(
//   "spalte 2" => "inhalt für spalte 2",
//   "lol" => "inhalt für spalte loool",
//   "spalte 1" => "inhalt für spalte eins"
// );
//
// $fp = fopen('vowserdb/testcsv.csv', 'w');
// fputcsv($fp, $columns);
// fputcsv($fp, $data);
// fclose($fp);
 ?>
