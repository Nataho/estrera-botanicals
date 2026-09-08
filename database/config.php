<?php
// pdo connection setup
require_once __DIR__ . '/../config.php';

function get_db_connection(): PDO {
    static $pdo = null;

    // reuse existing connection so we don't spam new ones
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Database connection error: ' . $e->getMessage());
        }
    }
    
    return $pdo;
}

// connect once and use anywhere
$pdo = get_db_connection();
$dbcon = $pdo; // keep for now so old code won't break lol
