<?php
function getDeviceTypes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM device_types WHERE is_active=1 ORDER BY category, sort_order");
    $types = [];
    while($row = $stmt->fetch()) {
        $types[$row['category']][] = $row;
    }
    return $types;
}

function getDeviceTypeOptions($selected = null) {
    $types = getDeviceTypes();
    $html = '<option value="">Select Device Type</option>';
    foreach($types as $category => $devices) {
        $html .= '<optgroup label="' . ucfirst($category) . '">';
        foreach($devices as $device) {
            $selected_attr = ($selected == $device['name']) ? 'selected' : '';
            $html .= '<option value="' . htmlspecialchars($device['name']) . '" ' . $selected_attr . '>' . $device['name'] . '</option>';
        }
        $html .= '</optgroup>';
    }
    return $html;
}

function getAssignmentStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>',
        'under_observation' => '<span class="badge bg-info"><i class="fas fa-search"></i> Under Observation</span>',
        'approved' => '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Approved</span>',
        'rejected' => '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>',
        'completed' => '<span class="badge bg-primary"><i class="fas fa-check-double"></i> Completed</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}
?>