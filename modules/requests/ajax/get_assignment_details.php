<?php
require_once '../../../config/database.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit();
}

$assignment_id = $_POST['assignment_id'] ?? $_GET['assignment_id'] ?? 0;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'message' => 'Assignment ID is required']);
    exit();
}

try {
    // Check if assignment exists
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit();
    }
    
    // Get full assignment details
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.assignment_no,
            a.quantity,
            a.assigned_date,
            a.expected_return_date,
            a.status,
            a.notes,
            a.returned_date,
            a.created_at,
            a.assigned_by,
            a.employee_id,
            a.item_id,
            -- Employee details
            e.full_name as employee_name,
            e.pf_no,
            e.designation,
            e.department,
            e.job_location,
            e.phone,
            e.email,
            -- Item details
            i.name as item_name,
            i.item_code,
            i.specification,
            i.brand,
            i.model_number as item_model,
            i.serial_number as item_serial,
            i.version as item_version,
            i.price,
            i.warranty_status,
            i.warranty_end_date,
            -- Serial number details from item_serial_numbers table
            isn.serial_number,
            isn.model_number,
            isn.version,
            -- Assigned by user
            u.full_name as assigned_by_name
        FROM assignments a
        LEFT JOIN employees e ON a.employee_id = e.id
        LEFT JOIN items i ON a.item_id = i.id
        LEFT JOIN item_serial_numbers isn ON a.id = isn.assignment_id
        LEFT JOIN users u ON a.assigned_by = u.id
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        // Determine status badge class
        $status_class = match(strtolower($assignment['status'])) {
            'assigned' => 'success',
            'active' => 'primary',
            'returned' => 'info',
            'damaged' => 'danger',
            'replaced' => 'warning',
            'pending' => 'warning',
            'cancelled' => 'secondary',
            default => 'secondary'
        };
        
        // Format dates
        $assigned_date = $assignment['assigned_date'] ? date('d-m-Y', strtotime($assignment['assigned_date'])) : 'N/A';
        $expected_return_date = $assignment['expected_return_date'] ? date('d-m-Y', strtotime($assignment['expected_return_date'])) : 'Not specified';
        $returned_date = $assignment['returned_date'] ? date('d-m-Y', strtotime($assignment['returned_date'])) : null;
        $warranty_end = $assignment['warranty_end_date'] ? date('d-m-Y', strtotime($assignment['warranty_end_date'])) : 'N/A';
        
        // Get warranty status badge
        $warranty_badge = '';
        if ($assignment['warranty_status'] == 'in_warranty') {
            $warranty_badge = '<span class="badge bg-success">In Warranty</span>';
        } elseif ($assignment['warranty_status'] == 'expiring_soon') {
            $warranty_badge = '<span class="badge bg-warning">Expiring Soon</span>';
        } elseif ($assignment['warranty_status'] == 'out_of_warranty') {
            $warranty_badge = '<span class="badge bg-danger">Out of Warranty</span>';
        } else {
            $warranty_badge = '<span class="badge bg-secondary">No Warranty Info</span>';
        }
        
        // Determine which serial number to use (from item_serial_numbers table or items table)
        $serial_number = $assignment['serial_number'] ?? $assignment['item_serial'] ?? 'N/A';
        $model_number = $assignment['model_number'] ?? $assignment['item_model'] ?? 'N/A';
        $version = $assignment['version'] ?? $assignment['item_version'] ?? 'N/A';
        
        $response = [
            'success' => true,
            'assignment_id' => $assignment['id'],
            'assignment_no' => $assignment['assignment_no'],
            'quantity' => $assignment['quantity'],
            'assigned_date' => $assigned_date,
            'expected_return_date' => $expected_return_date,
            'returned_date' => $returned_date,
            'status' => ucfirst($assignment['status']),
            'status_class' => $status_class,
            'notes' => $assignment['notes'] ?? 'No notes',
            'assigned_by_name' => $assignment['assigned_by_name'] ?? 'System',
            'created_at' => date('d-m-Y H:i', strtotime($assignment['created_at'])),
            
            // Employee details
            'employee_name' => $assignment['employee_name'] ?? 'Unknown',
            'pf_no' => $assignment['pf_no'] ?? 'N/A',
            'designation' => $assignment['designation'] ?? 'N/A',
            'department' => $assignment['department'] ?? 'N/A',
            'job_location' => $assignment['job_location'] ?? 'N/A',
            'employee_phone' => $assignment['phone'] ?? 'N/A',
            'employee_email' => $assignment['email'] ?? 'N/A',
            
            // Item details
            'item_name' => $assignment['item_name'] ?? 'N/A',
            'item_code' => $assignment['item_code'] ?? 'N/A',
            'specification' => $assignment['specification'] ?? 'N/A',
            'brand' => $assignment['brand'] ?? 'N/A',
            'serial_number' => $serial_number,
            'model_number' => $model_number,
            'version' => $version,
            'price' => $assignment['price'] ? number_format($assignment['price'], 2) : 'N/A',
            'warranty_status' => $assignment['warranty_status'] ?? 'unknown',
            'warranty_end_date' => $warranty_end,
            'warranty_badge' => $warranty_badge
        ];
        
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Assignment details not found']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in get_assignment_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in get_assignment_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>