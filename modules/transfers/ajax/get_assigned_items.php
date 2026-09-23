<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT DISTINCT 
                i.id, 
                i.item_code, 
                i.name, 
                i.specification, 
                i.brand_id,
                b.name as brand_name,
                i.total_assigned
            FROM items i
            LEFT JOIN brands b ON i.brand_id = b.id
            WHERE i.is_active = 1 
            AND i.total_assigned > 0
            AND (i.name LIKE ? OR i.item_code LIKE ? OR i.specification LIKE ?)
            ORDER BY i.item_code ASC
            LIMIT 100";
    
    $searchParam = "%$search%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
    $items = $stmt->fetchAll();
    
    $formattedItems = [];
    foreach($items as $item) {
        $stmt2 = $pdo->prepare("
            SELECT COUNT(DISTINCT a.id) as assignment_count,
                   COUNT(DISTINCT isn.id) as serial_count,
                   GROUP_CONCAT(DISTINCT e.full_name SEPARATOR ', ') as assigned_to_employees
            FROM item_serial_numbers isn
            INNER JOIN assignments a ON isn.assignment_id = a.id
            INNER JOIN employees e ON a.employee_id = e.id
            WHERE isn.item_id = ? AND isn.is_assigned = 1 AND a.status = 'assigned'
        ");
        $stmt2->execute([$item['id']]);
        $assignmentInfo = $stmt2->fetch();
        
        $stmt3 = $pdo->prepare("
            SELECT serial_number, model_number, version
            FROM item_serial_numbers 
            WHERE item_id = ? AND is_assigned = 1
            ORDER BY serial_number ASC
        ");
        $stmt3->execute([$item['id']]);
        $serials = $stmt3->fetchAll();
        
        $formattedItems[] = [
            'id' => $item['id'],
            'item_code' => $item['item_code'],
            'name' => $item['name'],
            'brand_name' => $item['brand_name'],
            'specification' => $item['specification'],
            'assigned_count' => $assignmentInfo['assignment_count'] ?? 0,
            'serial_count' => $assignmentInfo['serial_count'] ?? 0,
            'assigned_to_employees' => $assignmentInfo['assigned_to_employees'] ?? '',
            'serials' => $serials
        ];
    }
    
    echo json_encode(['success' => true, 'items' => $formattedItems]);
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>