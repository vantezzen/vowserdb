![vowserDB logo](https://github.com/vantezzen/vowserDB/blob/master/logo.png?raw=true)
# vowserdb
vowserdb allows you to use csv files as a standalone database with SQL-like commands.
It is written purely in PHP without any frameworks, additional services or deamons.

# installation
You can install vowserdb manually or with composer.
## install manually
Move the "vowserdb.php" script and the "extensions/" folder to your project folder(optinally into a seperate subfolder) and include it via

```PHP
include("vowserdb.php");
```

## install with composer

Simply run

```
composer require "vowserdb/vowserdb":"*"
```

or add `vowserdb/vowserdb` to your requirements.
<br />
To include vowserdb, use the autoloader and use `vowserdb\vowserdb`

```PHP
require __DIR__ . '/vendor/autoload.php';
use vowserdb\vowserdb;
```

## after installation
Create a new folder called "vowserdb" (the name of the folder can be changed in vowserdb.php) and give PHP/www-data enought file permissions to read and write to this folder.<br />
You can also create a .htaccess in that folder to deny all requests to the database directly.

# usage
You can find the full vowserDB documentation at https://vantezzen.github.io/vowserdb-docs/documentation.html

## updating from 2.X.X to 3.X.X
If you want to update from vowserdb 2.X.X to 3.X.X take a look at [the corresponding documentation section](https://vantezzen.github.io/vowserdb-docs/documentation.html#updating)

# share some love
If you like vowserdb, consider starring this repository and telling your friends and family about how awesome vowserdb is (yay!).
You can also check out my other repositories on GitHub, maybe you'll find some other things, that might interest you.
<br />
<br />
vantezzen
