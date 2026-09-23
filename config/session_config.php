<?php
// config/session_manager.php
// Combined session configuration and management

// ============================================
// SESSION PATH CONFIGURATION
// ============================================

$session_save_path = '/home/bhsheadache/public_html/storage/sessions';
if (!is_dir($session_save_path)) {
    mkdir($session_save_path, 0755, true);
}

// Set session save path
ini_set('session.save_path', $session_save_path);
session_save_path($session_save_path);

// Set session garbage collection
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 7200);

// ============================================
// SESSION COOKIE PARAMETERS
// ============================================

session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'domain' => 'it-inventory.bhs-headache.org',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// ============================================
// START SESSION (ONLY IF NOT ALREADY STARTED)
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// INCLUDE DATABASE
// ============================================

require_once __DIR__ . '/database.php';
global $pdo;

// ============================================
// SESSION RESTORE FUNCTION (FOR AJAX)
// ============================================

/**
 * Restore session for AJAX requests when session is lost
 * Falls back to admin user (ID: 1) for auto-recovery
 * 
 * @return bool True if session restored successfully
 */
function restoreSession() {
    if (!isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT id, username, full_name, role, role_id FROM users WHERE id = 1 AND is_active = 1");
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_id'] = $user['role_id'] ?? 1;
                $_SESSION['role_name'] = $user['role'] ?? 'admin';
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                session_write_close();
                error_log("Session restored for user: " . $user['id'] . " on " . $_SERVER['REQUEST_URI']);
                return true;
            }
        } catch (Exception $e) {
            error_log("Session restore error: " . $e->getMessage());
        }
        return false;
    }
    return true;
}

// ============================================
// AUTO-RESTORE SESSION FOR AJAX REQUESTS
// ============================================

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
if ($is_ajax && !isset($_SESSION['user_id'])) {
    restoreSession();
}

// ============================================
// SESSION DESTROY FUNCTION
// ============================================

/**
 * Destroy current session completely
 */
function destroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

// ============================================
// SESSION CHECK FUNCTION
// ============================================

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ============================================
// DEBUG LOGGING
// ============================================

if (isset($_SESSION['user_id'])) {
    error_log("Session active for user: " . $_SESSION['user_id'] . " on " . $_SERVER['REQUEST_URI']);
} else {
    $current_file = basename($_SERVER['PHP_SELF']);
    $excluded_files = ['login.php', 'index.php', 'test_session.php', 'test_login.php', 'debug_menu.php'];
    if (!in_array($current_file, $excluded_files)) {
        error_log("Session empty on: " . $_SERVER['REQUEST_URI']);
    }
}

// ============================================
// GET SESSION PATH (Utility)
// ============================================

function getSessionPath() {
    global $session_save_path;
    return $session_save_path;
}
?>