<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$device_id = $_POST['device_id'] ?? 0;
if($device_id <= 0) { echo json_encode(['success' => false]); exit; }

$stmt = $pdo->prepare("SELECT id, item_code, name, serial_number, specification, brand, model_number, current_qty FROM items WHERE id = ?");
$stmt->execute([$device_id]);
$device = $stmt->fetch();

if($device) {
    echo json_encode(['success' => true, 'item_code' => $device['item_code'], 'name' => $device['name'], 'serial_number' => $device['serial_number'], 'specification' => $device['specification'], 'brand' => $device['brand'], 'model_number' => $device['model_number'], 'current_qty' => $device['current_qty']]);
} else {
    echo json_encode(['success' => false]);
}
?>