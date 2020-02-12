<?php

/*
Example of using a base directory and preventing
breaking out of the directory with user supplied paths
using packages from composer/packagist
*/

require 'vendor/autoload.php';

use Webmozart\PathUtil\Path;

$base     =  Path::canonicalize('~/data/');
$relative = "/../../..\\/\\../\\.\\./etc/passwd";

$joint    = Path::join($base, $relative);
$resolved = Path::makeAbsolute($relative, $base);

// If resolved path does not have the same base path as the base
// then the the user was trying to traverse into parent directories
if (!Path::isBasePath($base, $resolved)) {
    $resolved = Path::join($base, $resolved);
}

echo $resolved . "\n";
