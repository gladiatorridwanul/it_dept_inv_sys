<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$term = $_GET['term'] ?? '';
if(strlen($term) < 1) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT id, item_code, name, specification FROM items WHERE is_active=1 AND current_qty > 0 AND (item_code LIKE ? OR name LIKE ?) LIMIT 20");
$searchTerm = "%$term%";
$stmt->execute([$searchTerm, $searchTerm]);
$items = $stmt->fetchAll();

$results = [];
foreach($items as $item) {
    $results[] = ['id' => $item['id'], 'text' => $item['item_code'] . ' - ' . $item['name']];
}
echo json_encode($results);
?>