<?php
// auth helpers & route guards
require_once ROOT_DIR . 'database/config.php';
require_once ROOT_DIR . 'classes/User.php';

// get the current logged-in user or guest
function current_user(): User {
    global $pdo;
    static $user = null;

    if ($user === null) {
        $user = new User($pdo);
    }

    return $user;
}

// kick logged-in users away from login/signup
function require_guest(string $redirect_path = 'shop'): void {
    if (current_user()->is_logged_in()) {
        header('Location: ' . BASE_URL . $redirect_path);
        exit;
    }
}

// kick guests away from protected pages
function require_auth(string $redirect_path = 'login'): void {
    if (!current_user()->is_logged_in()) {
        header('Location: ' . BASE_URL . $redirect_path);
        exit;
    }
}

// only allow specific roles (e.g. 'admin')
function require_role(string $role): void {
    $user = current_user();
    if (!$user->is_logged_in() || $user->role !== $role) {
        header('Location: ' . BASE_URL . 'error.php?code=403');
        exit;
    }
}

// register new account with email uniqueness check
function register_user(PDO $pdo, string $username, string $email, string $password) {
    try {
        // see if email already taken
        $check = $pdo->prepare("SELECT user_id FROM users WHERE user_email = ? LIMIT 1");
        $check->execute([$email]);

        if ($check->fetch()) {
            return "An account using this email already exists.";
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare("INSERT INTO users (user_name, user_email, user_password) VALUES (?, ?, ?)");
        $insert->execute([$username, $email, $hashed]);

        return true;
    } catch (PDOException $e) {
        return "Database error during registration: " . $e->getMessage();
    }
}
?>
