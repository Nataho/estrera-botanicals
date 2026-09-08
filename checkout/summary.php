<?php
// order summary / invoice view
// accessible by both the customer who placed it and any admin

require_once '../config.php';
require_once ROOT_DIR . 'database/config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'classes/Image.php';

// guests must login to view orders
require_auth('login');

$user = current_user();
$img_helper = new Image($pdo);

$order_id = (int)($_GET['order_id'] ?? 0);
$just_placed = isset($_GET['placed']);

if ($order_id <= 0) {
    header('Location: ' . BASE_URL . 'shop');
    exit;
}

// fetch order details
$stmt = $pdo->prepare("
    SELECT o.*, u.user_name, u.user_email, u.user_role 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = ? 
    LIMIT 1
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . BASE_URL . 'error.php?code=404');
    exit;
}

// access control: user can only view their own order, unless they are an admin
if (!$user->is_admin() && (int)$order['user_id'] !== (int)$user->id) {
    header('Location: ' . BASE_URL . 'account');
    exit;
}

// fetch order items
$stmt_items = $pdo->prepare("
    SELECT oi.*, p.product_name, p.product_img 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = ? 
    ORDER BY oi.item_id ASC
");
$stmt_items->execute([$order_id]);
$items = $stmt_items->fetchAll();

$status = strtolower($order['status'] ?? 'pending');
$status_class = match($status) {
    'completed', 'delivered' => 'badge-status active',
    'cancelled', 'declined' => 'badge-status banned',
    default => 'badge-status pending'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= str_pad((string)$order['order_id'], 5, '0', STR_PAD_LEFT) ?> Summary | Estrera Botanicals</title>
    <?php require_once ROOT_DIR . 'components/base_css.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>account/style.css">
    <link rel="stylesheet" href="style.css?v=<?= file_exists(__DIR__ . '/style.css') ? filemtime(__DIR__ . '/style.css') : '1' ?>">
</head>
<body class="account-page">
    <?php require_once ROOT_DIR . 'components/header.php'; ?>

    <main class="summary-container">
        <div class="summary-card">
            <?php if ($just_placed): ?>
                <div class="summary-banner">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>
                        <h3>Thank You! Order Placed Successfully</h3>
                        <p>Your botanical order has been registered and inventory has been reserved.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="panel-header-row" style="margin-bottom: 24px;">
                <div>
                    <h2 style="font-family: var(--heading-font); color: var(--palette-dark-green); font-size: 1.8rem; margin: 0 0 6px 0;">
                        Order #<?= str_pad((string)$order['order_id'], 5, '0', STR_PAD_LEFT) ?>
                    </h2>
                    <p class="subtext" style="margin: 0;">Official purchase record & checkout summary</p>
                </div>
                <span class="<?= $status_class ?>" style="font-size: 0.95rem; padding: 6px 14px;">
                    <i class="fa-solid fa-circle-notch"></i> <?= ucfirst(htmlspecialchars($order['status'] ?? 'Pending')) ?>
                </span>
            </div>

            <!-- Order Metadata Grid -->
            <div class="order-meta-grid">
                <div class="order-meta-item">
                    <label>Order Date</label>
                    <span><?= date('M d, Y · h:i A', strtotime($order['order_date'])) ?></span>
                </div>
                <div class="order-meta-item">
                    <label>Customer Name</label>
                    <span><?= htmlspecialchars($order['user_name'] ?? 'User') ?></span>
                </div>
                <div class="order-meta-item">
                    <label>Customer Email</label>
                    <span style="font-size: 0.9rem; font-weight: 500;"><?= htmlspecialchars($order['user_email'] ?? 'N/A') ?></span>
                </div>
                <div class="order-meta-item">
                    <label>Viewing As</label>
                    <span style="color: var(--palette-light-green);"><?= $user->is_admin() ? 'Administrator' : 'Customer' ?></span>
                </div>
            </div>

            <!-- Items Purchased Table -->
            <div class="table-responsive">
                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Botanical Product</th>
                            <th style="text-align: center;">Price</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php 
                            // image resolution
                            $img_src = BASE_URL . 'assets/images/3.png';
                            if (!empty($item['product_img'])) {
                                $img_data = $img_helper->get($item['product_img']);
                                if ($img_data && $img_data['exists']) {
                                    $img_src = $img_data['url'];
                                } elseif (file_exists(ROOT_DIR . 'assets/images/' . $item['product_img'])) {
                                    $img_src = BASE_URL . 'assets/images/' . $item['product_img'];
                                }
                            }
                            $item_price = (float)$item['price_at_purchase'];
                            $item_qty   = (int)$item['quantity'];
                            $item_total = $item_price * $item_qty;
                            ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($img_src) ?>" alt="" class="summary-table-thumb">
                                    <strong><?= htmlspecialchars($item['product_name'] ?? 'Product #' . $item['product_id']) ?></strong>
                                </td>
                                <td style="text-align: center;">₱<?= number_format($item_price, 2) ?></td>
                                <td style="text-align: center; font-weight: 700;"><?= $item_qty ?></td>
                                <td style="text-align: right; font-weight: 700;">₱<?= number_format($item_total, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Totals -->
            <div class="summary-totals-box">
                <div class="summary-totals-row">
                    <span>Subtotal:</span>
                    <strong>₱<?= number_format((float)$order['total_amount'], 2) ?></strong>
                </div>
                <div class="summary-totals-row">
                    <span>Shipping:</span>
                    <strong style="color: var(--palette-light-green);">FREE</strong>
                </div>
                <div class="summary-totals-row grand">
                    <span>Grand Total:</span>
                    <span>₱<?= number_format((float)$order['total_amount'], 2) ?></span>
                </div>
            </div>

            <!-- Actions -->
            <div class="summary-footer-actions">
                <div style="display: flex; gap: 10px;">
                    <a href="<?= BASE_URL ?>account?tab=logs" class="account-btn secondary" style="text-decoration: none;">
                        <i class="fa-solid fa-clock-rotate-left"></i> View Order Logs
                    </a>
                    <a href="<?= BASE_URL ?>shop" class="account-btn outline" style="text-decoration: none;">
                        <i class="fa-solid fa-arrow-left"></i> Return to Shop
                    </a>
                </div>
                <button type="button" class="account-btn outline" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print Invoice
                </button>
            </div>
        </div>
    </main>

    <?php require_once ROOT_DIR . 'components/footer.php'; ?>
</body>
</html>
