<?php 
session_start();

require_once '../config.php';
require_once '../functions/form_handler.php';

?>


<?php if (!empty($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form method="POST">
        <input type="hidden" name="request_type" value="login">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required><br><br>
        
        <button type="submit" name="login">Login</button><br><br>
        <a href="<?= BASE_URL ?>signup">No Account?</a>
    </form>
</body>
</html>
