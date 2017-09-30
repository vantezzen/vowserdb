<?php
// This file will check all important functions of vowserDB to see if everything still works
error_reporting(E_ALL);
echo "testing vowserdb...";
$notpassed = false;
include("vowserdb.php");
echo '<br />Include test: ';
if (class_exists('vowserdb')) {
  echo "PASSED";
} else {
  echo "vowserDB could not be included. Please make sure that this file is located in the same folder as vowserdb.php and the extensions/ folder.";
  echo '<br /><br /><b style="color: red;">DID NOT PASS! Please correct the errors!</b>';
  exit();
}
echo "<br />Executing 'check()' function<br />";
vowserdb::check();
echo "<br />Folder: ".vowserdb::$folder;
echo "<br />Path for table 'test': ".vowserdb::get_table_path('test');
echo '<br />Including extentions: <ul>';
$extensions = array('backups', 'encryptTables', 'tableLock');
foreach($extensions as $extension) {
  echo '<br /><li>'.$extension;
  vowserdb::load_extension($extension);
  if (class_exists($extension)) {
    echo ': Included successfully!</li>';
  } else {
    $notpassed = true;
    echo ': Did not include!</li>';
  }
}
echo '</ul>';

vowserdb::CREATE("testing", array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9"));
vowserdb::INSERT("testing", array("1" => "select me pls", "3" => "Heyy", "4" => "ahsdkahsdgasdagsjdgajsd", "8" => "a", "9" => "xxxx"));
echo "<br />SELECT test: ";
$expected = '[["","select me pls","","Heyy","ahsdkahsdgasdagsjdgajsd","","","","a","xxxx"]]';
if (json_encode(vowserdb::SELECT("testing", array("1" => "select me pls"))) == $expected) {
    echo "PASSED";
} else {
    $notpassed = true;
    echo "Not as expected: <br />";
    echo 'Result: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print_r(vowserdb::SELECT("testing", array("1" => "select me pls")));
    echo '<br />Expected: ';
    print_r(json_decode($expected, true));
}
vowserdb::UPDATE("testing", array("1" => "select me pls pls"), array("1" => "select me pls"));
vowserdb::DELETE("testing", array("1" => "select me pls pls"));
vowserdb::RENAME("testing", "1", "NEW");
vowserdb::DROP("testing");
vowserdb::CREATE("table1", array("user", "text", "addedOn"));
vowserdb::CREATE("table2", array("username", "password", "mail"));
vowserdb::relationship("table1", "user", "table2", "username");
vowserdb::INSERT("table1", array("text" => "This is a test message", "addedOn" => "Today", "user" => "vantezzen"));
vowserdb::INSERT("table2", array("username" => "vantezzen", "password" => "1234", "mail" => "mail@example.com"));
echo "<br />Relationship test: ";
$expected = '[{"user":[{"username":"vantezzen","password":"1234","mail":"mail@example.com"}],"text":"This is a test message","addedOn":"Today"}]';
if (json_encode(vowserdb::SELECT("table1")) == $expected) {
    echo "PASSED";
} else {
    $notpassed = true;
    echo "Not as expected: <br />";
    echo 'Result: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print_r(vowserdb::SELECT("table1"));
    echo '<br />Expected: ';
    print_r(json_decode($expected, true));
}
vowserdb::destroyrelationship("table1", "user", "table2", "username");

echo "<br />Extension load test: ";
if (!file_exists(realpath(dirname(__FILE__)).'/extensions/migrateMysql/main.php')) {
  echo 'Could not execute test because the extension "migrateMysql" is not availible';
} else {
  vowserdb::load_extension('migrateMysql');
  if (class_exists("migrateMysql")) {
    echo "PASSED";
  } else {
    echo "Not passed";
    $notpassed = true;
  }
}

echo "<br />Extension trigger test: ";

class testExtension extends vowserdb {
  public static $passed = false;
  public static function init() {
    vowserdb::listen('onCheckDone', function() {
      self::$passed = true;
    });
  }
}
testExtension::init();
vowserdb::check(true);
if (testExtension::$passed) {
  echo 'PASSED';
} else {
  $notpassed = true;
  echo 'Did not trigger event';
}

echo "<br />Column manipulation test: ";
vowserdb::ADD_COLUMN("table1", "testcolumn");
$notpassedcolumntest = false;
$expect = '["user","text","addedOn","testcolumn"]';
if (json_encode(vowserdb::GET_COLUMNS("table1", false)) !== $expect) {
  $notpassedcolumntest = true;
}
vowserdb::RENAME("table1", "testcolumn", "newcolumn");
$expect = '["user","text","addedOn","newcolumn"]';
if (json_encode(vowserdb::GET_COLUMNS("table1", false)) !== $expect) {
  $notpassedcolumntest = true;
}
vowserdb::REMOVE_COLUMN("table1", "newcolumn");
$expect = '["user","text","addedOn"]';
if (json_encode(vowserdb::GET_COLUMNS("table1", false)) !== $expect) {
  $notpassedcolumntest = true;
}
if ($notpassedcolumntest) {
  $notpassed = true;
  echo 'Not passed';
} else {
  echo 'PASSED';
}

echo "<br />Table truncate test: ";
vowserdb::TRUNCATE("table1");
if (json_encode(vowserdb::SELECT("table1")) == "[]") {
  echo "PASSED";
} else {
  echo "Not passed";
  $notpassed = true;
}


if (!$notpassed) {
  echo '<br /><br /><b style="color: green;">The test was completed successfully!</b>';
} else {
  echo '<br /><br /><b style="color: red;">DID NOT PASS! Please correct the errors!</b>';
}

vowserdb::TABLES();
vowserdb::DROP("table1");
vowserdb::DROP("table2");
echo "<br />done";
