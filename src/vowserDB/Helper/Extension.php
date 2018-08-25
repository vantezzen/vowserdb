<?php
/**
 * vowserDB Extension Helper
 * Manage extensions
 *
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 * @link          https://vantezzen.github.io/vowserdb-docs/index.html vowserDB
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @version       4.0.0 - Alpha 1
 */

namespace vowserDB\Helper;

class Extension {
    /**
     * All listeners for all events
     * 
     * $listeners[$eventName] is an array with all listeners for event with $eventName
     * 
     * @type array
     */
    protected $listeners = [];

    /**
     * All methods from extensions
     * 
     * $method[$methodName] is an array with information about the listener for the method
     * 
     * @type array
     */
    protected $methods = [];

    /**
     * Name of the table the Extension instance is attached to.
     * Filled in __construct
     * 
     * @type string
     */
    protected $table;

    /**
     * Absolute Path of the table the Extension instance is attached to.
     * Filled in __construct
     * 
     * @type string
     */
    protected $path;

    /**
     * Constructor for the Extension instance.
     * 
     * @param string $table Name of the table the instance is attached to
     * @param string $path Absolute path of the attached table
     */
    public function __construct($table, $path) {
        $this->table = $table;
        $this->path = $path;
    }

    /**
     * Attach an extension to the Extension instance
     * 
     * @param class $extension Instance of the extension to attach
     */
    public function attach($extension) {
        if (isset($extension->listeners)) {
            foreach($extension->listeners as $event => $listener) {
                $this->listen($event, $extension, $listener);
            }
        }
        if (isset($extension->methods)) {
            foreach($extension->methods as $function => $handler) {
                $this->registerMethod($function, $extension, $handler);
            }
        }
    }

    /**
     * Register a new method to the Extension instance
     * This function will automatically be called once a table has been attached.
     * 
     * @param string $name Name of the method
     * @param class $extension Instance of the extension that registeres the method
     * @param string $handler Name of the handler function on the extension instance
     */
    public function registerMethod(string $name, $extension, string $handler) {
        $this->methods[$name] = [
            "extension" => $extension,
            "handler" => $handler
        ];
    }

    /**
     * Call a method from an extension (called from the vowserDB\Table instance's __call function)
     * 
     * @param string $name Name of the method to be called
     * @param mixed $arguments Arguments to call the method with
     * 
     * @return bool If a method has been found with that name
     */
    public function call(string $name, $arguments): bool {
        if (isset($this->methods[$name])) {
            $class = $this->methods[$name]['extension'];
            $function = $this->methods[$name]['handler'];
            call_user_func_array($class->$function, $arguments);
            return true;
        }
        return false;
    }

    /**
     * Listen for an event of the Table instance
     * This function will automatically be called once a table has been attached.
     * 
     * @param string $event Event name to listen for
     * @param class $class Instance of the extenstion that listenes for the event
     * @param string $function Name of the function to trigger on the event
     */
    public function listen(string $event, $class, string $function) {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = [
            "class" => $class,
            "function" => $function
        ];
    }

    /**
     * Trigger an event
     * This method will be executed by the vowserDB\Table instance to trigger events
     * 
     * @param string $event Name of the event to trigger
     * @param array $data Data to pass to the listener function
     */
    public function trigger(string $event, $data = []) {
        $listeners = isset($this->listeners[$event]) ? $this->listeners[$event] : [];
        foreach($listeners as $listener) {
            $class = $listener['class'];
            $function = $listener['function'];
            $class->$function($event, $data);
        }
    }
}