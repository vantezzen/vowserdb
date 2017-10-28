![vowserDB logo](https://github.com/vantezzen/vowserDB/blob/master/logo.png?raw=true)
# vowserdb
vowserdb allows you to use csv files as a standalone database with SQL-like commands.
It is written purely in PHP without any frameworks, additional services or deamons.

# Getting started with vowserDB
If you want to get started with vowserDB but you don't want to dig through its documentation right away you can take the vowserDB Getting-Started online course in your browser. [Click here to get to it](https://vantezzen.github.io/vowserdb-tryit/)

# installation and usage
vowserDB can be eather installed manually or using composer.

## install manually
Move the "vowserdb.php" script and the "extensions/" folder to your project folder (optinally into a seperate subfolder) and include it via

```PHP
include("vowserdb.php");
```

## install with composer

Simply run

```
composer require "vowserdb/vowserdb":"*"
```

or add `vowserdb/vowserdb` to your requirements.

To include vowserdb, use the autoloader and `use vowserdb\vowserdb`

```PHP
require __DIR__ . '/vendor/autoload.php';
use vowserdb\vowserdb;
```

You can find the full vowserDB documentation at https://vantezzen.github.io/vowserdb-docs/documentation.html
