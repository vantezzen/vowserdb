<?php
/**
 * vowserDB Extension abstract class
 * Abstract class for the creation of extensions
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

abstract class AbstractExtension {
    /**
     * Listeners that will be attached to the current vowserDB instance.
     * The name of the event will be the function name that got called,
     * the name of the listener is a function name of the extensions class
     * 
     * @type public array
     */
    public $listeners = [];

    /**
     * Methods that will be attached to the current vowserDB instance.
     * The name of the method will be the method of the Table instance that will trigger the method,
     * the name of the method is a function name of the extensions class.
     * Methods will not overwrite internal functions
     * 
     * @type public array
     */
    public $methods = [];

    /**
     * Constructor of the extension class
     * The extension isn't attached to a Table instance yet.
     * This method can be used to get configuration data
     */
    abstract public function __construct();

    /**
     * Listener for when the method gets attached to a Table instance.
     * 
     * @param string $table Name of the table the extension got attached to
     * @param string $path Absolute path to the tables file
     * @param Table $instance Current vowserDB\Table instance the extension got attached to
     */
    abstract public function onAttach(string $table, string $path, vowserDB\Table $instance);
}