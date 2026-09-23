<?php
require_once '../../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    $stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
                                  u.full_name as accepted_by_name
                           FROM requests r 
                           JOIN employees e ON r.employee_id = e.id 
                           LEFT JOIN users u ON r.accepted_by = u.id
                           WHERE r.id = ? AND r.request_type = 'upgrade'");
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
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-user"></i> Employee Information</strong>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><th width="35%">Name:</th><td><strong><?php echo htmlspecialchars($request['full_name']); ?></strong></td>
                            </tr>
                            <tr><th>PF No:</th><td><?php echo $request['pf_no']; ?></td>
                            </tr>
                            <tr><th>Designation:</th><td><?php echo $request['designation'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr><th>Department:</th><td><?php echo $request['department'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr><th>Job Location:</th><td><?php echo $request['job_location'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr><th>Phone:</th><td><?php echo $request['phone'] ?? 'N/A'; ?></td>
                            </tr>
                            <tr><th>Email:</th><td><?php echo $request['email'] ?? 'N/A'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-info-circle"></i> Request Information</strong>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless">
                            <tr><th width="40%">Request No:</th><td><strong><?php echo $request['request_no']; ?></strong></td>
                            </tr>
                            <tr><th>Request Date:</th><td><?php echo date('d-m-Y H:i:s', strtotime($request['requested_date'])); ?></td>
                            </tr>
                            <tr><th>Status:</th>
                                <td>
                                    <?php if($request['status'] == 'pending'): ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php elseif($request['status'] == 'under_observation'): ?>
                                        <span class="badge bg-info">Under Observation</span>
                                    <?php elseif($request['status'] == 'damage'): ?>
                                        <span class="badge bg-danger">Damage</span>
                                    <?php elseif($request['status'] == 'repaired'): ?>
                                        <span class="badge bg-secondary">Repaired</span>
                                    <?php elseif($request['status'] == 'approved'): ?>
                                        <span class="badge bg-success">Approved</span>
                                    <?php else: ?>
                                        <span class="badge bg-dark">Rejected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if($request['accepted_date']): ?>
                            <tr><th>Processed Date:</th><td><?php echo date('d-m-Y H:i:s', strtotime($request['accepted_date'])); ?></td>
                            </tr>
                            <tr><th>Processed By:</th><td><?php echo $request['accepted_by_name'] ?? 'System'; ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-laptop"></i> Device & Upgrade Details</strong>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <?php foreach($details as $key => $value): ?>
                            <tr>
                                <th width="30%"><?php echo $key; ?>:</th>
                                <td><?php echo nl2br(htmlspecialchars($value)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
            <?php if($request['notes']): ?>
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-sticky-note"></i> Staff Notes</strong>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0"><?php echo nl2br(htmlspecialchars($request['notes'])); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Request not found!</div>';
    }
}
?>