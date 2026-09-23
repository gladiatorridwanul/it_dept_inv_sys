<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $device_id = $_POST['device_id'];
    $pass_type = $_POST['pass_type'];
    $issue_date = $_POST['issue_date'];
    $expected_return_date = $_POST['expected_return_date'];
    $notes = $_POST['notes'];
    
    $pass_no = generateNumber('PASS-', 'pass_slips', 'pass_no');
    
    $stmt = $pdo->prepare("INSERT INTO pass_slips (pass_no, employee_id, device_id, pass_type, issue_date, expected_return_date, notes, created_by) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if($stmt->execute([$pass_no, $employee_id, $device_id, $pass_type, $issue_date, $expected_return_date, $notes, $_SESSION['user_id']])) {
        echo '<div class="alert alert-success">Pass Slip created! Number: ' . $pass_no . '</div>';
        echo '<a href="print_pass.php?id=' . $pdo->lastInsertId() . '" class="btn btn-info" target="_blank">Print Pass Slip</a>';
    } else {
        echo '<div class="alert alert-danger">Error creating pass slip!</div>';
    }
}

$employees = $pdo->query("SELECT * FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();
$devices = $pdo->query("SELECT * FROM items WHERE is_active=1 ORDER BY name")->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-passport"></i> Create IT Desk Pass Slip</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Employee *</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            <?php foreach($employees as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo $emp['pf_no'] . ' - ' . htmlspecialchars($emp['full_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Device *</label>
                        <select name="device_id" class="form-control" required>
                            <option value="">Select Device</option>
                            <?php foreach($devices as $device): ?>
                            <option value="<?php echo $device['id']; ?>">
                                <?php echo htmlspecialchars($device['name'] . ' - ' . $device['item_code']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Pass Type *</label>
                        <select name="pass_type" class="form-control" required>
                            <option value="send">Send Device</option>
                            <option value="receive">Receive Device</option>
                            <option value="repair">Repair Device</option>
                            <option value="return">Return Device</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Issue Date *</label>
                        <input type="date" name="issue_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label>Expected Return Date</label>
                        <input type="date" name="expected_return_date" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create Pass Slip</button>
                <a href="list_passes.php" class="btn btn-secondary">View All Passes</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>