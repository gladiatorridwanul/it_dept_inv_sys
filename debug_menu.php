<?php
// debug_menu.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Menu Debug Page</h1>";

// Include session fix
require_once 'config/session_fix.php';

echo "<h2>1. Session Status:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

echo "<h2>2. Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>3. User Info:</h2>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "Role ID: " . ($_SESSION['role_id'] ?? 'NOT SET') . "<br>";

echo "<h2>4. Server Info:</h2>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

echo "<h2>5. Test Menu Links:</h2>";
echo "<a href='modules/items/list.php'>Items List</a><br>";
echo "<a href='modules/dashboard.php'>Dashboard</a><br>";
echo "<a href='modules/login.php'>Login</a><br>";

// Check if session file exists and has content
$session_file = session_save_path() . '/sess_' . session_id();
echo "<h2>6. Session File:</h2>";
if (file_exists($session_file)) {
    echo "File exists: " . $session_file . "<br>";
    echo "File size: " . filesize($session_file) . " bytes<br>";
    echo "File contents:<br>";
    echo "<pre>" . htmlspecialchars(file_get_contents($session_file)) . "</pre>";
} else {
    echo "File does NOT exist!<br>";
}

// Check permissions
echo "<h2>7. Directory Permissions:</h2>";
echo "Session directory: " . session_save_path() . "<br>";
echo "Is writable: " . (is_writable(session_save_path()) ? 'YES' : 'NO') . "<br>";
?>