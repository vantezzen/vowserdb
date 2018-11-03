<?php
use vowserDB\AbstractExtension;

class exampleExtension extends AbstractExtension {
    /**
     * Listeners that will be attached to the current vowserDB instance.
     * The name of the event will be the function name that got called,
     * the name of the listener is a function name of the extensions class
     * 
     * @var public array
     */
    public $listeners = [
        "select" => "myEventListener"
    ];

    public $methods = [
        "example" => "exampleMethod"
    ];

    /**
     * Internal example variables that will be used to store data of the attached table
     */
    protected $config;
    protected $table;
    protected $path;
    protected $instance;

    /**
     * Normal PHP contructor function. The extension isn't attached to any tables yet!
     */
    public function __construct($config) {
        $this->config = $config;
    }

    /**
     * Example event listener
     * 
     * @param string $eventType Name of the event that triggered the listeners 
     *              (used when one listener is attached to multiple events)
     * @param array $data Data that gives more information about the event
     *              $data['args'] holds the arguments the function has been called with
     */
    public function myEventListener($eventType, $eventData) {
        echo "There has been a new event: " . $eventType . "." . PHP_EOL;
        var_dump($eventData);
    }

    /**
     * Example method that will be triggered when calling $table->example()
     * Declared in $methods
     */
    public function exampleMethod($argument1 = "", $argument2 = []) {
        echo "Example extension has been called with arguments:";
        var_dump($argument1, $argument2);
    }

    /**
     * Special listener that gets triggered once the extension has been attached.
     * The name of this listener is reserved to be the "onAttach" function
     * 
     * @param string $table Name of the attached table
     * @param string $path Absolute path to the table
     * @param vowserDB\Table $instance vowserDB Table instance of the attached table
     */
    public function onAttach($table, $path, $instance) {
        $this->table = $table;
        $this->path = $path;
        $this->instance = $instance;
        echo "Extension has been attached to table " . $table . " with path " . $path . PHP_EOL;
    }
}