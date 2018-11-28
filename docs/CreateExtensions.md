# Creating extensions for vowserDB
vowserDB comes with build-in extension support. 

vowserDB extensions are PHP classes that extend the `vowserDB\AbstractExtension` abstract class. They can both add new functionality in the form of the functions as well as extending existing vowserDB functions by adding [listeners](#listener) or [middlewares](#middleware) to them.

A full example extension can be found in `/exampleExtension.php`.

# Creation of an extension instance
The creation of an extension instance has two phases:

1. Creation of the extension instace via `$extension = new myExtension();`

2. Attachement to a table/vowserDB instace via `$table->attach($extension);`. This will call the `onAttach` method on the extension instance

# Creating the extension file
As vowserDB extensions are classes they can come as simple files or composer packages.

A file containing a vowserDB extension should contain the following code:
```php
<?php
// Class declaration, extending vowserDB\AbstractClass
class myExtension extends \vowserDB\AbstractExtension {

    // PHP constructor
    // Info: The extension isn't attached to any tables yet
    public function __construct() {
        // ... your construction code here...
    }

    // Attachment handler
    // This will be called once the extension will be attached to a table
    public function onAttach($table, $path, $instance) {
        // $table: Name of the attached table
        // $path: Absolute path to the table file
        // $instance: Current instance of vowserDB\Table
    }
}
?>
```

# Listener
A listener can attach itself to a "trigger" of the vowserDB\Table instance. It can then perform actions on itself but cannot interact with the function's return value. For doing this, please use [middleware](#middleware).

An example for an extension that is using a trigger is the table encryption extension. This extension will set a listener for the "beforeRead" and "beforeSave" triggers to decrypt a table and to "afterRead" and "afterSave" to encrypt the table.

## Availible triggers
Availible triggers are:

- waitForRead: Before triggering "beforeRead". The table file may not be readable (e.g. encrypted) at this moment
- beforeRead: Before triggering "readyForRead". The table file may not be readable (e.g. encrypted) at this moment
- readyForRead: Before reading a table file. The table file should be ready for reading and manipulating at this moment
- afterRead: After reading the table file
- select: When calling vowserDB "select" method. The argument $data['args'] will hold the exact arguments that "select" has been called with (using func_get_args())
- beforeSave: Before saving the table to file
- afterSave: After saving the table to file

## Arguments for the listener
The listener method will be called with the following arguments in this order:

- $event: Name of the event/trigger that called the listener (used when attaching multiple triggers to a listener)
- $data: Data that the function supplied
- $table: Table name (used when attaching multiple tables to a single extension instance)

## Attaching listeners
You can attach listeners by adding them to a public "$listeners" array in your extension class by using the format
```php
$listeners = [
    'triggerName' => 'listenerMethodName'
];
```
## Example
The example shows the listener "myListener" getting attached to the trigger "beforeSave".
```php
class myExtension extends \vowserDB\AbstractExtension {

    public $listeners = [
        'beforeSave' => 'myListener'
    ];

    public function myListener($eventType, $eventData, $table) {
        // ...
    }

    public function onAttach($table, $path, $instance) {
        // ...
    }
}
```

# Middleware
Middleware will be called before outputting the table contents, i.e when using `selected` or `data`. The middleware can then edit the data before returning it to the user.

## Availible middleware triggers
- data: Calling `data()`
- selected: Calling `selected()`

## Arguments for the middleware
The middleware method will be called with the following arguments in this order:

- $event: Event that triggered the middleware
- $value: Current return value
- $table: Current table

## Attaching middleware
Attaching middleware is similar to attaching [listener](#attaching-listeners).

You can attach middleware by adding them to a public "$middlewares" array in your extension class by using the format
```php
$middlwares = [
    'triggerName' => 'middlewareMethodName'
];
```

## Example
The example shows the middleware "middleware" getting attached to the trigger "selected".
```php
class myExtension extends \vowserDB\AbstractExtension {

    public $middlewares = [
        'selected' => 'middleware'
    ];

    public function middlware($eventType, $eventData, $table) {
        // ...
    }

    public function onAttach($table, $path, $instance) {
        // ...
    }
}
```

# Methods
You can add additional methods to the vowserDB\Table instance by using methods. If vowserDB has no build-in function with a given name `__call` will search for a registered extension method with that name. 

An example for this is `$table->doSomething();`. vowserDB has no build-in function with the name "doSomething". Your extension can register the method "doSomething" to get called once the user calls `doSomething()` on the table instance.

## Argument for methods
Methods will be called with the same arguments as it has been called on the table instance by using `call_user_func_array`.

This means that if `$table->doSomething('a', 2, 'c')` gets called, your method listening on "doSomething" will also get called with the arguments `('a', 2, 'c')`.

## Attaching methods
Attaching methods is similar to attaching [listener](#attaching-listeners).

You can attach methods by adding them to a public "$methods" array in your extension class by using the format
```php
$methods = [
    'publicName' => 'InternalMethodName'
];
```
Your internal method name can differ from its public name, meaning `$table->doSomething()` could call `$extension->handleDoSomethingCall()`.

## Example
The example shows the method "handleMethod" getting attached to the name "doSomething".
```php
class myExtension extends \vowserDB\AbstractExtension {

    public $methods = [
        'doSomething' => 'handleMethod'
    ];

    public function handleMethod($a, $b) {
        // ...
    }

    public function onAttach($table, $path, $instance) {
        // ...
    }
}
```
You could now call this to trigger your method:
```php
$table->doSomething('a', 'b');
```

# Publishing extensions
Extensions can be published as standalone files or composer packages