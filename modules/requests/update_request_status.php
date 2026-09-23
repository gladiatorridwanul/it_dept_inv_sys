<?php
require_once '../../includes/auth.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $status = $_POST['status'];
    $priority = $_POST['priority'] ?? 'medium';
    $estimated_completion_date = $_POST['estimated_completion_date'] ?? null;
    $resolution_notes = $_POST['resolution_notes'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE requests SET 
                          status = ?, 
                          priority = ?, 
                          estimated_completion_date = ?, 
                          resolution_notes = ?,
                          processed_by = ?,
                          accepted_date = IF(accepted_date IS NULL AND status != 'pending', NOW(), accepted_date)
                          WHERE id = ?");
    
    if($stmt->execute([$status, $priority, $estimated_completion_date, $resolution_notes, $_SESSION['user_id'], $request_id])) {
        // Add to comments
        $comment = "Status changed to: " . ucfirst(str_replace('_', ' ', $status));
        $stmt = $pdo->prepare("INSERT INTO request_comments (request_id, comment, commented_by) VALUES (?, ?, ?)");
        $stmt->execute([$request_id, $comment, $_SESSION['user_id']]);
        
        header("Location: view_request.php?id=$request_id&success=1");
        exit();
    } else {
        header("Location: view_request.php?id=$request_id&error=1");
        exit();
    }
}

header("Location: all_requests.php");
exit();
?>