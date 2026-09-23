<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle approve request (mark as upgraded - device returned to stock)
if(isset($_GET['approve']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    
    $pdo->beginTransaction();
    
    try {
        // Get request details
        $stmt = $pdo->prepare("SELECT r.*, r.notes as device_condition 
                               FROM requests r 
                               WHERE r.id = ? AND r.request_type = 'upgrade'");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if($request) {
            // Extract assignment info from description
            preg_match('/Assignment No: (.*?)\n/', $request['description'], $assignment_match);
            
            // Get assignment details
            $stmt = $pdo->prepare("SELECT a.* FROM assignments a WHERE a.assignment_no = ?");
            $stmt->execute([$assignment_match[1] ?? '']);
            $assignment = $stmt->fetch();
            
            if($assignment) {
                // Update request status
                $stmt = $pdo->prepare("UPDATE requests SET status = 'approved', accepted_date = NOW(), accepted_by = ?, notes = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], 'Approved by IT Staff - Upgrade completed', $request_id]);
                
                // Update assignment status
                $stmt = $pdo->prepare("UPDATE assignments SET status = 'returned' WHERE id = ?");
                $stmt->execute([$assignment['id']]);
                
                // Update item quantity (add back to stock after upgrade)
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
                $stmt->execute([$assignment['quantity'], $assignment['item_id']]);
                
                $pdo->commit();
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> Upgrade request approved! Device returned to stock.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
            }
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Handle reject request
if(isset($_GET['reject']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $reason = $_GET['reason'] ?? 'Rejected by IT Staff';
    $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', accepted_date = NOW(), accepted_by = ?, notes = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $reason, $request_id]);
    echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle"></i> Upgrade request rejected!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Handle status update (pending/under_observation/damage/repaired)
if(isset($_POST['update_status']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    $observation_notes = $_POST['observation_notes'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE requests SET status = ?, notes = ?, accepted_date = NOW(), accepted_by = ? WHERE id = ?");
    $stmt->execute([$new_status, $observation_notes, $_SESSION['user_id'], $request_id]);
    echo '<div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle"></i> Request status updated to: ' . ucfirst(str_replace('_', ' ', $new_status)) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Filter by status
$status_filter = $_GET['status'] ?? 'all';
$where_condition = "r.request_type = 'upgrade'";
if($status_filter != 'all') {
    $where_condition .= " AND r.status = '$status_filter'";
}

// Get all upgrade requests
$stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
                              u.full_name as accepted_by_name
                       FROM requests r 
                       JOIN employees e ON r.employee_id = e.id 
                       LEFT JOIN users u ON r.accepted_by = u.id
                       WHERE $where_condition 
                       ORDER BY FIELD(r.status, 'pending', 'under_observation', 'damage', 'repaired', 'approved', 'rejected'), r.requested_date DESC");
$stmt->execute();
$requests = $stmt->fetchAll();

// Get counts
$totalPending = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade' AND status = 'pending'")->fetch();
$totalObservation = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade' AND status = 'under_observation'")->fetch();
$totalDamage = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade' AND status = 'damage'")->fetch();
$totalRepaired = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade' AND status = 'repaired'")->fetch();
$totalApproved = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade' AND status = 'approved'")->fetch();
$totalRejected = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade' AND status = 'rejected'")->fetch();
$totalAll = $pdo->query("SELECT COUNT(*) as count FROM requests WHERE request_type = 'upgrade'")->fetch();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-arrow-up"></i> Upgradation Requests Management</h2>
            <p class="text-muted">Manage device upgrade requests from employees</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="upgrade_requests_list.php" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <a href="?status=pending" class="text-decoration-none">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h6 class="card-title">Pending</h6>
                        <h3 class="mb-0"><?php echo $totalPending['count']; ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=under_observation" class="text-decoration-none">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h6 class="card-title">Under Observation</h6>
                        <h3 class="mb-0"><?php echo $totalObservation['count']; ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=damage" class="text-decoration-none">
                <div class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <h6 class="card-title">Damage</h6>
                        <h3 class="mb-0"><?php echo $totalDamage['count']; ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=repaired" class="text-decoration-none">
                <div class="card text-white bg-secondary">
                    <div class="card-body text-center">
                        <h6 class="card-title">Repaired</h6>
                        <h3 class="mb-0"><?php echo $totalRepaired['count']; ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=approved" class="text-decoration-none">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h6 class="card-title">Approved</h6>
                        <h3 class="mb-0"><?php echo $totalApproved['count']; ?></h3>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=rejected" class="text-decoration-none">
                <div class="card text-white bg-dark">
                    <div class="card-body text-center">
                        <h6 class="card-title">Rejected</h6>
                        <h3 class="mb-0"><?php echo $totalRejected['count']; ?></h3>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'all' ? 'active' : ''; ?>" href="?status=all">
                All Requests <span class="badge bg-secondary"><?php echo $totalAll['count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" href="?status=pending">
                Pending <span class="badge bg-warning"><?php echo $totalPending['count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'under_observation' ? 'active' : ''; ?>" href="?status=under_observation">
                Under Observation <span class="badge bg-info"><?php echo $totalObservation['count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'damage' ? 'active' : ''; ?>" href="?status=damage">
                Damage <span class="badge bg-danger"><?php echo $totalDamage['count']; ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $status_filter == 'repaired' ? 'active' : ''; ?>" href="?status=repaired">
                Repaired <span class="badge bg-secondary"><?php echo $totalRepaired['count']; ?></span>
            </a>
        </li>
    </ul>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Upgradation Requests List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="10%">Request No</th>
                            <th width="15%">Employee</th>
                            <th width="8%">PF No</th>
                            <th width="12%">Department</th>
                            <th width="12%">Request Date</th>
                            <th width="8%">Condition</th>
                            <th width="10%">Status</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sn = 1;
                        foreach($requests as $req): 
                            // Extract device condition from description
                            preg_match('/Device Condition: (.*?)\n/', $req['description'], $condition_match);
                            $condition = $condition_match[1] ?? 'N/A';
                            $badgeClass = 'secondary';
                            if($condition == 'good') $badgeClass = 'success';
                            elseif($condition == 'minor_issues') $badgeClass = 'warning';
                            elseif($condition == 'damaged') $badgeClass = 'danger';
                            elseif($condition == 'repairable') $badgeClass = 'info';
                        ?>
                        <tr>
                            <td><?php echo $sn++; ?></td>
                            <td><strong><?php echo $req['request_no']; ?></strong></td>
                            <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                            <td><?php echo $req['pf_no']; ?></td>
                            <td><?php echo $req['department'] ?? 'N/A'; ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($req['requested_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $condition)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($req['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif($req['status'] == 'under_observation'): ?>
                                    <span class="badge bg-info">Under Observation</span>
                                <?php elseif($req['status'] == 'damage'): ?>
                                    <span class="badge bg-danger">Damage</span>
                                <?php elseif($req['status'] == 'repaired'): ?>
                                    <span class="badge bg-secondary">Repaired</span>
                                <?php elseif($req['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-dark">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-info" onclick="viewRequest(<?php echo $req['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($req['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $req['id']; ?>, 'under_observation')">
                                        <i class="fas fa-clock"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="updateStatus(<?php echo $req['id']; ?>, 'damage')">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if($req['status'] == 'under_observation' || $req['status'] == 'damage'): ?>
                                    <button class="btn btn-sm btn-secondary" onclick="updateStatus(<?php echo $req['id']; ?>, 'repaired')">
                                        <i class="fas fa-tools"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if($req['status'] == 'repaired'): ?>
                                    <a href="?approve=1&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this upgrade request? Device will be returned to stock.')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-danger" onclick="rejectRequest(<?php echo $req['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($requests)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="fas fa-info-circle"></i> No upgrade requests found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-eye"></i> Upgrade Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading request details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="statusRequestId">
                    <input type="hidden" name="update_status" value="1">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" required>
                            <option value="pending">Pending - Awaiting review</option>
                            <option value="under_observation">Under Observation - Device being checked</option>
                            <option value="damage">Damage - Device is damaged</option>
                            <option value="repaired">Repaired - Device has been repaired</option>
                            <option value="approved">Approved - Upgrade completed</option>
                            <option value="rejected">Rejected - Request denied</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observation Notes / Remarks</label>
                        <textarea name="observation_notes" rows="4" class="form-control" placeholder="Enter notes about device condition, required repairs, upgrade details, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Update Status</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle"></i> Reject Upgrade Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rejection Reason</label>
                    <textarea id="rejectReason" rows="3" class="form-control" placeholder="Please provide reason for rejection..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="confirmReject()">Confirm Rejection</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentRejectId = null;

function viewRequest(id) {
    $.ajax({
        url: 'get_upgrade_details.php',
        type: 'POST',
        data: { id: id },
        dataType: 'html',
        success: function(response) {
            $('#requestDetails').html(response);
            $('#viewRequestModal').modal('show');
        },
        error: function() {
            $('#requestDetails').html('<div class="alert alert-danger">Error loading request details!</div>');
        }
    });
}

function updateStatus(id, status) {
    $('#statusRequestId').val(id);
    $('#updateStatusModal select[name="status"]').val(status);
    $('#updateStatusModal').modal('show');
}

function rejectRequest(id) {
    currentRejectId = id;
    $('#rejectModal').modal('show');
}

function confirmReject() {
    const reason = $('#rejectReason').val();
    if(reason.trim() === '') {
        alert('Please provide a reason for rejection.');
        return;
    }
    window.location.href = '?reject=1&id=' + currentRejectId + '&reason=' + encodeURIComponent(reason);
}
</script>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}
.btn-group .btn {
    margin: 0 2px;
}
.badge {
    font-size: 11px;
    padding: 5px 8px;
}
.nav-tabs .nav-link {
    font-weight: 500;
}
.nav-tabs .nav-link.active {
    border-bottom: 2px solid #0d6efd;
}
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
</style>

<?php include '../../includes/footer.php'; ?>