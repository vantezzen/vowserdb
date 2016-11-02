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
A command that doesn't exist in SQL, but does in sdb is ```CLEAR```. ```CLEAR``` deletes all empty lines in the database file to make it prettier and smaller. ```CLEAR``` is automatically run when you ```DELETE``` something.
```PHP
sdb::CLEAR("test");
```
<br />
If you don't want your database anymore, throw it away using
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
# share some love
If you like sdb, consider starring this repository and telling your friends and family about how awesome sdb is (yay!).
You can also check out my other repositories on GitHub, maybe you'll find some other things, that might interest you.
<br />
<br />
vantezzen
