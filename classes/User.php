<?php
// User model / class

class User {
    private PDO $pdo;

    // user info (null if guest)
    public ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;
    public ?string $role = null;
    public ?string $profile_pic = null;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->reload_from_session();
    }

    // grab user data from session if logged in
    public function reload_from_session(): void {
        if (!empty($_SESSION['logged_in'])) {
            $this->id          = $_SESSION['user_id'] ?? null;
            $this->name        = $_SESSION['user_name'] ?? null;
            $this->role        = $_SESSION['user_role'] ?? null;
            $this->profile_pic = $_SESSION['user_profile_pic'] ?? null;
        }
    }

    // quick check if someone is logged in
    public function is_logged_in(): bool {
        return !empty($this->id);
    }

    // check if user is admin
    public function is_admin(): bool {
        return $this->role === 'admin';
    }

    // authenticate and populate object + session
    public function login(string $user_input, string $password): bool {
        // can log in using either email or username
        $field = filter_var($user_input, FILTER_VALIDATE_EMAIL) ? 'user_email' : 'user_name';
        $sql = "SELECT * FROM users WHERE {$field} = ? LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_input]);
        $data = $stmt->fetch();

        if ($data && password_verify($password, $data['user_password'])) {
            $this->id          = (int) $data['user_id'];
            $this->name        = $data['user_name'];
            $this->email       = $data['user_email'];
            $this->role        = $data['user_role'];
            $this->profile_pic = $data['user_profile_pic'] ?? null;

            // save to session
            $_SESSION['logged_in']        = true;
            $_SESSION['user_id']          = $this->id;
            $_SESSION['user_name']        = $this->name;
            $_SESSION['user_role']        = $this->role;
            $_SESSION['user_profile_pic'] = $this->profile_pic;
            $_SESSION['just_logged_in']   = true;

            return true;
        }

        return false;
    }

    // wipe session and reset object state
    public function logout(): void {
        $this->id = null;
        $this->name = null;
        $this->email = null;
        $this->role = null;
        $this->profile_pic = null;

        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    // sync object with database directly
    public function refresh(): void {
        if (!$this->id) return;

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$this->id]);
        $data = $stmt->fetch();

        if ($data) {
            $this->name        = $data['user_name'];
            $this->email       = $data['user_email'];
            $this->role        = $data['user_role'];
            $this->profile_pic = $data['user_profile_pic'] ?? null;

            $_SESSION['user_name']        = $this->name;
            $_SESSION['user_role']        = $this->role;
            $_SESSION['user_profile_pic'] = $this->profile_pic;
        }
    }

    // update username
    public function update_username(string $new_username): true|string {
        $trimmed = trim($new_username);
        if (strlen($trimmed) < 3) {
            return "Username must be at least 3 characters long.";
        }

        // check if taken by someone else
        $check = $this->pdo->prepare("SELECT user_id FROM users WHERE user_name = ? AND user_id != ? LIMIT 1");
        $check->execute([$trimmed, $this->id]);
        if ($check->fetch()) {
            return "This username is already taken.";
        }

        $stmt = $this->pdo->prepare("UPDATE users SET user_name = ? WHERE user_id = ?");
        $stmt->execute([$trimmed, $this->id]);

        $this->name = $trimmed;
        $_SESSION['user_name'] = $trimmed;

        return true;
    }

    // update password
    public function update_password(string $current_password, string $new_password): true|string {
        if (strlen($new_password) < 8) {
            return "New password must be at least 8 characters.";
        }

        // verify current password
        $stmt = $this->pdo->prepare("SELECT user_password FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$this->id]);
        $data = $stmt->fetch();

        if (!$data || !password_verify($current_password, $data['user_password'])) {
            return "Current password does not match.";
        }

        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $this->pdo->prepare("UPDATE users SET user_password = ? WHERE user_id = ?");
        $update->execute([$new_hash, $this->id]);

        return true;
    }

    // update profile picture filename
    public function update_profile_pic(string $filename): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET user_profile_pic = ? WHERE user_id = ?");
        $res = $stmt->execute([$filename, $this->id]);

        if ($res) {
            $this->profile_pic = $filename;
            $_SESSION['user_profile_pic'] = $filename;
        }

        return $res;
    }

    // delete user account
    public function delete_account(): bool {
        if (!$this->id) return false;

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $res = $stmt->execute([$this->id]);

        if ($res) {
            $this->logout();
        }

        return $res;
    }
}
