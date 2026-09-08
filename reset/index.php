<?php
require_once '../config.php';
require_once ROOT_DIR . 'functions/auth.php';

// wipe session and kick back to home
current_user()->logout();
header('Location: ' . BASE_URL);
exit;
?>