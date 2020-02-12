<?php
/*

composer require defuse/php-encryption
vendor/bin/generate-defuse-key > key.txt
php example.php

*/
require 'vendor/autoload.php';

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

$key = Key::loadFromAsciiSafeString(file_get_contents('key.txt'));
$ssn = '111-11-1111';
$ciphered = Crypto::encrypt($ssn, $key);

echo "ciphered: $ciphered \n";
try {
   echo "deciphered: " . Crypto::decrypt($ciphered, $key) . "\n";
} catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
}
