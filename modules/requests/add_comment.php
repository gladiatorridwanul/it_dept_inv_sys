<?php
require_once '../../includes/auth.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $comment = $_POST['comment'];
    
    if(!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO request_comments (request_id, comment, commented_by) VALUES (?, ?, ?)");
        $stmt->execute([$request_id, $comment, $_SESSION['user_id']]);
    }
    
    header("Location: view_request.php?id=$request_id");
    exit();
}
?>