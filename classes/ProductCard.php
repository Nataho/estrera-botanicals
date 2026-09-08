<?php
// product card component
// clean OOP card rendering for shop catalog

class ProductCard {
    private array $data;
    private ?string $image_url = null;
    private bool $is_banned = false;

    public function __construct(array $product_data, ?string $image_url = null, bool $is_banned = false) {
        $this->data = $product_data;
        $this->image_url = $image_url;
        $this->is_banned = $is_banned;
    }

    // render product card html
    public function render(): void {
        $id          = (int)($this->data['product_id'] ?? 0);
        $name        = $this->data['product_name'] ?? 'Botanical Item';
        $desc        = $this->data['product_description'] ?? '';
        $price       = (float)($this->data['product_price'] ?? 0);
        $stock       = (int)($this->data['product_stock_quantity'] ?? 0);
        $in_stock    = $stock > 0;
        $stock_class = $in_stock ? ($stock <= 5 ? 'stock-low' : 'stock-ok') : 'stock-out';
        $stock_label = $in_stock ? ($stock <= 5 ? "Only {$stock} left" : 'In Stock') : 'Out of Stock';

        // fallback placeholder image if none set or not found
        $img_src = !empty($this->image_url) ? $this->image_url : BASE_URL . 'assets/images/3.png';
        ?>
        <div class="shop-product-card <?= !$in_stock ? 'out-of-stock-card' : '' ?>"
             <?= $in_stock && !$this->is_banned ? 'data-product-card="true"' : '' ?>
             data-id="<?= $id ?>"
             data-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
             data-price="<?= $price ?>"
             data-stock="<?= $stock ?>"
             data-image="<?= htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8') ?>">
            <div class="product-card-image-wrap">
                <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($name) ?>" loading="lazy">
                <span class="product-stock-pill <?= $stock_class ?>"><?= $stock_label ?></span>
            </div>

            <div class="product-card-info">
                <h3 class="product-card-name"><?= htmlspecialchars($name) ?></h3>
                <?php if (!empty($desc)): ?>
                    <p class="product-card-desc"><?= htmlspecialchars($desc) ?></p>
                <?php endif; ?>

                <div class="product-card-footer">
                    <div class="product-card-price">₱<?= number_format($price, 2) ?></div>

                    <?php if (!$in_stock): ?>
                        <button type="button" class="btn-product-action disabled" disabled>
                            <i class="fa-solid fa-ban"></i> Out of Stock
                        </button>
                    <?php elseif ($this->is_banned): ?>
                        <button type="button" class="btn-product-action banned" disabled title="Banned accounts cannot add to cart">
                            <i class="fa-solid fa-lock"></i> Restricted
                        </button>
                    <?php else: ?>
                        <!-- trigger add to cart modal -->
                        <button type="button" class="btn-product-action active btn-open-cart-modal">
                            <i class="fa-solid fa-bag-shopping"></i> Add to Cart
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>
