<?php
// get css
$css_path = ROOT_DIR . 'style.css';

//translates to: if file exsists then get file modified time else return 1
$version = file_exists($css_path) ? filemtime($css_path) : '1';
?>
<!-- fontawesome icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- base css with auto cache buster -->
<link rel="stylesheet" href="<?= BASE_URL ?>style.css?v=<?= $version ?>">