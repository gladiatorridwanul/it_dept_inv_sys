<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Only admin can access this page
if($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Complete module list
$modules = [
    'dashboard' => ['view'],
    'users' => ['view', 'create', 'edit', 'delete'],
    'permissions' => ['view'],
    'roles' => ['view', 'create', 'edit', 'delete'],
    'items' => ['view', 'create', 'edit', 'delete'],
    'brands' => ['view', 'create', 'edit', 'delete'],
    'vendors' => ['view', 'create', 'edit', 'delete'],
    'employees' => ['view', 'create', 'edit', 'delete'],
    'categories' => ['view', 'create', 'edit', 'delete'],
    'stock' => ['view', 'create', 'edit', 'delete'],
    'bills' => ['view', 'create', 'edit', 'delete'],
    'assignments' => ['view', 'create', 'edit', 'delete', 'print', 'barcode'],
    'requests' => ['view', 'create', 'edit', 'process', 'delete'],
    'returns' => ['view', 'create', 'process', 'approve'],
    'damages' => ['view', 'create', 'edit', 'delete', 'report'],
    'unlisted_devices' => ['view', 'create', 'edit', 'process', 'delete'],
    'license_warranty' => ['view', 'create', 'edit', 'delete'],
    'reports' => ['view', 'export'],
    'cash_register' => ['view', 'create', 'edit']
];

// Module display names
$module_display_names = [
    'dashboard' => 'Dashboard',
    'users' => 'User Management',
    'permissions' => 'Permissions',
    'roles' => 'Roles',
    'items' => 'Items / Devices',
    'brands' => 'Brands',
    'vendors' => 'Vendors',
    'employees' => 'Employees',
    'categories' => 'Categories',
    'stock' => 'Stock Management',
    'bills' => 'Bill Management',
    'assignments' => 'Assignments',
    'requests' => 'IT Support Requests',
    'returns' => 'Return Management',
    'damages' => 'Damage Management',
    'unlisted_devices' => 'Device Discovery',
    'license_warranty' => 'License & Warranty',
    'reports' => 'Reports',
    'cash_register' => 'Cash Register / Finance'
];

// Get all roles
$roles = $pdo->query("SELECT id, name, display_name FROM roles ORDER BY id")->fetchAll();

// If no roles found, insert default roles
if(empty($roles)) {
    $pdo->exec("INSERT INTO roles (id, name, display_name) VALUES (1, 'admin', 'Administrator'), (2, 'it_staff', 'IT Staff')");
    $roles = $pdo->query("SELECT id, name, display_name FROM roles ORDER BY id")->fetchAll();
}

$selected_role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : ($roles[0]['id'] ?? 1);
$current_role = null;

foreach($roles as $role) {
    if($role['id'] == $selected_role_id) {
        $current_role = $role;
        break;
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_permissions'])) {
    $role_id = intval($_POST['role_id']);
    $role_name = '';

    // Get role name
    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
    $stmt->execute([$role_id]);
    $roleData = $stmt->fetch();
    if($roleData) {
        $role_name = $roleData['name'];
    } else {
        $role_name = $role_id == 1 ? 'admin' : 'it_staff';
    }
    
    // Delete existing permissions for this role
    $stmt = $pdo->prepare("DELETE FROM permissions WHERE role_id = ?");
    $stmt->execute([$role_id]);
    
    // Insert new permissions
    $insert_stmt = $pdo->prepare("INSERT INTO permissions (role_id, role, module, permission) VALUES (?, ?, ?, ?)");
    
    foreach($modules as $module => $perms) {
        foreach($perms as $perm) {
            $field_name = 'perm_' . $module . '_' . $perm;
            if(isset($_POST[$field_name]) && $_POST[$field_name] == '1') {
                $insert_stmt->execute([$role_id, $role_name, $module, $perm]);
            }
        }
    }
    
    $_SESSION['success'] = "Permissions updated successfully for " . htmlspecialchars($current_role['display_name']);
    header("Location: permissions.php?role_id=" . $role_id);
    exit();
}

// Get current permissions for the selected role
$current_perms = [];
if($current_role) {
    $stmt = $pdo->prepare("SELECT module, permission FROM permissions WHERE role_id = ?");
    $stmt->execute([$current_role['id']]);
    while($row = $stmt->fetch()) {
        $current_perms[$row['module']][$row['permission']] = true;
    }
}
?>

<style>
    .permission-table { font-size: 12px; }
    .permission-table th { background: #f8fafc; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 8px; }
    .permission-table td { padding: 10px 8px; vertical-align: middle; }
    .permission-checkbox { width: 18px; height: 18px; cursor: pointer; }
    .role-tab {
        display: inline-block;
        padding: 8px 20px;
        border-radius: 30px;
        text-decoration: none;
        margin-right: 8px;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .role-tab.active { background: #3b82f6; color: white; }
    .role-tab.inactive { background: #f1f5f9; color: #475569; }
    .role-tab.inactive:hover { background: #e2e8f0; }
    .select-all-btn { cursor: pointer; font-size: 10px; color: #3b82f6; }
    .table-container { max-height: 600px; overflow-y: auto; }
    .sticky-header { position: sticky; top: 0; background: white; z-index: 10; }
    .permission-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; }
    .badge-view { background: #dbeafe; color: #1e40af; }
    .badge-create { background: #d1fae5; color: #065f46; }
    .badge-edit { background: #fef3c7; color: #92400e; }
    .badge-delete { background: #fee2e2; color: #991b1b; }
    .badge-process { background: #e0e7ff; color: #3730a3; }
    .badge-export { background: #f3e8ff; color: #6b21a5; }
    .badge-print { background: #cffafe; color: #0891b2; }
    .badge-barcode { background: #fce7f3; color: #9d174d; }
    .badge-approve { background: #dcfce7; color: #166534; }
    .badge-report { background: #fff7ed; color: #9a3412; }
    .card { border: none; border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .card-header { background: white; border-bottom: 1px solid #e2e8f0; }
</style>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-lock text-primary me-2"></i>Permission Management</h4>
            <p class="text-muted small mb-0">Control menu and button access for user roles</p>
        </div>
        <a href="list.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Users
        </a>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Role Selection Tabs -->
    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach($roles as $role): ?>
            <a href="?role_id=<?php echo $role['id']; ?>" class="role-tab <?php echo ($current_role && $current_role['id'] == $role['id']) ? 'active' : 'inactive'; ?>">
                <i class="fas fa-<?php echo $role['name'] == 'admin' ? 'crown' : 'user-shield'; ?> me-1"></i>
                <?php echo htmlspecialchars($role['display_name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if($current_role): ?>
    <div class="card">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Permissions for: <?php echo htmlspecialchars($current_role['display_name']); ?>
                </h6>
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="selectAll()">
                        <i class="fas fa-check-double me-1"></i> Select All
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="deselectAll()">
                        <i class="fas fa-times me-1"></i> Deselect All
                    </button>
                </div>
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="role_id" value="<?php echo $current_role['id']; ?>">
            <input type="hidden" name="update_permissions" value="1">
            
            <div class="table-container">
                <table class="table table-bordered permission-table mb-0">
                    <thead class="sticky-header">
                        <tr>
                            <th style="width: 200px;">Module</th>
                            <th class="text-center" style="width: 80px;">View <br><small onclick="toggleColumn('view')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Create <br><small onclick="toggleColumn('create')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Edit <br><small onclick="toggleColumn('edit')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Delete <br><small onclick="toggleColumn('delete')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Process <br><small onclick="toggleColumn('process')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Export <br><small onclick="toggleColumn('export')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Print <br><small onclick="toggleColumn('print')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Barcode <br><small onclick="toggleColumn('barcode')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Approve <br><small onclick="toggleColumn('approve')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                            <th class="text-center" style="width: 80px;">Report <br><small onclick="toggleColumn('report')" style="cursor:pointer;color:#3b82f6;">[All]</small></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($modules as $module => $perms): 
                            $display_name = $module_display_names[$module] ?? ucfirst(str_replace('_', ' ', $module));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $display_name; ?></strong>
                                <div class="mt-1">
                                    <?php foreach($perms as $p): ?>
                                    <span class="permission-badge badge-<?php echo $p; ?> me-1"><?php echo ucfirst($p); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted"><?php echo $module; ?></small>
                             </div>
                            <?php 
                            $all_perms = ['view', 'create', 'edit', 'delete', 'process', 'export', 'print', 'barcode', 'approve', 'report'];
                            foreach($all_perms as $perm): 
                            ?>
                            <td class="text-center">
                                <?php if(in_array($perm, $perms)): ?>
                                <input type="checkbox" 
                                       name="perm_<?php echo $module . '_' . $perm; ?>" 
                                       value="1" 
                                       class="permission-checkbox perm-<?php echo $perm; ?>"
                                       <?php echo isset($current_perms[$module][$perm]) ? 'checked' : ''; ?>>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                             </div>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer bg-white py-3">
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fas fa-save me-2"></i> Save Permissions
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Permission Types Info -->
    <div class="alert alert-light border py-2 small mt-4">
        <div class="d-flex flex-wrap gap-3">
            <span><span class="badge bg-primary me-1">View</span> - View module/list page</span>
            <span><span class="badge bg-success me-1">Create</span> - Add new records</span>
            <span><span class="badge bg-warning me-1">Edit</span> - Modify existing records</span>
            <span><span class="badge bg-danger me-1">Delete</span> - Remove records</span>
            <span><span class="badge bg-info me-1">Process</span> - Approve/reject requests</span>
            <span><span class="badge bg-secondary me-1">Export</span> - Export data</span>
            <span><span class="badge bg-cyan me-1">Print</span> - Print documents</span>
            <span><span class="badge bg-pink me-1">Barcode</span> - Generate barcode</span>
            <span><span class="badge bg-green me-1">Approve</span> - Approve returns</span>
            <span><span class="badge bg-orange me-1">Report</span> - Generate reports</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleColumn(perm) {
    const checkboxes = document.querySelectorAll(`.perm-${perm}`);
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}

function selectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
}
</script>

<?php include '../../includes/footer.php'; ?>