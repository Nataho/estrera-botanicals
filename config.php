<?php
$dir = str_replace('\\', '/', __DIR__);
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

$web_path = str_replace($docRoot, '', $dir);

define('BASE_URL', $web_path . '/');
define('ROOT_DIR', __DIR__ . '/');

// Establish the connection
$dbcon = mysqli_connect("localhost", "root", "", "eb_shop");

// Halt execution if the connection fails
if (!$dbcon) {
    die("Connection failed: " . mysqli_connect_error());
}
?>