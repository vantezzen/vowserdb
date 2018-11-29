<?php
/**
 * vowserDB encrypt Extension
 * Encrypt vowserDB tables.
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
 * @version       4.1.0
 */

namespace vowserDB\Extensions;

use vowserDB\AbstractExtension;

class encryptExtension extends AbstractExtension
{
    public $listeners = [
        'beforeRead' => 'decrypt',
        'afterRead'  => 'encrypt',
        'beforeSave' => 'decrypt',
        'afterSave'  => 'encrypt',
    ];

    protected $key;
    protected $decryptedPath;
    protected $encryptedPath;

    public function __construct($key = '')
    {
        $this->key = $key;
    }

    public function onAttach($table, $path, $instance)
    {
        $this->decryptedPath = $path;
        $this->encryptedPath = $path.'.enc';

        // Extension is now attached - table can now be read
        $instance->read();
        $instance->select('*');
    }

    public function decrypt()
    {
        if ($this->isEncrypted()) {
            $this->decryptFile($this->encryptedPath, $this->key, $this->decryptedPath);

            $f = fopen($this->encryptedPath, 'w');
            fwrite($f, 'decrypted');
            fclose($f);
        }
    }

    public function encrypt()
    {
        if (!$this->isEncrypted()) {
            $this->encryptFile($this->decryptedPath, $this->key, $this->encryptedPath);

            $f = fopen($this->decryptedPath, 'w');
            fwrite($f, 'encrypted');
            fclose($f);
        }
    }

    /**
     * Get current encryption status of table.
     *
     * @return bool Encryption status of the table
     */
    public function isEncrypted()
    {
        if (!file_exists($this->encryptedPath)) {
            return false;
        } elseif (file_get_contents($this->encryptedPath) == 'decrypted') {
            return false;
        }
        if (file_get_contents($this->decryptedPath) == 'encrypted') {
            return true;
        }

        return false;
    }

    /*
     * Encrypt and decrypt file using smaller file chunks
     *
     * @source https://secure.php.net/manual/de/function.openssl-encrypt.php#120141
     */

    /**
     * Encrypt the passed file and saves the result in a new file with ".enc" as suffix.
     *
     * @param string $source Path to file that should be encrypted
     * @param string $key    The key used for the encryption
     * @param string $dest   File name where the encryped file should be written to.
     *
     * @return string|false Returns the file name that has been created or FALSE if an error occured
     */
    public function encryptFile($source, $key, $dest)
    {
        $key = substr(sha1($key, true), 0, 16);
        $iv = openssl_random_pseudo_bytes(16);

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            // Put the initialzation vector to the beginning of the file
            fwrite($fpOut, $iv);
            if ($fpIn = fopen($source, 'rb')) {
                while (!feof($fpIn)) {
                    $plaintext = fread($fpIn, 16 * 10000);
                    $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                    // Use the first 16 bytes of the ciphertext as the next initialization vector
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

        return $error ? false : $dest;
    }

    /**
     * Dencrypt the passed file and saves the result in a new file, removing the
     * last 4 characters from file name.
     *
     * @param string $source Path to file that should be decrypted
     * @param string $key    The key used for the decryption (must be the same as for encryption)
     * @param string $dest   File name where the decryped file should be written to.
     *
     * @return string|false Returns the file name that has been created or FALSE if an error occured
     */
    public function decryptFile($source, $key, $dest)
    {
        $key = substr(sha1($key, true), 0, 16);

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            if ($fpIn = fopen($source, 'rb')) {
                // Get the initialzation vector from the beginning of the file
                $iv = fread($fpIn, 16);
                while (!feof($fpIn)) {
                    // we have to read one block more for decrypting than for encrypting
                    $ciphertext = fread($fpIn, 16 * (10000 + 1));
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

        return $error ? false : $dest;
    }
}
