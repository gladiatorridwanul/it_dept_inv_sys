<?php
// Helper functions for request management
// Check if functions already exist before declaring to prevent redeclaration errors

if (!function_exists('generateRequestNumber')) {
    function generateRequestNumber($prefix, $table, $column) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING($column, LOCATE('-', $column) + 1) AS UNSIGNED)) as max_num 
                               FROM $table WHERE $column LIKE ?");
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch();
        $next_num = ($result['max_num'] ?? 0) + 1;
        return $prefix . str_pad($next_num, 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('uploadRequestFile')) {
    function uploadRequestFile($file, $subfolder, $prefix) {
        if(isset($file) && $file['error'] == 0 && $file['size'] > 0) {
            $upload_dir = '../uploads/' . $subfolder . '/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if(in_array($ext, $allowed)) {
                $new_filename = $prefix . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $destination = $upload_dir . $new_filename;
                
                if(move_uploaded_file($file['tmp_name'], $destination)) {
                    return 'uploads/' . $subfolder . '/' . $new_filename;
                }
            }
        }
        return null;
    }
}

if (!function_exists('getRequestTypeIcon')) {
    function getRequestTypeIcon($type) {
        $icons = [
            'technical_support' => '<i class="fas fa-exclamation-triangle text-danger"></i>',
            'software_access' => '<i class="fas fa-key text-primary"></i>',
            'accessories' => '<i class="fas fa-mouse text-warning"></i>',
            'return_device' => '<i class="fas fa-undo-alt text-info"></i>',
            'update' => '<i class="fas fa-edit text-secondary"></i>',
            'upgrade' => '<i class="fas fa-arrow-up text-success"></i>',
            'assign' => '<i class="fas fa-laptop text-primary"></i>',
            'device_assign' => '<i class="fas fa-laptop-house text-primary"></i>',
            'replace' => '<i class="fas fa-exchange-alt text-warning"></i>',
            'handover' => '<i class="fas fa-handshake text-info"></i>',
        ];
        return $icons[$type] ?? '<i class="fas fa-ticket-alt"></i>';
    }
}

if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> Pending</span>',
            'under_observation' => '<span class="badge bg-info"><i class="fas fa-eye"></i> Under Observation</span>',
            'processing' => '<span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> Processing</span>',
            'completed' => '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Completed</span>',
            'rejected' => '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>',
        ];
        return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
    }
}

if (!function_exists('getPriorityBadge')) {
    function getPriorityBadge($priority) {
        $badges = [
            'low' => '<span class="badge bg-secondary"><i class="fas fa-arrow-down"></i> Low</span>',
            'medium' => '<span class="badge bg-info"><i class="fas fa-equals"></i> Medium</span>',
            'high' => '<span class="badge bg-warning text-dark"><i class="fas fa-arrow-up"></i> High</span>',
            'critical' => '<span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> Critical</span>',
        ];
        return $badges[$priority] ?? '<span class="badge bg-secondary">Medium</span>';
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo($url) {
        header("Location: $url");
        exit();
    }
}

// Note: generateNumber() function is already in config/database.php
// Do NOT redeclare it here - use the one from database.php
?>