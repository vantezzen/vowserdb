<?php
/**
 * vowserDB Exception interface
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
namespace vowserDB\Exception;

/**
 * @method string getMessage()
 * @method \Throwable|null getPrevious()
 * @method mixed getCode()
 * @method string getFile()
 * @method int getLine()
 * @method array getTrace()
 * @method string getTraceAsString()
 */
interface vowserDBException extends \Throwable {}