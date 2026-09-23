<?php
require_once '../../includes/auth.php';

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if($action == 'approve') {
    $status = 'approved';
} elseif($action == 'reject') {
    $status = 'rejected';
} else {
    header("Location: list_requests.php");
    exit();
}

$stmt = $pdo->prepare("UPDATE device_assignment_requests SET status = ?, approved_by = ?, approved_date = NOW() WHERE id = ?");
$stmt->execute([$status, $_SESSION['user_id'], $id]);

header("Location: list_requests.php");
exit();
?>