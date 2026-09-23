<?php
require_once '../../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    // Get request details
    $stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
                                  u.full_name as approved_by_name
                           FROM device_assignment_requests r
                           JOIN employees e ON r.employee_id = e.id
                           LEFT JOIN users u ON r.approved_by = u.id
                           WHERE r.id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if($request) {
        // Get devices for this request
        $stmt = $pdo->prepare("SELECT di.*, i.name as device_name, i.item_code, i.specification, i.brand, i.model_number
                               FROM device_assignment_items di
                               JOIN items i ON di.device_id = i.id
                               WHERE di.request_id = ?");
        $stmt->execute([$id]);
        $devices = $stmt->fetchAll();
        
        // Parse request data
        $request_data = json_decode($request['request_data'], true);
        ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-info-circle"></i> Request Information</strong>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th width="40%">Request No:</th><td><strong><?php echo $request['request_no']; ?></strong></tr>
                                    <tr><th>Status:</th><td>
                                        <?php if($request['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif($request['status'] == 'under_observation'): ?>
                                            <span class="badge bg-info">Under Observation</span>
                                        <?php elseif($request['status'] == 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif($request['status'] == 'rejected'): ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Completed</span>
                                        <?php endif; ?>
                                    </tr>
                                    <tr><th>Assigned By Date:</th><td><?php echo date('d-m-Y', strtotime($request['assignment_date'])); ?></tr>
                                    <tr><th>Project Duration:</th><td><?php echo str_replace('_', ' ', $request_data['project_duration'] ?? 'N/A'); ?></tr>
                                    <tr><th>Urgent:</th><td><?php echo ($request_data['urgent_requirement'] ?? 0) ? 'Yes' : 'No'; ?></tr>
                                    <tr><th>Request Date:</th><td><?php echo date('d-m-Y H:i:s', strtotime($request['created_at'])); ?></tr>
                                    <?php if($request['approved_date']): ?>
                                    <tr><th>Processed Date:</th><td><?php echo date('d-m-Y H:i:s', strtotime($request['approved_date'])); ?></tr>
                                    <tr><th>Processed By:</th><td><?php echo $request['approved_by_name'] ?? 'System'; ?></tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-borderless">
                                    <tr><th>Employee Name:</th><td><?php echo htmlspecialchars($request['full_name']); ?></tr>
                                    <tr><th>PF Number:</th><td><?php echo $request['pf_no']; ?></tr>
                                    <tr><th>Designation:</th><td><?php echo $request['designation']; ?></tr>
                                    <tr><th>Department:</th><td><?php echo $request['department']; ?></tr>
                                    <tr><th>Job Location:</th><td><?php echo $request['job_location']; ?></tr>
                                    <tr><th>Phone:</th><td><?php echo $request['phone']; ?></tr>
                                    <tr><th>Email:</th><td><?php echo $request['email']; ?></tr>
                                </table>
                            </div>
                        </div>
                        <?php if($request['rejection_reason']): ?>
                        <div class="alert alert-danger mt-3">
                            <strong>Rejection Reason:</strong><br>
                            <?php echo nl2br(htmlspecialchars($request['rejection_reason'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if($request['notes']): ?>
                        <div class="alert alert-info mt-3">
                            <strong>Notes:</strong><br>
                            <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-laptop"></i> Devices Requested (<?php echo count($devices); ?> items)</strong>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Item Code</th>
                                        <th>Device Name</th>
                                        <th>Brand/Model</th>
                                        <th>Required Specifications</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($devices as $index => $device): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo $device['item_code']; ?></td>
                                        <td><?php echo htmlspecialchars($device['device_name']); ?></td>
                                        <td><?php echo $device['brand'] ?? 'N/A'; ?> <?php echo $device['model_number'] ?? ''; ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($device['required_specifications'] ?: 'N/A')); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($device['reason'] ?: 'N/A')); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <?php if($request_data['overall_reason'] ?? false): ?>
                <div class="card">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-question-circle"></i> Overall Reason</strong>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($request_data['overall_reason'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Request not found!</div>';
    }
}
?>