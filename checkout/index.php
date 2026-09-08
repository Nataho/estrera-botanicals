<?php
// checkout page
// review items in session cart, adjust quantities, and place order

require_once '../config.php';
require_once ROOT_DIR . 'database/config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'classes/Image.php';
require_once ROOT_DIR . 'classes/AlertModal.php';
require_once ROOT_DIR . 'classes/ConfirmModal.php';

// guests must login to checkout
require_auth('login');

$user = current_user();
$img_helper = new Image($pdo);
$message = '';
$error = '';

// if account is banned, disallow checkout
if ($user->is_banned()) {
    header('Location: ' . BASE_URL . 'shop');
    exit;
}

// initialize cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// handle post actions: update cart item, remove item, clear cart, place order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. update item quantity
    if ($action === 'update_qty') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        if ($pid > 0) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$pid]);
                $message = "Item removed from cart.";
            } else {
                // verify stock
                $stmt = $pdo->prepare("SELECT product_stock_quantity FROM products WHERE product_id = ?");
                $stmt->execute([$pid]);
                $stock = (int)$stmt->fetchColumn();

                if ($qty > $stock) {
                    $_SESSION['cart'][$pid] = $stock;
                    $error = "Quantity capped to available stock ({$stock} units).";
                } else {
                    $_SESSION['cart'][$pid] = $qty;
                    $message = "Cart quantity updated.";
                }
            }
        }
    }

    // 2. remove item from cart
    elseif ($action === 'remove_item') {
        $pid = (int)($_POST['product_id'] ?? 0);
        if (isset($_SESSION['cart'][$pid])) {
            unset($_SESSION['cart'][$pid]);
            $message = "Item removed from cart.";
        }
    }

    // 3. place order (checkout submission)
    elseif ($action === 'place_order') {
        if (empty($_SESSION['cart'])) {
            $error = "Your cart is empty. Add botanical items first.";
        } else {
            // fetch fresh product info and validate stock
            $pids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($pids), '?'));
            $stmt = $pdo->prepare("SELECT product_id, product_name, product_price, product_stock_quantity FROM products WHERE product_id IN ($placeholders)");
            $stmt->execute($pids);
            $fresh_products = $stmt->fetchAll();

            $products_by_id = [];
            foreach ($fresh_products as $fp) {
                $products_by_id[(int)$fp['product_id']] = $fp;
            }

            $order_items_to_save = [];
            $total_amount = 0.0;
            $stock_insufficient = false;
            $insufficient_item_name = '';

            foreach ($_SESSION['cart'] as $pid => $qty) {
                $qty = (int)$qty;
                if ($qty <= 0) continue;

                if (!isset($products_by_id[$pid])) {
                    $stock_insufficient = true;
                    $insufficient_item_name = "Item #{$pid} is no longer available.";
                    break;
                }

                $pinfo = $products_by_id[$pid];
                $current_stock = (int)$pinfo['product_stock_quantity'];

                if ($qty > $current_stock) {
                    $stock_insufficient = true;
                    $insufficient_item_name = "Not enough stock for '{$pinfo['product_name']}' (Only {$current_stock} available).";
                    break;
                }

                $unit_price = (float)$pinfo['product_price'];
                $line_total = $unit_price * $qty;
                $total_amount += $line_total;

                $order_items_to_save[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'price_at_purchase' => $unit_price
                ];
            }

            if ($stock_insufficient) {
                $error = $insufficient_item_name;
            } else {
                // start database transaction
                try {
                    $pdo->beginTransaction();

                    // insert order
                    $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, total_amount, order_date, status) VALUES (?, ?, NOW(), 'pending')");
                    $stmt_order->execute([$user->id, $total_amount]);
                    $order_id = (int)$pdo->lastInsertId();

                    // insert items and deduct stock
                    $stmt_item = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)");
                    $stmt_deduct = $pdo->prepare("UPDATE products SET product_stock_quantity = product_stock_quantity - ? WHERE product_id = ?");

                    foreach ($order_items_to_save as $item) {
                        $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price_at_purchase']]);
                        $stmt_deduct->execute([$item['quantity'], $item['product_id']]);
                    }

                    $pdo->commit();

                    // clear session cart
                    $_SESSION['cart'] = [];

                    // redirect to order summary
                    header("Location: " . BASE_URL . "checkout/summary.php?order_id=" . $order_id . "&placed=1");
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $error = "Failed to process order: " . $e->getMessage();
                }
            }
        }
    }
}

// load all cart products details
$cart_items = [];
$subtotal = 0.0;

if (!empty($_SESSION['cart'])) {
    $pids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($pids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id IN ($placeholders)");
    $stmt->execute($pids);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $pid = (int)$row['product_id'];
        $qty = (int)($_SESSION['cart'][$pid] ?? 1);
        $unit_price = (float)$row['product_price'];
        $line_total = $unit_price * $qty;
        $subtotal += $line_total;

        // image resolution
        $img_src = BASE_URL . 'assets/images/3.png';
        if (!empty($row['product_img'])) {
            $img_data = $img_helper->get($row['product_img']);
            if ($img_data && $img_data['exists']) {
                $img_src = $img_data['url'];
            } elseif (file_exists(ROOT_DIR . 'assets/images/' . $row['product_img'])) {
                $img_src = BASE_URL . 'assets/images/' . $row['product_img'];
            }
        }

        $cart_items[] = [
            'id' => $pid,
            'name' => $row['product_name'],
            'price' => $unit_price,
            'stock' => (int)$row['product_stock_quantity'],
            'qty' => $qty,
            'line_total' => $line_total,
            'image' => $img_src
        ];
    }
}

$alertModal = null;
if (!empty($message)) {
    $alertModal = new AlertModal($message, "success", "Continue", "checkoutAlertModal");
} elseif (!empty($error)) {
    $alertModal = new AlertModal($error, "error", "Okay", "checkoutAlertModal");
}

// confirm order modal
$placeOrderModal = new ConfirmModal(
    "Are you sure you want to place this botanical order for ₱" . number_format($subtotal, 2) . "?",
    ["Confirm & Place Order", "Review Cart"],
    "placeOrderModal",
    BASE_URL . "checkout/",
    "POST",
    ["action" => "place_order"],
    "primary"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Estrera Botanicals</title>
    <?php require_once ROOT_DIR . 'components/base_css.php'; ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>account/style.css">
    <style>
        .checkout-container {
            width: 92%;
            max-width: var(--container-max-width);
            margin: 40px auto 80px auto;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
            align-items: start;
        }

        .checkout-panel {
            background: var(--palette-white);
            border-radius: 16px;
            padding: 28px;
            border: 1px solid rgba(43, 80, 46, 0.1);
            box-shadow: 0 4px 20px rgba(43, 80, 46, 0.05);
        }

        .checkout-panel h2 {
            font-family: var(--heading-font);
            color: var(--palette-dark-green);
            font-size: 1.5rem;
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #eef2ee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cart-item-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 0;
            border-bottom: 1px solid #f2f5f2;
        }

        .cart-item-row:last-child {
            border-bottom: none;
        }

        .cart-item-thumb {
            width: 72px;
            height: 72px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #e2e8e2;
            background: #f8faf8;
            flex-shrink: 0;
        }

        .cart-item-meta {
            flex: 1;
        }

        .cart-item-meta h4 {
            font-family: var(--heading-font);
            font-size: 1.15rem;
            color: var(--palette-dark-green);
            margin: 0 0 4px 0;
        }

        .cart-item-meta .unit-price {
            font-family: var(--document-font);
            font-size: 0.9rem;
            color: var(--palette-gray);
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .checkout-stepper {
            display: inline-flex;
            align-items: center;
            border: 1.5px solid #d8e2d7;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .checkout-stepper button {
            background: #f7faf7;
            border: none;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--palette-dark-green);
            font-size: 0.75rem;
            transition: background 0.15s ease;
        }

        .checkout-stepper button:hover {
            background: #eaf2ea;
        }

        .checkout-stepper input {
            width: 44px;
            height: 32px;
            border: none;
            border-left: 1px solid #eef2ee;
            border-right: 1px solid #eef2ee;
            text-align: center;
            font-family: var(--document-font);
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--palette-black);
            outline: none;
        }

        .cart-item-line-total {
            font-family: var(--document-font);
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--palette-black);
            min-width: 90px;
            text-align: right;
        }

        .btn-remove-item {
            background: transparent;
            border: none;
            color: #dc2626;
            cursor: pointer;
            padding: 6px;
            font-size: 1rem;
            transition: opacity 0.2s ease;
        }

        .btn-remove-item:hover {
            opacity: 0.7;
        }

        .order-summary-card {
            background: #fafcfa;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #dce8dc;
            position: sticky;
            top: 24px;
        }

        .order-summary-card h3 {
            font-family: var(--heading-font);
            font-size: 1.35rem;
            color: var(--palette-dark-green);
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2ece2;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: var(--document-font);
            font-size: 0.95rem;
            color: var(--palette-gray);
            margin-bottom: 12px;
        }

        .summary-row.total {
            margin-top: 16px;
            padding-top: 14px;
            border-top: 2px dashed #d8e2d7;
            font-size: 1.15rem;
            color: var(--palette-black);
            font-weight: 700;
        }

        .summary-row.total span:last-child {
            color: var(--palette-dark-green);
            font-size: 1.45rem;
        }

        .btn-place-order {
            width: 100%;
            padding: 14px 20px;
            background: var(--palette-dark-green);
            color: var(--palette-white);
            border: none;
            border-radius: 10px;
            font-family: var(--document-font);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-place-order:hover {
            background: var(--palette-light-green);
            transform: translateY(-1px);
        }

        .btn-place-order:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }

        .empty-cart-view {
            text-align: center;
            padding: 60px 20px;
            color: var(--palette-gray);
            font-family: var(--document-font);
        }

        @media (max-width: 860px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .cart-item-row {
                flex-wrap: wrap;
            }
            .cart-item-actions {
                width: 100%;
                justify-content: space-between;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body class="account-page">
    <?php require_once ROOT_DIR . 'components/header.php'; ?>

    <main class="checkout-container">
        <div class="account-header-bar">
            <div>
                <h1>Checkout & Review</h1>
                <p style="margin: 4px 0 0 0; color: var(--palette-gray); font-size: 0.95rem;">
                    Review your botanical items, update quantities, and confirm your order.
                </p>
            </div>
            <a href="<?= BASE_URL ?>shop" class="account-btn outline">
                <i class="fa-solid fa-arrow-left"></i> Continue Shopping
            </a>
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

        <?php if (empty($cart_items)): ?>
            <div class="checkout-panel empty-cart-view">
                <i class="fa-solid fa-cart-arrow-down" style="font-size: 3.5rem; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                <h2 style="border: none; justify-content: center; font-size: 1.6rem;">Your cart is currently empty</h2>
                <p style="margin-bottom: 24px;">Explore our catalog to add nutrient-dense botanicals to your cart.</p>
                <a href="<?= BASE_URL ?>shop" class="account-btn primary" style="text-decoration: none; display: inline-flex; padding: 12px 24px;">
                    <i class="fa-solid fa-bag-shopping"></i> Browse Botanical Shop
                </a>
            </div>
        <?php else: ?>
            <div class="checkout-grid">
                <!-- Cart Items List -->
                <div class="checkout-panel">
                    <h2>
                        <i class="fa-solid fa-basket-shopping"></i> 
                        Items in Your Cart (<?= count($cart_items) ?>)
                    </h2>

                    <div class="cart-items-list">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item-row">
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-item-thumb">
                                
                                <div class="cart-item-meta">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <div class="unit-price">₱<?= number_format($item['price'], 2) ?> each</div>
                                </div>

                                <div class="cart-item-actions">
                                    <!-- quantity update form -->
                                    <form method="POST" action="" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <input type="hidden" name="action" value="update_qty">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <div class="checkout-stepper">
                                            <button type="submit" name="quantity" value="<?= max(0, $item['qty'] - 1) ?>" title="Decrease">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                            <input type="text" readonly value="<?= $item['qty'] ?>">
                                            <button type="submit" name="quantity" value="<?= min($item['stock'], $item['qty'] + 1) ?>" title="Increase" <?= $item['qty'] >= $item['stock'] ? 'disabled style="opacity:0.4;"' : '' ?>>
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>
                                    </form>

                                    <div class="cart-item-line-total">
                                        ₱<?= number_format($item['line_total'], 2) ?>
                                    </div>

                                    <!-- remove item button -->
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn-remove-item" title="Remove Item">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Order Summary & Checkout Action -->
                <aside class="order-summary-card">
                    <h3>Order Summary</h3>

                    <div class="summary-row">
                        <span>Items Subtotal:</span>
                        <strong>₱<?= number_format($subtotal, 2) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Standard Shipping:</span>
                        <strong style="color: var(--palette-light-green);">FREE</strong>
                    </div>

                    <div class="summary-row">
                        <span>Tax & Fees:</span>
                        <span>₱0.00</span>
                    </div>

                    <div class="summary-row total">
                        <span>Grand Total:</span>
                        <span>₱<?= number_format($subtotal, 2) ?></span>
                    </div>

                    <button type="button" class="btn-place-order" onclick="ConfirmModal.open('placeOrderModal')">
                        <i class="fa-solid fa-circle-check"></i> Place Order Now
                    </button>

                    <div style="margin-top: 18px; font-size: 0.8rem; color: var(--palette-gray); text-align: center; font-family: var(--document-font);">
                        <i class="fa-solid fa-shield-halved"></i> Secure botanical transaction
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modals -->
    <?php 
    $placeOrderModal->render();
    if ($alertModal) {
        $alertModal->render();
    }
    ?>

    <?php require_once ROOT_DIR . 'components/footer.php'; ?>
    <?php require_once ROOT_DIR . 'components/modal_scripts.php'; ?>

    <?php if ($alertModal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AlertModal.open('checkoutAlertModal');
        });
    </script>
    <?php endif; ?>
</body>
</html>
