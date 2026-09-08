<?php
require_once '../config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'classes/User.php';
require_once ROOT_DIR . 'classes/Image.php';
require_once ROOT_DIR . 'classes/UploadManager.php';
require_once ROOT_DIR . 'classes/ConfirmModal.php';
require_once ROOT_DIR . 'classes/ConfirmTypeModal.php';
require_once ROOT_DIR . 'classes/AlertModal.php';

// guests cannot view account page
require_auth('login');

$user = current_user();
$uploader = new UploadManager($pdo);
$img_helper = new Image($pdo);

$message = '';
$error = '';

// determine active tab
$allowed_tabs = ['general', 'logs'];
if ($user->is_admin()) {
    $allowed_tabs = array_merge(['dashboard'], $allowed_tabs, ['users', 'stock', 'add_product']);
}
$default_tab = $user->is_admin() ? 'dashboard' : 'general';
$active_tab = $_GET['tab'] ?? $default_tab;
if (!in_array($active_tab, $allowed_tabs)) {
    $active_tab = $default_tab;
}

// handle post actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. update profile picture
    if ($action === 'update_avatar' && !empty($_FILES['avatar']['name'])) {
        $result = $uploader->upload($_FILES['avatar'], $user->id);
        if (is_array($result)) {
            $user->update_profile_pic($result['filename']);
            $message = "Profile picture updated successfully!";
        } else {
            $error = "Upload failed: " . $result;
        }
    }

    // 2. change username
    elseif ($action === 'change_username') {
        $new_username = $_POST['username'] ?? '';
        $res = $user->update_username($new_username);
        if ($res === true) {
            $message = "Username updated to '" . htmlspecialchars($user->name) . "'!";
        } else {
            $error = $res;
        }
    }

    // 3. change password
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

    // 4. logout
    elseif ($action === 'logout') {
        $user->logout();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }

    // 5. delete account (admins are not allowed to delete themselves)
    elseif ($action === 'delete_account') {
        if ($user->is_admin()) {
            $error = "Admin accounts cannot be deleted.";
        } elseif ($user->delete_account()) {
            header('Location: ' . BASE_URL . 'signup');
            exit;
        } else {
            $error = "Failed to delete account. Please try again.";
        }
    }

    // 6. admin: ban or unban user
    elseif ($action === 'toggle_user_ban' && $user->is_admin()) {
        $target_user_id = (int)($_POST['target_user_id'] ?? 0);
        $target_action  = $_POST['target_action'] ?? '';

        if ($target_user_id === (int)$user->id) {
            $error = "You cannot ban or unban your own account.";
        } elseif ($target_user_id > 0) {
            $new_role = ($target_action === 'unban') ? 'customer' : 'banned';
            $stmt = $pdo->prepare("UPDATE users SET user_role = ? WHERE user_id = ?");
            $res = $stmt->execute([$new_role, $target_user_id]);

            if ($res) {
                $status_word = ($new_role === 'banned') ? 'banned' : 'unbanned';
                $message = "User has been successfully {$status_word}.";
                $active_tab = 'users';
            } else {
                $error = "Failed to update user status.";
            }
        }
    }

    // 7. admin: update stock quantity
    elseif ($action === 'update_stock' && $user->is_admin()) {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $new_qty = (int)($_POST['stock_quantity'] ?? 0);

        if ($product_id > 0 && $new_qty >= 0) {
            $stmt = $pdo->prepare("UPDATE products SET product_stock_quantity = ? WHERE product_id = ?");
            $res = $stmt->execute([$new_qty, $product_id]);

            if ($res) {
                $message = "Stock quantity updated successfully!";
                $active_tab = 'stock';
            } else {
                $error = "Failed to update stock quantity.";
            }
        } else {
            $error = "Invalid product or quantity specified.";
        }
    }

    // 8. admin: add new product
    elseif ($action === 'add_product' && $user->is_admin()) {
        $p_name  = trim($_POST['product_name'] ?? '');
        $p_price = (float)($_POST['product_price'] ?? 0);
        $p_stock = (int)($_POST['product_stock_quantity'] ?? 0);
        $p_desc  = trim($_POST['product_description'] ?? '');
        $p_img   = '';

        if (empty($p_name) || $p_price < 0 || $p_stock < 0) {
            $error = "Please fill in all required product fields with valid numbers.";
            $active_tab = 'add_product';
        } else {
            // handle optional product image upload
            if (!empty($_FILES['product_image']['name'])) {
                $upload_res = $uploader->upload($_FILES['product_image'], $user->id);
                if (is_array($upload_res)) {
                    $p_img = $upload_res['filename'];
                } else {
                    $error = "Image upload failed: " . $upload_res;
                    $active_tab = 'add_product';
                }
            }

            if (empty($error)) {
                $stmt = $pdo->prepare("INSERT INTO products (product_name, product_description, product_price, product_stock_quantity, product_img) VALUES (?, ?, ?, ?, ?)");
                $res = $stmt->execute([$p_name, $p_desc, $p_price, $p_stock, $p_img]);

                if ($res) {
                    $message = "Product '{$p_name}' added to store inventory!";
                    $active_tab = 'stock';
                } else {
                    $error = "Failed to save product to database.";
                    $active_tab = 'add_product';
                }
            }
        }
    }

    // 9. admin: delete product and its stock
    elseif ($action === 'delete_product' && $user->is_admin()) {
        $product_id = (int)($_POST['product_id'] ?? 0);

        if ($product_id > 0) {
            // delete any order items referencing this product first, then the product itself
            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $res = $stmt->execute([$product_id]);

            if ($res && $stmt->rowCount() > 0) {
                $message = "Product has been permanently deleted.";
                $active_tab = 'stock';
            } else {
                $error = "Failed to delete product or product not found.";
                $active_tab = 'stock';
            }
        } else {
            $error = "Invalid product specified for deletion.";
            $active_tab = 'stock';
        }
    }
}

// compute avatar url
$avatar_url = null;
if (!empty($user->profile_pic)) {
    $avatar_data = $img_helper->get($user->profile_pic);
    if ($avatar_data && $avatar_data['exists']) {
        $avatar_url = $avatar_data['url'];
    }
}

// fetch data for tabs based on active view
$orders_list = [];
$admin_users_list = [];
$admin_products_list = [];
$recent_orders_dashboard = [];
$users_count = 0;
$products_count = 0;
$orders_count = 0;

if ($active_tab === 'dashboard' && $user->is_admin()) {
    $users_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $products_count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $orders_count = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    $stmt_orders = $pdo->query("SELECT o.*, u.user_name, u.user_email FROM orders o LEFT JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT 5");
    $recent_orders_dashboard = $stmt_orders->fetchAll();
} elseif ($active_tab === 'logs') {
    if ($user->is_admin()) {
        $stmt = $pdo->query("SELECT o.*, u.user_name, u.user_email FROM orders o LEFT JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
        $stmt->execute([$user->id]);
    }
    $orders_list = $stmt->fetchAll();
} elseif ($active_tab === 'users' && $user->is_admin()) {
    $q = trim($_GET['user_q'] ?? '');
    if (!empty($q)) {
        $stmt = $pdo->prepare("SELECT user_id, user_name, user_email, user_role, user_profile_pic FROM users WHERE user_name LIKE ? OR user_email LIKE ? ORDER BY user_id DESC");
        $stmt->execute(["%$q%", "%$q%"]);
    } else {
        $stmt = $pdo->query("SELECT user_id, user_name, user_email, user_role, user_profile_pic FROM users ORDER BY user_id DESC");
    }
    $admin_users_list = $stmt->fetchAll();
} elseif ($active_tab === 'stock' && $user->is_admin()) {
    $q = trim($_GET['stock_q'] ?? '');
    if (!empty($q)) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_name LIKE ? ORDER BY product_id DESC");
        $stmt->execute(["%$q%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY product_id DESC");
    }
    $admin_products_list = $stmt->fetchAll();
}

// show alert modal if there is a message or error
$alertModal = null;
if (!empty($message)) {
    $alertModal = new AlertModal($message, "success", "Continue", "accountAlertModal");
} elseif (!empty($error)) {
    $alertModal = new AlertModal($error, "error", "Okay", "accountAlertModal");
}

// instantiate confirm modals
$logoutModal = new ConfirmModal(
    "Are you sure you want to log out?",
    ["Log Out", "Cancel"],
    "logoutModal",
    BASE_URL . "account/",
    "POST",
    ["action" => "logout"],
    "secondary"
);

// word needed to confirm account deletion based on session username (only for regular users)
$deleteModal = null;
if (!$user->is_admin()) {
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
}

// confirm type modal for deleting a product (admin only, product_id set by js)
$deleteProductModal = null;
if ($user->is_admin()) {
    $deleteProductModal = new ConfirmTypeModal(
        "Are you sure you want to permanently delete this product? This cannot be undone.",
        "DELETE_PLANT_PRODUCT",
        "deleteProductModal",
        BASE_URL . "account/",
        "POST",
        ["action" => "delete_product", "product_id" => ""],
        "Delete Product",
        "Cancel"
    );
}
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
            <div>
                <h1>Account Settings</h1>
                <p style="margin: 4px 0 0 0; color: var(--palette-gray); font-size: 0.95rem;">
                    Manage your personal account preferences and access.
                </p>
            </div>
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
            <!-- Sidebar / Profile Card & Left Tabs -->
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

                <!-- Left Navigation Tabs -->
                <nav class="account-nav-tabs">
                    <a href="?tab=general" class="account-nav-tab <?= $active_tab === 'general' ? 'active' : '' ?>">
                        <i class="fa-solid fa-user-gear"></i> General & Security
                    </a>
                    <a href="?tab=logs" class="account-nav-tab <?= $active_tab === 'logs' ? 'active' : '' ?>">
                        <i class="fa-solid fa-clock-rotate-left"></i> <?= $user->is_admin() ? 'Store Logs & Orders' : 'My Orders' ?>
                    </a>

                    <?php if ($user->is_admin()): ?>
                        <div class="nav-group-title">Admin Controls</div>
                        <a href="?tab=dashboard" class="account-nav-tab <?= $active_tab === 'dashboard' ? 'active' : '' ?>">
                            <i class="fa-solid fa-chart-line"></i> Dashboard
                        </a>
                        <a href="?tab=users" class="account-nav-tab <?= $active_tab === 'users' ? 'active' : '' ?>">
                            <i class="fa-solid fa-users"></i> Manage Users
                        </a>
                        <a href="?tab=stock" class="account-nav-tab <?= $active_tab === 'stock' ? 'active' : '' ?>">
                            <i class="fa-solid fa-boxes-stacked"></i> Stock & Inventory
                        </a>
                        <a href="?tab=add_product" class="account-nav-tab <?= $active_tab === 'add_product' ? 'active' : '' ?>">
                            <i class="fa-solid fa-plus-circle"></i> Add New Product
                        </a>
                    <?php endif; ?>
                </nav>
            </aside>

            <!-- Main Tab Content Area -->
            <div class="account-panels">
                <?php 
                // load the active component tab
                switch ($active_tab) {
                    case 'dashboard':
                        if ($user->is_admin()) {
                            require __DIR__ . '/tabs/dashboard.php';
                        }
                        break;
                    case 'logs':
                        require __DIR__ . '/tabs/logs.php';
                        break;
                    case 'users':
                        if ($user->is_admin()) {
                            require __DIR__ . '/tabs/users.php';
                        }
                        break;
                    case 'stock':
                        if ($user->is_admin()) {
                            require __DIR__ . '/tabs/stock.php';
                        }
                        break;
                    case 'add_product':
                        if ($user->is_admin()) {
                            require __DIR__ . '/tabs/add_product.php';
                        }
                        break;
                    case 'general':
                    default:
                        require __DIR__ . '/tabs/general.php';
                        break;
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <?php 
    $logoutModal->render();
    if ($deleteModal) {
        $deleteModal->render();
    }
    if ($deleteProductModal) {
        $deleteProductModal->render();
    }
    if ($alertModal) {
        $alertModal->render();
    }
    ?>

    <?php require ROOT_DIR . 'components/footer.php'; ?>

    <?php require ROOT_DIR . 'components/modal_scripts.php'; ?>

    <script>
        // set the product_id in the delete product modal before opening it
        function prepareDeleteProduct(productId) {
            document.querySelector('#deleteProductModal input[name="product_id"]').value = productId;
            ConfirmTypeModal.open('deleteProductModal');
        }
    </script>

    <?php if ($alertModal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AlertModal.open('accountAlertModal');
        });
    </script>
    <?php endif; ?>
</body>
</html>
