<?php
/**
 * vowserDB : Standalone database software for PHP (https://vantezzen.github.io/vowserdb-docs/index.html)
 * Copyright (c) vantezzen (https://github.com/vantezzen/)
 *
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 * @link          https://vantezzen.github.io/vowserdb
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @version       4.0.0
 */

namespace vowserDB;

use vowserDB\CSVFile;
use vowserDB\Helper\CRUD;
use vowserDB\Helper\Initialize;
use vowserDB\Helper\Extension;
use vowserDB\Exception\TableInitializeException;

class Table {

    /**
      * Path to the folder in which tables will be saved
      *
      * @var string
      */
    public $folder = "vowserDB/";

    /**
      * Name of the opened table
      *
      * @var string
      */
    protected $table;
    /**
      * Array of columns that exist in the open table
      *
      * @var array
      */
    public $columns;

    /**
      * Last argument that has been used when calling "select" function
      *
      * @var mixed
      */
    public $lastSelection = '*';

    /**
      * Absolute path to the currently open table
      *
      * @var string
      */
    public $path;

    /**
      * Complete array with all data rows of the opened table
      *
      * @var array
      */
    protected $data;

    /**
      * Array with all selected rows in the opened table
      *
      * @var array
      */
    protected $selected;

    /**
     * Boolean that saves if the table has unsaved changes
     * 
     * @var bool
     */
    protected $hasChanges = false;

    /**
     * Instance of vowserDB\Helper\Extension connected to the current instance of Table.
     * This extension instance will manage extension functionality such as attaching, methods and listeners.
     * It will be initialized in the __constructor method
     * 
     * @var vowserDB\Helper\Extension
     */
    protected $extension;

    /**
     * Constructor
     *
     * Create a new vowserDB Table instance by setting class variables for the selected table and reading it
     *
     *
     * @param string $table              Name of the table that will be opened
     * @param mixed $columns             Array of columns for the table or name of column template (see $templates) (optional)
     * @param array $additional_columns  Additional columns that will be added to the used template columns (optional)
     * @param array $config              Array of additional configuration data (optional)
     * @throws vowserDB\Exception\TableInitializeException If the table can not be opened and initialized. When this error gets thrown, another error will be thrown by 'Initialize::table' giving more information about the error
     */
    public function __construct(string $table, $columns = false, $additionalColumns = false, $config = false) {
        $this->table = $table;
        if ($config !== false) {
            if (isset($config['folder'])) {
                $this->folder = $config['folder'];
            }
        }
        // $this->path = realpath($this->folder) . $this->table . ".csv";
        $this->path = getcwd() . '/' . $this->folder . $this->table . ".csv";

        if (!Initialize::table($this->path, $columns, $additionalColumns)) {
            throw new TableInitializeException("Could not open and initialize table " . $table);
            return false;
        }

        $this->columns = CSVFile::columns($this->path);

        $this->extension = new Extension($table, $this->path);

        $this->read();

        $this->select('*');
    }

    /**
     * Returns all rows in the opened table
     *
     * @return array
     */
    public function data(): array {
        $data = $this->extension->applyMiddlewares('data', $this->data);
        return $data;
    }

    /**
     * Returns selected rows
     *
     * @return array
     */
    public function selected(): array {
        $selected = $this->extension->applyMiddlewares('selected', $this->selected);
        return $selected;
    }

    /**
     * Read the opened table and set $this->data to the read data
     *
     * @return Table $this
     */
    public function read() {
        $this->extension->trigger('waitForRead');
        $this->extension->trigger('beforeRead');
        $this->extension->trigger('readyForRead');
        $this->data = CSVFile::read($this->path, $this->columns);
        $this->extension->trigger('afterRead');
        return $this;
    }
    
    /**
     * Select rows from the main data array with the given arguments
     *
     * The selection argument can be given in the following formats:
     * - As an array:
     *     array("selectColumn" => "selectValue"), e.g. array("username" => "testUser")
     * - Empty string to select everything:
     *     ""
     * - '*' to select everything:
     *     '*'
     *
     * @param mixed $selection            The selection argument with which data will be selected
     * @param bool $fromSelected          If the selection should be made from only the previous selected data or all data from the table (default: false)
     * @return Table $this
     */
    public function select($selection, bool $fromSelected = false, bool $particalArrayMatch = false): self {
        $data = $fromSelected ? $this->selected : $this->data;
        if (empty($this->selected) && $fromSelected == true) {
            $data = $this->data;
        }
        $this->selected = CRUD::applySelection($data, $selection, $this->columns, $particalArrayMatch);
        $this->lastSelection = $selection;

        $this->extension->trigger('select', [
            "args" => func_get_args()
        ]);

        return $this;
    }

    /**
     * Select the first row of selected or data
     * 
     * @param bool $fromSelected Whether the first row should be from the selected array (default: true)
     * @return Table $this
     */
    public function first(bool $fromSelected = true): self {
        if ($fromSelected) {
            $this->selected = $this->selected[0];
        } else {
            $this->selected = $this->data[0];
        }
        return $this;
    }

    /**
     * Limit the number of selected items.
     * This function can be used with one or two parameters
     * If only one parameter is given, the slice will be from 0 to $limit1,
     * otherwise it will be from $limit1 to $limit2
     * 
     * @param int $limit1 First limit of items
     * @param int $limit2 Upper limit of items (optional)
     * @return Table $this
     */
    public function limit(int $limit1, $limit2 = false): self {
        if ($limit2 === false) {
            $this->selected = array_slice($this->selected, 0, $limit1 - 1);
        } else {
            $this->selected = array_slice($this->selected, $limit1 - 1, $limit2 - 1);
        }
        return $this;
    }
    
    /**
     * Insert one or more rows into the table
     * This function support both a one dimensional and more-dimensional array:
     * ["column" => "value"]
     * and
     * [["column" => "value"], ["column" => "value2"]]
     * 
     * @param array $data   Data to insert into the table
     * @return Table $this
     */
    public function insert(array $data): self {
        // Check if array is associative (one-dimensional) and convert to two-dimensional
        $isAssociative = (array_keys($data) !== range(0, count($data) - 1));
        if ($isAssociative) {
            $data = [ $data ];
        }
        foreach($data as $row) {
            $this->data[] = $row;
        }

        $this->select($this->lastSelection);

        $this->hasChanges = true;

        return $this;
    }

    /**
     * Update the table by a given criteria
     * 
     * @param array $update Updated values for the column. This supports update keywords
     * @return Table $this
     */
    public function update(array $update): self {
        $this->data = CRUD::update($this->selected, $this->data, $update);
        $this->select($this->lastSelection);

        $this->hasChanges = true;

        return $this;
    }
    
    /**
     * Delete the selection from the table
     * 
     * @return Table $this
     */
    public function delete(): self {
        $this->data = CRUD::delete($this->selected, $this->data);
        $this->select($this->lastSelection);

        $this->hasChanges = true;

        return $this;
    }

    /**
     * Truncate the table
     *
     * @return Table $this
     */
    public function truncate(): self {
        $this->data = [];
        $this->selected = [];

        $this->hasChanges = true;

        return $this;
    }

    /**
     * Save data from data array to the table file
     * 
     * @return Table $this
     */
    public function save(): self {
        if (!$this->hasChanges) {
            return $this;
        }
        $this->extension->trigger('beforeSave');
        CSVFile::save($this->path, $this->columns, $this->data);
        $this->extension->trigger('afterSave');
        $this->hasChanges = false;

        return $this;
    }

    /**
     * Drop/delete a table
     * 
     * @return Table $this
     */
    public function drop(): self {
        CSVFile::delete($this->path);

        $this->hasChanges = true;

        return $this;
    }

    /**
     * Attach an extension to the current table
     * 
     * @param Class $extension  Constructed class of the extension
     * @return Table $this
     */
    public function attach($extension): self {
        $this->extension->attach($extension);
        if(method_exists($extension, 'onAttach')) {
            $extension->onAttach($this->table, $this->path, $this);
        }
        return $this;
    }

    /**
     * Detach all extensions attached to the current table
     * 
     * @return Table $this
     */
    public function detach(): self {
        $this->extension->detach();
        return $this;
    }

    /* MAGIC FUNCTIONS */
    public function __call($name, $arguments) {
        return $this->extension->call($name, $arguments);
    }
    public function __invoke() {
        return $this->data;
    }
}