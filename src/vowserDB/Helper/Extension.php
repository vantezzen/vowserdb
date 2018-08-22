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
    protected $listeners = [];
    protected $methods = [];
    protected $table;
    protected $path;

    public function __construct($table, $path) {
        $this->table = $table;
        $this->path = $path;
    }

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

    public function registerMethod($name, $extension, $handler) {
        $this->methods[$name] = [
            "extension" => $extension,
            "handler" => $handler
        ];
    }

    public function call($name, $arguments) {
        if (isset($this->methods[$name])) {
            $class = $this->methods[$name]['extension'];
            $function = $this->methods[$name]['handler'];
            call_user_func_array($class->$function, $arguments);
        } else {
            return false;
        }
    }

    public function listen($event, $class, $function) {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = [
            "class" => $class,
            "function" => $function
        ];
    }

    public function trigger($event, $data = []) {
        $listeners = isset($this->listeners[$event]) ? $this->listeners[$event] : [];
        foreach($listeners as $listener) {
            $class = $listener['class'];
            $function = $listener['function'];
            $class->$function($event, $data);
        }
    }
}