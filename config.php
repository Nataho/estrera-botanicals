<?php
// defining variables
$dir = str_replace('\\', '/', __DIR__);
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

$web_path = str_replace($docRoot, '', $dir);

define('BASE_URL', $web_path . '/'); //project directory
define('ROOT_DIR', __DIR__ . '/');//php server directory
?>