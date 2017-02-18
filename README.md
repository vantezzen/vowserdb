# vowserdb (former sdb)
vowserdb is a simple database, that is purely written in PHP and doesn't need any additional services, programs or deamons running to work. It uses .vowserdb files to store tables.

# installation
You can install vowserdb manually or with composer.<br />
## install manually
Move the "vowserdb.php" script to your project folder and include it via<br />
```PHP
include("vowserdb.php");
```
<br />
## install with composer
Simply run
<br />
```Bash
composer require "vowserdb/vowserdb":"*"
```
<br />
or add ```vowserdb/vowserdb``` to your requirements.
<br />
To include vowserdb, use the autoloader and use ```vowserdb\vowserdb```
<br />
```PHP
require __DIR__ . '/vendor/autoload.php';
use vowserdb\vowserdb;
```
<br />
<br />
## after installation
Create a new folder called "vowserdb" (the name of the folder can be changed in vowserdb.php) and give PHP/www-data enought file permissions to read and write to this folder.<br />
You can also create a .htaccess in that folder to deny all requests to the database directly.

# usage
vowserdb uses SQL like commands to manage databases and tables.<br />
Before you start working with vowserdb, please let vowserdb check it's requirements by running
```PHP
vowserdb::check();
```
This will check if the database folder (normally "vowserdb/") exists, if vowserdb can read and write to it, if the ".htaccess" file exists and has the right content ("deny from all"). This function will return an array with all found errors. Please fix them before continuing, otherwise vowserdb will will probably won't work correctly or your tables will be visible to everybody.
<br />
To create a new table with the name "test" and the columns "username", "password" and "mail", run<br />
```PHP
vowserdb::CREATE("test", array("username", "password", "mail"));
```
<br />
You can later add columns to a table with
```PHP
vowserdb::ADD_COLUMN("test", "myNewColumn");
```
or remove columns with
<br />
```PHP
vowserdb::REMOVE_COLUMN("test", "myNewColumn");
```
<br />
<br />
Data can be inserted to the table via,
```PHP
vowserdb::INSERT(array("username" => "vantezzen", "password" => "1234"), "test");
```
<br />
Note, that the column "mail" is left empty in this example. It will just be an empty value in the table.
<br />
To get data from the database, use
```PHP
vowserdb::SELECT("test", array("username" => "vantezzen"));
```
<br />
The second argument can be left empty to get all columns.
<br />
Whenever you want to select something (not only in ```SELECT```, but also in ```UPDATE``` or ```DELETE```) you can use ```SMALLER THAN```, ```BIGGER THAN```, ```SMALLER EQUAL``` and ```BIGGER EQUAL``` to compare numbers
```PHP
vowserdb::SELECT("test", array("timestamp" => "SMALLER THAN ".time()));
```
<br />
,```IS NOT``` to check if a column has not a specific value
```PHP
vowserdb::SELECT("test", array("is_admin" => "IS NOT yes"));
```
<br />
or ```LIKE``` to search for similarities (this uses PHP's ```stristr()``` function)
<br />
```PHP
vowserdb::SELECT("test", array("name" => "LIKE te"));
```
<br />
To update/change data in the database, use
```PHP
vowserdb::UPDATE("test", array("password" => "123456"), array("username" => "vantezzen"));
```
The second argument is the new data that will be inserted (in this case "```password```" will be set to "```123456```"). The third argument is the ```SELECT``` argument.
Again, the third argument can be left empty to change all columns.
<br />
If you ```UPDATE``` columns, you can also use the arguments ```INCREASE BY```, ```DECREASE BY```, ```MULTIPY BY``` and ```DIVIDE BY``` to calculate the the value of the column
```PHP
vowserdb::UPDATE("test", array("clicks" => "INCREASE BY 10"));
```
<br />
To change the name of a column use (```($table, $oldname, $newname)```)
```PHP
vowserdb::RENAME("test", "myoldcolum", "mynewcolumn");
```
<br />
To delete data from the database, use
```PHP
vowserdb::DELETE("test", array("username" => "vantezzen"));
```
The second argument can be left empty to delete everything (similar to SQL's "TRUNCATE").
<br />
Optionally, you can also use the following command to get the same effect as ```DELETE``` with an empty second argument:<br />
```PHP
vowserdb::TRUNCATE("test");
```
<br />
A command that doesn't exist in SQL, but does in vowserdb is ```CLEAR```. ```CLEAR``` deletes all empty lines in the database file to make it prettier and smaller. ```CLEAR``` is automatically run when you ```DELETE``` something.
```PHP
vowserdb::CLEAR("test");
```
<br />
If you don't want your database anymore, throw it away using<br />
```PHP
vowserdb::DROP("test");
```
and your database is gone (don't use this command if you still use your database though).
<br />
If you want to check, which tables exist, use
```PHP
vowserdb::TABLES();
```
This function will just return an array of all your tables in your database.
<br />
<br />
# migrate MySQL table
<br />
If you want to migrate your current MySQL table to a vowserdb table, you can use the ```MIGRATE``` function. ```MIGRATE``` takes the following arguments: ```($host, $username, $password, $database, $table, $where = "1")```. You can use the ```$where``` argument like a normal SQL ```WHERE``` statement or leave it empty("1") to migrate all entries in the table.
<br />
```PHP
vowserdb::MIGRATE("localhost", "database-user", "password123", "test-database", "my-test-table", "`isadmin` = '0'");
```
<br />
If you want to migrate a whole MySQL Database at once (with all it's tables), you can use ```MIGRATE_DB```. ```MIGRATE_DB``` uses almost the same arguments as ```MIGRATE```: ```($host, $username, $password, $database)```
<br />
```PHP
vowserdb::MIGRATE_DB("localhost", "database-user", "password123, "test-database");
```
<br />
# backup
<br />
By default, no backups will be made, but you can easily activate auto-backups of your vowserdb tables. <br />
To activate backups, change the config variable ```$dobackup``` in the top of vowserdb.php to ```true```. A backup of your current table will be made before every change (e.g. ```UPDATE``` or ```DELETE```) and can be restored later with
```PHP
vowserdb::RESTORE_BACKUP("test");
```
The current ("updated") version of the table will then be saved to the backup file, so you can redo your changed with the same function if you want.
<br />
<br />
<br />
# escaping
<br />
Like MySQL, vowserdb has 'forbidden characters', that should not be used in the table. In vowserdb, these are the double-semicolon (';;') and a new line ('<br />
'). Whenever something gets ```INSERT```-ed into a table, all inputted strings will be ```ESCAPE```-d, to remove these characters. If you want to avoid loosing these 'forbidden characters' from your strings, you can, for example, base64 encode the string before ```INSERT```-ing it to the table.
<br />
# lock mechanism
<br />
vowserdb uses a special lock mechanism. This lock mechanism will protect a table when a script writes to it. This can prevent data loss when two scripts try to write to the same table at the same time. It will temporarely create a .lock file named after the table name. If you see this .lock file comming up when a script writes to a table this is normal and it should be automatically deleted after that. If you want to disable this mechanism (e.g. when you want to reduce read and writes to your hard drive), you can turn it off in the vowserdb.php file by changing the "$disablelock" variable in the config section on the top of the file to "true"
<br />
<br />
# share some love
If you like vowserdb, consider starring this repository and telling your friends and family about how awesome vowserdb is (yay!).
You can also check out my other repositories on GitHub, maybe you'll find some other things, that might interest you.
<br />
<br />
vantezzen
