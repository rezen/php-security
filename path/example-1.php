<?php 
/*
Example of using a base directory and preventing
breaking out of the directory with user supplied paths
without any dependancies
*/

function resolvePath($filename)
{
    $filename = str_replace('//', '/', $filename);
    $parts    = explode('/', $filename);
    $resolved = array_reduce($parts, function($path, $part) {
        if ($part === '.') {
            return $path;
        }
        if ($part === '..') {
            array_pop($path);
            return $path;
        }
        $path[] = $part;
        return $path;
    }, []);
    return implode('/', $resolved);
}

function resolveWithBasepath($root, $path) 
{   
    // Add trailing slash
    $root = $root[strlen($root) - 1] === '/' ? $root : $root . '/';
    $root = str_replace('\\', '/', $root);
    $path = str_replace('\\', '/', $path);
	$info = pathinfo($root . $path);
    $dir  = resolvePath($info['dirname']);
    
    // If the directory that gets resolved with does not 
    // start with the root ... force it to start in root
    if (strpos($dir, $root) !== 0) {
        $dir = $root . $dir;
    }
    return $dir . '/' . $info['filename'];
}

// Directory with data user is allowed to view
$base = "/var/app/data";

// An attempt by malicious user to access data they should not
$relative =  "../../../../../..//etc/passwd";

$resolved = resolveWithBasepath($base, $relative);


echo $resolved . "\n";
