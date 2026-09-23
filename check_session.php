<?php
session_start();
echo "<h1>Session Check</h1>";
echo "Session ID: " . session_id() . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if(empty($_SESSION)) {
    echo "<p style='color:green'>✓ Session is empty - Logout successful!</p>";
} else {
    echo "<p style='color:red'>✗ Session still has data!</p>";
}

echo "<br><a href='/public/index.php'>Go to Homepage</a>";
?>