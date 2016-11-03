# sdb
sdb is a simple database, that is purely written in PHP and doesn't need any additional services, programs or deamons running to work. It uses .sdb files to store tables.

# installation
Move the "sdb.php" script to your project folder and include it via<br />
```PHP
include("sdb.php");
```
<br />
Create a new folder called "sdb" (the name of the folder can be changed in sdb.php) and give PHP/www-data enought file permissions to read and write to this folder.<br />
You can also create a .htaccess in that folder to deny all requests to the database directly.

# usage
sdb uses SQL like commands to manage databases and tables.<br />
To create a new table with the name "test" and the columns "username", "password" and "mail", run<br />
```PHP
sdb::CREATE("test", array("username", "password", "mail"));
```
<br />
Data can be inserted to the table via,
```PHP
sdb::INSERT(array("username" => "vantezzen", "password" => "1234"), "test");
```
<br />
Note, that the column "mail" is left empty in this example. It will just be an empty value in the table.
<br />
To get data from the database, use
```PHP
sdb::SELECT("test", array("username" => "vantezzen"));
```
<br />
The second argument can be left empty to get all columns.
<br />
Whenever you want to select something (not only in ```SELECT```, but also in ```UPDATE``` or ```DELETE```) you can use ```SMALLER THAN```, ```BIGGER THAN```, ```SMALLER EQUAL``` and ```BIGGER EQUAL``` to compare numbers, e.g.
```PHP
sdb::SELECT("test", array("timestamp" => "BIGGER THAN 100"));
```
or<br />
```PHP
sdb::SELECT("test", array("delete_at" => "SMALLER EQUAL " . time()));
```
<br />
To update/change data in the database, use
```PHP
sdb::UPDATE("test", array("password" => "123456"), array("username" => "vantezzen"));
```
Again, the third argument can be left empty to change all columns.
<br />
To delete data from the database, use
```PHP
sdb::DELETE("test", array("username" => "vantezzen"));
```
The second argument can be left empty to delete everything (similar to SQL's "TRUNCATE").
<br />
Optionally, you can also use the following command to get the same effect as ```DELETE``` with an empty second argument:<br />
```PHP
sdb::TRUNCATE("test");
```
<br />
A command that doesn't exist in SQL, but does in sdb is ```CLEAR```. ```CLEAR``` deletes all empty lines in the database file to make it prettier and smaller. ```CLEAR``` is automatically run when you ```DELETE``` something.
```PHP
sdb::CLEAR("test");
```
<br />
If you don't want your database anymore, throw it away using<br />
```PHP
sdb::DROP("test");
```
and your database is gone (don't use this command if you still use your database though).
<br />
If you want to check, which tables exist, use
```PHP
sdb::TABLES();
```
This function will just return an array of all your tables in your database.
<br />
<br />
# Migrate MySQL table
<br />
If you want to migrate your current MySQL table to a sdb table, you can use the ```MIGRATE``` function. ```MIGRATE``` takes the following arguments: ```($host, $username, $password, $database, $table, $where = "1")```. You can use the ```$where``` argument to like a normal SQL ```WHERE``` statement or leave it empty("1") to migrate all entries in the table.
<br />
```PHP
sdb::MIGRATE("localhost", "database-user", "passwort124", "test-database", "my-test-table", "`isadmin` = '1'");
```
# lock mechanism
<br />
sdb uses a special lock mechanism. This lock mechanism will protect a table when a script writes to it. This can prevent data loss when two scripts try to write to the same table at the same time. It will temporarely create a .lock file named after the table name. If you see this .lock file comming up when a script writes to a table this is normal and it should be automatically deleted after that. If you want to disable this mechanism (e.g. when you want to reduce read and writes to your hard drive), you can turn it off in the sdb.php file by changing the "$disablelock" variable in the config section on the top of the file to "true"
<br />
<br />
# share some love
If you like sdb, consider starring this repository and telling your friends and family about how awesome sdb is (yay!).
You can also check out my other repositories on GitHub, maybe you'll find some other things, that might interest you.
<br />
<br />
vantezzen
