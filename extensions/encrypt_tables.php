<?php
//TODO: make it work
class encrypt_tables extends vowserdb
{
  public static $file_encryption_blocks = 10000;
  private static $defaultkey = '20E4A879C13ADB03A74324A8B9792C10';

  // Set triggers
  public static function init()
  {
      vowserdb::listen('afterTableAccess', function ($table) {
          self::encrypt($table);
      });

      vowserdb::listen('beforeTableAccess', function ($table) {
          self::decrypt($table);
      });

      vowserdb::register_postfix('.encrypt');
      vowserdb::register_postfix('.backup.encrypt');
  }

  public static function encrypt($table) {
    $tablefile = vowserdb::get_table_path($table);
    $encrypted = vowserdb::get_table_path($table . '.encrypt');
    $backupfile= vowserdb::get_table_path($table . '.backup');
    $backupencr= vowserdb::get_table_path($table . '.backup.encrypt');
    $encrypkey = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';

    // Encrypt table file
    if (!file_exists($tablefile)) {
      return false;
    }
    if (file_get_contents($tablefile) == "ENCRYPTED") {
        return array("error" => "Already encrypted");
    }
    self::encryptFile($tablefile, $encrypkey, $encrypted);

    // Check if backup file exists, if yes encrypt it
    if (file_exists($backupfile) && file_get_contents($backupfile) !== "ENCRYPTED") {
      //self::encryptFile($backupfile, $encrypkey, $backupencr);
    }
  }

  public static function decrypt($table) {
    $tablefile = vowserdb::get_table_path($table);
    $encrypted = vowserdb::get_table_path($table . '.encrypt');
    $backupfile= vowserdb::get_table_path($table . '.backup');
    $backupencr= vowserdb::get_table_path($table . '.backup.encrypt');
    $encrypkey = defined('VOWSERDBENCRKEY') ? VOWSERDBENCRKEY : '20E4A879C13ADB03A74324A8B9792C10';

    // Decrypt table file
    if (!file_exists($encrypted)) {
      return false;
    }
    if (file_get_contents($encrypted) == "DECRYPTED") {
        return array("error" => "Already decrypted");
    }
    self::decryptfile($encrypted, $encrypkey, $tablefile);

    // Check if backup file exists, if yes decrypt it
    if (file_exists($backupencr) && file_get_contents($backupencr) !== "DECRYPTED") {
      self::decryptfile($backupencr, $encrypkey, $backupfile);
    }
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

        if (!$error) {
          $f = fopen($source, 'w');
          fwrite($f, 'ENCRYPTED');
          fclose($f);
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
        if (!$error) {
          $f = fopen($source, 'w');
          fwrite($f, 'DECRYPTED');
          fclose($f);
        }

        return $error ? false : $dest;
    }
}
