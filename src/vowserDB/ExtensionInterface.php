<?php
/**
 * vowserDB Extension interface
 * Interface used by extensions.
 *
 * Licensed under MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) vantezzen (https://github.com/vantezzen/)
 *
 * @link          https://vantezzen.github.io/vowserdb
 *
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 *
 * @version       4.1.1
 */

namespace vowserDB;

interface ExtensionInterface
{
    /**
     * Listener for when the method gets attached to a Table instance.
     *
     * @param string $table    Name of the table the extension got attached to
     * @param string $path     Absolute path to the tables file
     * @param Table  $instance Current vowserDB\Table instance the extension got attached to
     */
    public function onAttach(string $table, string $path, Table $instance);
}
