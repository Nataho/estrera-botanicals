<?php
$dir = str_replace('\\', '/', __DIR__);
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);

$web_path = str_replace($docRoot, '', $dir);

define('BASE_URL', $web_path . '/');
define('ROOT_DIR', __DIR__ . '/');

// db credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'eb_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// start session everywhere so we don't have to call session_start() on every single page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>