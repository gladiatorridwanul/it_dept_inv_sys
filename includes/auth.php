<?php
// includes/auth.php

// IMPORTANT: Include session_fix FIRST
require_once dirname(__DIR__) . '/config/session_fix.php';

// Get current page
$current_file = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['REQUEST_URI'];

// Pages that don't require login
$public_pages = ['login.php', 'index.php', 'test_session.php', 'test_login.php', 'debug_menu.php'];

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Allow access to public pages
    if (in_array($current_file, $public_pages)) {
        return;
    }
    
    // Try to restore session for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // For AJAX, try to restore session
        if (restoreSession() && isset($_SESSION['user_id'])) {
            error_log("AJAX session restored for user: " . $_SESSION['user_id']);
            // Continue processing
        } else {
            // AJAX session restore failed
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please reload the page.']);
            exit();
        }
    } else {
        // Redirect to login for regular pages
        $login_url = 'https://it-inventory.bhs-headache.org/modules/login.php';
        if (!headers_sent()) {
            header("Location: $login_url");
            exit();
        } else {
            echo "<script>window.location.href='$login_url';</script>";
            exit();
        }
    }
}

// Force admin role for user ID 1 (temporary fix if role is missing)
if ($_SESSION['user_id'] == 1) {
    $_SESSION['role'] = 'admin';
    $_SESSION['role_id'] = 1;
    $_SESSION['role_name'] = 'admin';
}

// If role is still not set, try to get it from database
if (!isset($_SESSION['role'])) {
    try {
        $stmt = $pdo->prepare("SELECT role, role_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['role'] = $user['role'];
            $_SESSION['role_id'] = $user['role_id'] ?? 1;
        } else {
            // User not found in database
            session_destroy();
            header("Location: https://it-inventory.bhs-headache.org/modules/login.php");
            exit();
        }
    } catch (Exception $e) {
        error_log("Error getting user role: " . $e->getMessage());
        $_SESSION['role'] = 'it_staff';
        $_SESSION['role_id'] = 2;
    }
}

// Update last activity
$_SESSION['last_activity'] = time();

// ============================================
// PERMISSION FUNCTIONS
// ============================================

function hasPermission($pdo, $user_role, $module, $permission = 'view') {
    // Admin has ALL permissions
    if ($user_role == 'admin') {
        return true;
    }
    
    // Check role_id
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }
    
    // For non-admin, check permissions table
    try {
        static $permissions_cache = [];
        $key = $user_role . '_' . $module . '_' . $permission;
        
        if (!isset($permissions_cache[$key])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissions WHERE role = ? AND module = ? AND permission = ?");
            $stmt->execute([$user_role, $module, $permission]);
            $permissions_cache[$key] = $stmt->fetchColumn() > 0;
        }
        return $permissions_cache[$key];
    } catch (Exception $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

function canView($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'view');
}

function canCreate($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'create');
}

function canEdit($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'edit');
}

function canDelete($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'delete');
}

function canProcess($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'process');
}

function canExport($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'export');
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function isITStaff() {
    return isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'it_staff');
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? 'it_staff';
}
?>