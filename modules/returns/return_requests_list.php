<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Check permissions
if(!canView($pdo, $_SESSION['role'], 'requests') && $_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Handle approval/rejection
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $return_request_id = (int)$_POST['return_request_id'];
    $action = $_POST['action'];
    $notes = trim($_POST['notes'] ?? '');
    
    if($action == 'approve' || $action == 'reject') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT request_id FROM return_device_requests WHERE id = ?");
            $stmt->execute([$return_request_id]);
            $request_data = $stmt->fetch();
            
            if($request_data) {
                $new_status = ($action == 'approve') ? 'approved' : 'rejected';
                $stmt2 = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
                $stmt2->execute([$new_status, $request_data['request_id']]);
            }
            
            $stmt = $pdo->prepare("INSERT INTO return_approvals (return_request_id, action, processed_by, notes) VALUES (?, ?, ?, ?)");
            $stmt->execute([$return_request_id, ($action == 'approve' ? 'approved' : 'rejected'), $_SESSION['user_id'], $notes]);
            
            if($action == 'approve') {
                $stmt = $pdo->prepare("SELECT assignment_id FROM return_device_items WHERE return_request_id = ?");
                $stmt->execute([$return_request_id]);
                $assignments = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach($assignments as $assignment_id) {
                    $stmt2 = $pdo->prepare("UPDATE assignments SET return_status = 'return_requested', return_request_id = ? WHERE id = ?");
                    $stmt2->execute([$return_request_id, $assignment_id]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Return request " . ($action == 'approve' ? "approved" : "rejected") . " successfully!";
        } catch(Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
        header("Location: return_requests_list.php");
        exit();
    }
}

// Get all return requests
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sql = "SELECT 
            rdr.id,
            rdr.request_no,
            rdr.return_reason,
            rdr.reason_details,
            rdr.cleaning_done,
            rdr.data_backup_confirmed,
            rdr.exit_clearance,
            rdr.total_devices,
            rdr.created_at as rdr_created_at,
            r.id as request_id,
            r.status as request_status,
            r.requested_date,
            e.id as employee_id,
            e.full_name as employee_name,
            e.pf_no,
            e.designation,
            e.department,
            (SELECT COUNT(*) FROM return_device_items WHERE return_request_id = rdr.id) as device_count,
            (SELECT COUNT(*) FROM return_device_items WHERE return_request_id = rdr.id AND processing_status = 'added_to_stock') as processed_count
        FROM return_device_requests rdr
        JOIN requests r ON rdr.request_id = r.id
        JOIN employees e ON r.employee_id = e.id";

if($status_filter != 'all') {
    $sql .= " WHERE r.status = '" . addslashes($status_filter) . "'";
}

$sql .= " ORDER BY rdr.created_at DESC";
$result = $pdo->query($sql);
$requests = $result->fetchAll();
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-approved { background: #d1fae5; color: #059669; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .status-completed { background: #dbeafe; color: #2563eb; }
    .status-processing { background: #fef3c7; color: #d97706; }
    .return-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        transition: all 0.2s;
    }
    .device-chip {
        display: inline-block;
        background: #f3f4f6;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        margin: 3px;
    }
    .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .btn-process {
        background: #3b82f6;
        color: white;
    }
    .btn-process:hover:not(:disabled) {
        background: #2563eb;
        color: white;
    }
    .btn-process:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .btn-success:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .btn-danger:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        opacity: 0.6;
    }
    @media (max-width: 768px) {
        .action-buttons {
            justify-content: flex-start;
            margin-top: 15px;
        }
        .return-card .row > div:last-child {
            text-align: left !important;
        }
    }
    /* Details Modal Styles */
    .detail-row {
        display: flex;
        padding: 6px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .detail-label {
        width: 140px;
        font-weight: 600;
        color: #475569;
        flex-shrink: 0;
    }
    .detail-value {
        flex: 1;
        color: #1e293b;
    }
    .detail-section {
        margin-bottom: 12px;
    }
    .detail-section-title {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
        padding-bottom: 5px;
        border-bottom: 2px solid #e2e8f0;
    }
    .modal-body {
        padding: 20px 25px;
        max-height: 70vh;
        overflow-y: auto;
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-undo-alt text-primary me-2"></i>Device Return Requests</h4>
            <p class="text-muted small">Review and process employee device return requests</p>
        </div>
        <div>
            <select id="statusFilter" class="form-select form-select-sm w-auto" onchange="window.location.href='?status='+this.value">
                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Requests</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(empty($requests)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
            No return requests found.
        </div>
    <?php else: ?>
        <?php foreach($requests as $req): 
            $current_status = $req['request_status'] ?? 'pending';
            $total_devices = $req['device_count'] ?? 0;
            $processed_count = $req['processed_count'] ?? 0;
            
            // Determine if all devices are processed
            $all_processed = ($total_devices > 0 && $processed_count >= $total_devices);
            
            // Determine button states
            $process_disabled = ($current_status == 'rejected' || $current_status == 'completed' || $all_processed);
            
            // Get devices for this request
            $stmt = $pdo->prepare("
                SELECT rdi.*, a.assignment_no, i.name as item_name, i.item_code, i.serial_number,
                       rdi.processing_status
                FROM return_device_items rdi
                JOIN assignments a ON rdi.assignment_id = a.id
                JOIN items i ON a.item_id = i.id
                WHERE rdi.return_request_id = ?
            ");
            $stmt->execute([$req['id']]);
            $devices = $stmt->fetchAll();
        ?>
        <div class="return-card">
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($req['request_no']); ?></h6>
                        <span class="status-badge status-<?php echo $current_status; ?>">
                            <i class="fas fa-<?php echo $current_status == 'pending' ? 'clock' : ($current_status == 'approved' ? 'check' : ($current_status == 'processing' ? 'spinner fa-pulse' : ($current_status == 'completed' ? 'check-circle' : 'times'))); ?> me-1"></i>
                            <?php echo ucfirst($current_status); ?>
                        </span>
                        <?php if($processed_count > 0 && $total_devices > 0): ?>
                            <span class="badge bg-info"><?php echo $processed_count; ?>/<?php echo $total_devices; ?> Processed</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row small mb-2 mt-2">
                        <div class="col-md-4">
                            <i class="fas fa-user me-1 text-muted"></i> 
                            <strong><?php echo htmlspecialchars($req['employee_name'] ?? 'N/A'); ?></strong>
                            <br><span class="text-muted"><?php echo htmlspecialchars($req['pf_no'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-building me-1 text-muted"></i> 
                            <?php echo htmlspecialchars($req['designation'] ?: 'N/A'); ?>
                            <br><span class="text-muted"><?php echo htmlspecialchars($req['department'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-calendar me-1 text-muted"></i> 
                            Requested: <?php echo date('d-m-Y H:i', strtotime($req['rdr_created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Devices to return:</strong>
                        <div class="mt-1">
                            <?php foreach($devices as $dev): ?>
                                <span class="device-chip">
                                    <i class="fas fa-<?php echo $dev['processing_status'] == 'added_to_stock' ? 'check-circle text-success' : 'laptop'; ?>"></i> 
                                    <?php echo htmlspecialchars($dev['item_code'] ?? 'N/A'); ?> - 
                                    <?php echo htmlspecialchars($dev['item_name'] ?? 'N/A'); ?>
                                    <?php if($dev['processing_status'] == 'added_to_stock'): ?>
                                        <span class="text-success ms-1">(Added to Stock)</span>
                                    <?php else: ?>
                                        <span class="text-muted ms-1">(<?php echo htmlspecialchars($dev['device_condition'] ?? 'good'); ?>)</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="small text-muted">
                        <strong>Return Reason:</strong> 
                        <?php echo ucfirst(str_replace('_', ' ', $req['return_reason'] ?? 'other')); ?>
                        <?php if(!empty($req['reason_details'])): ?> 
                            - <?php echo htmlspecialchars($req['reason_details']); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="action-buttons">
                        <!-- Process Button -->
                        <a href="process_return_devices.php?id=<?php echo $req['id']; ?>" 
                           class="btn btn-sm btn-process <?php echo $process_disabled ? 'disabled' : ''; ?>"
                           style="<?php echo $process_disabled ? 'pointer-events: none; opacity: 0.6;' : ''; ?>">
                            <i class="fas fa-boxes me-1"></i> Process Devices
                            <?php if($all_processed): ?>
                                (Completed)
                            <?php elseif($current_status == 'completed'): ?>
                                (Done)
                            <?php endif; ?>
                        </a>
                        
                        <!-- Approve/Reject Buttons -->
                        <?php if($current_status == 'pending'): ?>
                            <button class="btn btn-sm btn-success" onclick="showApproveModal(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['request_no']); ?>')">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showRejectModal(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['request_no']); ?>')">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-success" disabled style="opacity:0.6; cursor:not-allowed;">
                                <i class="fas fa-check me-1"></i> Approved
                            </button>
                            <button class="btn btn-sm btn-danger" disabled style="opacity:0.6; cursor:not-allowed;">
                                <i class="fas fa-times me-1"></i> Rejected
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-sm btn-outline-secondary" onclick="viewDetails(<?php echo $req['id']; ?>)">
                            <i class="fas fa-eye me-1"></i> View
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Return Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="return_request_id" id="approve_request_id">
                    <input type="hidden" name="action" value="approve">
                    <p>Are you sure you want to approve this return request?</p>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Return Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="return_request_id" id="reject_request_id">
                    <input type="hidden" name="action" value="reject">
                    <p>Are you sure you want to reject this return request?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showApproveModal(id, requestNo) {
    document.getElementById('approve_request_id').value = id;
    $('#approveModal').modal('show');
}

function showRejectModal(id, requestNo) {
    document.getElementById('reject_request_id').value = id;
    $('#rejectModal').modal('show');
}

function viewDetails(id) {
    $('#detailsModal').modal('show');
    $('#detailsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading details...</div>');
    
    // Use ajax/get_return_details.php path
    var ajaxUrl = 'ajax/get_return_details.php';
    
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: { return_request_id: id },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            console.log('Response received:', response);
            
            if(response && response.success && response.data) {
                var data = response.data;
                var returnInfo = data.return_info || {};
                var employee = data.employee || {};
                var devices = data.devices || [];
                var approvals = data.approvals || [];
                
                var statusBadge = '';
                var status = returnInfo.status || 'pending';
                switch(status) {
                    case 'pending': statusBadge = 'bg-warning text-dark'; break;
                    case 'approved': statusBadge = 'bg-success'; break;
                    case 'rejected': statusBadge = 'bg-danger'; break;
                    case 'completed': statusBadge = 'bg-info'; break;
                    default: statusBadge = 'bg-secondary';
                }
                
                var html = '';
                
                // Return Request Info
                html += '<div class="detail-section">';
                html += '<div class="detail-section-title"><i class="fas fa-info-circle me-2"></i>Return Request Information</div>';
                html += '<div class="detail-row"><div class="detail-label">Request No:</div><div class="detail-value"><strong>' + escapeHtml(returnInfo.request_no) + '</strong></div></div>';
                html += '<div class="detail-row"><div class="detail-label">Status:</div><div class="detail-value"><span class="badge ' + statusBadge + '">' + escapeHtml(status) + '</span></div></div>';
                html += '<div class="detail-row"><div class="detail-label">Return Reason:</div><div class="detail-value">' + escapeHtml(returnInfo.return_reason || 'N/A') + '</div></div>';
                if(returnInfo.reason_details) {
                    html += '<div class="detail-row"><div class="detail-label">Reason Details:</div><div class="detail-value">' + escapeHtml(returnInfo.reason_details) + '</div></div>';
                }
                html += '<div class="detail-row"><div class="detail-label">Requested Date:</div><div class="detail-value">' + new Date(returnInfo.created_at).toLocaleString() + '</div></div>';
                html += '</div>';
                
                // Employee Info
                html += '<div class="detail-section">';
                html += '<div class="detail-section-title"><i class="fas fa-user me-2"></i>Employee Information</div>';
                html += '<div class="detail-row"><div class="detail-label">Name:</div><div class="detail-value"><strong>' + escapeHtml(employee.name) + '</strong></div></div>';
                html += '<div class="detail-row"><div class="detail-label">PF No:</div><div class="detail-value">' + escapeHtml(employee.pf_no) + '</div></div>';
                html += '<div class="detail-row"><div class="detail-label">Designation:</div><div class="detail-value">' + escapeHtml(employee.designation) + '</div></div>';
                html += '<div class="detail-row"><div class="detail-label">Department:</div><div class="detail-value">' + escapeHtml(employee.department) + '</div></div>';
                html += '</div>';
                
                // Devices
                html += '<div class="detail-section">';
                html += '<div class="detail-section-title"><i class="fas fa-laptop me-2"></i>Devices to Return</div>';
                if(devices.length > 0) {
                    html += '<div class="table-responsive"><table class="table table-bordered table-sm">';
                    html += '<thead class="table-light"><tr><th>Item</th><th>Item Code</th><th>Serial Number</th><th>Condition</th><th>Status</th><th>Assignment</th></tr></thead>';
                    html += '<tbody>';
                    for(var i = 0; i < devices.length; i++) {
                        var dev = devices[i];
                        var procStatus = dev.processing_status || 'pending';
                        var statusClass = procStatus == 'added_to_stock' ? 'success' : (procStatus == 'approved' ? 'primary' : (procStatus == 'rejected' ? 'danger' : 'warning'));
                        html += '<tr>';
                        html += '<td><strong>' + escapeHtml(dev.item_name) + '</strong></td>';
                        html += '<td>' + escapeHtml(dev.item_code) + '</td>';
                        html += '<td>' + escapeHtml(dev.serial_number || 'N/A') + '</td>';
                        html += '<td>' + escapeHtml(dev.device_condition || 'good') + '</td>';
                        html += '<td><span class="badge bg-' + statusClass + '">' + escapeHtml(procStatus) + '</span></td>';
                        html += '<td>' + escapeHtml(dev.assignment_no || 'N/A') + '</td>';
                        html += '</tr>';
                    }
                    html += '</tbody></table></div>';
                } else {
                    html += '<p class="text-muted">No devices found for this request.</p>';
                }
                html += '</div>';
                
                // Approvals/History
                if(approvals.length > 0) {
                    html += '<div class="detail-section">';
                    html += '<div class="detail-section-title"><i class="fas fa-history me-2"></i>Approval History</div>';
                    for(var j = 0; j < approvals.length; j++) {
                        var app = approvals[j];
                        var actionClass = app.action == 'approved' ? 'success' : (app.action == 'rejected' ? 'danger' : 'secondary');
                        html += '<div class="detail-row"><div class="detail-label">' + new Date(app.created_at).toLocaleString() + ':</div>';
                        html += '<div class="detail-value"><span class="badge bg-' + actionClass + '">' + escapeHtml(app.action) + '</span>';
                        if(app.notes) html += ' <span class="text-muted">- ' + escapeHtml(app.notes) + '</span>';
                        html += '</div></div>';
                    }
                    html += '</div>';
                }
                
                $('#detailsContent').html(html);
            } else {
                var errorMsg = response && response.message ? response.message : 'No data received';
                $('#detailsContent').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i> ' + escapeHtml(errorMsg) + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            
            var errorMsg = 'Error loading details. Please try again.';
            
            // Try to parse error response
            try {
                if(xhr.responseText) {
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if(jsonResponse.message) {
                        errorMsg = jsonResponse.message;
                    }
                }
            } catch(e) {
                // If not JSON, check if the file exists
                if(xhr.status === 404) {
                    errorMsg = 'The details endpoint was not found. Please check the server configuration.';
                } else if(xhr.responseText && xhr.responseText.includes('<')) {
                    errorMsg = 'Server error occurred. Please check the server logs.';
                }
            }
            
            $('#detailsContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i> ' + errorMsg + '</div>');
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>

<?php include '../../includes/footer.php'; ?>