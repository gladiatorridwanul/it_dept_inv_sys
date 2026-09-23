<?php
require_once '../config/database.php';
require_once '../config/paths.php';

// Session is already started in database.php
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
?>
<!-- Rest of your HTML remains the same -->