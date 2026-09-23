<?php
// includes/permissions.php - Permission helper functions

function hasPermission($pdo, $user_role, $module, $permission = 'view') {
    // Admin has all permissions
    if($user_role == 'admin') return true;
    
    static $permissions_cache = [];
    $key = $user_role . '_' . $module . '_' . $permission;
    
    if(!isset($permissions_cache[$key])) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM permissions WHERE role = ? AND module = ? AND permission = ?");
            $stmt->execute([$user_role, $module, $permission]);
            $permissions_cache[$key] = $stmt->fetchColumn() > 0;
        } catch(Exception $e) {
            $permissions_cache[$key] = false;
        }
    }
    return $permissions_cache[$key];
}

function canView($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'view');
}

function canCreate($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'create');
}

function canEdit($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'edit');
}

function canDelete($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'delete');
}

function canProcess($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'process');
}

function canExport($pdo, $user_role, $module) {
    return hasPermission($pdo, $user_role, $module, 'export');
}
?>