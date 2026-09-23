<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Test database connection directly
require_once '../../config/database.php';

header('Content-Type: application/json');

// Simple test query to check database connection
try {
    // Test 1: Check connection
    $test = $pdo->query("SELECT 1 as test")->fetch();
    
    // Test 2: Check assignments table
    $table_check = $pdo->query("SHOW TABLES LIKE 'assignments'")->fetch();
    
    // Test 3: Check columns in assignments
    $columns = $pdo->query("SHOW COLUMNS FROM assignments")->fetchAll(PDO::FETCH_COLUMN);
    
    // Test 4: Simple query with search
    $search = isset($_GET['search']) ? $_GET['search'] : 'W';
    $search_param = "%$search%";
    
    $sql = "
        SELECT a.id, a.assignment_no, a.source, i.id as item_id, i.name as item_name, i.item_code
        FROM assignments a
        JOIN items i ON a.item_id = i.id
        WHERE a.status = 'assigned'
        AND (a.return_status IS NULL OR a.return_status = 'active')
        AND (i.name LIKE :search OR a.assignment_no LIKE :search)
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => $search_param]);
    $results = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'connection' => 'OK',
        'tables' => [
            'assignments_exists' => $table_check ? true : false
        ],
        'assignments_columns' => $columns,
        'query_results' => $results,
        'debug' => [
            'search' => $search,
            'search_param' => $search_param,
            'row_count' => count($results)
        ]
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>