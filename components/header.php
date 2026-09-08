<?php

// require_once '../config.php';

$heading_button_text = 'Shop Now';
$heading_button_path =  BASE_URL . 'shop';


require_once ROOT_DIR . 'functions/auth.php';

if (current_user()->is_logged_in()) {
    $heading_button_text = 'My Account';
    $heading_button_path =  BASE_URL . 'account';
}
?>

<header>
        <h1>ESTRERA BOTANICALS</h1>
        <nav>
            <a class="heading-nav" href="<?= BASE_URL ?>">HOME</a>
            <a class="heading-nav" href="<?= BASE_URL ?>shop"> SHOP</a>
            <a class="heading-nav" href="<?= BASE_URL ?>shop?filter=best_sellers">BEST SELLERS</a>
            <a class="heading-nav" href="<?= BASE_URL ?>about-us">ABOUT US</a>
            <a class="heading-nav" id="heading-cta-button" href=<?= $heading_button_path ?>><?=$heading_button_text?></a>
        </nav>
</header>