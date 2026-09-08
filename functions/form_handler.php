<?php
// form handler - catches posts and routes to the right function

require_once ROOT_DIR . 'database/config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'functions/validation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = $_POST['request_type'] ?? '';

    switch ($request_type) {
        case 'login':
            $u_input = trim($_POST['user_input'] ?? '');
            $u_pass  = $_POST['password'] ?? '';

            if (empty($u_input) || empty($u_pass)) {
                $login_error = "Please fill in all fields.";
                break;
            }

            if (current_user()->login($u_input, $u_pass)) {
                header('Location: ../auth-success?type=login');
                exit;
            } else {
                $login_error = "Invalid username or password.";
            }
            break;

        case 'signup':
            $u_name  = trim($_POST['username'] ?? '');
            $u_email = trim($_POST['email'] ?? '');
            $u_pass  = $_POST['password'] ?? '';
            $u_vpass = $_POST['valid_password'] ?? '';

            // check in memory first so we don't bother the database
            if (empty($u_name) || empty($u_email) || empty($u_pass)) {
                $signup_err = "All fields are required.";
                break;
            }

            $email_err = validateEmailFormat($u_email);
            if ($email_err !== null) {
                $signup_err = $email_err;
                break;
            }

            if ($u_pass !== $u_vpass) {
                $signup_err = "Passwords do not match.";
                break;
            }

            // create account and log them straight in
            $signup_result = register_user($pdo, $u_name, $u_email, $u_pass);

            if ($signup_result === true) {
                current_user()->login($u_name, $u_pass);
                header('Location: ../auth-success?type=signup');
                exit;
            } else {
                $signup_err = $signup_result;
            }
            break;
    }
}
?>