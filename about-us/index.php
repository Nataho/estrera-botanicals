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
    
    <section class="about-us-image-container">
        <img src="<?=BASE_URL?>assets/images/aboutus1.jpeg" alt="aboutus1">

        <div class="palette-overlay light-green blur"></div>

        <div class = "overlay-content center">
            <h1>The Most Intelligent Skincare in the Philippines</h1>
            <p>Truth is a commodity that we’ve been trading in since day one. We’re here to help you find your truth within your skincare routine. That’s true love. That’s true beauty. Applied daily.</p>
        </div>
    </section>

    <!-- Mission -->
    <section class="section-wrapper">
        <div class="center">
            <h1>Mission</h1>
            <p>Estrera Botanicals formulates clean, 100% plant-based
                body care to heal and protect skin using organic
                ingredients. Its mission is to eliminate synthetics, make
                holistic wellness accessible, and promote sustainable beauty</p>
        </div>
    </section>

    <!-- idk if apilon ba ni lol -->
    <!-- <section class="section-wrapper">
        <h1>Headquarters</h1>
        <p>Estrera Botanicals does not have a</p>
    </section> -->

    <section class="section-wrapper">
        <!-- picture ni owen diri dapita -->
        <h1>Our Truth-Seeking Founder</h1>
        <p>Owen Estrera</p>
    </section>


    <!-- footer -->
    <?php require ROOT_DIR . 'components/footer.php'?>
</body>
</html>