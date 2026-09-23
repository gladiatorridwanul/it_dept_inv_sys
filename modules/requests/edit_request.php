<?php
require_once '../../includes/auth.php';
require_once '../../includes/request_functions.php';
include '../../includes/header.php';

// Helper function for redirect if not exists
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

$id = $_GET['id'] ?? 0;

// Get request details
$stmt = $pdo->prepare("
    SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
           u.full_name as processed_by_name
    FROM requests r 
    JOIN employees e ON r.employee_id = e.id 
    LEFT JOIN users u ON r.processed_by = u.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$request = $stmt->fetch();

if(!$request) {
    echo '<div class="alert alert-danger">Request not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get specific request details based on type
$specific_data = null;
$multiple_devices = [];

if($request['request_type'] == 'technical_support') {
    $stmt = $pdo->prepare("SELECT * FROM technical_support_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'software_access') {
    $stmt = $pdo->prepare("SELECT * FROM software_access_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
    
    // If no record exists, create an empty array for safe access
    if(!$specific_data) {
        $specific_data = [];
    }
} elseif($request['request_type'] == 'accessories') {
    $stmt = $pdo->prepare("SELECT * FROM accessories_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'return_device') {
    $stmt = $pdo->prepare("SELECT * FROM return_device_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'upgrade') {
    $stmt = $pdo->prepare("SELECT * FROM upgrade_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'update') {
    $stmt = $pdo->prepare("SELECT * FROM update_device_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'assign') {
    $stmt = $pdo->prepare("SELECT * FROM assign_device_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'device_assign') {
    $stmt = $pdo->prepare("SELECT * FROM assign_device_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $multiple_devices = $stmt->fetchAll();
    
    if(empty($multiple_devices) && !empty($request['request_data'])) {
        $request_data = json_decode($request['request_data'], true);
        if($request_data && isset($request_data['devices'])) {
            $multiple_devices = $request_data['devices'];
        }
    }
}

// Get attachments
$attachments = $pdo->prepare("SELECT * FROM request_attachments WHERE request_id = ? ORDER BY created_at DESC");
$attachments->execute([$id]);
$attachments = $attachments->fetchAll();

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Set default values to avoid undefined array key warnings
    $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
    $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
    $estimated_completion_date = isset($_POST['estimated_completion_date']) && !empty($_POST['estimated_completion_date']) ? $_POST['estimated_completion_date'] : null;
    $resolution_notes = isset($_POST['resolution_notes']) ? trim($_POST['resolution_notes']) : '';
    $assigned_to_team = isset($_POST['assigned_to_team']) ? $_POST['assigned_to_team'] : null;
    $escalation_level = isset($_POST['escalation_level']) ? $_POST['escalation_level'] : 'level1';
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update main request
        $stmt = $pdo->prepare("
            UPDATE requests 
            SET status = ?, priority = ?, estimated_completion_date = ?, 
                resolution_notes = ?, assigned_to_team = ?, escalation_level = ?,
                processed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $status, $priority, $estimated_completion_date, 
            $resolution_notes, $assigned_to_team, $escalation_level,
            $_SESSION['user_id'], $id
        ]);
        
        // Update specific request type data
        if($request['request_type'] == 'technical_support') {
            // For technical_support_requests table, only update the notes/resolution
            // The table doesn't have diagnosis, root_cause, fix_applied, etc.
            // Instead, we'll store resolution info in the main requests table's resolution_notes
            
            // Also update the status in technical_support_requests if needed
            // Check if table has a status column
            try {
                $stmt = $pdo->prepare("SHOW COLUMNS FROM technical_support_requests");
                $stmt->execute();
                $ts_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if(in_array('status', $ts_columns)) {
                    $stmt = $pdo->prepare("UPDATE technical_support_requests SET status = ? WHERE request_id = ?");
                    $stmt->execute([$status, $id]);
                }
                
                if(in_array('resolution_notes', $ts_columns)) {
                    $stmt = $pdo->prepare("UPDATE technical_support_requests SET resolution_notes = ? WHERE request_id = ?");
                    $stmt->execute([$resolution_notes, $id]);
                }
            } catch(Exception $e) {
                // Ignore errors if columns don't exist
            }
            
        } elseif($request['request_type'] == 'software_access') {
            // Get form values
            $software_name = isset($_POST['software_name']) ? $_POST['software_name'] : '';
            $version = isset($_POST['version']) ? $_POST['version'] : '';
            $duration_needed = isset($_POST['duration_needed']) ? $_POST['duration_needed'] : '';
            $access_granted = isset($_POST['access_granted']) ? 1 : 0;
            $license_key = isset($_POST['license_key']) && !empty($_POST['license_key']) ? $_POST['license_key'] : null;
            $installation_status = isset($_POST['installation_status']) ? $_POST['installation_status'] : 'pending';
            $completion_notes = isset($_POST['completion_notes']) ? $_POST['completion_notes'] : '';
            $user_trained = isset($_POST['user_trained']) ? 1 : 0;
            $access_revoked = isset($_POST['access_revoked']) ? 1 : 0;
            
            // Handle license_id - check if license exists or set to NULL
            $license_id = null;
            if($license_key) {
                // Check if license exists with this key
                $stmt = $pdo->prepare("SELECT id FROM licenses WHERE license_key = ?");
                $stmt->execute([$license_key]);
                $license = $stmt->fetch();
                if($license) {
                    $license_id = $license['id'];
                }
            }
            
            // Get table columns first to avoid errors with missing columns
            $stmt = $pdo->prepare("SHOW COLUMNS FROM software_access_requests");
            $stmt->execute();
            $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Check if record exists
            if($specific_data && isset($specific_data['id'])) {
                // Build update query dynamically based on existing columns
                $update_fields = [];
                $update_params = [];
                
                if(in_array('software_name', $existing_columns)) {
                    $update_fields[] = "software_name = ?";
                    $update_params[] = $software_name;
                }
                if(in_array('version', $existing_columns)) {
                    $update_fields[] = "version = ?";
                    $update_params[] = $version;
                }
                if(in_array('duration_needed', $existing_columns)) {
                    $update_fields[] = "duration_needed = ?";
                    $update_params[] = $duration_needed;
                }
                if(in_array('access_granted_date', $existing_columns)) {
                    $update_fields[] = "access_granted_date = ?";
                    $update_params[] = $access_granted ? date('Y-m-d H:i:s') : null;
                }
                if(in_array('license_id', $existing_columns)) {
                    $update_fields[] = "license_id = ?";
                    $update_params[] = $license_id;
                }
                if(in_array('installation_status', $existing_columns)) {
                    $update_fields[] = "installation_status = ?";
                    $update_params[] = $installation_status;
                }
                if(in_array('completion_notes', $existing_columns)) {
                    $update_fields[] = "completion_notes = ?";
                    $update_params[] = $completion_notes;
                }
                if(in_array('user_trained', $existing_columns)) {
                    $update_fields[] = "user_trained = ?";
                    $update_params[] = $user_trained;
                }
                if(in_array('access_revoked', $existing_columns)) {
                    $update_fields[] = "access_revoked = ?";
                    $update_params[] = $access_revoked;
                }
                if(in_array('granted_by', $existing_columns)) {
                    $update_fields[] = "granted_by = ?";
                    $update_params[] = $_SESSION['user_id'];
                }
                
                $update_params[] = $id;
                
                if(!empty($update_fields)) {
                    $sql = "UPDATE software_access_requests SET " . implode(", ", $update_fields) . " WHERE request_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($update_params);
                }
            } else {
                // Insert new record - only use columns that exist
                $columns = ['request_id'];
                $placeholders = ['?'];
                $values = [$id];
                
                if(in_array('software_name', $existing_columns)) {
                    $columns[] = 'software_name';
                    $placeholders[] = '?';
                    $values[] = $software_name;
                }
                if(in_array('version', $existing_columns)) {
                    $columns[] = 'version';
                    $placeholders[] = '?';
                    $values[] = $version;
                }
                if(in_array('duration_needed', $existing_columns)) {
                    $columns[] = 'duration_needed';
                    $placeholders[] = '?';
                    $values[] = $duration_needed;
                }
                if(in_array('access_granted_date', $existing_columns)) {
                    $columns[] = 'access_granted_date';
                    $placeholders[] = '?';
                    $values[] = $access_granted ? date('Y-m-d H:i:s') : null;
                }
                if(in_array('license_id', $existing_columns)) {
                    $columns[] = 'license_id';
                    $placeholders[] = '?';
                    $values[] = $license_id;
                }
                if(in_array('installation_status', $existing_columns)) {
                    $columns[] = 'installation_status';
                    $placeholders[] = '?';
                    $values[] = $installation_status;
                }
                if(in_array('completion_notes', $existing_columns)) {
                    $columns[] = 'completion_notes';
                    $placeholders[] = '?';
                    $values[] = $completion_notes;
                }
                if(in_array('user_trained', $existing_columns)) {
                    $columns[] = 'user_trained';
                    $placeholders[] = '?';
                    $values[] = $user_trained;
                }
                if(in_array('access_revoked', $existing_columns)) {
                    $columns[] = 'access_revoked';
                    $placeholders[] = '?';
                    $values[] = $access_revoked;
                }
                if(in_array('granted_by', $existing_columns)) {
                    $columns[] = 'granted_by';
                    $placeholders[] = '?';
                    $values[] = $_SESSION['user_id'];
                }
                
                $sql = "INSERT INTO software_access_requests (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $placeholders) . ")";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($values);
            }
            
        } elseif($request['request_type'] == 'accessories') {
            $delivery_status = isset($_POST['delivery_status']) ? $_POST['delivery_status'] : 'pending';
            $allocation_notes = isset($_POST['allocation_notes']) ? $_POST['allocation_notes'] : '';
            $expected_return_date = isset($_POST['expected_return_date']) && !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null;
            $actual_return_date = isset($_POST['actual_return_date']) && !empty($_POST['actual_return_date']) ? $_POST['actual_return_date'] : null;
            $return_condition = isset($_POST['return_condition']) ? $_POST['return_condition'] : null;
            
            if($specific_data && isset($specific_data['id'])) {
                // Get table columns first
                $stmt = $pdo->prepare("SHOW COLUMNS FROM accessories_requests");
                $stmt->execute();
                $acc_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $update_fields = [];
                $update_params = [];
                
                if(in_array('delivery_status', $acc_columns)) {
                    $update_fields[] = "delivery_status = ?";
                    $update_params[] = $delivery_status;
                }
                if(in_array('allocation_notes', $acc_columns)) {
                    $update_fields[] = "allocation_notes = ?";
                    $update_params[] = $allocation_notes;
                }
                if(in_array('expected_return_date', $acc_columns)) {
                    $update_fields[] = "expected_return_date = ?";
                    $update_params[] = $expected_return_date;
                }
                if(in_array('actual_return_date', $acc_columns)) {
                    $update_fields[] = "actual_return_date = ?";
                    $update_params[] = $actual_return_date;
                }
                if(in_array('return_condition', $acc_columns)) {
                    $update_fields[] = "return_condition = ?";
                    $update_params[] = $return_condition;
                }
                if(in_array('allocated_by', $acc_columns)) {
                    $update_fields[] = "allocated_by = ?";
                    $update_params[] = $_SESSION['user_id'];
                }
                if(in_array('allocation_date', $acc_columns)) {
                    $update_fields[] = "allocation_date = NOW()";
                }
                
                $update_params[] = $id;
                
                if(!empty($update_fields)) {
                    $sql = "UPDATE accessories_requests SET " . implode(", ", $update_fields) . " WHERE request_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($update_params);
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['success_message'] = "Request updated successfully!";
        header("Location: view_request.php?id=" . $id);
        exit();
        
    } catch(Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Set current values with null coalescing to avoid undefined array key warnings
$current_status = isset($request['status']) ? $request['status'] : 'pending';
$current_priority = isset($request['priority']) ? $request['priority'] : 'medium';
$current_estimated_date = isset($request['estimated_completion_date']) ? $request['estimated_completion_date'] : '';
$current_resolution_notes = isset($request['resolution_notes']) ? $request['resolution_notes'] : '';
$current_assigned_to_team = isset($request['assigned_to_team']) ? $request['assigned_to_team'] : '';
$current_escalation_level = isset($request['escalation_level']) ? $request['escalation_level'] : 'level1';

// Check for success message from session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);
?>

<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
    }
    .info-box {
        background: #f8f9fa;
        border-left: 4px solid #28a745;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .alert-info-box {
        background: #e0f2fe;
        border-left: 4px solid #0ea5e9;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-edit text-primary"></i> Edit Request</h2>
                    <p class="text-muted">Request #: <strong><?php echo htmlspecialchars($request['request_no']); ?></strong></p>
                </div>
                <div>
                    <a href="view_request.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-eye"></i> View Request
                    </a>
                    <a href="all_requests.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list"></i> Back to List
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> Error updating request: <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Request Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="editForm">
                        <!-- Basic Information -->
                        <div class="section-title">Basic Information</div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Employee</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($request['full_name']); ?> (<?php echo htmlspecialchars($request['pf_no']); ?>)" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Request Type</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst(str_replace('_', ' ', $request['request_type'])); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Request Date</label>
                                <input type="text" class="form-control" value="<?php echo date('d-m-Y H:i:s', strtotime($request['requested_date'])); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="pending" <?php echo $current_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="under_observation" <?php echo $current_status == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                                    <option value="processing" <?php echo $current_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo $current_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="rejected" <?php echo $current_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?php echo $current_priority == 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo $current_priority == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo $current_priority == 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="critical" <?php echo $current_priority == 'critical' ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assigned Team</label>
                                <input type="text" name="assigned_to_team" class="form-control" value="<?php echo htmlspecialchars($current_assigned_to_team); ?>" placeholder="e.g., Hardware Team, Software Team">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Escalation Level</label>
                                <select name="escalation_level" class="form-select">
                                    <option value="level1" <?php echo $current_escalation_level == 'level1' ? 'selected' : ''; ?>>Level 1 - L1 Support</option>
                                    <option value="level2" <?php echo $current_escalation_level == 'level2' ? 'selected' : ''; ?>>Level 2 - L2 Support</option>
                                    <option value="level3" <?php echo $current_escalation_level == 'level3' ? 'selected' : ''; ?>>Level 3 - L3 Support</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estimated Completion Date</label>
                                <input type="date" name="estimated_completion_date" class="form-control" value="<?php echo $current_estimated_date; ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Resolution Notes</label>
                                <textarea name="resolution_notes" rows="3" class="form-control" placeholder="Add resolution notes..."><?php echo htmlspecialchars($current_resolution_notes); ?></textarea>
                                <small class="text-muted">For technical support requests, add diagnosis, root cause analysis, and fix applied here.</small>
                            </div>
                        </div>
                        
                        <!-- Request Description -->
                        <div class="section-title">Request Description</div>
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="info-box">
                                    <?php 
                                    $description = $request['description'] ?: 'No description provided.';
                                    // If it's a technical support request, also show additional fields
                                    if($request['request_type'] == 'technical_support' && $specific_data) {
                                        $description .= "\n\n--- Technical Support Details ---\n";
                                        $description .= "Issue Type: " . ($specific_data['issue_type'] ?? 'N/A') . "\n";
                                        $description .= "Issue Title: " . ($specific_data['issue_title'] ?? 'N/A') . "\n";
                                        $description .= "Urgency Level: " . ($specific_data['urgency_level'] ?? 'N/A') . "\n";
                                        if(!empty($specific_data['affected_device'])) {
                                            $description .= "Affected Device: " . $specific_data['affected_device'] . "\n";
                                        }
                                        if(!empty($specific_data['error_message'])) {
                                            $description .= "Error Message: " . $specific_data['error_message'] . "\n";
                                        }
                                        if(!empty($specific_data['steps_to_reproduce'])) {
                                            $description .= "Steps to Reproduce: " . $specific_data['steps_to_reproduce'] . "\n";
                                        }
                                        if(!empty($specific_data['expected_behavior'])) {
                                            $description .= "Expected Behavior: " . $specific_data['expected_behavior'] . "\n";
                                        }
                                        if(!empty($specific_data['actual_behavior'])) {
                                            $description .= "Actual Behavior: " . $specific_data['actual_behavior'] . "\n";
                                        }
                                    }
                                    echo nl2br(htmlspecialchars($description)); 
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Software Access Details -->
                        <?php if($request['request_type'] == 'software_access'): ?>
                        <div class="section-title">Software Access Details</div>
                        <div class="alert-info-box">
                            <i class="fas fa-info-circle me-1"></i> 
                            <strong>Note:</strong> License ID must exist in the licenses table. If you don't have a license, leave it empty.
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Software Name</label>
                                <input type="text" name="software_name" class="form-control" value="<?php echo htmlspecialchars($specific_data['software_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Version</label>
                                <input type="text" name="version" class="form-control" value="<?php echo htmlspecialchars($specific_data['version'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label">Duration Needed</label>
                                <select name="duration_needed" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="temporary" <?php echo ($specific_data['duration_needed'] ?? '') == 'temporary' ? 'selected' : ''; ?>>Temporary (30 days)</option>
                                    <option value="permanent" <?php echo ($specific_data['duration_needed'] ?? '') == 'permanent' ? 'selected' : ''; ?>>Permanent</option>
                                    <option value="project_based" <?php echo ($specific_data['duration_needed'] ?? '') == 'project_based' ? 'selected' : ''; ?>>Project Based</option>
                                </select>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-check">
                                    <input type="checkbox" name="access_granted" class="form-check-input" id="accessGranted" <?php echo ($specific_data['access_granted_date'] ?? false) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="accessGranted">Access Granted</label>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label">License Key (must exist in licenses table)</label>
                                <input type="text" name="license_key" class="form-control" value="<?php echo htmlspecialchars($specific_data['license_key'] ?? ''); ?>" placeholder="Leave empty if no license">
                                <small class="text-muted">The license key must already exist in the licenses table. Leave empty to skip.</small>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label">Installation Status</label>
                                <select name="installation_status" class="form-select">
                                    <option value="pending" <?php echo ($specific_data['installation_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="installed" <?php echo ($specific_data['installation_status'] ?? '') == 'installed' ? 'selected' : ''; ?>>Installed</option>
                                    <option value="failed" <?php echo ($specific_data['installation_status'] ?? '') == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="not_applicable" <?php echo ($specific_data['installation_status'] ?? '') == 'not_applicable' ? 'selected' : ''; ?>>Not Applicable</option>
                                </select>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label">Completion Notes</label>
                                <textarea name="completion_notes" rows="2" class="form-control"><?php echo htmlspecialchars($specific_data['completion_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-check">
                                    <input type="checkbox" name="user_trained" class="form-check-input" id="userTrained" <?php echo ($specific_data['user_trained'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="userTrained">User Trained on Software</label>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <div class="form-check">
                                    <input type="checkbox" name="access_revoked" class="form-check-input" id="accessRevoked" <?php echo ($specific_data['access_revoked'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="accessRevoked">Access Revoked (if temporary)</label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Accessories Details -->
                        <?php if($request['request_type'] == 'accessories' && $specific_data): ?>
                        <div class="section-title">Accessories Details</div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Accessory Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($specific_data['accessory_name'] ?? ''); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Quantity</label>
                                <input type="text" class="form-control" value="<?php echo $specific_data['quantity'] ?? 1; ?>" disabled>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label">Delivery Status</label>
                                <select name="delivery_status" class="form-select">
                                    <option value="pending" <?php echo ($specific_data['delivery_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo ($specific_data['delivery_status'] ?? '') == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="delivered" <?php echo ($specific_data['delivery_status'] ?? '') == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo ($specific_data['delivery_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-12 mt-3">
                                <label class="form-label">Allocation Notes</label>
                                <textarea name="allocation_notes" rows="2" class="form-control"><?php echo htmlspecialchars($specific_data['allocation_notes'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label">Expected Return Date</label>
                                <input type="date" name="expected_return_date" class="form-control" value="<?php echo $specific_data['expected_return_date'] ?? ''; ?>">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label class="form-label">Actual Return Date</label>
                                <input type="date" name="actual_return_date" class="form-control" value="<?php echo $specific_data['actual_return_date'] ?? ''; ?>">
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Device Assign Details -->
                        <?php if($request['request_type'] == 'device_assign' && count($multiple_devices) > 0): ?>
                        <div class="section-title">Requested Devices</div>
                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Device Name</th>
                                        <th>Required Specs</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($multiple_devices as $idx => $device): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($device['item_name'] ?? $device['device_name'] ?? 'N/A'); ?></strong>
                                            <?php if(!empty($device['quantity']) && $device['quantity'] > 1): ?>
                                                <br><small class="text-muted">Qty: <?php echo $device['quantity']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo nl2br(htmlspecialchars($device['required_specifications'] ?? 'N/A')); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($device['reason'] ?? 'N/A')); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Action Buttons -->
                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="view_request.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Attachments Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-paperclip"></i> Attachments (<?php echo count($attachments); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($attachments) > 0): ?>
                        <?php foreach($attachments as $att): ?>
                        <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                            <div>
                                <i class="fas fa-file-alt text-primary me-2"></i>
                                <strong><?php echo htmlspecialchars($att['file_name']); ?></strong>
                                <br><small class="text-muted">Uploaded: <?php echo date('d-m-Y H:i', strtotime($att['created_at'])); ?></small>
                            </div>
                            <a href="/it-inventory/<?php echo $att['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">No attachments</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Info Card -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Quick Info</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Created By:</span>
                        <strong><?php echo htmlspecialchars($request['full_name']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Created At:</span>
                        <strong><?php echo date('d-m-Y H:i', strtotime($request['created_at'])); ?></strong>
                    </div>
                    <?php if($request['processed_by_name']): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Last Processed By:</span>
                        <strong><?php echo htmlspecialchars($request['processed_by_name']); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between">
                        <span>Total Attachments:</span>
                        <strong><?php echo count($attachments); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('editForm')?.addEventListener('submit', function(e) {
    const submitBtn = this.querySelector('button[type="submit"]');
    if(submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        submitBtn.disabled = true;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>