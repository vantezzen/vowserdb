<?php

$inserted_rows = 10000;

$start = microtime(true);

include("sdb.php");

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to include sdb<br />";

$start = microtime(true);

sdb::CREATE("benchmark", array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9"));

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to create table<br />";

$start = microtime(true);

for($i = 0; $i < ($inserted_rows - 1); $i++) {
  sdb::INSERT(array("1" => "This is a test", "3" => "Heyy", "4" => "ahsdkahsdgasdagsjdgajsd", "8" => "a", "9" => "xxxx"), "benchmark");
}
sdb::INSERT(array("1" => "select me pls", "3" => "Heyy", "4" => "ahsdkahsdgasdagsjdgajsd", "8" => "a", "9" => "xxxx"), "benchmark");

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to create " . ($i + 1) . " rows<br />";

$start = microtime(true);

sdb::SELECT("benchmark", array("1" => "select me pls"));

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to select a row from the table<br />";

$start = microtime(true);

sdb::UPDATE("benchmark", array("1" => "select me pls pls"), array("1" => "select me pls"));

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to update a row from the table<br />";

$start = microtime(true);

sdb::DELETE("benchmark", array("1" => "select me pls pls"));

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to delete a row from the table<br />";

$start = microtime(true);

sdb::RENAME("benchmark", "1", "NEW");

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to rename a row from the table<br />";

$start = microtime(true);

sdb::DROP("benchmark");

$end = microtime(true);

echo "Needed ". round($end-$start,4) . "s to drop table<br />";

 ?>
