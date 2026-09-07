<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';
require_once '../functions/form_handler.php';

?>

<?php if (!empty($signup_err)) echo "<p style='color:red;'>$signup_err</p>"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <?php require ROOT_DIR . 'components/base_css.php' ?>


</head>
<body>
    <h1>sign up</h1>
    <form method="POST">
        <input type="hidden" name="request_type" value="signup">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        
        <button type="submit" name="signup">Sign Up</button>
        <a href="<?= BASE_URL ?>login"> Already have an Account? </a>
    </form>
</body>
</html>