<?php
require_once '../../config/database.php';
session_start();

header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$request_id = $_GET['request_id'] ?? 0;

if($request_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM request_attachments WHERE request_id = ? ORDER BY created_at DESC");
    $stmt->execute([$request_id]);
    $attachments = $stmt->fetchAll();
    
    // Get base URL for attachments
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . $host . '/';
    
    foreach($attachments as &$att) {
        $att['created_at'] = date('d-m-Y H:i:s', strtotime($att['created_at']));
        // Fix file path - remove leading slash if exists
        $file_path = ltrim($att['file_path'], '/');
        $att['file_url'] = $base_url . $file_path;
        $att['file_name'] = basename($att['file_name']);
        
        // Get file extension for preview type
        $ext = strtolower(pathinfo($att['file_name'], PATHINFO_EXTENSION));
        $att['file_ext'] = $ext;
        $att['is_image'] = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']);
    }
    
    echo json_encode(['success' => true, 'attachments' => $attachments]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request ID']);
}
?>