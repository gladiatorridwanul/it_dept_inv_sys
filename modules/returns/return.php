<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle return request approval
if(isset($_GET['approve']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    
    $pdo->beginTransaction();
    
    try {
        // Get request details
        $stmt = $pdo->prepare("SELECT r.*, r.notes as device_condition 
                               FROM requests r 
                               WHERE r.id = ? AND r.request_type = 'handover'");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        if($request) {
            // Extract assignment info from description
            preg_match('/Assignment No: (.*?)\n/', $request['description'], $assignment_match);
            preg_match('/Device: (.*?)\n/', $request['description'], $device_match);
            preg_match('/Item Code: (.*?)\n/', $request['description'], $item_code_match);
            preg_match('/Serial: (.*?)\n/', $request['description'], $serial_match);
            
            // Get assignment details
            $stmt = $pdo->prepare("SELECT a.* FROM assignments a WHERE a.assignment_no = ?");
            $stmt->execute([$assignment_match[1] ?? '']);
            $assignment = $stmt->fetch();
            
            if($assignment) {
                // Update request status
                $stmt = $pdo->prepare("UPDATE requests SET status = 'approved', accepted_date = NOW(), accepted_by = ?, notes = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], 'Approved by IT Staff', $request_id]);
                
                // Update assignment status
                $stmt = $pdo->prepare("UPDATE assignments SET status = 'returned' WHERE id = ?");
                $stmt->execute([$assignment['id']]);
                
                // Update item quantity (add back to stock)
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
                $stmt->execute([$assignment['quantity'], $assignment['item_id']]);
                
                $pdo->commit();
                echo '<div class="alert alert-success">Handover request approved! Device returned to stock.</div>';
            }
        }
    } catch(Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

// Handle return request rejection
if(isset($_GET['reject']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', accepted_date = NOW(), accepted_by = ?, notes = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], 'Rejected by IT Staff', $request_id]);
    echo '<div class="alert alert-warning">Handover request rejected!</div>';
}

// Handle status update (pending/under observation)
if(isset($_POST['update_status']) && isset($_POST['request_id'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    $observation_notes = $_POST['observation_notes'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE requests SET status = ?, notes = ?, accepted_date = NOW(), accepted_by = ? WHERE id = ?");
    $stmt->execute([$new_status, $observation_notes, $_SESSION['user_id'], $request_id]);
    echo '<div class="alert alert-info">Request status updated to: ' . ucfirst($new_status) . '</div>';
}

// Get all handover requests
$stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department 
                       FROM requests r 
                       JOIN employees e ON r.employee_id = e.id 
                       WHERE r.request_type = 'handover' 
                       ORDER BY FIELD(r.status, 'pending', 'under_observation', 'approved', 'rejected'), r.requested_date DESC");
$stmt->execute();
$requests = $stmt->fetchAll();

// Get counts
$pendingCount = count(array_filter($requests, function($r) { return $r['status'] == 'pending'; }));
$observationCount = count(array_filter($requests, function($r) { return $r['status'] == 'under_observation'; }));
$approvedCount = count(array_filter($requests, function($r) { return $r['status'] == 'approved'; }));
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-handshake"></i> Handover Request Management</h2>
            <hr>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Pending Requests</h5>
                    <h2 class="mb-0"><?php echo $pendingCount; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Under Observation</h5>
                    <h2 class="mb-0"><?php echo $observationCount; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Approved</h5>
                    <h2 class="mb-0"><?php echo $approvedCount; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-secondary">
                <div class="card-body">
                    <h5 class="card-title">Total Requests</h5>
                    <h2 class="mb-0"><?php echo count($requests); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Handover Requests</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Request No</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Department</th>
                            <th>Request Date</th>
                            <th>Device Condition</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                        <tr>
                            <td><?php echo $req['request_no']; ?></td>
                            <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                            <td><?php echo $req['pf_no']; ?></td>
                            <td><?php echo $req['department']; ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($req['requested_date'])); ?></td>
                            <td>
                                <?php 
                                $condition = '';
                                preg_match('/Device Condition: (.*?)\n/', $req['description'], $condition_match);
                                $condition = $condition_match[1] ?? 'N/A';
                                $badgeClass = 'secondary';
                                if($condition == 'good') $badgeClass = 'success';
                                elseif($condition == 'minor_issues') $badgeClass = 'warning';
                                elseif($condition == 'major_issues') $badgeClass = 'danger';
                                ?>
                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $condition)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($req['status'] == 'pending'): ?>
                                    <span class="badge bg-warning">Pending</span>
                                <?php elseif($req['status'] == 'under_observation'): ?>
                                    <span class="badge bg-info">Under Observation</span>
                                <?php elseif($req['status'] == 'approved'): ?>
                                    <span class="badge bg-success">Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-info" onclick="viewRequest(<?php echo $req['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if($req['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $req['id']; ?>, 'under_observation')">
                                        <i class="fas fa-clock"></i> Observe
                                    </button>
                                    <a href="?approve=1&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this handover? Device will be returned to stock.')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="?reject=1&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                    <?php elseif($req['status'] == 'under_observation'): ?>
                                    <a href="?approve=1&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this handover after observation?')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($requests)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No handover requests found.</td>
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
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetails">
                <div class="text-center">Loading...</div>
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
                <h5 class="modal-title">Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="statusRequestId">
                    <input type="hidden" name="update_status" value="1">
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="under_observation">Under Observation</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Observation Notes</label>
                        <textarea name="observation_notes" rows="3" class="form-control" placeholder="Enter notes about device condition, required repairs, etc."></textarea>
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
        }
    });
}

function updateStatus(id, status) {
    $('#statusRequestId').val(id);
    $('#updateStatusModal select[name="status"]').val(status);
    $('#updateStatusModal').modal('show');
}
</script>

<?php include '../../includes/footer.php'; ?>