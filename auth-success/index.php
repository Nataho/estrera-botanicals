<?php
require_once '../config.php';

$auth_type = $_GET['type'] ?? 'login';
$user_name = $_SESSION['user_name'] ?? '';

if ($auth_type === 'signup') {
    $badge_text = "Welcome to Estrera Botanicals";
    $heading = "Account Created!";
    $subtext = !empty($user_name) 
        ? "Welcome, <strong>" . htmlspecialchars($user_name) . "</strong>! Your account has been registered successfully."
        : "Your account has been registered successfully. Welcome to pure, botanical skincare.";
} else {
    $badge_text = "Signed In Successfully";
    $user_role = $_SESSION['user_role'] ?? 'customer';
    
    if ($user_role === 'admin') {
        $heading = "Admin Dashboard Ready!";
        $subtext = !empty($user_name)
            ? "Welcome back, <strong>" . htmlspecialchars($user_name) . "</strong>! Ready to review today's botanical orders and manage the shop inventory?"
            : "Welcome back, Admin! Ready to manage the shop today?";
    } else {
        $heading = "Welcome Back!";
        $subtext = !empty($user_name)
            ? "Hello, <strong>" . htmlspecialchars($user_name) . "</strong>! You have successfully signed in to your account."
            : "You have successfully signed in to your account.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $heading ?> | Estrera Botanicals</title>
    <?php require ROOT_DIR . 'components/base_css.php'; ?>
</head>
<body class="auth-page">
    <?php require ROOT_DIR . 'components/header.php'; ?>

    <main class="auth-page-container">
        <div class="auth-card" style="text-align: center;">
            <div class="auth-success-icon">
                <i class="fa-solid fa-check"></i>
            </div>

            <div class="auth-header" style="margin-bottom: 20px;">
                <span class="brand-badge"><?= $badge_text ?></span>
                <h1><?= $heading ?></h1>
                <p><?= $subtext ?></p>
            </div>

            <div class="auth-success-actions">
                <a href="<?= BASE_URL ?>shop" class="auth-btn-submit" style="display: block; text-decoration: none; text-align: center;">
                    Continue to Shop
                </a>
                <a href="<?= BASE_URL ?>" class="auth-btn-secondary">
                    Go to Homepage
                </a>
            </div>

            <p class="auth-redirect-notice">
                Automatically continuing in <span id="countdown" style="font-weight: 700; color: var(--palette-dark-green);">5</span>s...
            </p>
        </div>
    </main>

    <?php require ROOT_DIR . 'components/footer.php'; ?>

    <script>
        let timeLeft = 10;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            timeLeft--;
            if (countdownEl) countdownEl.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.href = '<?= BASE_URL ?>shop';
            }
        }, 1000);
    </script>
</body>
</html>
