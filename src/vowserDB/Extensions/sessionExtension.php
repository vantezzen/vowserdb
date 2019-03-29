<?php
/**
 * vowserDB session Extension
 * Save PHP session data in a vowserDB table
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

namespace vowserDB\Extensions;

use Exception;

class sessionExtension extends \SessionHandler
{
    /**
     * \vowserDB\Table instance used for storing sessions
     * 
     * @var \vowserDB\Table
     */
    protected $table;

    /**
     * Default table used when no table is provided
     * 
     * @var string
     */
    public static $defaultTableName = 'sessions';

    /**
     * Columns needed in the sessions table
     * 
     * @var array
     */
    public static $columns = [
        'id',
        'data',
        'lastused'
    ];

    /**
     * Constructor
     * 
     * @param $createTable = true; Will create table instance itself if true
     *        otherwise a \vowserDB\Table instance needs to be attached later
     */
    public function __construct($createTable = true) {
        if ($createTable) {
            $table = new \vowserDB\Table(
                self::$defaultTableName,
                self::$columns
            );
            $table->attach($this);
        }
    }

    /**
     * Attach vowserDB\Table instance to extension
     */
    public function onAttach(string $table, string $path, \vowserDB\Table $instance)
    {
        $this->table = $instance;
    }

    /* METHODS FOR SESSION HANDLER */
    public function open($save_path, $sessionid)
    {
        return true;
    }
    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $data = $this->table->select(['id' => $id])->selected();
        if (count($data) > 0) {
            return $data[0]['data'];
        } else {
            return false;
        }
    }

    public function write($id, $data)
    {
        if ($this->read($id) == false) {
            $this->table->insert(
                [
                    'id' => $id,
                    'data' => $data,
                    'lastused' => time()
                ]
            )->save();
        } else {
            $this->table
                ->select(['id' => $id])
                ->update([
                    'id' => $id,
                    'data' => $data,
                    'lastused' => time()
                ])->save();
        }
        return true;
    }

    public function destroy($id)
    {
        $this->table->select(['id' => $id])->delete()->save();
    }

    public function gc($maxlifetime)
    {
        $deletewhen = time() - $maxlifetime;
        $this->table->select(
            [
                'lastused' => 'SMALLER EQUAL ' . $deletewhen
            ]
        )->save();
        return true;
    }
}
