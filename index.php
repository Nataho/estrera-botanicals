<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estrera Botanicals</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1>ESTRERA BOTANICALS</h1>
        <nav>
            <a class="heading-nav" href="<?= BASE_URL ?>">HOME</a>
            <a class="heading-nav" href="#none">SHOP</a>
            <a class="heading-nav" href="#none">BEST SELLERS</a>
            <a class="heading-nav" href="<?= BASE_URL ?>/about-us">ABOUT US</a>
            <a class="heading-nav" id="heading-cta-button" href="#none">Shop Now</a>
        </nav>
    </header>

    <div class="hero-image-container">
        <img id="img1" src="assets/images/1.png" alt="heading image">
        <div class="overlay-content">
            <h1>Chebula Active Serum</h1>
            <p>A clinically proven serum that works at the cellular level to boost <br>your skin s natural collagen for more radiant, resilient skin.</p>
            <a class="btn" href="#none"> Shop Now</a>
        </div>
    </div>

    <section class="section-wrapper">
        <h1 id="second-heading" class="section-heading">Skincare Essentials</h1>
        <div class="photo-container">
            <div class="photo-card">
                <div class="image-wrapper">
                    <img src="assets/images/3.png" alt="Chebula Active Serum">
                </div>
                <h3>Chebula Active Serum</h3>
                <p>This serum is clinically proven to outperform two leading anti-aging ingredients.</p>
            </div>
            <div class="photo-card">
                <div class="image-wrapper">
                    <img src="assets/images/4.png" alt="FloraShield">
                </div>
                <h3>FloraShield</h3>
                <p>Plant-powered UV protection that defends your skin with gentle, botanical nourishment.</p>
            </div>
            <div class="photo-card">
                <div class="image-wrapper">
                    <img src="assets/images/5.png" alt="Midnight Recovery Oil">
                </div>
                <h3>Midnight Recovery Oil</h3>
                <p>Calm redness and lock in deep overnight moisture with soothing blue tansy and balancing jojoba.</p>
            </div>
        </div>
    </section>

    <!-- SHOP BY CONCERN -->
    <section class="section-wrapper">
        <h1 id="third-heading" class="section-heading">Shop by Concern</h1>
        <div class="concern-container">
            <div class="concern-card">
                <img src="assets/images/6.png" alt="Sensitive Skin">
                <div class="concern-content">
                    <h2>Sensitive Skin</h2>
                    <a href="#none">Shop Now</a>
                </div>
            </div>
            <div class="concern-card">
                <img src="assets/images/7.png" alt="Breakouts & Blemishes">
                <div class="concern-content">
                    <h2>Breakouts & Blemishes</h2>
                    <a href="#none">Shop Now</a>
                </div>
            </div>
        </div>
    </section>

    <!-- extra -->
    <section class="extra-image-container">
        <img src="assets/images/8.png" alt="midnight recovery oil">
        <div class="overlay-content">
            <h1>Midnight Recovery Oil</h1>
            <p id="ingredients">Bloe Tansy & Jojova Infusion</p>
            <p>30ml | 1.08oz</p>
            <a class="btn" href="#none">Shop Now</a>
        </div>
    </section>

    <!-- FEEDBACK -->
    <section class="section-wrapper">
        <h1 id="fourth-heading" class="section-heading">Feedback</h1>
        <div class="feedback-container">

            <div class="feedback-card">
                <h2>"A terrific piece of praise"</h2>
                <div class="profile">
                    <img src="" alt="sheena">
                    <div>
                        <p class="profile-name">Sheena F. Rentuaya</p>
                        <p>Customer</p>
                    </div>
                </div>
            </div>
            <div class="feedback-card">
                <h2>"A terrific piece of praise"</h2>
                <div class="profile">
                    <img src="" alt="sheena">
                    <div>
                        <p class="profile-name">Sheena F. Rentuaya</p>
                        <p>Customer</p>
                    </div>
                </div>
            </div>
            <div class="feedback-card">
                <h2>"A terrific piece of praise"</h2>
                <div class="profile">
                    <img src="" alt="sheena">
                    <div>
                        <p class="profile-name">Sheena F. Rentuaya</p>
                        <p>Customer</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="white-bg">
        <div class="section-wrapper" id="center-all">
            <h1 id="fifth-heading" class="section-heading">Become a Prototype Tester!</h1>
            <p id="prototype-description">Testing breakthrough plant gear and biotech innovations<br>for Estrera Botanicals to optimize global growth and<br>sustainability</p>
            <a class="btn" href="#none" id="apply-button">Apply Now</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="footer-container">
            <div class="footer-brand">
                <h2>Estrera Botanicals</h2>
                <div class="footer-socials">
                    <!-- from online cloudflare link -->
                    <a href="https://www.facebook.com/owen.estrera1/"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#none"><i class="fa-brands fa-linkedin"></i></a>
                    <a href="#none"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#none"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <div class="footer-column">
                    <h3>Shop</h3>
                    <a href="#none">NEW</a>
                    <a href="#none">SETS</a>
                    <a href="#none">BUILD YOUR BUNDLE</a>
                </div>
                <div class="footer-column">
                    <h3>Learn</h3>
                    <a href="#none">ABOUT US</a>
                    <a href="#none">SUSTAINABILITY</a>
                    <a href="#none">GET A FREE GIFT</a>
                </div>
                <div class="footer-column">
                    <h3>Help</h3>
                    <a href="#none">FAQs</a>
                    <a href="#none">RETURNS</a>
                    <a href="#none">SHIPPING & DELIVERY</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>