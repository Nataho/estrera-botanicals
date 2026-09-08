<?php 
require_once '../config.php';
session_start();
if (!isset($_SESSION['logged_in'])){
	header('location: ../login');
	exit();
}
?>
<h1>welcome to shop</h1>