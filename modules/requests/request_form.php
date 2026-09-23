<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $item_id = $_POST['item_id'] ?: NULL;
    $request_type = $_POST['request_type'];
    $description = $_POST['description'];
    $requested_date = date('Y-m-d H:i:s');
    $request_no = generateNumber('REQ-', 'requests', 'request_no');
    
    $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, item_id, request_type, description, requested_date, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    
    if($stmt->execute([$request_no, $employee_id, $item_id, $request_type, $description, $requested_date])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Request submitted successfully! Request No: ' . $request_no . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    } else {
        echo '<div class="alert alert-danger">Error submitting request!</div>';
    }
}

$employees = $pdo->query("SELECT * FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();
$items = $pdo->query("SELECT * FROM items WHERE is_active=1 ORDER BY name")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-paper-plane"></i> Submit Request (Staff)</h2>
            <hr>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">New Request Form</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Employee *</label>
                        <select name="employee_id" class="form-control select2" required>
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo $emp['pf_no'] . ' - ' . htmlspecialchars($emp['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Request Type *</label>
                        <select name="request_type" class="form-control" required id="requestType">
                            <option value="">Select Request Type</option>
                            <option value="handover">Handover Device</option>
                            <option value="upgrade">Upgrade Device</option>
                            <option value="repair">Repair Device</option>
                            <option value="assign">New Device Assignment</option>
                            <option value="update">Update Device Information</option>
                            <option value="replace">Replace Device</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3" id="itemDiv">
                        <label class="form-label">Item/Device (Optional)</label>
                        <select name="item_id" class="form-control">
                            <option value="">Select Item (if applicable)</option>
                            <?php foreach($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['name'] . ' - ' . $item['item_code']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Description / Reason *</label>
                        <textarea name="description" rows="5" class="form-control" required 
                                  placeholder="Please provide detailed information about your request..."></textarea>
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Request
                    </button>
                    <a href="my_requests.php" class="btn btn-secondary">
                        <i class="fas fa-list"></i> View My Requests
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Instructions</h5>
        </div>
        <div class="card-body">
            <ul class="mb-0">
                <li>Select the employee for whom the request is being made</li>
                <li>Choose the appropriate request type</li>
                <li>Select an item/device if relevant to the request</li>
                <li>Provide a detailed description for faster processing</li>
                <li>All requests will be reviewed by the admin</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.getElementById('requestType').addEventListener('change', function() {
    var type = this.value;
    var itemDiv = document.getElementById('itemDiv');
    if(type == 'assign' || type == 'replace' || type == 'upgrade') {
        itemDiv.style.display = 'block';
    } else {
        itemDiv.style.display = 'block';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>