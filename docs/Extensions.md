# vowserDB build-in extensions
vowserDB comes pre-packages with some extensions.

# relationshipExtension
The `relationshipExtension` can create relationships between two tables.

Relationships are similar to `JOIN` in SQL. You will have to declare the relationship on each `vowserDB\Table` instance, the relationships are then automatically applied when getting table data with `->selected()` or `->data()`.

Example of how to use relationships: We have a table called `posts` in which all posts from the users are located with the columns: `user` containing the username of the user who posted the post and `text` with the text of the post.
We also have a table called `users` in which the informations about all users are saved with the columns `username` containing the username of the user and `mail` containing the mail of the user.

We can now add a relationship between `posts`' user column and `users`' username column as both columns contain the username of the user.
If we now get data from our `posts` table using `->data()`, the output could look like this:

original data from `posts`
```JSON
array(1) { 
    [0]=> array(2) { 
        ["user"]=> string(9) "vantezzen" 
        ["text"]=> string(15) "This is my post" 
    }
}
```

original data from `users`
```JSON
array(1) { 
    [0]=> array(2) { 
        ["user"]=> string(9) "vantezzen" 
        ["mail"]=> string(16) "mail@example.com" 
    }
}
```

Returned data from `->data()`
```JSON
array(1) { 
    [0]=> array(2) { 
        ["user"]=> array(1) { 
            [0]=> array(2) { 
                ["user"]=> string(9) "vantezzen" 
                ["mail"]=> string(16) "mail@example.com" 
            } 
        } 
        ["text"]=> string(15) "This is my post" 
    } 
}
```
Relationships are *always* many-to-many connections, but - as seen in the example above - can also be used for one-to-one or one-to-many connections.

## Installation
relationshipExtension comes pre-installed with vowserDB - no installation required.

## Attaching
When creating a relationship you will always have two tables - in this case `table1` and `table2`. 

The following example connects `row1` from `table1` to `row2` from `table2`.
```php
use vowserDB\Table;

// Initialize table instances
$table1 = new Table('one', ['row1']);
$table2 = new Table('two', ['row2']);

// Create relationshipExtension
use vowserDB\Extensions\relationshipExtension;
$relationshipExtension = new relationshipExtension('row1', 'row2'); // (Row on first table to connect, row on second table to connect)

// Attach extension to tables
$table1->attach($relationshipExtension); // First table to be attached is first table for the extension
$table2->attach($relationshipExtension); // Second table to be attached is second table for the extension

// You can now use the relationship extension.
```

# encryptExtension
The `encryptExtension` allows you to automatically encrypt your vowserDB tables using the PHP OpenSSL extension.

## Installation
encryptExtension comes pre-installed with vowserDB - no installation required.

## Attaching
When encrypting tables, you need an AES-128-CBC key. When using table encryption, vowserDB cannot read your tables when you initalize your vowserDB\Table instance yet - encryptExtension needs to be attached first. This is why you'll need to set `$config['skip_read']` to `true` when creating your `vowserDB\Table` instance. When attaching your `encryptExtension` instance, it will automatically read your table - you won't have to start a manual read.

The following example adds encryption to the table '`table`':
```php
use vowserDB\Table;

// Initialize table instance with 'skip_read' => true
$table = new Table('table', [], false, ['skip_read' => true]);

// Create encryptExtension
use vowserDB\Extensions\encryptExtension;
$encryptExtension = new encryptExtension('YOUR_AES_128_CBC_KEY_HERE');

// Attache the extension
$table->attach($encryptExtension);

// You can now use your table
```