<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle status updates from view page
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $transfer_id = intval($_GET['id']);
    
    $status_map = [
        'approve' => 'approved',
        'dispatch' => 'dispatched',
        'deliver' => 'delivered',
        'cancel' => 'cancelled'
    ];
    
    if(isset($status_map[$action])) {
        $new_status = $status_map[$action];
        
        // Get current status before update
        $check_stmt = $pdo->prepare("SELECT status FROM device_transfers WHERE id = ?");
        $check_stmt->execute([$transfer_id]);
        $current_status = $check_stmt->fetchColumn();
        
        $update_stmt = $pdo->prepare("UPDATE device_transfers SET status = ?, updated_at = NOW() WHERE id = ?");
        $update_stmt->execute([$new_status, $transfer_id]);
        
        // Add to delivery log with status_from
        $log_stmt = $pdo->prepare("INSERT INTO delivery_logs (transfer_id, action, status_from, status_to, performed_by) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->execute([$transfer_id, $action, $current_status, $new_status, $_SESSION['user_id']]);
        
        $_SESSION['success'] = "Transfer " . ucfirst($new_status) . " successfully!";
        header("Location: view.php?id=" . $transfer_id);
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM device_transfers WHERE id = ?");
$stmt->execute([$id]);
$transfer = $stmt->fetch();

if(!$transfer) {
    echo '<div class="alert alert-danger m-4">Transfer not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get transfer logs - ORDER BY created_at ASC for timeline
$log_stmt = $pdo->prepare("SELECT * FROM delivery_logs WHERE transfer_id = ? ORDER BY created_at ASC");
$log_stmt->execute([$id]);
$logs = $log_stmt->fetchAll();

// Get attachments
$att_stmt = $pdo->prepare("SELECT * FROM transfer_attachments WHERE transfer_id = ? ORDER BY created_at DESC");
$att_stmt->execute([$id]);
$attachments = $att_stmt->fetchAll();

// Get employee details if available
$employee_info = null;
if($transfer['employee_id']) {
    $emp_stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $emp_stmt->execute([$transfer['employee_id']]);
    $employee_info = $emp_stmt->fetch();
}

// Get item details if available
$item_info = null;
if($transfer['item_id']) {
    $item_stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $item_stmt->execute([$transfer['item_id']]);
    $item_info = $item_stmt->fetch();
}

// ============================================
// TIMELINE STATUS DETECTION - FIXED
// ============================================

// First, check what statuses are in the logs
$statuses_in_logs = [];
foreach($logs as $log) {
    if(!empty($log['status_to'])) {
        $statuses_in_logs[] = $log['status_to'];
    }
}

// Get status dates from logs with proper mapping
function getStatusDate($logs, $status) {
    foreach($logs as $log) {
        if($log['status_to'] == $status) {
            return date('d-m-Y H:i', strtotime($log['created_at']));
        }
    }
    return null;
}

// Get status by action
function getStatusDateByAction($logs, $action) {
    foreach($logs as $log) {
        if($log['action'] == $action) {
            return date('d-m-Y H:i', strtotime($log['created_at']));
        }
    }
    return null;
}

// Also check if the transfer itself has status changes recorded in updated_at
$approved_date = getStatusDate($logs, 'approved');
$dispatched_date = getStatusDate($logs, 'dispatched');
$delivered_date = getStatusDate($logs, 'delivered');
$cancelled_date = getStatusDate($logs, 'cancelled');

// If status exists in transfer but not in logs, use transfer's updated_at
$current_status = $transfer['status'];
if($current_status == 'approved' && !$approved_date) {
    $approved_date = date('d-m-Y H:i', strtotime($transfer['updated_at']));
}
if($current_status == 'dispatched' && !$dispatched_date) {
    $dispatched_date = date('d-m-Y H:i', strtotime($transfer['updated_at']));
}
if($current_status == 'delivered' && !$delivered_date) {
    $delivered_date = date('d-m-Y H:i', strtotime($transfer['updated_at']));
}
if($current_status == 'cancelled' && !$cancelled_date) {
    $cancelled_date = date('d-m-Y H:i', strtotime($transfer['updated_at']));
}

// Also check by action
if(!$approved_date) {
    $approve_date_action = getStatusDateByAction($logs, 'approve');
    if($approve_date_action) $approved_date = $approve_date_action;
}
if(!$dispatched_date) {
    $dispatch_date_action = getStatusDateByAction($logs, 'dispatch');
    if($dispatch_date_action) $dispatched_date = $dispatch_date_action;
}
if(!$delivered_date) {
    $deliver_date_action = getStatusDateByAction($logs, 'deliver');
    if($deliver_date_action) $delivered_date = $deliver_date_action;
}
if(!$cancelled_date) {
    $cancel_date_action = getStatusDateByAction($logs, 'cancel');
    if($cancel_date_action) $cancelled_date = $cancel_date_action;
}

// ============================================
// ATTACHMENT HELPER FUNCTIONS
// ============================================

/**
 * Get file icon class based on extension
 */
function getFileIcon($ext) {
    $icon_map = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'ppt' => 'fa-file-powerpoint',
        'pptx' => 'fa-file-powerpoint',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'gif' => 'fa-file-image',
        'bmp' => 'fa-file-image',
        'webp' => 'fa-file-image',
        'svg' => 'fa-file-image',
        'txt' => 'fa-file-alt',
        'log' => 'fa-file-alt',
        'md' => 'fa-file-alt',
        'zip' => 'fa-file-archive',
        'rar' => 'fa-file-archive',
        '7z' => 'fa-file-archive',
        'tar' => 'fa-file-archive',
        'gz' => 'fa-file-archive'
    ];
    return $icon_map[$ext] ?? 'fa-file';
}

/**
 * Get color class for file icon
 */
function getFileColor($ext) {
    $color_map = [
        'pdf' => '#dc2626',
        'doc' => '#2563eb',
        'docx' => '#2563eb',
        'xls' => '#16a34a',
        'xlsx' => '#16a34a',
        'ppt' => '#ea580c',
        'pptx' => '#ea580c',
        'jpg' => '#16a34a',
        'jpeg' => '#16a34a',
        'png' => '#16a34a',
        'gif' => '#16a34a',
        'zip' => '#7c3aed',
        'rar' => '#7c3aed',
        '7z' => '#7c3aed'
    ];
    return $color_map[$ext] ?? '#6b7280';
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

/**
 * Get file extension badge class
 */
function getFileBadgeClass($ext) {
    $badge_map = [
        'pdf' => 'pdf',
        'doc' => 'doc',
        'docx' => 'doc',
        'xls' => 'xls',
        'xlsx' => 'xls',
        'ppt' => 'ppt',
        'pptx' => 'ppt',
        'jpg' => 'jpg',
        'jpeg' => 'jpg',
        'png' => 'jpg',
        'gif' => 'jpg',
        'zip' => 'zip',
        'rar' => 'zip',
        '7z' => 'zip'
    ];
    return $badge_map[$ext] ?? 'file';
}

/**
 * Check if file is viewable in browser
 */
function isFileViewable($ext) {
    $viewable = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'svg'];
    return in_array($ext, $viewable);
}

/**
 * Generate absolute URL for file path
 * Converts relative path to absolute URL starting from root
 */
function getFileUrl($file_path) {
    // Remove leading slash if exists
    $file_path = ltrim($file_path, '/');
    
    // Get base URL (protocol + domain)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host;
    
    // If file path already starts with http, return as is
    if(strpos($file_path, 'http://') === 0 || strpos($file_path, 'https://') === 0) {
        return $file_path;
    }
    
    // Return absolute URL
    return $base_url . '/' . $file_path;
}
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-draft { background: #f1f5f9; color: #475569; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #dbeafe; color: #1e40af; }
    .status-dispatched { background: #cffafe; color: #0891b2; }
    .status-delivered { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .info-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .info-header {
        background: #f8fafc;
        padding: 15px 20px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
    }
    .info-body {
        padding: 20px;
    }
    .info-row {
        display: flex;
        margin-bottom: 12px;
        flex-wrap: wrap;
    }
    .info-label {
        width: 140px;
        font-weight: 600;
        color: #475569;
        flex-shrink: 0;
    }
    .info-value {
        flex: 1;
        color: #1e293b;
        word-break: break-word;
    }
    .timeline-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 15px;
        position: relative;
    }
    .timeline-item:not(:last-child):after {
        content: '';
        position: absolute;
        left: 14px;
        top: 30px;
        bottom: -15px;
        width: 2px;
        background: #e2e8f0;
    }
    .timeline-dot {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        flex-shrink: 0;
        margin-right: 15px;
        position: relative;
        z-index: 1;
    }
    .timeline-dot-success { background: #22c55e; }
    .timeline-dot-warning { background: #f59e0b; }
    .timeline-dot-info { background: #3b82f6; }
    .timeline-dot-danger { background: #ef4444; }
    .timeline-dot-secondary { background: #94a3b8; }
    .timeline-content {
        flex: 1;
        padding-top: 3px;
    }
    .timeline-content strong {
        display: block;
        margin-bottom: 2px;
    }
    .timeline-content small {
        color: #94a3b8;
        font-size: 12px;
    }
    
    /* Attachment Styles */
    .attachments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }
    .attachment-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 15px 12px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
        display: block;
        position: relative;
    }
    .attachment-item:hover {
        border-color: #3b82f6;
        background: #f0f7ff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    .attachment-item .file-icon {
        font-size: 40px;
        display: block;
        margin-bottom: 8px;
    }
    .attachment-item .file-name {
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
        word-break: break-all;
        margin-bottom: 4px;
    }
    .attachment-item .file-size {
        font-size: 11px;
        color: #94a3b8;
        display: block;
    }
    .attachment-item .upload-date {
        font-size: 10px;
        color: #cbd5e1;
        display: block;
        margin-top: 4px;
    }
    .attachment-item .badge-ext {
        position: absolute;
        top: 8px;
        right: 8px;
        font-size: 9px;
        padding: 2px 8px;
        border-radius: 12px;
        background: #e2e8f0;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
    }
    .attachment-item .badge-ext.pdf { background: #fee2e2; color: #dc2626; }
    .attachment-item .badge-ext.jpg, 
    .attachment-item .badge-ext.jpeg, 
    .attachment-item .badge-ext.png, 
    .attachment-item .badge-ext.gif { background: #dcfce7; color: #16a34a; }
    .attachment-item .badge-ext.doc, 
    .attachment-item .badge-ext.docx { background: #dbeafe; color: #2563eb; }
    .attachment-item .badge-ext.xls, 
    .attachment-item .badge-ext.xlsx { background: #dcfce7; color: #16a34a; }
    .attachment-item .badge-ext.ppt, 
    .attachment-item .badge-ext.pptx { background: #ffedd5; color: #ea580c; }
    .attachment-item .badge-ext.zip, 
    .attachment-item .badge-ext.rar, 
    .attachment-item .badge-ext.7z { background: #ede9fe; color: #7c3aed; }
    .attachment-item .badge-ext.txt { background: #e2e8f0; color: #475569; }
    
    @media (max-width: 768px) {
        .attachments-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 10px;
        }
        .info-label {
            width: 100px;
        }
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-exchange-alt text-primary me-2"></i>Transfer Details</h4>
            <p class="text-muted small mb-0">View complete transfer information</p>
        </div>
        <div>
            <a href="list.php" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
            <a href="print.php?id=<?php echo $transfer['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                <i class="fas fa-print me-1"></i> Print PDF
            </a>
        </div>
    </div>
    
    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; ?>
        <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Transfer Info -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-info-circle me-2 text-primary"></i> Transfer Information
                </div>
                <div class="info-body">
                    <div class="info-row">
                        <div class="info-label">Transfer No:</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($transfer['transfer_no']); ?></strong></div>
                    </div>
                    <?php if($transfer['tracking_no']): ?>
                    <div class="info-row">
                        <div class="info-label">Tracking No:</div>
                        <div class="info-value"><span class="badge bg-info"><?php echo htmlspecialchars($transfer['tracking_no']); ?></span></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Transfer Date:</div>
                        <div class="info-value"><?php echo date('d-m-Y', strtotime($transfer['transfer_date'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Created:</div>
                        <div class="info-value"><?php echo date('d-m-Y H:i', strtotime($transfer['created_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value"><span class="status-badge status-<?php echo $transfer['status']; ?>"><?php echo ucfirst($transfer['status']); ?></span></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Handled By:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['handled_by']) ?: '—'; ?></div>
                    </div>
                    <?php if($transfer['updated_at'] && $transfer['status'] != 'pending'): ?>
                    <div class="info-row">
                        <div class="info-label">Last Updated:</div>
                        <div class="info-value"><?php echo date('d-m-Y H:i', strtotime($transfer['updated_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- From Location -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-map-marker-alt me-2 text-danger"></i> From (Sender)
                </div>
                <div class="info-body">
                    <div class="info-row">
                        <div class="info-label">Location:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['from_location']); ?></div>
                    </div>
                    <?php if($transfer['from_department']): ?>
                    <div class="info-row">
                        <div class="info-label">Department:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['from_department']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Address:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($transfer['from_address'])); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- To Location -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-arrow-right me-2 text-success"></i> To (Recipient)
                </div>
                <div class="info-body">
                    <div class="info-row">
                        <div class="info-label">Location:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['to_location']); ?></div>
                    </div>
                    <?php if($transfer['to_department']): ?>
                    <div class="info-row">
                        <div class="info-label">Department:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['to_department']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['to_attn']): ?>
                    <div class="info-row">
                        <div class="info-label">Attention To:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['to_attn']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Address:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($transfer['to_address'])); ?></div>
                    </div>
                    <?php if($transfer['delivery_location']): ?>
                    <div class="info-row">
                        <div class="info-label">Delivery Location:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['delivery_location']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Employee Information -->
            <?php if($employee_info || $transfer['employee_name']): ?>
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-user me-2 text-info"></i> Employee Information
                </div>
                <div class="info-body">
                    <?php if($transfer['employee_name']): ?>
                    <div class="info-row">
                        <div class="info-label">Employee Name:</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($transfer['employee_name']); ?></strong></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['employee_pf_no']): ?>
                    <div class="info-row">
                        <div class="info-label">PF No:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['employee_pf_no']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['employee_designation']): ?>
                    <div class="info-row">
                        <div class="info-label">Designation:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['employee_designation']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['employee_department']): ?>
                    <div class="info-row">
                        <div class="info-label">Department:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['employee_department']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['employee_phone']): ?>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['employee_phone']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['employee_email']): ?>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><a href="mailto:<?php echo htmlspecialchars($transfer['employee_email']); ?>"><?php echo htmlspecialchars($transfer['employee_email']); ?></a></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Product Info -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-box me-2 text-warning"></i> Product Information
                </div>
                <div class="info-body">
                    <?php if($item_info): ?>
                    <div class="info-row">
                        <div class="info-label">Item Code:</div>
                        <div class="info-value"><span class="badge bg-secondary"><?php echo htmlspecialchars($item_info['item_code']); ?></span></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Product Name:</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($transfer['product_name']); ?></strong></div>
                    </div>
                    <?php if($transfer['product_type']): ?>
                    <div class="info-row">
                        <div class="info-label">Product Type:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['product_type']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Quantity:</div>
                        <div class="info-value"><?php echo $transfer['quantity']; ?> unit(s)</div>
                    </div>
                    <?php if($item_info && $item_info['brand']): ?>
                    <div class="info-row">
                        <div class="info-label">Brand:</div>
                        <div class="info-value"><?php echo htmlspecialchars($item_info['brand']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['model_number']): ?>
                    <div class="info-row">
                        <div class="info-label">Model No:</div>
                        <div class="info-value"><?php echo htmlspecialchars($transfer['model_number']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['serial_numbers']): ?>
                    <div class="info-row">
                        <div class="info-label">Serial No(s):</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($transfer['serial_numbers'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['description']): ?>
                    <div class="info-row">
                        <div class="info-label">Description:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($transfer['description'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['reason']): ?>
                    <div class="info-row">
                        <div class="info-label">Reason:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($transfer['reason'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Attachments -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-paperclip me-2 text-secondary"></i> Attachments
                    <?php if(count($attachments) > 0): ?>
                    <span class="badge bg-secondary ms-2"><?php echo count($attachments); ?> file(s)</span>
                    <?php endif; ?>
                </div>
                <div class="info-body">
                    <?php if(count($attachments) > 0): ?>
                        <div class="attachments-grid">
                            <?php foreach($attachments as $att): 
                                $ext = strtolower(pathinfo($att['original_name'], PATHINFO_EXTENSION));
                                $icon_class = getFileIcon($ext);
                                $icon_color = getFileColor($ext);
                                $badge_class = getFileBadgeClass($ext);
                                $file_size = formatFileSize($att['file_size']);
                                $is_viewable = isFileViewable($ext);
                                $file_url = getFileUrl($att['file_path']);
                            ?>
                            <a href="<?php echo $file_url; ?>" 
                               target="_blank" 
                               class="attachment-item" 
                               title="Click to view/download: <?php echo htmlspecialchars($att['original_name']); ?>"
                               <?php if($is_viewable): ?>
                               data-viewable="true"
                               <?php endif; ?>>
                                <span class="badge-ext <?php echo $badge_class; ?>"><?php echo strtoupper($ext ?: 'FILE'); ?></span>
                                <i class="fas <?php echo $icon_class; ?> file-icon" style="color: <?php echo $icon_color; ?>;"></i>
                                <div class="file-name"><?php echo htmlspecialchars($att['original_name']); ?></div>
                                <span class="file-size">
                                    <i class="fas fa-hdd me-1"></i> <?php echo $file_size; ?>
                                </span>
                                <span class="upload-date">
                                    <i class="far fa-clock me-1"></i> 
                                    <?php echo date('d-m-Y H:i', strtotime($att['created_at'])); ?>
                                </span>
                                <?php if($is_viewable): ?>
                                    <span style="display:block; margin-top:6px; font-size:9px; color:#3b82f6;">
                                        <i class="fas fa-eye"></i> View in browser
                                    </span>
                                <?php endif; ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-paperclip fa-2x mb-2 d-block" style="color: #d1d5db;"></i>
                            <p class="mb-0">No attachments found for this transfer.</p>
                            <small>Upload documents like gate pass, authorization letters, etc.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Notes -->
            <?php if($transfer['notes']): ?>
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-sticky-note me-2 text-secondary"></i> Additional Notes
                </div>
                <div class="info-body">
                    <?php echo nl2br(htmlspecialchars($transfer['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Status Actions -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-cog me-2 text-secondary"></i> Actions
                </div>
                <div class="info-body">
                    <div class="d-grid gap-2">
                        <?php if($transfer['status'] == 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $transfer['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Approve this transfer?')">
                            <i class="fas fa-check me-1"></i> Approve Transfer
                        </a>
                        <?php endif; ?>
                        <?php if($transfer['status'] == 'approved'): ?>
                        <a href="?action=dispatch&id=<?php echo $transfer['id']; ?>" class="btn btn-info btn-sm" onclick="return confirm('Mark as dispatched?')">
                            <i class="fas fa-truck me-1"></i> Mark as Dispatched
                        </a>
                        <?php endif; ?>
                        <?php if($transfer['status'] == 'dispatched'): ?>
                        <a href="?action=deliver&id=<?php echo $transfer['id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('Mark as delivered?')">
                            <i class="fas fa-check-circle me-1"></i> Mark as Delivered
                        </a>
                        <?php endif; ?>
                        <?php if(!in_array($transfer['status'], ['delivered', 'cancelled'])): ?>
                        <a href="?action=cancel&id=<?php echo $transfer['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this transfer?')">
                            <i class="fas fa-times me-1"></i> Cancel Transfer
                        </a>
                        <?php endif; ?>
                        <a href="edit.php?id=<?php echo $transfer['id']; ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit me-1"></i> Edit Transfer
                        </a>
                        <a href="print.php?id=<?php echo $transfer['id']; ?>" target="_blank" class="btn btn-primary btn-sm">
                            <i class="fas fa-print me-1"></i> Print / PDF
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Status Timeline -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-history me-2 text-secondary"></i> Timeline
                </div>
                <div class="info-body">
                    <!-- Created -->
                    <div class="timeline-item">
                        <div class="timeline-dot timeline-dot-success">
                            <i class="fas fa-plus fa-xs"></i>
                        </div>
                        <div class="timeline-content">
                            <strong>Created</strong>
                            <small><?php echo date('d-m-Y H:i', strtotime($transfer['created_at'])); ?></small>
                        </div>
                    </div>
                    
                    <!-- Approved -->
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $approved_date ? 'timeline-dot-success' : 'timeline-dot-secondary'; ?>">
                            <i class="fas fa-check fa-xs"></i>
                        </div>
                        <div class="timeline-content">
                            <strong>Approved</strong>
                            <small><?php echo $approved_date ?: 'Pending'; ?></small>
                        </div>
                    </div>
                    
                    <!-- Dispatched -->
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $dispatched_date ? 'timeline-dot-info' : 'timeline-dot-secondary'; ?>">
                            <i class="fas fa-truck fa-xs"></i>
                        </div>
                        <div class="timeline-content">
                            <strong>Dispatched</strong>
                            <small><?php echo $dispatched_date ?: 'Pending'; ?></small>
                        </div>
                    </div>
                    
                    <!-- Delivered -->
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $delivered_date ? 'timeline-dot-success' : 'timeline-dot-secondary'; ?>">
                            <i class="fas fa-check-circle fa-xs"></i>
                        </div>
                        <div class="timeline-content">
                            <strong>Delivered</strong>
                            <small><?php echo $delivered_date ?: 'Pending'; ?></small>
                        </div>
                    </div>
                    
                    <!-- Cancelled (if applicable) -->
                    <?php if($cancelled_date): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot timeline-dot-danger">
                            <i class="fas fa-times fa-xs"></i>
                        </div>
                        <div class="timeline-content">
                            <strong>Cancelled</strong>
                            <small><?php echo $cancelled_date; ?></small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Current Status Highlight -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Current Status:</span>
                            <span class="status-badge status-<?php echo $transfer['status']; ?>"><?php echo ucfirst($transfer['status']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Card -->
            <div class="info-card">
                <div class="info-header">
                    <i class="fas fa-file-alt me-2 text-secondary"></i> Summary
                </div>
                <div class="info-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Transfer No</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($transfer['transfer_no']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Product</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($transfer['product_name']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Quantity</span>
                        <span class="fw-bold"><?php echo $transfer['quantity']; ?> unit(s)</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">From</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($transfer['from_location']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-muted">To</span>
                        <span class="fw-bold"><?php echo htmlspecialchars($transfer['to_location']); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add click handler for attachments to open in new tab
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.attachment-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            // Allow default behavior (open link)
            // The link already has target="_blank"
        });
    });
});
</script>

<?php include '../../includes/footer.php'; ?>