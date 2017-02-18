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
You can find the full vowserDB documentation at https://vantezzen.github.io/vowserdb-docs/documentation.html

# share some love
If you like vowserdb, consider starring this repository and telling your friends and family about how awesome vowserdb is (yay!).
You can also check out my other repositories on GitHub, maybe you'll find some other things, that might interest you.
<br />
<br />
vantezzen
