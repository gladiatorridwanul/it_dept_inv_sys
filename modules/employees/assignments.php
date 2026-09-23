<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if(!$employee) {
    redirect('list.php');
}

// Get assignments for this employee with item details
$stmt = $pdo->prepare("SELECT a.*, i.name as item_name, i.item_code, i.serial_number, i.model_number, i.brand, i.specification
                       FROM assignments a 
                       JOIN items i ON a.item_id = i.id 
                       WHERE a.employee_id = ? 
                       ORDER BY 
                           CASE a.status 
                               WHEN 'assigned' THEN 1 
                               WHEN 'damaged' THEN 2 
                               WHEN 'returned' THEN 3 
                               WHEN 'replaced' THEN 4 
                           END, 
                           a.assigned_date DESC");
$stmt->execute([$id]);
$assignments = $stmt->fetchAll();

// Get assignment statistics
$totalAssigned = count($assignments);
$activeAssignments = count(array_filter($assignments, function($a) { return $a['status'] == 'assigned'; }));
$returnedCount = count(array_filter($assignments, function($a) { return $a['status'] == 'returned'; }));
$damagedCount = count(array_filter($assignments, function($a) { return $a['status'] == 'damaged'; }));
$replacedCount = count(array_filter($assignments, function($a) { return $a['status'] == 'replaced'; }));

// Get total items currently assigned (sum of quantities)
$totalItemsAssigned = array_sum(array_filter(array_column($assignments, 'quantity'), function($key) use ($assignments) {
    return $assignments[$key]['status'] == 'assigned';
}, ARRAY_FILTER_USE_KEY));

// Get employee contact info
$employeeContact = [];
if($employee['phone']) $employeeContact[] = '<i class="fas fa-phone-alt me-1"></i> ' . htmlspecialchars($employee['phone']);
if($employee['email']) $employeeContact[] = '<i class="fas fa-envelope me-1"></i> ' . htmlspecialchars($employee['email']);
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-600: #475569;
        --gray-700: #334155;
    }

    /* Profile Header */
    .profile-header {
        background: var(--primary-gradient);
        color: white;
        padding: 1.5rem 1.75rem;
        border-radius: 20px;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .profile-avatar {
        width: 65px;
        height: 65px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1.25rem;
    }
    .profile-avatar i {
        font-size: 2.5rem;
    }
    .profile-name {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    .profile-meta {
        font-size: 0.7rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    .profile-meta i {
        margin-right: 4px;
    }

    /* Statistics Cards */
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1rem 0.75rem;
        text-align: center;
        transition: all 0.2s;
        border: 1px solid var(--gray-200);
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .stat-icon {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }
    .stat-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        margin-bottom: 0;
    }
    .stat-sub {
        font-size: 0.6rem;
        color: #94a3b8;
    }

    /* Cards */
    .info-card {
        background: white;
        border-radius: 18px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        margin-bottom: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .card-header-custom {
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: var(--gray-50);
    }
    .card-header-primary {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        border-bottom: none;
    }
    .card-header-info {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        color: white;
        border-bottom: none;
    }

    /* Tables */
    .assignments-table {
        width: 100%;
        margin-bottom: 0;
    }
    .assignments-table th {
        background: var(--gray-50);
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--gray-600);
        padding: 0.75rem 0.875rem;
        border-bottom: 1px solid var(--gray-200);
    }
    .assignments-table td {
        font-size: 0.7rem;
        padding: 0.75rem 0.875rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--gray-100);
    }
    .assignments-table tr:hover {
        background: var(--gray-50);
    }

    /* Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 0.25rem 0.625rem;
        border-radius: 30px;
        font-size: 0.6rem;
        font-weight: 600;
    }
    .status-assigned { background: #d1fae5; color: #065f46; }
    .status-returned { background: #cffafe; color: #0891b2; }
    .status-damaged { background: #fed7aa; color: #9a3412; }
    .status-replaced { background: #e0e7ff; color: #3730a3; }

    .request-type-badge {
        display: inline-block;
        padding: 0.2rem 0.5rem;
        border-radius: 30px;
        font-size: 0.6rem;
        font-weight: 600;
        background: var(--gray-100);
        color: var(--gray-600);
    }

    /* Action Buttons */
    .action-btn-group {
        display: flex;
        gap: 4px;
    }
    .action-btn {
        width: 28px;
        height: 28px;
        padding: 0;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        transition: all 0.2s;
    }
    .action-btn-warning { background: #fef3c7; color: #d97706; border: none; }
    .action-btn-warning:hover { background: #fde68a; color: #b45309; }
    .action-btn-info { background: #e0f2fe; color: #0284c7; border: none; }
    .action-btn-info:hover { background: #bae6fd; color: #0369a1; }
    .action-btn-danger { background: #fee2e2; color: #dc2626; border: none; }
    .action-btn-danger:hover { background: #fecaca; color: #b91c1c; }

    /* Quick Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        padding: 1rem 1.25rem;
    }
    .info-item {
        background: var(--gray-50);
        border-radius: 12px;
        padding: 0.625rem 0.875rem;
        border: 1px solid var(--gray-200);
    }
    .info-label {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-size: 0.75rem;
        font-weight: 500;
        color: #1e293b;
        word-break: break-word;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 2rem;
    }
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 1rem;
    }
    .empty-state h6 {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .empty-state p {
        font-size: 0.75rem;
        color: var(--gray-600);
    }

    @media (max-width: 768px) {
        .profile-header {
            padding: 1rem;
        }
        .profile-avatar {
            width: 50px;
            height: 50px;
        }
        .profile-avatar i {
            font-size: 2rem;
        }
        .profile-name {
            font-size: 1rem;
        }
        .info-grid {
            grid-template-columns: 1fr;
        }
        .assignments-table {
            display: block;
            overflow-x: auto;
        }
        .stat-number {
            font-size: 1.2rem;
        }
    }
</style>

<div class="container-fluid px-4">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($employee['full_name']); ?></h2>
                    <p class="profile-meta mb-1">
                        <i class="fas fa-id-card"></i> <?php echo $employee['pf_no']; ?>
                        <?php if($employee['designation']): ?>
                        <span class="mx-2">•</span> <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($employee['designation']); ?>
                        <?php endif; ?>
                        <?php if($employee['department']): ?>
                        <span class="mx-2">•</span> <i class="fas fa-building"></i> <?php echo htmlspecialchars($employee['department']); ?>
                        <?php endif; ?>
                    </p>
                    <p class="profile-meta mb-0">
                        <?php if($employee['job_location']): ?>
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($employee['job_location']); ?>
                        <?php endif; ?>
                        <?php if($employee['company']): ?>
                        <span class="mx-2">•</span> <i class="fas fa-building"></i> <?php echo htmlspecialchars($employee['company']); ?>
                        <?php endif; ?>
                        <?php if($employee['joining_date']): ?>
                        <span class="mx-2">•</span> <i class="fas fa-calendar-alt"></i> Joined: <?php echo date('d-M-Y', strtotime($employee['joining_date'])); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="list.php" class="btn btn-light btn-sm rounded-pill px-3 me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
                <a href="../assignments/assign.php?emp_id=<?php echo $id; ?>" class="btn btn-warning btn-sm rounded-pill px-3">
                    <i class="fas fa-plus me-1"></i> Assign Device
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-primary"><i class="fas fa-laptop"></i></div>
                <div class="stat-number"><?php echo $totalAssigned; ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-success"><i class="fas fa-check-circle"></i></div>
                <div class="stat-number"><?php echo $activeAssignments; ?></div>
                <div class="stat-label">Active Devices</div>
                <div class="stat-sub"><?php echo $totalItemsAssigned; ?> items</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-info"><i class="fas fa-undo-alt"></i></div>
                <div class="stat-number"><?php echo $returnedCount; ?></div>
                <div class="stat-label">Returned</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-warning"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-number"><?php echo $damagedCount + $replacedCount; ?></div>
                <div class="stat-label">Issues</div>
                <div class="stat-sub">Damaged/Replaced</div>
            </div>
        </div>
    </div>

    <!-- Quick Contact Info -->
    <?php if($employee['phone'] || $employee['email']): ?>
    <div class="info-card mb-4">
        <div class="card-header-custom">
            <i class="fas fa-address-card me-2"></i> Contact Information
        </div>
        <div class="info-grid">
            <?php if($employee['phone']): ?>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-phone-alt"></i> Phone</div>
                <div class="info-value"><?php echo htmlspecialchars($employee['phone']); ?></div>
            </div>
            <?php endif; ?>
            <?php if($employee['email']): ?>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                <div class="info-value"><?php echo htmlspecialchars($employee['email']); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Assignments List -->
    <div class="info-card">
        <div class="card-header-custom card-header-primary">
            <i class="fas fa-list me-2"></i> Device Assignment History
            <span class="ms-2 small opacity-75">(<?php echo $totalAssigned; ?> records)</span>
        </div>
        <div class="p-0">
            <?php if(count($assignments) > 0): ?>
                <div class="table-responsive">
                    <table class="assignments-table" id="assignmentsTable">
                        <thead>
                            <tr>
                                <th>Assignment No</th>
                                <th>Device</th>
                                <th>Item Code</th>
                                <th>Brand / Model</th>
                                <th>Serial Number</th>
                                <th>Qty</th>
                                <th>Assigned Date</th>
                                <th>Expected Return</th>
                                <th>Status</th>
                                <th width="90">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($assignments as $assign): 
                                $statusClass = 'status-' . $assign['status'];
                                $statusIcon = $assign['status'] == 'assigned' ? 'check-circle' : ($assign['status'] == 'returned' ? 'undo-alt' : 'exclamation-triangle');
                            ?>
                            <tr>
                                <td><strong><?php echo $assign['assignment_no']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($assign['item_name']); ?>
                                    <?php if($assign['specification']): ?>
                                    <br><span class="text-muted" style="font-size: 0.6rem;"><?php echo htmlspecialchars(substr($assign['specification'], 0, 40)); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $assign['item_code']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($assign['brand'] ?? 'N/A'); ?>
                                    <?php if($assign['model_number']): ?>
                                    <br><span class="text-muted" style="font-size: 0.6rem;"><?php echo htmlspecialchars($assign['model_number']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $assign['serial_number'] ?? '<span class="text-muted">—</span>'; ?></td>
                                <td class="text-center"><?php echo $assign['quantity']; ?></td>
                                <td><?php echo date('d-M-y', strtotime($assign['assigned_date'])); ?></td>
                                <td><?php echo $assign['expected_return_date'] ? date('d-M-y', strtotime($assign['expected_return_date'])) : '<span class="text-muted">—</span>'; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                        <?php echo ucfirst($assign['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <?php if($assign['status'] == 'assigned'): ?>
                                            <a href="../returns/return.php?assignment_id=<?php echo $assign['id']; ?>&emp_id=<?php echo $id; ?>" 
                                               class="action-btn action-btn-warning" title="Return Device"
                                               onclick="return confirm('Return this device?')">
                                                <i class="fas fa-undo-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="../assignments/print.php?id=<?php echo $assign['id']; ?>" 
                                           target="_blank" class="action-btn action-btn-info" title="Print Slip">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <?php if($assign['status'] == 'damaged'): ?>
                                            <a href="../damages/print_damage.php?assignment_id=<?php echo $assign['id']; ?>" 
                                               class="action-btn action-btn-danger" title="Damage Report">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-laptop"></i>
                    <h6>No Devices Assigned</h6>
                    <p class="mb-3">This employee has not been assigned any devices yet.</p>
                    <a href="../assignments/assign.php?emp_id=<?php echo $id; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-plus me-1"></i> Assign Device Now
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Support Requests -->
    <div class="info-card">
        <div class="card-header-custom card-header-info">
            <i class="fas fa-tasks me-2"></i> Recent Support Requests
            <span class="ms-2 small opacity-75">(Last 5 requests)</span>
        </div>
        <div class="p-0">
            <?php
            $reqStmt = $pdo->prepare("SELECT * FROM requests WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
            $reqStmt->execute([$id]);
            $requests = $reqStmt->fetchAll();
            ?>
            <?php if(count($requests) > 0): ?>
                <div class="table-responsive">
                    <table class="assignments-table">
                        <thead>
                            <tr>
                                <th>Request No</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Requested Date</th>
                                <th width="80">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($requests as $req): 
                                $priorityClass = $req['priority'] == 'high' ? 'text-danger' : ($req['priority'] == 'medium' ? 'text-warning' : 'text-muted');
                                $statusClass = $req['status'] == 'pending' ? 'status-pending' : ($req['status'] == 'completed' ? 'status-completed' : 'status-processing');
                            ?>
                            <tr>
                                <td><strong><?php echo $req['request_no']; ?></strong></td>
                                <td><span class="request-type-badge"><?php echo ucfirst(str_replace('_', ' ', $req['request_type'] ?? 'General')); ?></span></td>
                                <td><span class="<?php echo $priorityClass; ?>"><i class="fas fa-flag"></i> <?php echo ucfirst($req['priority'] ?? 'Medium'); ?></span></td>
                                <td>
                                    <span class="status-badge <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($req['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d-M-y', strtotime($req['requested_date'])); ?></td>
                                <td>
                                    <a href="../requests/view_request.php?id=<?php echo $req['id']; ?>" 
                                       class="action-btn action-btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state py-3">
                    <i class="fas fa-ticket-alt"></i>
                    <p class="mb-0 small text-muted">No support requests found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>