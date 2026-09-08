<?php 
session_start();

require_once '../config.php';
require_once '../functions/form_handler.php';

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . BASE_URL . 'shop');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Estrera Botanicals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php require ROOT_DIR . 'components/base_css.php'; ?>
</head>
<body class="auth-page">
    <?php require ROOT_DIR . 'components/header.php'; ?>

    <main class="auth-page-container">
        <div class="auth-card">
            <div class="auth-header">
                <span class="brand-badge">Pure Botanical Care</span>
                <h1>Welcome Back</h1>
                <p>Sign in to manage your orders and skincare essentials</p>
            </div>

            <?php if (!empty($login_error)): ?>
                <div class="auth-alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($login_error) ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="request_type" value="login">

                <div class="auth-form-group">
                    <label for="user_input">Username or Email</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-user auth-input-icon"></i>
                        <input type="text" id="user_input" name="user_input" placeholder="Enter username or email" value="<?= htmlspecialchars($_POST['user_input'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="password">Password</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-lock auth-input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" name="login" class="auth-btn-submit">Sign In</button>
            </form>

            <div class="auth-footer-nav">
                Don't have an account yet?
                <a href="<?= BASE_URL ?>signup">Create an Account</a>
            </div>
        </div>
    </main>

    <?php require ROOT_DIR . 'components/footer.php'; ?>
</body>
</html>

