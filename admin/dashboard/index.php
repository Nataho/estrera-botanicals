<?php 
require_once '../../config.php';
require_once ROOT_DIR . 'functions/auth.php';

// only admins allowed
require_role('admin');

// redirect straight to the integrated dashboard tab in account
header('Location: ' . BASE_URL . 'account?tab=dashboard');
exit;