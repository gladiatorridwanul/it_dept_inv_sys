<?php
require_once '../../config/database.php';

echo "<h2>Fixing Request Types</h2>";

$fixes = [
    'ISS-' => 'technical_support',
    'HND-' => 'handover',
    'UPG-' => 'upgrade',
    'RET-' => 'return_device',
    'ACCY-' => 'accessories',
    'ACC-' => 'software_access',
    'UPD-' => 'update',
    'ASN-' => 'assign',
    'DEVASN-' => 'device_assign'
];

$total_fixed = 0;

foreach($fixes as $prefix => $type) {
    $stmt = $pdo->prepare("UPDATE requests SET request_type = ? WHERE request_no LIKE ? AND (request_type IS NULL OR request_type = '' OR request_type IN ('pending', 'under_observation', 'processing', 'completed', 'rejected', 'approved'))");
    $stmt->execute([$type, $prefix . '%']);
    $fixed = $stmt->rowCount();
    $total_fixed += $fixed;
    echo "<p>Fixed $fixed records with prefix $prefix to type $type</p>";
}

echo "<h3>Total fixed: $total_fixed requests</h3>";
echo "<p><a href='all_requests.php' class='btn btn-primary'>Go to All Requests</a></p>";
?>