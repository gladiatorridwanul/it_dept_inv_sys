<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fix the path - use absolute path or correct relative path
// Since we're in /modules/assignments/ajax/, we need to go up 3 levels
// /modules/assignments/ajax/ -> /modules/assignments/ -> /modules/ -> / (root)

// Option 1: Use absolute path from document root
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

// Option 2: Use relative path (go up 3 levels)
// require_once '../../../config/database.php';

header('Content-Type: application/json');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// If no search term, return empty
if(empty($search) || strlen($search) < 1) {
    echo json_encode([
        'success' => false,
        'message' => 'Please enter at least 1 character',
        'items' => []
    ]);
    exit;
}

try {
    $search_param = "%$search%";
    
    // SIMPLE QUERY - Only from assignments table
    $sql = "
        SELECT 
            a.id as assignment_id,
            a.assignment_no,
            a.employee_id,
            a.assigned_date,
            a.status,
            a.source,
            a.quantity,
            i.id as item_id,
            i.item_code,
            i.name as item_name,
            i.specification,
            i.brand_id,
            e.full_name as employee_name,
            e.pf_no,
            e.designation,
            e.department as employee_department
        FROM assignments a
        INNER JOIN items i ON a.item_id = i.id
        INNER JOIN employees e ON a.employee_id = e.id
        WHERE a.status = 'assigned'
        AND (a.return_status IS NULL OR a.return_status = 'active')
        AND (
            i.item_code LIKE :search 
            OR i.name LIKE :search 
            OR a.assignment_no LIKE :search
            OR e.full_name LIKE :search
            OR e.pf_no LIKE :search
        )
        ORDER BY a.assigned_date DESC
        LIMIT 30
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['search' => $search_param]);
    $results = $stmt->fetchAll();
    
    // Format results
    $items = [];
    foreach($results as $row) {
        $source = $row['source'] ?? 'admin';
        
        $items[] = [
            'id' => $row['item_id'],
            'name' => $row['item_name'],
            'item_code' => $row['item_code'],
            'specification' => $row['specification'],
            'brand_name' => '',
            'source' => $source,
            'assignment_id' => $row['assignment_id'],
            'assignment_no' => $row['assignment_no'],
            'assigned_date' => $row['assigned_date'],
            'employee_name' => $row['employee_name'],
            'employee_id' => $row['employee_id'],
            'pf_no' => $row['pf_no'],
            'designation' => $row['designation'],
            'employee_department' => $row['employee_department'],
            'assigned_count' => $row['quantity'] ?? 1
        ];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => count($items),
        'search' => $search
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>