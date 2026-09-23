<?php
// Include session_fix.php to use the same session configuration
require_once dirname(__DIR__, 2) . '/config/session_fix.php';

header('Content-Type: application/json');

// Get user data from database
$user_data = null;
if(isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT id, username, full_name, role, role_id, is_active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch();
    } catch(Exception $e) {
        error_log("test_session.php error: " . $e->getMessage());
    }
}

echo json_encode([
    'session_id' => session_id(),
    'session_save_path' => session_save_path(),
    'session_user_id' => $_SESSION['user_id'] ?? 'not set',
    'session_role' => $_SESSION['role'] ?? 'not set',
    'session_role_id' => $_SESSION['role_id'] ?? 'not set',
    'session_username' => $_SESSION['username'] ?? 'not set',
    'session_full_name' => $_SESSION['full_name'] ?? 'not set',
    'database_user' => $user_data,
    'session_data' => $_SESSION
], JSON_PRETTY_PRINT);
?>