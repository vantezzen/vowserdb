<?php
// This file will check all important functions of vowserDB to see if everything still works
error_reporting(E_ALL);
echo "testing vowserdb...";
include("vowserdb.php");
echo "<br />Encryption is ";
echo vowserdb::$encrypt ? 'activated' : 'deactivated';
echo "<br />Backups are ";
echo vowserdb::$dobackup ? 'activated' : 'deactivated';
echo "<br />The lock mechanism is ";
echo vowserdb::$disablelock ? 'deactivated' : 'activated';
echo "<br />vowserdb check: ";
print_r(vowserdb::check());
vowserdb::CREATE("testing", array("0", "1", "2", "3", "4", "5", "6", "7", "8", "9"));
vowserdb::INSERT("testing", array("1" => "select me pls", "3" => "Heyy", "4" => "ahsdkahsdgasdagsjdgajsd", "8" => "a", "9" => "xxxx"));
echo "<br />SELECT test: ";
if (json_encode(vowserdb::SELECT("testing", array("1" => "select me pls"))) == '[["","select me pls","","Heyy","ahsdkahsdgasdagsjdgajsd","","","","a","xxxx"]]') {
    echo "PASSED";
} else {
    echo "Not as expected: ";
    print_r(vowserdb::SELECT("testing", array("1" => "select me pls")));
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
if (json_encode(vowserdb::SELECT("table1")) == '[{"user":[{"username":"vantezzen","password":"1234","mail":"mail@example.com"}],"text":"This is a test message","addedOn":"Today"}]') {
    echo "PASSED";
} else {
    echo "Not as expected: ";
    print_r(vowserdb::SELECT("table1"));
}
vowserdb::TABLES();
vowserdb::DROP("table1");
vowserdb::DROP("table2");
echo "<br />testing done";
