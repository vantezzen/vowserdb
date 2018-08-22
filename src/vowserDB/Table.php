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
 * @link          https://vantezzen.github.io/vowserdb-docs/index.html vowserDB
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @version       4.0.0 - Alpha 1
 */

namespace vowserDB;

use vowserDB\CSVFile;
use vowserDB\Helper\CRUD;
use vowserDB\Helper\Initialize;
use vowserDB\Helper\Extension;
use Exception;

class Table {

    /**
      * Path to the folder in which tables will be saved
      *
      * @type string
      */
    public $folder = "vowserDB/";

    /**
      * Name of the opened table
      *
      * @type string
      */
    protected $table;
    /**
      * Array of columns that exist in the open table
      *
      * @type array
      */
    protected $columns;

    /**
      * Last argument that has been used when calling "select" function
      *
      * @type mixed
      */
    protected $lastSelection = '*';

    /**
      * Absolute path to the currently open table
      *
      * @type string
      */
    protected $path;

    /**
      * Complete array with all data rows of the opened table
      *
      * @type array
      */
    protected $data;

    /**
      * Array with all selected rows in the opened table
      *
      * @type array
      */
    protected $selected;

    /**
     * Boolean that saves if the table has unsaved changes
     */
    protected $hasChanges = false;

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
     */
    public function __construct($table, $columns = false, $additionalColumns = false, $config = false) {
        $this->table = $table;
        if ($config !== false) {
            if (isset($config['folder'])) {
                $this->folder = $config['folder'];
            }
        }
        $this->path = realpath($this->folder . $this->table . ".csv");

        if (!Initialize::table($this->path, $columns, $additionalColumns)) {
            throw new Exception("Could not open table " . $table);
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
    public function data() {
        return $this->data;
    }

    /**
     * Returns selected rows
     *
     * @return array
     */
    public function selected() {
        return $this->selected;
    }

    /**
     * Read the opened table and set $this->data to the read data
     *
     * @return Table $this
     */
    public function read() {
        $this->data = CSVFile::read($this->path, $this->columns);
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
    public function select($selection, $fromSelected = false) {
        $data = $fromSelected ? $this->selected : $this->data;
        if (empty($this->selected) && $fromSelected == true) {
            $data = $this->data;
        }
        $this->selected = CRUD::applySelection($data, $selection, $this->columns);
        $this->lastSelection = $selection;

        $this->extension->trigger('select', [
            "args" => func_get_args()
        ]);

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
    public function insert($data) {
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
    public function update($update) {
        $this->data = CRUD::update($this->selected, $this->data, $update);
        $this->select($this->lastSelection);

        return $this;
    }
    
    /**
     * Delete the selection from the table
     * 
     * @return Table $this
     */
    public function delete() {
        $this->data = CRUD::delete($this->selected, $this->data);
        $this->select($this->lastSelection);

        return $this;
    }

    /**
     * Truncate the table
     *
     * @return Table $this
     */
    public function truncate() {
        $this->data = [];
        $this->selected = [];

        return $this;
    }

    /**
     * Save data from data array to the table file
     * 
     * @return Table $this
     */
    public function save() {
        if (!$this->hasChanges) {
            return $this;
        }
        CSVFile::save($this->path, $this->columns, $this->data);
        $this->hasChanges = false;

        return $this;
    }

    /**
     * Drop/delete a table
     * 
     * @return Table $this
     */
    public function drop() {
        CSVFile::delete($this->path);

        return $this;
    }

    /**
     * Attach an extension to the current table
     * 
     * @param Class $extension  Constructed class of the extension
     */
    public function attachExtension($extension) {
        $this->extension->attach($extension);
        if(method_exists($extension, 'onAttach')) {
            $extension->onAttach($this->table, $this->path, $this);
        }
    }

    /* MAGIC FUNCTIONS */
    public function __call($name, $arguments) {
        return $this->extension->call($name, $arguments);
    }
    public function __invoke() {
        return $this->data;
    }
}