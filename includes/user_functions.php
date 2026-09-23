<?php
function logActivity($pdo, $user_id, $action, $description = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $action, $description, $ip]);
}
?>