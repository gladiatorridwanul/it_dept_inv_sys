<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../../config/database.php';
session_start();

$response = ['success' => false, 'message' => ''];

if($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['submission_id'])) {
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit();
}

$submission_id = intval($_POST['submission_id']);
$action = $_POST['action'];
$admin_notes = $_POST['admin_notes'] ?? '';
$user_id = $_SESSION['user_id'] ?? 1;

try {
    $pdo->beginTransaction();
    
    $sub_stmt = $pdo->prepare("
        SELECT s.*, e.id as employee_id, e.pf_no, e.full_name 
        FROM unlisted_device_submissions s
        JOIN employees e ON s.employee_id = e.id
        WHERE s.id = ?
    ");
    $sub_stmt->execute([$submission_id]);
    $submission = $sub_stmt->fetch();
    
    if(!$submission) {
        throw new Exception('Submission not found');
    }
    
    if($action == 'approve') {
        $update_devices = $pdo->prepare("
            UPDATE submission_devices 
            SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?)
            WHERE submission_id = ? AND status = 'pending'
        ");
        $update_devices->execute([$user_id, $admin_notes, $submission_id]);
        
        $sub_status = 'approved';
        $message = "All devices approved successfully!";
        
    } elseif($action == 'reject') {
        $update_devices = $pdo->prepare("
            UPDATE submission_devices 
            SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?)
            WHERE submission_id = ? AND status = 'pending'
        ");
        $update_devices->execute([$user_id, $admin_notes, $submission_id]);
        
        $sub_status = 'rejected';
        $message = "Submission rejected!";
        
    } else {
        throw new Exception('Invalid action');
    }
    
    $update_sub = $pdo->prepare("
        UPDATE unlisted_device_submissions 
        SET status = ?, admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n', ?), reviewed_by = ?, reviewed_at = NOW()
        WHERE id = ?
    ");
    $update_sub->execute([$sub_status, $admin_notes, $user_id, $submission_id]);
    
    $pdo->commit();
    
    $response['success'] = true;
    $response['message'] = $message;
    
} catch(Exception $e) {
    $pdo->rollBack();
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log("process_submission.php error: " . $e->getMessage());
}

echo json_encode($response);
?>