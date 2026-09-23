<?php
require_once '../../../config/database.php';
require_once '../../../config/session_fix.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['return_request_id']) || empty($_POST['return_request_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
    exit();
}

$return_request_id = intval($_POST['return_request_id']);

try {
    // Get return request details
    $stmt = $pdo->prepare("
        SELECT 
            rdr.id,
            rdr.request_no,
            rdr.return_reason,
            rdr.reason_details,
            rdr.cleaning_done,
            rdr.data_backup_confirmed,
            rdr.exit_clearance,
            rdr.total_devices,
            rdr.created_at,
            r.status as request_status,
            r.requested_date,
            e.id as employee_id,
            e.full_name as employee_name,
            e.pf_no,
            e.designation,
            e.department,
            e.phone,
            e.email
        FROM return_device_requests rdr
        JOIN requests r ON rdr.request_id = r.id
        JOIN employees e ON r.employee_id = e.id
        WHERE rdr.id = ?
    ");
    $stmt->execute([$return_request_id]);
    $returnRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$returnRequest) {
        echo json_encode(['success' => false, 'message' => 'Return request not found']);
        exit();
    }

    // Get devices for this request
    $stmt = $pdo->prepare("
        SELECT 
            rdi.id,
            rdi.assignment_id,
            rdi.device_condition,
            rdi.damage_description,
            rdi.accessories_returned,
            rdi.processing_status,
            rdi.stock_added_date,
            a.assignment_no,
            i.id as item_id,
            i.name as item_name,
            i.item_code,
            i.serial_number,
            i.model_number,
            i.brand,
            isn.serial_number as actual_serial,
            isn.model_number as actual_model,
            isn.version
        FROM return_device_items rdi
        JOIN assignments a ON rdi.assignment_id = a.id
        JOIN items i ON a.item_id = i.id
        LEFT JOIN item_serial_numbers isn ON a.id = isn.assignment_id AND isn.is_assigned = 1
        WHERE rdi.return_request_id = ?
    ");
    $stmt->execute([$return_request_id]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get approval history
    $stmt = $pdo->prepare("
        SELECT 
            ra.id,
            ra.action,
            ra.notes,
            ra.created_at,
            u.full_name as processed_by_name
        FROM return_approvals ra
        LEFT JOIN users u ON ra.processed_by = u.id
        WHERE ra.return_request_id = ?
        ORDER BY ra.created_at DESC
    ");
    $stmt->execute([$return_request_id]);
    $approvals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare response data
    $data = [
        'return_info' => [
            'id' => $returnRequest['id'],
            'request_no' => $returnRequest['request_no'],
            'return_reason' => $returnRequest['return_reason'],
            'reason_details' => $returnRequest['reason_details'],
            'cleaning_done' => (bool)$returnRequest['cleaning_done'],
            'data_backup_confirmed' => (bool)$returnRequest['data_backup_confirmed'],
            'exit_clearance' => (bool)$returnRequest['exit_clearance'],
            'total_devices' => $returnRequest['total_devices'],
            'status' => $returnRequest['request_status'],
            'created_at' => $returnRequest['created_at'],
            'requested_date' => $returnRequest['requested_date']
        ],
        'employee' => [
            'id' => $returnRequest['employee_id'],
            'name' => $returnRequest['employee_name'],
            'pf_no' => $returnRequest['pf_no'],
            'designation' => $returnRequest['designation'],
            'department' => $returnRequest['department'],
            'phone' => $returnRequest['phone'],
            'email' => $returnRequest['email']
        ],
        'devices' => array_map(function($device) {
            return [
                'id' => $device['id'],
                'assignment_id' => $device['assignment_id'],
                'assignment_no' => $device['assignment_no'],
                'item_id' => $device['item_id'],
                'item_name' => $device['item_name'],
                'item_code' => $device['item_code'],
                'serial_number' => $device['actual_serial'] ?? $device['serial_number'] ?? 'N/A',
                'model_number' => $device['actual_model'] ?? $device['model_number'] ?? 'N/A',
                'version' => $device['version'] ?? '',
                'brand' => $device['brand'] ?? 'N/A',
                'device_condition' => $device['device_condition'],
                'damage_description' => $device['damage_description'],
                'accessories_returned' => $device['accessories_returned'],
                'processing_status' => $device['processing_status'] ?? 'pending',
                'stock_added_date' => $device['stock_added_date']
            ];
        }, $devices),
        'approvals' => array_map(function($approval) {
            return [
                'id' => $approval['id'],
                'action' => $approval['action'],
                'notes' => $approval['notes'],
                'created_at' => $approval['created_at'],
                'processed_by' => $approval['processed_by_name'] ?? 'System'
            ];
        }, $approvals)
    ];

    echo json_encode(['success' => true, 'data' => $data]);

} catch(PDOException $e) {
    error_log("Error loading return details: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>