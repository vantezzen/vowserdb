<?php
/**
 * vowserDB encrypt Extension
 * Encrypt vowserDB tables
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

namespace vowserDB\Extensions;

use vowserDB\AbstractExtension;

class encryptExtension extends AbstractExtension {
    public $listeners = [
        "beforeRead" => "decrypt",
        "afterRead" => "encrypt",
        "beforeSave" => "decrypt",
        "afterSave" => "encrypt"
    ];
    
    protected $key;
    protected $decryptedPath;
    protected $encryptedPath;

    // Configuration
    protected $cipher; // Cipher method to use for openssl_encrypt

    public function __construct($key = "", $cipher = 'aes-128-gcm') {
        $this->key = $key;
        $this->cipher = $cipher;
    }

    public function onAttach($table, $path, $instance) {
        $this->decryptedPath = $path;
        $this->encryptedPath = $path . ".enc";
        $instance->read();
    }

    public function decrypt() {
    }

    public function encrypt() {
    }

    /**
     * Get current encryption status of table
     * 
     * @return int Encryption status of the table as an int (0 => not encrypted, 1 => encrypted)
     */
    public function status() {
        if (!file_exists($this->encryptedPath)) {
            return 0;
        } else if (file_get_contents($this->encryptedPath) == 'decrypted') {
            return 0;
        } if (file_get_contents($this->decryptedPath) == 'encrypted,file') {
            return 1;
        }
        return 0;
    }
}