# Installation
vowserDB can be installed using composer using the packagist package "vowserdb/vowserdb"

```bash
composer require vowserdb/vowserdb
```

# Components
vowserDB has two main components: `Table` and `Database`.
`Table` will be the only component used in most cases. It is not dependent on `Database`. `Table` allows creation, access and manipulation of tables.
`Database` is used to perform actions on the whole Database, e.g. get a list of tables.


# vowserDB\Table
## General usage
The main class for working with vowserDB Tables is the `vowserDB\Table` class.
You should add a `use` to your file so you can work with it easier.
```php
use vowserDB\Table;
```

vowserDB works in a similar way to Laravels Eloquent Database System.

You will first create a new table object. This object will be linked to a single table but you can create multiple objects for multiple tables.
```php
$table = new Table('users');
```

You can then use this table object to access the table
```php
$table->select(['username' => 'testuser'])->update(['password' => '1234']);
```

Updates to the table are only done temporarly. To save changes like updates or deletes to the table file you have to save them
```php
$table->save();
```

To get data from the table you can use the two helper functions `data`, to get all data from the table, and `selected`, to get only the currently selected rows
```php
$allData = $table->data();
$testUser = $table->select(['username' => 'testuser'])->selected();
```

## Stacking methods
All methods, excluding `data` and `selected`, will return the current `Table` instance. This allows to stack multiple methods as seen in the example code.
```php
$table
    ->select(['username' => 'testuser'])
    ->update(['password' => '1234'])
    ->select(['username' => 'deleteme'])
    ->delete()
    ->insert(['username' => 'someuser']);
```

## Creating and using a table
When accessing a table you don't have to create it first seperately. Rather you should always give vowserDB the information which columns the table should have. If the table already exists it will use it but if it doesn't it will create one with the specified columns.
```php
$table = new Table(
    string $tableName
        [, mixed $columns = false
            [, array $additionalColumns = false
                [, array $config = []
                ]
            ]
        ]
    );
```
Example: Creating and/or using a table with the name 'users' that has the columns 'username', 'password' and 'mail'.
```php
$table = new Table('users', ['username', 'password', 'mail']);
```
Additionally, you can use [table templates](#table-templates) when creating tables. Table templates allow you to use a small name instead of a full array with all columns.
When using table templates you can use the `$additionalColumns` argument to add additional columns to the template.

Example: Creating and/or using a table with the name 'posts' (first argument) using the template 'posts' (second argument) and adding the columns 'updated_date' and 'comments'.
```php
$table = new Table('posts', 'posts', ['updated_date', 'comments']);
```

You can also create a table with the same name as a [table template](#table-templates) (e.g. `users`) without additional columns specified. This will use the [table template](#table-templates) when creating the table.

Example: Creating and/or using a table with the name 'users' without any columns specified. This will use the table template 'users'.
```php
$table = new Table('users');
```

### Table templates
Availible table templates are:

| Name  | Columns                                 |
| ----- | --------------------------------------- |
| users | username, uuid, password, mail, data    |
| posts | uuid, post_id, type, data, created_date |
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

### Config argument
When opening or creating a table via `new Table` you can add a config array as the 4th argument.
This argument currently supports:

| Name      | Description                                                                                                                      |
| --------- | -------------------------------------------------------------------------------------------------------------------------------- |
| folder    | Folder to save vowserDB tables into (default: "vowserDB/")                                                                       |
| storage   | [Storage provider](#vowserdbtable-using-storage-providers) used for storing the table file (default: `new vowserDB\Storage\CSV`) |
| skip_read | Don't read table contents when initializing                                                                                      |

Example: Change the folder to "tables/"
```php
$table = new Table('users', ['username', 'password'], false, ['folder' => 'tables/']);
```

### Naming convensions
When naming a vowserDB table please follow these rules:
- You are not allowed to use slash (/), backslash (\\), dot (.), comma (,), spaces ( ) and hash symbols (#) as these can create problems with the filesystem
- We advice you to only use letters of the english alphabet and numbers

## Using storage providers
vowserDB supports storage provider. These storage provider handle reading and writing to the table file.

By default, vowserDB uses the `vowserDB\Storage\CSV` provider to store your table to a CSV file but vowserDB also comes with a `vowserDB\Storage\JSON` provider to store your table to a JSON file.

To use this provider first create a new `vowserDB\Storage\JSON` instance, then pass it to your new `vowserDB\Table` instance using the `config['storage']` argument.
```php
$storage = new vowserDB\Storage\JSON;
$table = new vowserDB\Table('name', 'users', false, [
    'storage' => $storage
]);
```

You can also create your own vowserDB storage providers. Please take a look at `src/vowserDB/Storage/JSON.php` for a simple storage provider.

## Inserting data into a table
To insert new data into the table use the `insert` function.
```php
$table->insert($data);
```
This function takes eather an associative array with key value pairs or a two-dimensional array of associative arrays.
vowserDB allows you to save strings, numbers of all types and arrays into tables.

Example: Insert one user.
```php
$table->insert(
    [
        "username" => "testuser",
        "password" => "1234"
    ]
);
```

Example 2: Insert multiple posts into the table using a two-dimenisional array of associative arrays.
```php
$table->insert(
    [
        [
            "text" => "This is post 1",
            "created_at" => "3.6.2019, 20:19",
            "promotion_limit" => "$50"
        ],
        [
            "text" => "This is the second post",
            "created_at" => "6.6.2019, 20:59"
        ]
    ]
);
```
Unused columns of the table can be left unset (like the `promotion_limit` of the second post), they will be left empty.

Inserted values will not be directly written to the table file and only stored temporarly. To save, use the `save` method.
```php
$table->insert(...)->save();
```

### Arrays
vowserDB tables support arrays. These can simply be used as a value of a column
```php
$table->insert(
    [
        "username" => "testuser",
        "subscriptions" => 
            [
                "otheruser",
                "anotheruser",
                "someotheruser"
            ]
    ]
);
```

## Selecting data from a table
Selecting can be done via the `select` function.
The first argument will be a selection - an argument of what to select. This can eather be an empty string or array or an asterix ('*') to select everything or an associative array of key value pairs.
The second argument, `$fromSelected`, defines if the new selection should be made from the previous selection. If set to false (default) the selection will be made from all data in the table.
The selection is case-sensitive
```php
$table->select(
    $selection
    [, $fromSelected = false]
);
```
Example: Select all rows where username is `testuser`
```php
$table->select(['username' => 'testuser']);
```
Example 2: Select all posts with the `text` `Hello` and the `user` `testuser`
```php
$table->select(
    [
        'text' => 'Hello',
        'user' => 'testuser'
    ]
);
```
Example 3: First select all post from `user` `testuser`, do something with it, then, from these posts, select all with `text` `Hello`
```php
$table->select(['username' => 'testuser']);
// Do something
$table->select(['text' => 'Hello'], true);
```

### Selection arguments
When selecting data from a table, vowserDB selection arguments can be used.

| Selection argument | SQL equivalent   | Description                                                   |
| ------------------ | ---------------- | ------------------------------------------------------------- |
| BIGGER THAN        | >                | Column value is bigger than given value                       |
| SMALLER THAN       | <                | Column value is smaller than given value                      |
| BIGGER EQUAL       | >=               | Column value is bigger or equal to given value                |
| SMALLER EQUAL      | <=               | Column value is smaller or equal to given value               |
| IS NOT             | !=               | Column value is not given value                               |
| LIKE               | LIKE             | Column value is similar to given value (using PHPs `stristr`) |
| MATCH              | LIKE '%[regex]%' | Column value matches given regex                              |

Example: Select all rows with 3 or more likes
```php
$table->select(['likes' => 'BIGGER EQUAL 3']);
```
Example 2: Select all rows where text is not "Hello, World!"
```php
$table->select(['likes' => 'IS NOT Hello, World!']);
```

### Limiting selected data
To limit the number of selected data the `first` and `limit` functions can be used
```php
$table->first($fromSelected = true);
$table->limit($limit1, $limit2 = false);
```
#### first()
`first` selects the first row. This can eather be done from the current selection or from all data by setting `$fromSelected` accordingly.
```php
$table->select('*')->first();
```
`first` will only select the row, to get the row data please use `selected`
```php
$firstRow = $table->first(true)->selected();
```

#### limit()
`limit` works like the SQL `limit` function but it can only limit currently selected rows. It can take one or two arguments. As with `first`, it will not return but only select the data. It is based on number of items not position in the array, resulting in it being 1-based instead of 0-based: When selecting the first 2 items, use `limit(2)`, not `limit(1)`.

When using with one argument, `limit` will select the first n rows.
```php
$firstFiveRows = $table->select('*')->limit(5)->selected();
```
When using with two arguments, `limit` will select the slice from the array - again being 1-based. `limit` with two arguments can be helpful when dealing with paginations.
```php
$rowsFiveToTen = $table->select('*')->limit(5,10)->selected();
```

### Selection auto-update
Selections will automatically update itself. This means that when you `insert`, `update` or `delete` in your table, your selection will update automatically and add or remove effected rows.

## Getting data from the table
The `select` function does **not** return an array with the select rows, it only selects them internally. To actually get the selected rows you'll have to use the `selected` function. This returns an array with all currently selected rows
```php
$rows = $table->selected();
```
To get all rows, not only the selected ones, from the table, use the `data` function which returns an array with all rows in the table.
```php
$rows = $table->data();
```

## Updating data
Data in a table can be changed with the `update` function. The update function will update all data that has been selected using the `select` function beforehand.
```php
$table->update($data);
```
The function takes an associative array as its only argument. This array contains the updates values, unused columns can be left unset.

Updated values will not be directly written to the table file and only stored temporarly. To save updates, use the `save` method.
```php
$table->update(['password' => '1234'])->save();
```

Example: Select the user with `username` `testuser`, then change its `password` to `1234`
```php
$table->select(['username' => 'testuser'])->update(['password' => '1234']);
```

### Update arguments
Like with `select`, `update` supports special update arguments that can be used to update columns based on their current value.
To get the parameter with which the argument has been called, the argument including a trailing space simply gets replaced via `str_replace`, meaning the paramenter can be multiple words long.

| Argument     | Description                                                       |
| ------------ | ----------------------------------------------------------------- |
| INCREASE BY  | Increase the column value by given value                          |
| DECREASE BY  | Decrease the column value by given value                          |
| MULTIPLY BY  | Multiply the column value by given value                          |
| DIVIDE BY    | Divide the column value by given value                            |
| ARRAY PUSH   | Push value to the array (column needs to be of type array)        |
| ARRAY REMOVE | Remove given value from column (column needs to be of type array) |

Example: Increase the number of `likes` by 1
```php
$table->update(['likes' => 'INCREASE BY 1']);
```

Example 2: Push `someuser` to the `subscriptions` array
```php
$table->update(['subscriptions' => 'ARRAY PUSH someuser']);
```

Example 3: Remove `someuser` from `subscriptions` array
```php
$table->update(['subscriptions' => 'ARRAY REMOVE someuser']);
```

Example 4: Remove `Hello, World!` from `posts` array
```php
$table->update(['posts' => 'ARRAY REMOVE Hello, World!']);
```

## Delete data
Rows can be deleted using the `delete` function. This function will remove all selected rows from the table.
```php
$table->delete();
```

As with `update`, `delete` will not directly save the changes to the table file. To save, use the `save` method
```php
$table->delete()->save();
```

## Truncate table
To easily truncate a table use the `truncate` method as it is faster than selecting everything and deleting it.
```php
$table->truncate();
```
As with `update` and `delete`, `truncate` will not directly save the changes to the table file. To save, use the `save` method
```php
$table->truncate()->save();
```

## Drop/delete table
Deleting a table can be accomplished by using the `drop` method. This will delete the table file but the `Table` instance will still be availible meaning the data can still be accessed through it in this PHP instance.
```php
$table->drop();
```

Example: Selecting data is still possible after the table has been deleted
```php
$table
    ->drop()
    ->select(['username' => 'test']);
```
All methods are still usable, the `save` method can be used to rewrite the data to the deleted table file again.

## Extensions
`Table` functionality can be extended using Extensions. Extensions can add own methods to the `Table` instance or listen for certain activities.
Extensions are standalone classes that can be imported from standalone files or seperate composer packages.

To use an extension the extension class must first be included and initialized.
```php
use examplePackage\exampleExtension;
$extension = new exampleExtension();
```
The extension then needs to be attached to a `Table` instance using `attach`.
```php
use vowserDB\Table;
$table = new Table('users', 'users');
$table->attach($extension);
```

Please note that one extension instance can only be attached to one `Table` instance.
When using extensions with multiple tables please create seperate extension instances.
```php
// Wrong
$users = new Table('users', 'users');
$posts = new Table('posts', 'posts');
$extension = new exampleExtension();
$users->attach($extension);
$posts->attach($extension);

// Right
$users = new Table('users', 'users');
$posts = new Table('posts', 'posts');
$usersExtension = new exampleExtension();
$postsExtension = new exampleExtension();
$users->attach($usersExtension);
$posts->attach($postsExtension);
// or
$users = new Table('users', 'users');
$posts = new Table('posts', 'posts');
$users->attach(new exampleExtension());
$posts->attach(new exampleExtension());
```
Please take a look at the extensions documentation for more information about how to use it. Some extensions may require additional configuration.

### Build-in extensions
For documentation of vowserDBs build-in extensions visit <a href="#/Extensions?id=vowserdb-build-in-extensions">the vowserDB extension documentation</a>

### Creating extensions
For documentation on how to create vowserDB extensions visit <a href="#/CreateExtensions?id=creating-extensions-for-vowserdb">the vowserDB extension creation documentation</a>

# vowserDB\Database
## General usage
You should add a `use` to your file so you can work with it easier.
```php
use vowserDB\Database;
```

The `Database` class, in contrast to `Table`, is intended to use statically, without creating an instance.

By default, `Database` will use `vowserDB/` as the database folder. If you would like to use a different folder you can add it as the '$folder' argument availible in each method of `Database`. This will eather accept a relative or absolute path or an instance of `Table`, in which case the folder set in the `Table` instance will be used.

## Getting tables in the database
The static function `tables` will return an array with all tables in the database folder.
```php
array Database::tables($folder = false);
```

Example 1: Get tables in the default database folder.
```php
$tables = Database::tables();
```

Example 2: Get tables in folder 'tables/'
```php
$tables = Database::tables('tables/');
```

Example 3: Get all tables in the folder `$table` is in
```php
$table = new Table(...);
$tables = Database::tables($table);
```

## Truncating database
Truncating the database - meaning deleting all tables in the database - can be achieved using the `trunacate` method.
```php
Database::truncate($folder = false);
```
This function can be used in the same way `tables` gets used.