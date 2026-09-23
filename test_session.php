<?php
// test_session.php - Place this in your root directory
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Test</h1>";

// Include session config
require_once 'config/session_fix.php';

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session Status:</h2>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

// Check if session file exists
$session_file = session_save_path() . '/sess_' . session_id();
if (file_exists($session_file)) {
    echo "Session file exists: " . $session_file . "<br>";
    echo "File size: " . filesize($session_file) . " bytes<br>";
} else {
    echo "Session file NOT found!<br>";
}

echo "<h2>Server Info:</h2>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "<h2 style='color:green;'>User is logged in (user_id: " . $_SESSION['user_id'] . ")</h2>";
    
    // Try to load items/list.php
    echo "<h2>Testing items/list.php:</h2>";
    echo "<a href='modules/items/list.php' target='_blank'>Click here to test items/list.php</a><br>";
    echo "<a href='modules/dashboard.php' target='_blank'>Click here to test dashboard.php</a>";
} else {
    echo "<h2 style='color:red;'>User is NOT logged in</h2>";
    echo "<a href='modules/login.php'>Go to Login</a>";
}
?>