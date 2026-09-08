<?php 
require_once '../config.php';
// session_start();

// $_SESSION['curr_page'] = 'shop';
// if (isset($_SESSION['prev_page'])) {
// 	if ($_SESSION['prev_page'] != $_SESSION['curr_page']) {
		
// 	}
// }
// else{
// 	$_SESSION['prev_page'] = $_SESSION['curr_page'];
// }

if (!isset($_SESSION['logged_in'])){
	header('location: ../login');
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Shop</title>
	<?php require_once ROOT_DIR . 'components/base_css.php' ?>
</head>
<body>
	<?php require_once ROOT_DIR . 'components/header.php' ?>
</body>
</html>