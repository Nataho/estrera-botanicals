<?php
require_once '../config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'functions/form_handler.php';

require_guest('shop');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Estrera Botanicals</title>
    <?php require ROOT_DIR . 'components/base_css.php'; ?>
</head>
<body class="auth-page">
    <?php require ROOT_DIR . 'components/header.php'; ?>

    <main class="auth-page-container">
        <div class="auth-card">
            <div class="auth-header">
                <span class="brand-badge">Join Estrera Botanicals</span>
                <h1>Create Account</h1>
                <p>Experience clean, botanical body care formulated for real results</p>
            </div>

            <?php if (!empty($signup_err)): ?>
                <div class="auth-alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($signup_err) ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="request_type" value="signup">

                <div class="auth-form-group">
                    <label for="username">Username</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-user auth-input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="email">Email Address</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-envelope auth-input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="password">Password</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-lock auth-input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Create a strong password" required>
                    </div>
                </div>

                <div class="auth-form-group">
                    <label for="valid_password">Confirm Password</label>
                    <div class="auth-input-wrapper">
                        <i class="fa-solid fa-shield-halved auth-input-icon"></i>
                        <input type="password" id="valid_password" name="valid_password" placeholder="Re-enter your password" required>
                    </div>
                </div>

                <button type="submit" name="signup" class="auth-btn-submit">Register Account</button>
            </form>

            <div class="auth-footer-nav">
                Already have an account?
                <a href="<?= BASE_URL ?>login">Sign in</a>
            </div>
        </div>
    </main>

    <?php require ROOT_DIR . 'components/footer.php'; ?>
</body>
</html>