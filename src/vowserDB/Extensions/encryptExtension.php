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

    public function __construct($key = "") {
        $this->key = $key;
    }

    public function onAttach($table, $path, $instance) {
        $this->decryptedPath = $path;
        $this->encryptedPath = $path . ".enc";
        $instance->read();
    }

    public function decrypt($eventType) {
        if (file_exists($this->encryptedPath) && file_get_contents($this->encryptedPath) == 'decrypted') {
            return false;
        }

        $key = substr(sha1($this->key, true), 0, 16);

        $error = false;
        if ($fpOut = fopen($this->decryptedPath, 'w')) {
            if ($fpIn = fopen($this->encryptedPath, 'rb')) {
                // Get the initialzation vector from the beginning of the file
               $iv = fread($fpIn, 16);
                while (!feof($fpIn)) {
                    $ciphertext = fread($fpIn, 16 * (10000 + 1)); // we have to read one block more for decrypting than for encrypting
                   $plaintext = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                   // Use the first 16 bytes of the ciphertext as the next initialization vector
                   $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $plaintext);
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            fclose($fpOut);
        } else {
            $error = true;
        }
        if (!$error) {
          $f = fopen($this->encryptedPath, 'w');
          fwrite($f, 'decrypted');
          fclose($f);
        }
    }

    public function encrypt() {
        if (file_exists($this->decryptedPath) && file_get_contents($this->decryptedPath) == 'this;file;is;encrypted') {
            return false;
        }

        $key = substr(sha1($this->key, true), 0, 16);
        $iv = openssl_random_pseudo_bytes(16);

        $error = false;
        if ($fpOut = fopen($this->encryptedPath, 'w')) {
            // Put the initialzation vector to the beginning of the file
           fwrite($fpOut, $iv);
            if ($fpIn = fopen($this->decryptedPath, 'rb')) {
                while (!feof($fpIn)) {
                    $plaintext = fread($fpIn, 160000);
                    $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                   $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $ciphertext);
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            fclose($fpOut);
        } else {
            $error = true;
        }

        if (!$error) {
          $f = fopen($this->decryptedPath, 'w');
          fwrite($f, 'this;file;is;encrypted');
          fclose($f);
        }
    }
}