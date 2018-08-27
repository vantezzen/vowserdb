# vowserDB 4
vowserDB allows you to use csv files as a standalone database for PHP with SQL-like commands.

It is written purely in PHP without any dependencies.

# WIP
This version is still work in progress and shouldn't be used yet as there could be breaking changes in the future.

# Installation
vowserDB can be installed via composer by running
```php
composer require vowserDB
```

# Basic usage
```php
<?php
use vowserDB\Table;

// Use table 'users' with sepecified columns
$table = new Table('users', ['username', 'password', 'mail']);

// Insert new user into table
$table->insert([
    'username' => 'testuser',
    'password' => '1234',
    'mail' => 'mail@example.com'
]);

// Save changes to table file
$table->save();

// Select row from the table and update the password of the selected rows
$table
    ->select(['username' => 'testuser'])
    ->update(['password' => '5678'])
    ->save();

// Get selected rows
$rows = $table->selected();
```

# Documentation
For information on how to use vowserDB please take a look at the [documentation](https://vantezzen.github.io/vowserDB)

# Bugs and feature requests
Bugs and feature request are tracked on [GitHub](https://github.com/vantezzen/vowserDB/issues)

# Licence
vowserDB is licensed under the MIT License - see the `LICENSE` file for details

# Acknowledgements

This library is heavily inspired by Laravels Eloquent syntax.