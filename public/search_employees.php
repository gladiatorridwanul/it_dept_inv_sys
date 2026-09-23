<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$term = $_GET['term'] ?? '';
if(strlen($term) < 1) { echo json_encode([]); exit; }

$stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation FROM employees WHERE is_active=1 AND (pf_no LIKE ? OR full_name LIKE ?) LIMIT 20");
$searchTerm = "%$term%";
$stmt->execute([$searchTerm, $searchTerm]);
$employees = $stmt->fetchAll();

$results = [];
foreach($employees as $emp) {
    $results[] = ['id' => $emp['id'], 'text' => $emp['pf_no'] . ' - ' . $emp['full_name'] . ' (' . ($emp['designation'] ?? 'N/A') . ')'];
}
echo json_encode($results);
?>