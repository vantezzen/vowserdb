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
Relationships are *always* many-to-many connections, but - as seen in the example above - can also be used for one-to-one or many-to-many connections.

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
encryptExtension is currently still in developement and shouldn't be used yet.