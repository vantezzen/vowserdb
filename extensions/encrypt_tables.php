<?php
class encrypt_tables extends vowserdb
{
    // Set triggers
    public static function init()
    {
        vowserdb::listen('onTableAccessEnd', function () {
            if (vowserdb::$encrypt) {
                self::encrypt($table);
                self::encryptbackup($table);
            }
        });

        vowserdb::listen('onTableAccessBegin', function () {
            if (vowserdb::$encrypt) {
                self::decrypt($table);
                self::decryptbackup($table);
            }
        });
    }

    public static function encrypt($table)
    {
        $encryptfile = self::$folder.$table.'.encrypt'.self::$file_extension;
        $originalfile = self::$folder.$table.self::$file_extension;
        $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
        if (!file_exists($originalfile)) {
            return false;
        }
        if (file_get_contents($originalfile) == "encr") {
            return array("error" => "Already encrypted");
        }
        self::encryptFile($originalfile, $key, $encryptfile);
        $f = fopen($originalfile, 'w');
        fwrite($f, 'encr');
        fclose($f);
    }

    public static function encryptbackup($table)
    {
        $encryptfile = self::$folder.$table.'.backup.encrypt'.self::$file_extension;
        $originalfile = self::$folder.$table.'.backup'.self::$file_extension;
        $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
        if (!file_exists($originalfile)) {
            return false;
        }
        if (file_get_contents($originalfile) == "encr") {
            return array("error" => "Already encrypted");
        }
        self::encryptFile($originalfile, $key, $encryptfile);
        $f = fopen($originalfile, 'w');
        fwrite($f, 'encr');
        fclose($f);
    }

    private static function decrypt($table)
    {
        $encryptfile = self::$folder.$table.'.encrypt'.self::$file_extension;
        $originalfile = self::$folder.$table.self::$file_extension;
        $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
        if (!file_exists($encryptfile)) {
            return array("error" => "Already decrypted");
        }
        self::decryptFile($encryptfile, $key, $originalfile);
        unlink($encryptfile);
    }

    private static function decryptbackup($table)
    {
        $encryptfile = self::$folder.$table.'.backup.encrypt'.self::$file_extension;
        $originalfile = self::$folder.$table.'.backup'.self::$file_extension;
        $key = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';
        if (!file_exists($encryptfile)) {
            return array("error" => "Already decrypted");
        }
        self::decryptFile($encryptfile, $key, $originalfile);
        unlink($encryptfile);
    }

    private static function encryptFile($source, $key, $dest)
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
    private static function decryptFile($source, $key, $dest)
    {
        $key = substr(sha1($key, true), 0, 16);

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            if ($fpIn = fopen($source, 'rb')) {
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

        return $error ? false : $dest;
    }
}
encrypt_tables::init();
