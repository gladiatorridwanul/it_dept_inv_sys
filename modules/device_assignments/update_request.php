<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

// Get request details
$stmt = $pdo->prepare("SELECT * FROM device_assignment_requests WHERE id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if(!$request) {
    echo '<div class="alert alert-danger">Request not found!</div>';
    include '../../includes/footer.php';
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $rejection_reason = $_POST['rejection_reason'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE device_assignment_requests SET status = ?, approved_by = ?, approved_date = NOW(), rejection_reason = ? WHERE id = ?");
    
    if($stmt->execute([$status, $_SESSION['user_id'], $rejection_reason, $id])) {
        echo '<div class="alert alert-success">Request status updated successfully!</div>';
        echo '<script>setTimeout(function(){ window.location.href = "list_requests.php"; }, 1500);</script>';
    } else {
        echo '<div class="alert alert-danger">Error updating status!</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-edit"></i> Update Request Status</h2>
            <hr>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Request #<?php echo $request['request_no']; ?></h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Current Status</label>
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
                    <textarea name="rejection_reason" rows="3" class="form-control" placeholder="Please provide reason for rejection..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Status</button>
                <a href="list_requests.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('select[name="status"]').addEventListener('change', function() {
    const rejectionDiv = document.getElementById('rejectionReasonDiv');
    if(this.value === 'rejected') {
        rejectionDiv.style.display = 'block';
    } else {
        rejectionDiv.style.display = 'none';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>