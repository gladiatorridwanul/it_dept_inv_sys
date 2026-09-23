<?php
require_once '../../includes/auth.php';
require_once '../../includes/device_assignment_functions.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email
                       FROM device_assignment_requests r
                       JOIN employees e ON r.employee_id = e.id
                       WHERE r.id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if(!$request) {
    echo '<div class="alert alert-danger">Request not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get devices
$stmt = $pdo->prepare("SELECT * FROM device_assignment_items WHERE request_id = ?");
$stmt->execute([$id]);
$devices = $stmt->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $rejection_reason = $_POST['rejection_reason'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE device_assignment_requests SET status = ?, approved_by = ?, approved_date = NOW(), rejection_reason = ? WHERE id = ?");
    $stmt->execute([$status, $_SESSION['user_id'], $rejection_reason, $id]);
    
    echo '<div class="alert alert-success">Request status updated to: ' . ucfirst($status) . '</div>';
    echo '<script>setTimeout(function(){ window.location.href="list_requests.php"; }, 1500);</script>';
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-eye"></i> Assignment Request Details</h2>
            <hr>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Request Information -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Request No:</strong> <?php echo $request['request_no']; ?><br>
                            <strong>Required By Date:</strong> <?php echo date('d-m-Y', strtotime($request['assignment_date'])); ?><br>
                            <strong>Status:</strong> <?php echo getAssignmentStatusBadge($request['status']); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Purpose:</strong><br>
                            <?php echo nl2br(htmlspecialchars($request['purpose'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Information -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Employee Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>Name:</strong> <?php echo htmlspecialchars($request['full_name']); ?></div>
                        <div class="col-md-4"><strong>PF No:</strong> <?php echo $request['pf_no']; ?></div>
                        <div class="col-md-4"><strong>Designation:</strong> <?php echo $request['designation']; ?></div>
                        <div class="col-md-4 mt-2"><strong>Department:</strong> <?php echo $request['department']; ?></div>
                        <div class="col-md-4 mt-2"><strong>Location:</strong> <?php echo $request['job_location']; ?></div>
                        <div class="col-md-4 mt-2"><strong>Phone:</strong> <?php echo $request['phone']; ?></div>
                        <div class="col-md-12 mt-2"><strong>Email:</strong> <?php echo $request['email']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Devices Requested -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Devices Requested (<?php echo count($devices); ?> items)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr><th>#</th><th>Device Type</th><th>Device Name</th><th>Qty</th><th>Urgency</th><th>Specifications</th><th>Reason</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($devices as $index => $device): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo $device['device_type']; ?></td>
                                    <td><?php echo $device['custom_device_name'] ?: $device['device_name'] ?: 'Standard'; ?></td>
                                    <td><?php echo $device['quantity']; ?></td>
                                    <td><?php echo ucfirst($device['urgency']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($device['specification'])); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($device['reason'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Supervisor Info -->
            <?php if($request['supervisor_name']): ?>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Supervisor Information</h5>
                </div>
                <div class="card-body">
                    <strong>Supervisor Name:</strong> <?php echo $request['supervisor_name']; ?><br>
                    <strong>Approval Status:</strong> <?php echo $request['supervisor_approved'] ? '<span class="badge bg-success">Approved</span>' : '<span class="badge bg-warning">Not Provided</span>'; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if($request['notes']): ?>
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0">Additional Notes</h5>
                </div>
                <div class="card-body">
                    <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Update Status Card -->
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Update Status</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="under_observation" <?php echo $request['status'] == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                                <option value="approved" <?php echo $request['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $request['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="completed" <?php echo $request['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="mb-3" id="rejectionReasonDiv" style="display:none;">
                            <label class="form-label">Rejection Reason</label>
                            <textarea name="rejection_reason" rows="3" class="form-control" placeholder="Provide reason for rejection..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="status"]').addEventListener('change', function() {
    const reasonDiv = document.getElementById('rejectionReasonDiv');
    if(this.value === 'rejected') {
        reasonDiv.style.display = 'block';
    } else {
        reasonDiv.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>