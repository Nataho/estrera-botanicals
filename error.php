<?php
require_once 'config.php';

// Get the error code from the URL, default to 404 if none is provided
$error_code = $_GET['code'] ?? '404';

// Define the custom messages for different scenarios
$error_messages = [
    '403' => [
        'title' => '403 - Forbidden',
        'desc' => 'Nice try! You do not have the required permissions to view this page.'
    ],
    '404' => [
        'title' => '404 - Not Found',
        'desc' => 'We dug everywhere, but the page you are looking for has been uprooted.'
    ]
];

// Fallback to a generic error if they give a weird code
$title = $error_messages[$error_code]['title'] ?? 'Something went wrong';
$desc = $error_messages[$error_code]['desc'] ?? 'An unknown error occurred.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <?php require_once ROOT_DIR . 'components/base_css.php' ?>
</head>
<body>
    <?php require_once ROOT_DIR . 'components/header.php' ?>
    
    <div style="text-align: center; padding: 100px 20px;">
        <h1 style="font-size: 3rem; color: #d9534f;"><?= $title ?></h1>
        <p style="font-size: 1.2rem;"><?= $desc ?></p>
        <br>
        <a href="<?= BASE_URL ?>" class="btn">Return to Safety</a>
    </div>
</body>
</html>