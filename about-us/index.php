<?php
require_once '../config.php';
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <!-- an "AHA!" moment here. always loved oop -->
    <!-- header -->
    <?php require ROOT_DIR . 'components/header.php' ?>
    

    <!-- hero -->
    <!-- FIXME -->
    <!-- Add Middle Text -->
    
    <section class="about-us-image-container">
        <img src="<?=BASE_URL?>assets/images/aboutus1.jpeg" alt="aboutus1">

        <div class="palette-overlay light-green blur"></div>

        <div class = "overlay-content center">
            <h1>The Most Intelligent Skincare in the Philippines</h1>
            <p>Truth is a commodity that we’ve been trading in since day one. We’re here to help you find your truth within your skincare routine. That’s true love. That’s true beauty. Applied daily.</p>
        </div>
    </section>

    <!-- footer -->
    <?php require ROOT_DIR . 'components/footer.php'?>
</body>
</html>