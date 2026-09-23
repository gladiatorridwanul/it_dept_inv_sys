<?php
// config/database.php

$host = 'localhost';
$dbname = 'bhsheadache_in_inventory';
$username = 'bhsheadache_in_inventory';
$password = '<Admin123!@#>';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Make $pdo available globally
$GLOBALS['pdo'] = $pdo;

// Function to generate unique number
if (!function_exists('generateNumber')) {
    function generateNumber($prefix, $table, $column) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING($column, LOCATE('-', $column) + 1) AS UNSIGNED)) as max_num 
                               FROM $table WHERE $column LIKE ?");
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();
        $next_num = ($result['max_num'] ?? 0) + 1;
        return $prefix . str_pad($next_num, 6, '0', STR_PAD_LEFT);
    }
}
?>