<?php
require_once '../../../config/database.php';
header('Content-Type: application/json');

if(!isset($_GET['category_id'])) {
    echo json_encode([]);
    exit();
}

$category_id = intval($_GET['category_id']);

$stmt = $pdo->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY name");
$stmt->execute([$category_id]);
$sub_categories = $stmt->fetchAll();

echo json_encode($sub_categories);
?>