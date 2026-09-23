<?php
session_start();
header('Content-Type: application/json');

error_log("=== TEST SESSION AJAX ===");
error_log("Session ID: " . session_id());
error_log("Session Data: " . print_r($_SESSION, true));

if(isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => true,
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'session_id' => session_id()
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated',
        'session_id' => session_id(),
        'cookie' => $_COOKIE
    ]);
}
?>