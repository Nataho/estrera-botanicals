<?php 
require_once '../config.php';
require_once ROOT_DIR . 'database/config.php';
require_once ROOT_DIR . 'functions/auth.php';
require_once ROOT_DIR . 'classes/Image.php';
require_once ROOT_DIR . 'classes/ProductCard.php';
require_once ROOT_DIR . 'classes/AlertModal.php';

// guests must log in first to access shop
require_auth('login');

$user = current_user();
$img_helper = new Image($pdo);
$message = '';
$error = '';

// handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_to_cart') {
        if ($user->is_banned()) {
            $error = "Your account has been restricted. You cannot add products to cart.";
        } else {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $qty = max(1, (int)($_POST['quantity'] ?? 1));

            // check product stock
            $stmt = $pdo->prepare("SELECT product_name, product_price, product_stock_quantity FROM products WHERE product_id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $prod = $stmt->fetch();

            if ($prod && (int)$prod['product_stock_quantity'] > 0) {
                $available_stock = (int)$prod['product_stock_quantity'];
                
                // prevent adding more than available stock
                if ($qty > $available_stock) {
                    $qty = $available_stock;
                }

                // simple session-based cart
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                $current_in_cart = $_SESSION['cart'][$product_id] ?? 0;
                $new_total_qty = min($available_stock, $current_in_cart + $qty);
                $_SESSION['cart'][$product_id] = $new_total_qty;

                $batch_total = (float)$prod['product_price'] * $qty;
                $message = "Added {$qty}x '{$prod['product_name']}' to your cart! (Batch Total: ₱" . number_format($batch_total, 2) . ")";
            } else {
                $error = "Sorry, this item is currently out of stock.";
            }
        }
    }
}

// compute total cart count
$cart_count = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = array_sum($_SESSION['cart']);
}

// determine current view filter: 'all' or 'best_sellers'
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all', 'best_sellers'])) {
    $filter = 'all';
}

// fetch products according to filter
if ($filter === 'best_sellers') {
    // 1. count total products to find 50% rounded up
    $total_products_count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $best_seller_limit = (int)ceil($total_products_count * 0.5);

    // 2. fetch top 50% products ranked by total sold quantity, then alphabetical by name
    if ($best_seller_limit > 0) {
        $sql = "
            SELECT p.*, COALESCE(SUM(oi.quantity), 0) AS total_sold 
            FROM products p 
            LEFT JOIN order_items oi ON p.product_id = oi.product_id 
            GROUP BY p.product_id 
            ORDER BY total_sold DESC, p.product_name ASC 
            LIMIT $best_seller_limit
        ";
        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll();
    } else {
        $products = [];
    }
} else {
    // fetch all products: in-stock first, out-of-stock at the very end
    $sql = "SELECT * FROM products ORDER BY (product_stock_quantity > 0) DESC, product_id DESC";
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll();
}

// modal notification if message or error
$alertModal = null;
if (!empty($message)) {
    $alertModal = new AlertModal($message, "success", "Continue", "shopAlertModal");
} elseif (!empty($error)) {
    $alertModal = new AlertModal($error, "error", "Okay", "shopAlertModal");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | Estrera Botanicals</title>
    <?php require_once ROOT_DIR . 'components/base_css.php'; ?>
</head>
<body>
    <?php require_once ROOT_DIR . 'components/header.php'; ?>

    <main class="section-wrapper">
        <!-- Page Header & Checkout Trigger -->
        <div class="shop-page-header">
            <div class="shop-page-title">
                <h1><?= $filter === 'best_sellers' ? 'Best Sellers' : 'Botanical Shop' ?></h1>
                <p>
                    <?= $filter === 'best_sellers' 
                        ? 'Our top-performing botanical formulas, loved and ordered most by our customers.' 
                        : 'Pure, nutrient-dense skincare formulated directly from raw plant botanicals.' ?>
                </p>
            </div>

            <!-- Checkout button -->
            <a href="<?= BASE_URL ?>checkout/" class="shop-checkout-btn">
                <i class="fa-solid fa-cart-shopping"></i> Checkout
                <?php if ($cart_count > 0): ?>
                    <span style="background: var(--palette-light-green); color: #fff; padding: 2px 8px; border-radius: 20px; font-size: 0.8rem; font-weight: 700;">
                        <?= $cart_count ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Filter Tabs: All Products & Best Sellers -->
        <div class="shop-filter-tabs">
            <a href="<?= BASE_URL ?>shop" class="shop-tab-pill <?= $filter === 'all' ? 'active' : '' ?>">
                <i class="fa-solid fa-leaf"></i> All Products
            </a>
            <a href="<?= BASE_URL ?>shop?filter=best_sellers" class="shop-tab-pill <?= $filter === 'best_sellers' ? 'active' : '' ?>">
                <i class="fa-solid fa-fire"></i> Best Sellers
            </a>
        </div>

        <?php if ($user->is_banned()): ?>
            <div class="account-alert err" style="margin-bottom: 24px;">
                <i class="fa-solid fa-ban"></i>
                <span>Your account is currently restricted. Purchasing and cart additions are disabled.</span>
            </div>
        <?php endif; ?>

        <!-- Products Catalog Grid (centered when best sellers) -->
        <?php if (empty($products)): ?>
            <div style="text-align: center; padding: 60px 20px; color: var(--palette-gray); font-family: var(--document-font);">
                <i class="fa-solid fa-seedling" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                <h2>No products found</h2>
                <p>Check back soon as we restock our botanical catalog.</p>
            </div>
        <?php else: ?>
            <div class="shop-grid <?= $filter === 'best_sellers' ? 'centered-grid' : '' ?>">
                <?php 
                foreach ($products as $prod) {
                    // resolve product image url
                    $image_url = null;
                    if (!empty($prod['product_img'])) {
                        // see if it exists in eb-uploads
                        $img_data = $img_helper->get($prod['product_img']);
                        if ($img_data && $img_data['exists']) {
                            $image_url = $img_data['url'];
                        } elseif (file_exists(ROOT_DIR . 'assets/images/' . $prod['product_img'])) {
                            $image_url = BASE_URL . 'assets/images/' . $prod['product_img'];
                        }
                    }

                    // render through ProductCard class
                    $card = new ProductCard($prod, $image_url, $user->is_banned());
                    $card->render();
                }
                ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modals -->
    <?php 
    if ($alertModal) {
        $alertModal->render();
    }
    ?>

    <!-- Add To Cart Quantity & Batch Total Modal -->
    <div id="addToCartModal" class="confirm-modal-overlay" role="dialog" aria-modal="true" style="display: none;">
        <div class="confirm-modal-box cart-batch-modal-box">
            <div class="cart-modal-header">
                <img id="cartModalProductImg" src="" alt="Product" class="cart-modal-thumb">
                <div class="cart-modal-details">
                    <h3 id="cartModalProductName">Botanical Product</h3>
                    <div class="cart-modal-unit-price">Unit Price: <span id="cartModalUnitPrice">₱0.00</span></div>
                    <div class="cart-modal-stock-info">Available Stock: <span id="cartModalStock">0</span></div>
                </div>
            </div>

            <form method="POST" action="" id="addToCartForm" class="cart-modal-form">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_id" id="cartModalProductId" value="0">

                <div class="cart-modal-qty-control">
                    <label for="cartModalQty">Quantity to Add:</label>
                    <div class="qty-stepper-wrap">
                        <button type="button" class="btn-qty-step" onclick="stepCartQty(-1)">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <input type="number" 
                               name="quantity" 
                               id="cartModalQty" 
                               value="1" 
                               min="1" 
                               max="1" 
                               required 
                               oninput="calculateCartBatchTotal()">
                        <button type="button" class="btn-qty-step" onclick="stepCartQty(1)">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>

                <div class="cart-modal-summary-box">
                    <div class="summary-line">
                        <span>Quantity:</span>
                        <strong id="cartModalSummaryQty">1</strong>
                    </div>
                    <div class="summary-line total">
                        <span>Batch Total:</span>
                        <strong id="cartModalBatchTotal" class="batch-total-amount">₱0.00</strong>
                    </div>
                </div>

                <div class="confirm-modal-actions" style="margin-top: 20px;">
                    <button type="button" class="btn-modal-cancel" onclick="closeAddToCartModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn-modal-confirm" id="btnConfirmAddToCart">
                        <i class="fa-solid fa-bag-shopping"></i> Add to Cart
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php require ROOT_DIR . 'components/footer.php'; ?>
    <?php require ROOT_DIR . 'components/modal_scripts.php'; ?>

    <script>
        let currentProductPrice = 0;
        let currentProductStock = 0;

        function openAddToCartModal(prod) {
            currentProductPrice = parseFloat(prod.price) || 0;
            currentProductStock = parseInt(prod.stock) || 0;

            document.getElementById('cartModalProductId').value = prod.id;
            document.getElementById('cartModalProductName').textContent = prod.name;
            document.getElementById('cartModalProductImg').src = prod.image || '<?= BASE_URL ?>assets/images/3.png';
            document.getElementById('cartModalUnitPrice').textContent = '₱' + currentProductPrice.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('cartModalStock').textContent = currentProductStock + ' units';

            const qtyInput = document.getElementById('cartModalQty');
            qtyInput.max = currentProductStock;
            qtyInput.value = 1;

            calculateCartBatchTotal();

            const modal = document.getElementById('addToCartModal');
            modal.style.display = 'flex';
        }

        function closeAddToCartModal() {
            const modal = document.getElementById('addToCartModal');
            if (modal) modal.style.display = 'none';
        }

        function stepCartQty(delta) {
            const input = document.getElementById('cartModalQty');
            let val = parseInt(input.value) || 1;
            val += delta;
            if (val < 1) val = 1;
            if (val > currentProductStock) val = currentProductStock;
            input.value = val;
            calculateCartBatchTotal();
        }

        function calculateCartBatchTotal() {
            const qtyInput = document.getElementById('cartModalQty');
            let qty = parseInt(qtyInput.value) || 1;

            if (qty < 1) {
                qty = 1;
                qtyInput.value = 1;
            } else if (qty > currentProductStock) {
                qty = currentProductStock;
                qtyInput.value = currentProductStock;
            }

            const total = currentProductPrice * qty;
            document.getElementById('cartModalSummaryQty').textContent = qty;
            document.getElementById('cartModalBatchTotal').textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // bind click listeners to all active product cards
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-product-card="true"]').forEach(card => {
                card.style.cursor = 'pointer';
                card.addEventListener('click', (e) => {
                    const prod = {
                        id: card.dataset.id,
                        name: card.dataset.name,
                        price: card.dataset.price,
                        stock: card.dataset.stock,
                        image: card.dataset.image
                    };
                    openAddToCartModal(prod);
                });
            });
        });
    </script>

    <?php if ($alertModal): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            AlertModal.open('shopAlertModal');
        });
    </script>
    <?php endif; ?>
</body>
</html>