<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_POST['item_id'])) {
    echo json_encode(['success' => false, 'error' => 'Item ID required', 'returns' => []]);
    exit();
}

$item_id = intval($_POST['item_id']);
$serial_number = isset($_POST['serial_number']) && !empty($_POST['serial_number']) ? $_POST['serial_number'] : null;

// Build query for return requests related to this item - ALL STATUSES
$query = "
    SELECT 
        rdr.id,
        rdr.request_no,
        rdr.return_reason,
        rdr.reason_details,
        rdr.created_at,
        r.status,
        e.full_name as employee_name,
        e.pf_no,
        i.name as item_name,
        i.item_code,
        isn.serial_number,
        isn.model_number,
        isn.version
    FROM return_device_requests rdr
    JOIN requests r ON rdr.request_id = r.id
    JOIN employees e ON r.employee_id = e.id
    JOIN assignments a ON rdr.assignment_id = a.id
    JOIN items i ON a.item_id = i.id
    LEFT JOIN item_serial_numbers isn ON a.item_id = isn.item_id AND a.id = isn.assignment_id
    WHERE i.id = ?
";

$params = [$item_id];

if($serial_number) {
    $query .= " AND isn.serial_number = ?";
    $params[] = $serial_number;
}

$query .= " ORDER BY rdr.created_at DESC LIMIT 20";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$returns = $stmt->fetchAll();

// If no results with serial filter, try without it
if(empty($returns) && $serial_number) {
    $stmt = $pdo->prepare("
        SELECT 
            rdr.id,
            rdr.request_no,
            rdr.return_reason,
            rdr.reason_details,
            rdr.created_at,
            r.status,
            e.full_name as employee_name,
            e.pf_no,
            i.name as item_name,
            i.item_code
        FROM return_device_requests rdr
        JOIN requests r ON rdr.request_id = r.id
        JOIN employees e ON r.employee_id = e.id
        JOIN assignments a ON rdr.assignment_id = a.id
        JOIN items i ON a.item_id = i.id
        WHERE i.id = ?
        ORDER BY rdr.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$item_id]);
    $returns = $stmt->fetchAll();
}

echo json_encode([
    'success' => true,
    'returns' => $returns,
    'has_returns' => count($returns) > 0
]);
?>