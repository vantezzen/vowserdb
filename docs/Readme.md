# Installation
vowserDB can be installed using composer

```bash
composer install vowserdb
```
---------------
# Getting started
The main class for working with vowserDB Tables is the `vowserDB\Table` class.
You should add a `use` to your file so you can work with it easier.
```php
use vowserDB\Table;
```

---------------
# Usage
## Creating and using a table
When accessing a table you don't have to create it first seperately. Rather you should always give vowserDB the information which columns the table should have. If the table already exists it will use it but if it doesn't it will create one with the specified columns.
```php
$table = new Table(
    $tableName
        [, $columns = false
            [, $additionalColumns = false
                [, $config = []
                ]
            ]
        ]
    );
```
Example: Creating and/or using a table with the name 'users' that has the columns 'username', 'password' and 'mail'.
```php
$table = new Table('users', ['username', 'password', 'mail']);
```
Additionally, you can use [table templates](#table-templates) when creating tables. Table templates allows you to use a small name instead of a full array with all columns.
When using table templates you can use the `$additionalColumns` argument to add additional columns to the template.

Example: Creating and/or using a table with the name 'posts' (first argument) using the template 'posts' (second argument) and adding the columns 'updated_date' and 'comments'.
```php
$table = new Table('posts', 'posts', ['updated_date', 'comments']);
```

### Table templates
Availible table templates are:
```php
"users" => array(
    "username",
    "uuid",
    "password",
    "mail",
    "data"
),
"posts" => array(
    "uuid",
    "post_id",
    "type",
    "data",
    "created_date"
)
```