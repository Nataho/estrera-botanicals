<?php
require_once '../config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'classes/User.php';
require_once ROOT_DIR . 'classes/Image.php';
require_once ROOT_DIR . 'classes/UploadManager.php';
require_once ROOT_DIR . 'classes/ConfirmModal.php';
require_once ROOT_DIR . 'classes/ConfirmTypeModal.php';
require_once ROOT_DIR . 'classes/SuccessModal.php';

// Guests cannot view account page
require_auth('login');

$user = current_user();
$uploader = new UploadManager($pdo);
$img_helper = new Image($pdo);

$message = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Profile Picture
    if ($action === 'update_avatar' && !empty($_FILES['avatar']['name'])) {
        $result = $uploader->upload($_FILES['avatar'], $user->id);
        if (is_array($result)) {
            $user->update_profile_pic($result['filename']);
            $message = "Profile picture updated successfully!";
        } else {
            $error = "Upload failed: " . $result;
        }
    }

    // 2. Change Username
    elseif ($action === 'change_username') {
        $new_username = $_POST['username'] ?? '';
        $res = $user->update_username($new_username);
        if ($res === true) {
            $message = "Username updated to '" . htmlspecialchars($user->name) . "'!";
        } else {
            $error = $res;
        }
    }

    // 3. Change Password
    elseif ($action === 'change_password') {
        $curr_pass = $_POST['current_password'] ?? '';
        $new_pass  = $_POST['new_password'] ?? '';
        $conf_pass = $_POST['confirm_password'] ?? '';

        if ($new_pass !== $conf_pass) {
            $error = "New passwords do not match.";
        } else {
            $res = $user->update_password($curr_pass, $new_pass);
            if ($res === true) {
                $message = "Password updated successfully!";
            } else {
                $error = $res;
            }
        }
    }

    // 4. Logout
    elseif ($action === 'logout') {
        $user->logout();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    // 5. Delete Account
    elseif ($action === 'delete_account') {
        if ($user->delete_account()) {
            header('Location: ' . BASE_URL . 'signup');
            exit;
        } else {
            $error = "Failed to delete account. Please try again.";
        }
    }
}

// Compute avatar URL
$avatar_url = null;
if (!empty($user->profile_pic)) {
    $avatar_data = $img_helper->get($user->profile_pic);
    if ($avatar_data && $avatar_data['exists']) {
        $avatar_url = $avatar_data['url'];
    }
}

// Instantiate Success Modal if there is a success message
$successModal = null;
if (!empty($message)) {
    $successModal = new SuccessModal(
        $message,
        "Continue",
        "accountSuccessModal"
    );
}

// Instantiate Confirm Modals
$logoutModal = new ConfirmModal(
    "Are you sure you want to log out?",
    ["Log Out", "Cancel"],
    "logoutModal",
    BASE_URL . "account/",
    "POST",
    ["action" => "logout"],
    "secondary"
);

// Word needed to confirm account deletion based on session username
$delete_confirmation_word = 'DELETE_' . strtoupper($user->name ?? 'USER');

$deleteModal = new ConfirmTypeModal(
    "Are you sure you want to permanently delete your account?",
    $delete_confirmation_word,
    "deleteModal",
    BASE_URL . "account/",
    "POST",
    ["action" => "delete_account"],
    "Delete My Account",
    "Cancel"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account | Estrera Botanicals</title>
    <?php require ROOT_DIR . 'components/base_css.php'; ?>
    <link rel="stylesheet" href="style.css">
</head>
<body class="account-page">
    <?php require ROOT_DIR . 'components/header.php'; ?>

    <main class="account-container">
        <div class="account-header-bar">
            <h1>Account Settings</h1>
            <button type="button" class="account-btn secondary" onclick="ConfirmModal.open('logoutModal')">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> Log Out
            </button>
        </div>

        <?php if ($message): ?>
            <div class="account-alert ok">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="account-alert err">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <div class="account-grid">
            <!-- Sidebar / Profile Card -->
            <aside class="profile-card">
                <div class="profile-avatar-container">
                    <?php if ($avatar_url): ?>
                        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Profile Picture" class="profile-avatar">
                    <?php else: ?>
                        <div class="profile-avatar-placeholder">
                            <i class="fa-solid fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h3><?= htmlspecialchars($user->name ?? 'User') ?></h3>
                <p class="user-email"><?= htmlspecialchars($user->email ?? '') ?></p>
                <span class="profile-badge"><?= htmlspecialchars($user->role ?? 'Customer') ?></span>

                <!-- Avatar Change Form -->
                <form class="avatar-upload-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_avatar">
                    <label for="avatar_input">Change Profile Picture</label>
                    <input type="file" id="avatar_input" name="avatar" accept="image/*" required style="font-size: 0.8rem; margin-bottom: 8px;" onchange="this.form.submit()">
                </form>
            </aside>

            <!-- Main Account Panels -->
            <div class="account-panels">
                <!-- Username Panel -->
                <section class="account-panel">
                    <h2>Username</h2>
                    <p class="subtext">Change your public display username.</p>
                    <form class="account-form" method="POST">
                        <input type="hidden" name="action" value="change_username">
                        <div class="account-field">
                            <label for="username">New Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user->name ?? '') ?>" required minlength="3">
                        </div>
                        <button type="submit" class="account-btn">Update Username</button>
                    </form>
                </section>

                <!-- Password Panel -->
                <section class="account-panel">
                    <h2>Change Password</h2>
                    <p class="subtext">Ensure your account is using a long, random password.</p>
                    <form class="account-form" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="account-field">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="account-field">
                            <label for="new_password">New Password (at least 8 characters)</label>
                            <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="account-field">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" class="account-btn">Update Password</button>
                    </form>
                </section>

                <!-- Danger Zone (Delete Account) -->
                <section class="account-panel danger-zone">
                    <h2>Delete Account</h2>
                    <p class="subtext">Permanently remove your account and all associated profile data.</p>
                    <button type="button" class="account-btn danger" onclick="ConfirmTypeModal.open('deleteModal')">
                        <i class="fa-solid fa-trash"></i> Delete Account
                    </button>
                </section>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <?php 
    $logoutModal->render();
    $deleteModal->render();
    if ($successModal) {
        $successModal->render();
    }
    ?>

    <?php require ROOT_DIR . 'components/footer.php'; ?>

    <script>
        const ConfirmModal = {
            open(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'flex';
                }
            },
            close(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        };

        const ConfirmTypeModal = {
            open(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'flex';
                    const input = document.getElementById(modalId + '_input');
                    const btn = document.getElementById(modalId + '_btn');
                    if (input) {
                        input.value = '';
                        setTimeout(() => input.focus(), 50);
                    }
                    if (btn) {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                        btn.style.cursor = 'not-allowed';
                    }
                }
            },
            close(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                }
            },
            check(modalId, expectedWord) {
                const input = document.getElementById(modalId + '_input');
                const btn = document.getElementById(modalId + '_btn');
                if (input && btn) {
                    if (input.value.trim() === expectedWord) {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.style.cursor = 'pointer';
                    } else {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                        btn.style.cursor = 'not-allowed';
                    }
                }
            }
        };

        const SuccessModal = {
            open(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'flex';
                }
            },
            close(modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.style.display = 'none';
                }
            }
        };

        // Close on escape key or clicking backdrop
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.confirm-modal-overlay').forEach(m => m.style.display = 'none');
            }
        });

        document.querySelectorAll('.confirm-modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.style.display = 'none';
                }
            });
        });

        <?php if ($successModal): ?>
        // Auto-show success modal
        document.addEventListener('DOMContentLoaded', () => {
            SuccessModal.open('accountSuccessModal');
        });
        <?php endif; ?>
    </script>
</body>
</html>
