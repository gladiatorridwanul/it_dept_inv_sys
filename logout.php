<?php
// logout.php (app root)

// Disable caching so the back button can't show a cached authenticated page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Bootstrap the session EXACTLY the same way every other page does (same
// save path, same cookie params). Do NOT call session_start() directly -
// if the params here don't match what the cookie was actually set with,
// the clearing cookie below won't match it and the browser won't drop it.
require_once 'config/session_fix.php';

// Log who is logging out (before we wipe the session)
if (isset($_SESSION['user_id'])) {
    error_log("Logout for user_id: " . $_SESSION['user_id'] . " (" . ($_SESSION['username'] ?? 'unknown') . ")");
}

// Grab the REAL cookie params (domain/path/secure/httponly) that
// session_fix.php just set, before we destroy the session.
$params = session_get_cookie_params();

// Wipe all session data server-side
$_SESSION = array();
session_unset();
session_destroy();

// Expire the session cookie in the browser using the SAME params it was
// created with. Mismatched params here = browser silently keeps the old
// cookie and "logout" doesn't actually stick.
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to public index
header("Location: /public/index.php");
exit();