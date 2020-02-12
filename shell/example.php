<?php
/*
There are numerous ways to execute commands in PHP, none of 

https://github.com/kacperszurek/exploits/blob/master/GitList/exploit-bypass-php-escapeshellarg-escapeshellcmd.md

composer require symfony/process
php example.php
*/

require 'vendor/autoload.php';



use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

$target = "http://example.com";
$url = "--directory-prefix=/var/www/html $target";


// if (!filter_var($url, FILTER_VALIDATE_URL)) {
//     echo "! Invalid url\n";
//     die;
// }

echo "\n---- escapeshellcmd ----\n";
echo "exec: ". escapeshellcmd('wget '. $url) . "\n\n";
system(escapeshellcmd('wget '. $url));

echo "\n---- escapeshellarg ----\n";
echo "exec: wget " . escapeshellarg($url) . "\n\n";
system('wget '. escapeshellarg($url));


// The preferred way ...
echo "\n---- symfony ----\n";

$process = new Process(['wget', $url]);
$process->run();
echo "exec: " . (function() {
    return $this->processInformation['command'];
})->call($process) . "\n\n";

echo $process->getOutput();
echo $process->getErrorOutput();
