<?php
// For cPanel root directory with subfolder
// Your database name is 'bhsheadache_in_inventory', so your subfolder is likely the same
define('BASE_URL', '/it-inventory.bhs-headache.org');
define('BASE_PATH', dirname(__DIR__));

function url($path = '') {
    $clean_path = ltrim($path, '/');
    return BASE_URL . '/' . $clean_path;
}

function redirect_url($path) {
    $full_url = url($path);
    if (!headers_sent()) {
        header("Location: $full_url");
        exit();
    }
}
?>