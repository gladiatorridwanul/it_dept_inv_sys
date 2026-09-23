<?php
require_once '../../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    $stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email 
                           FROM requests r 
                           JOIN employees e ON r.employee_id = e.id 
                           WHERE r.id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if($request) {
        // Parse description
        $lines = explode("\n", $request['description']);
        $details = [];
        foreach($lines as $line) {
            if(strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $details[trim($key)] = trim($value);
            }
        }
        ?>
        <div class="row">
            <div class="col-md-6">
                <h6>Employee Information</h6>
                <table class="table table-sm">
                    <tr><th>Name:</th><td><?php echo htmlspecialchars($request['full_name']); ?></td></tr>
                    <tr><th>PF No:</th><td><?php echo $request['pf_no']; ?></td></tr>
                    <tr><th>Designation:</th><td><?php echo $request['designation']; ?></td></tr>
                    <tr><th>Department:</th><td><?php echo $request['department']; ?></td></tr>
                    <tr><th>Job Location:</th><td><?php echo $request['job_location']; ?></td></tr>
                    <tr><th>Phone:</th><td><?php echo $request['phone']; ?></td></tr>
                    <tr><th>Email:</th><td><?php echo $request['email']; ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Request Information</h6>
                <table class="table table-sm">
                    <tr><th>Request No:</th><td><?php echo $request['request_no']; ?></td></tr>
                    <tr><th>Request Date:</th><td><?php echo date('d-m-Y H:i', strtotime($request['requested_date'])); ?></td></tr>
                    <tr><th>Status:</th><td><span class="badge bg-<?php echo $request['status'] == 'approved' ? 'success' : ($request['status'] == 'pending' ? 'warning' : 'danger'); ?>"><?php echo ucfirst($request['status']); ?></span></td></tr>
                    <?php if($request['accepted_date']): ?>
                    <tr><th>Processed Date:</th><td><?php echo date('d-m-Y H:i', strtotime($request['accepted_date'])); ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
            <div class="col-md-12 mt-3">
                <h6>Device Details</h6>
                <table class="table table-bordered">
                    <?php foreach($details as $key => $value): ?>
                    <tr><th><?php echo $key; ?>:</th><td><?php echo nl2br(htmlspecialchars($value)); ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php if($request['notes']): ?>
            <div class="col-md-12 mt-3">
                <h6>Staff Notes</h6>
                <div class="alert alert-info"><?php echo nl2br(htmlspecialchars($request['notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Request not found!</div>';
    }
}
?>