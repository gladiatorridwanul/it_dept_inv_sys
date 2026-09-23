<?php
require_once '../../includes/auth.php';
require_once '../../includes/device_assignment_functions.php';
include '../../includes/header.php';

$status_filter = $_GET['status'] ?? 'pending';

// Fix: Use table alias to avoid ambiguous column error
$where = "";
if($status_filter == 'all') {
    $where = "1=1";
} else {
    $where = "r.status = '$status_filter'";
}

$stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department,
                              COUNT(i.id) as device_count
                       FROM device_assignment_requests r
                       JOIN employees e ON r.employee_id = e.id
                       LEFT JOIN device_assignment_items i ON r.id = i.request_id
                       WHERE $where
                       GROUP BY r.id
                       ORDER BY r.created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll();

// Get counts for each status
$counts = [];
$statuses = ['pending', 'under_observation', 'approved', 'rejected', 'completed'];
foreach($statuses as $s) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM device_assignment_requests WHERE status = ?");
    $stmt->execute([$s]);
    $counts[$s] = $stmt->fetch()['count'];
}
$total_all = array_sum($counts);
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-laptop"></i> Device Assignment Requests</h2>
            <p class="text-muted">Manage and approve device assignment requests from employees</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="list_requests.php" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
    </div>

    <!-- Status Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <a href="?status=all" class="text-decoration-none">
                <div class="card text-white bg-dark">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total</h6>
                        <h2 class="mb-0"><?php echo $total_all; ?></h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=pending" class="text-decoration-none">
                <div class="card text-white bg-warning">
                    <div class="card-body text-center">
                        <h6 class="card-title">Pending</h6>
                        <h2 class="mb-0"><?php echo $counts['pending']; ?></h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=under_observation" class="text-decoration-none">
                <div class="card text-white bg-info">
                    <div class="card-body text-center">
                        <h6 class="card-title">Under Obs.</h6>
                        <h2 class="mb-0"><?php echo $counts['under_observation']; ?></h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=approved" class="text-decoration-none">
                <div class="card text-white bg-success">
                    <div class="card-body text-center">
                        <h6 class="card-title">Approved</h6>
                        <h2 class="mb-0"><?php echo $counts['approved']; ?></h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=rejected" class="text-decoration-none">
                <div class="card text-white bg-danger">
                    <div class="card-body text-center">
                        <h6 class="card-title">Rejected</h6>
                        <h2 class="mb-0"><?php echo $counts['rejected']; ?></h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2">
            <a href="?status=completed" class="text-decoration-none">
                <div class="card text-white bg-primary">
                    <div class="card-body text-center">
                        <h6 class="card-title">Completed</h6>
                        <h2 class="mb-0"><?php echo $counts['completed']; ?></h2>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="under_observation" <?php echo $status_filter == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Device Assignment Requests List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable">
                    <thead>
                        <tr>
                            <th>Request No</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Department</th>
                            <th>Required Date</th>
                            <th>Devices</th>
                            <th>Status</th>
                            <th>Request Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                        <tr>
                            <td><strong><?php echo $req['request_no']; ?></strong></td>
                            <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                            <td><?php echo $req['pf_no']; ?></td>
                            <td><?php echo $req['department'] ?? 'N/A'; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($req['assignment_date'])); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo $req['device_count']; ?> item(s)</span>
                            </td>
                            <td>
                                <?php if($req['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif($req['status'] == 'under_observation'): ?>
                                    <span class="badge bg-info">Under Observation</span>
                                <?php elseif($req['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php elseif($req['status'] == 'rejected'): ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php elseif($req['status'] == 'completed'): ?>
                                    <span class="badge bg-primary">Completed</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo ucfirst($req['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($req['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-info" onclick="viewRequest(<?php echo $req['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="update_request.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if($req['status'] == 'pending'): ?>
                                    <a href="approve_request.php?id=<?php echo $req['id']; ?>&action=approve" class="btn btn-sm btn-success" onclick="return confirm('Approve this request?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="approve_request.php?id=<?php echo $req['id']; ?>&action=reject" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($requests)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No device assignment requests found.<?php echo $status_filter != 'all' ? ' Try changing the filter.' : ''; ?></td>
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
                <h5 class="modal-title"><i class="fas fa-eye"></i> Request Details</h5>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function viewRequest(id) {
    $.ajax({
        url: 'get_request_details.php',
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
.card-header {
    border-bottom: none;
}
</style>

<?php include '../../includes/footer.php'; ?>