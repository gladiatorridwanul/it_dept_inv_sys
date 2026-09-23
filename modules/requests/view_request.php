<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Helper Functions
function getRequestTypeDisplayFromDB($db_type) {
    $mapping = [
        'technical_support' => '📌 Report an Issue',
        'technical' => '📌 Report an Issue',
        'issue' => '📌 Report an Issue',
        'report' => '📌 Report an Issue',
        'software_access' => '🔑 Software Access',
        'software' => '🔑 Software Access',
        'accessories' => '🖱️ Accessories Request',
        'accessory' => '🖱️ Accessories Request',
        'return_device' => '🔄 Return Device',
        'return' => '🔄 Return Device',
        'update' => '✏️ Update Device',
        'upgrade' => '⬆️ Upgrade Request',
        'assign' => '💻 New Assignment',
        'device_assign' => '🏠 Multiple Device Assign',
        'replace' => '🔄 Replacement Request',
        'handover' => '🤝 Handover Request'
    ];
    return $mapping[$db_type] ?? '❓ ' . ucfirst(str_replace('_', ' ', $db_type));
}

function generateNumber($prefix, $table, $column) {
    global $pdo;
    // Get current date in YYYYMMDD format
    $datePrefix = date('Ymd');
    $fullPrefix = $prefix . $datePrefix;
    
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$fullPrefix . '%']);
    $last = $stmt->fetchColumn();
    if ($last) {
        // Extract the numeric part (last 4 digits after the date prefix)
        $numPart = substr($last, strlen($fullPrefix));
        if (is_numeric($numPart)) {
            $num = (int)$numPart + 1;
        } else {
            $num = 1;
        }
    } else {
        $num = 1;
    }
    return $fullPrefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$error = '';

// Get all IT Staff and Admin users for dropdown
$users_stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE is_active = 1 AND role IN ('admin', 'it_staff') ORDER BY full_name");
$users_stmt->execute();
$users_list = $users_stmt->fetchAll();

// Get available items for allocation with serial numbers - For AJAX
$availableItemsWithSerials = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            i.id, i.name, i.item_code, i.current_qty, i.brand,
            isn.id as serial_id, isn.serial_number, isn.model_number, isn.version,
            (CASE WHEN isn.is_assigned = 0 OR isn.is_assigned IS NULL THEN 1 ELSE 0 END) as is_available
        FROM items i
        INNER JOIN item_serial_numbers isn ON i.id = isn.item_id 
        WHERE i.is_active = 1 
            AND (isn.is_assigned = 0 OR isn.is_assigned IS NULL)
        ORDER BY i.name, isn.serial_number
    ");
    $stmt->execute();
    $availableItemsWithSerials = $stmt->fetchAll();
} catch (PDOException $e) {
    $availableItemsWithSerials = [];
}

// Handle POST requests for updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        // Update request status
        $status = $_POST['status'] ?? 'pending';
        $priority = $_POST['priority'] ?? 'medium';
        $estimated_date = !empty($_POST['estimated_completion_date']) ? $_POST['estimated_completion_date'] : null;
        $resolution_notes = trim($_POST['resolution_notes'] ?? '');
        $assigned_to_team = $_POST['assigned_to_team'] ?? null;
        
        $stmt = $pdo->prepare("
            UPDATE requests 
            SET status = ?, priority = ?, estimated_completion_date = ?, 
                resolution_notes = ?, assigned_to_team = ?, processed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $status, $priority, $estimated_date, $resolution_notes,
            $assigned_to_team, $_SESSION['user_id'], $id
        ]);
        
        if ($status == 'completed') {
            $stmt = $pdo->prepare("UPDATE requests SET resolved_date = NOW(), resolved_by = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $id]);
        }
        
        $message = "Request status updated successfully!";
        
    } elseif ($action === 'allocate_device') {
        // Allocate device from stock to request
        $serial_id = $_POST['serial_id'] ?? 0;
        $item_id = $_POST['item_id'] ?? 0;
        $serial_number = $_POST['serial_number'] ?? null;
        $model_number = $_POST['model_number'] ?? null;
        $version = $_POST['version'] ?? null;
        $quantity = 1;
        $allocation_date = !empty($_POST['allocation_date']) ? $_POST['allocation_date'] : date('Y-m-d');
        $expected_return_date = !empty($_POST['expected_return_date']) ? $_POST['expected_return_date'] : null;
        $allocation_notes = trim($_POST['allocation_notes'] ?? '');
        
        if ($serial_id > 0) {
            // Check if serial is still available
            $stmt = $pdo->prepare("
                SELECT * FROM item_serial_numbers 
                WHERE id = ? AND (is_assigned = 0 OR is_assigned IS NULL)
            ");
            $stmt->execute([$serial_id]);
            $serial_item = $stmt->fetch();
            
            if (!$serial_item) {
                $error = "This serial number is already assigned or not available!";
            } else {
                $item_id = $serial_item['item_id'];
                $serial_number = $serial_item['serial_number'];
                $model_number = $serial_item['model_number'];
                $version = $serial_item['version'];
            }
        }
        
        if (!$error && $item_id > 0) {
            $pdo->beginTransaction();
            try {
                // Generate unique assignment number with retry logic
                $attempts = 0;
                $maxAttempts = 5;
                $assignment_no = null;
                
                while ($attempts < $maxAttempts && !$assignment_no) {
                    $candidateNo = generateNumber('ASN-', 'assignments', 'assignment_no');
                    
                    // Check if this assignment number already exists
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE assignment_no = ?");
                    $checkStmt->execute([$candidateNo]);
                    $exists = $checkStmt->fetchColumn();
                    
                    if (!$exists) {
                        $assignment_no = $candidateNo;
                    }
                    $attempts++;
                }
                
                if (!$assignment_no) {
                    throw new Exception("Failed to generate unique assignment number. Please try again.");
                }
                
                $stmt = $pdo->prepare("SELECT employee_id FROM requests WHERE id = ?");
                $stmt->execute([$id]);
                $request_data = $stmt->fetch();
                
                // Create assignment
                $stmt = $pdo->prepare("
                    INSERT INTO assignments (assignment_no, employee_id, item_id, quantity, assigned_date, expected_return_date, notes, assigned_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$assignment_no, $request_data['employee_id'], $item_id, $quantity, 
                                $allocation_date, $expected_return_date, $allocation_notes, $_SESSION['user_id']]);
                $assignment_id = $pdo->lastInsertId();
                
                // Update item_serial_numbers table
                if ($serial_id > 0) {
                    $stmt = $pdo->prepare("
                        UPDATE item_serial_numbers 
                        SET is_assigned = 1, assigned_to = ?, assigned_date = ?, assignment_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$request_data['employee_id'], $allocation_date, $assignment_id, $serial_id]);
                }
                
                // Insert into request_assignments
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO request_assignments (request_id, item_id, assignment_id, quantity, status, allocated_by, allocated_date)
                        VALUES (?, ?, ?, ?, 'allocated', ?, NOW())
                    ");
                    $stmt->execute([$id, $item_id, $assignment_id, $quantity, $_SESSION['user_id']]);
                } catch (PDOException $e) {
                    // Table might not exist - ignore
                }
                
                // Update items table quantity
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ?, total_assigned = total_assigned + ?, available_qty = available_qty - ? WHERE id = ?");
                $stmt->execute([$quantity, $quantity, $quantity, $item_id]);
                
                $pdo->commit();
                $message = "Device allocated successfully! Assignment #: " . $assignment_no;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error allocating device: " . $e->getMessage();
            }
        } else {
            $error = "Please select a valid device from the list!";
        }
    }
    
    // After processing POST, redirect back to the same page
    $redirect_url = "view_request.php?id=" . $id;
    if ($message) {
        $redirect_url .= "&success=" . urlencode($message);
    }
    if ($error) {
        $redirect_url .= "&error=" . urlencode($error);
    }
    header("Location: " . $redirect_url);
    exit();
}

// Get main request data
$stmt = $pdo->prepare("
    SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
           u.full_name as processed_by_name,
           ru.full_name as resolved_by_name
    FROM requests r 
    JOIN employees e ON r.employee_id = e.id 
    LEFT JOIN users u ON r.processed_by = u.id
    LEFT JOIN users ru ON r.resolved_by = ru.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    echo '<div class="alert alert-danger">Request not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get attachments
$attachments = $pdo->prepare("SELECT * FROM request_attachments WHERE request_id = ? ORDER BY created_at DESC");
$attachments->execute([$id]);
$attachments = $attachments->fetchAll();

// Get request assignments with full details
$assignments = [];
try {
    $stmt = $pdo->prepare("
        SELECT ra.*, i.name as item_name, i.item_code, i.price, i.brand,
               isn.serial_number, isn.model_number, isn.version,
               a.assignment_no, a.assigned_date, a.expected_return_date, a.status as assignment_status
        FROM request_assignments ra
        LEFT JOIN items i ON ra.item_id = i.id
        LEFT JOIN assignments a ON ra.assignment_id = a.id
        LEFT JOIN item_serial_numbers isn ON a.id = isn.assignment_id
        WHERE ra.request_id = ?
        ORDER BY ra.created_at DESC
    ");
    $stmt->execute([$id]);
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    $assignments = [];
}

// Get specific request details
$specific_data = null;
$multiple_devices = [];
$request_type_display = getRequestTypeDisplayFromDB($request['request_type']);

if ($request['request_type'] == 'upgrade') {
    $stmt = $pdo->prepare("
        SELECT ur.*, a.assignment_no
        FROM upgrade_requests ur
        LEFT JOIN assignments a ON ur.assignment_id = a.id
        WHERE ur.request_id = ?
    ");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
    
} elseif ($request['request_type'] == 'device_assign') {
    if (!empty($request['request_data'])) {
        $request_data = json_decode($request['request_data'], true);
        if ($request_data && isset($request_data['devices'])) {
            $multiple_devices = $request_data['devices'];
        }
    }
} elseif ($request['request_type'] == 'software_access') {
    $stmt = $pdo->prepare("SELECT * FROM software_access_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif ($request['request_type'] == 'accessories') {
    $stmt = $pdo->prepare("SELECT * FROM accessories_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif ($request['request_type'] == 'return_device') {
    $stmt = $pdo->prepare("SELECT * FROM return_device_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
}

// Get comments
$comments = $pdo->prepare("
    SELECT c.*, u.full_name as commenter_name 
    FROM request_comments c 
    JOIN users u ON c.commented_by = u.id 
    WHERE c.request_id = ? 
    ORDER BY c.created_at DESC
");
$comments->execute([$id]);
$comments = $comments->fetchAll();

// Helper functions for badges
function getStatusBadgeFull($status) {
    $badges = [
        'pending' => '<span class="badge-status status-pending"><i class="fas fa-clock"></i> Pending</span>',
        'under_observation' => '<span class="badge-status status-under_observation"><i class="fas fa-eye"></i> Under Observation</span>',
        'processing' => '<span class="badge-status status-processing"><i class="fas fa-cog fa-spin"></i> Processing</span>',
        'completed' => '<span class="badge-status status-completed"><i class="fas fa-check-circle"></i> Completed</span>',
        'rejected' => '<span class="badge-status status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>'
    ];
    return $badges[$status] ?? '<span class="badge-status">' . ucfirst($status) . '</span>';
}

function getPriorityBadgeFull($priority) {
    $badges = [
        'low' => '<span class="badge-priority-low"><i class="fas fa-arrow-down"></i> Low</span>',
        'medium' => '<span class="badge-priority-medium"><i class="fas fa-minus"></i> Medium</span>',
        'high' => '<span class="badge-priority-high"><i class="fas fa-arrow-up"></i> High</span>',
        'critical' => '<span class="badge-priority-critical"><i class="fas fa-exclamation-triangle"></i> Critical</span>'
    ];
    return $badges[$priority] ?? '<span class="badge-priority-medium">' . ucfirst($priority) . '</span>';
}

function getRequestTypeIconFull($type) {
    $icons = [
        'technical_support' => '🐛', 'software_access' => '🔑', 'accessories' => '🖱️',
        'return_device' => '🔄', 'upgrade' => '⬆️', 'device_assign' => '🏠'
    ];
    return $icons[$type] ?? '📋';
}
?>

<style>
    :root {
        --primary: #3b82f6;
        --primary-dark: #2563eb;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #0ea5e9;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-600: #475569;
        --gray-700: #334155;
    }

    .request-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 1.5rem;
    }
    
    .card-modern {
        background: white;
        border-radius: 16px;
        border: 1px solid var(--gray-200);
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }
    
    .card-header-modern {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        background: var(--gray-50);
    }
    
    .card-body-modern {
        padding: 1.25rem;
    }
    
    .header-primary { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; }
    .header-primary .card-header-modern { background: transparent; color: white; border-bottom-color: rgba(255,255,255,0.2); }
    .header-success { background: linear-gradient(135deg, var(--success), #059669); color: white; }
    .header-warning { background: linear-gradient(135deg, var(--warning), #d97706); color: white; }
    .header-info { background: linear-gradient(135deg, var(--info), #0284c7); color: white; }
    .header-dark { background: linear-gradient(135deg, #1e293b, #0f172a); color: white; }
    
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
    
    .info-item {
        background: var(--gray-50);
        border-radius: 12px;
        padding: 0.875rem 1rem;
        border: 1px solid var(--gray-200);
    }
    
    .info-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        margin-bottom: 0.25rem;
    }
    
    .info-value {
        font-weight: 500;
        color: #1e293b;
        word-break: break-word;
    }
    
    .badge-status, .badge-priority-low, .badge-priority-medium, .badge-priority-high, .badge-priority-critical {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.75rem;
        border-radius: 30px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-under_observation { background: #cffafe; color: #0891b2; }
    .status-processing { background: #e0e7ff; color: #3730a3; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    
    .badge-priority-low { background: #f1f5f9; color: #475569; }
    .badge-priority-medium { background: #cffafe; color: #0891b2; }
    .badge-priority-high { background: #fed7aa; color: #9a3412; }
    .badge-priority-critical { background: #fee2e2; color: #dc2626; }
    
    .assignment-table {
        width: 100%;
        margin-bottom: 0;
    }
    
    .assignment-table th {
        background: var(--gray-50);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .assignment-table td {
        padding: 0.75rem;
        vertical-align: middle;
        font-size: 0.8rem;
    }
    
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 1.25rem;
    }
    
    .timeline-icon {
        position: absolute;
        left: -30px;
        top: 0;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.75rem;
    }
    
    .bg-success { background: var(--success); }
    .bg-info { background: var(--info); }
    .bg-primary { background: var(--primary); }
    .bg-secondary { background: #64748b; }
    .bg-danger { background: var(--danger); }
    .bg-warning { background: var(--warning); }
    
    .search-container {
        position: relative;
    }
    
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 300px;
        overflow-y: auto;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .search-result-item {
        padding: 10px 15px;
        cursor: pointer;
        border-bottom: 1px solid var(--gray-100);
        transition: background 0.2s;
    }
    
    .search-result-item:hover {
        background: var(--gray-50);
    }
    
    .selected-item-display {
        background: #e8f5e9;
        border: 1px solid var(--success);
        border-radius: 12px;
        padding: 12px 15px;
        margin-top: 10px;
    }
    
    .detail-section {
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .detail-section:last-child {
        border-bottom: none;
    }
    
    .requested-devices-table {
        width: 100%;
        margin-bottom: 0;
    }
    
    .requested-devices-table th {
        background: #f8fafc;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 0.75rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .requested-devices-table td {
        padding: 0.75rem;
        vertical-align: top;
        font-size: 0.8rem;
        border-bottom: 1px solid var(--gray-100);
    }
    
    /* Modal Styles */
    .modal-custom {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    
    .modal-custom-content {
        background-color: #fff;
        margin: 5% auto;
        padding: 0;
        border-radius: 16px;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        animation: modalFadeIn 0.3s ease;
    }
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-50px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .modal-custom-header {
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-radius: 16px 16px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-custom-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        opacity: 0.7;
    }
    
    .modal-custom-close:hover { opacity: 1; }
    
    .modal-custom-body {
        padding: 1.25rem;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-detail-item {
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .modal-detail-label {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--gray-600);
        margin-bottom: 0.25rem;
    }
    
    .assignment-link {
        color: var(--primary);
        cursor: pointer;
        text-decoration: none;
        font-weight: 500;
    }
    
    .assignment-link:hover {
        text-decoration: underline;
    }
    
    /* Bootstrap Grid - Ensure consistent layout */
    .row {
        display: flex;
        flex-wrap: wrap;
        margin-right: -12px;
        margin-left: -12px;
    }
    
    .col-lg-8 {
        position: relative;
        width: 100%;
        padding-right: 12px;
        padding-left: 12px;
    }
    
    .col-lg-4 {
        position: relative;
        width: 100%;
        padding-right: 12px;
        padding-left: 12px;
    }
    
    @media (min-width: 992px) {
        .col-lg-8 { flex: 0 0 66.666667%; max-width: 66.666667%; }
        .col-lg-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
    }
    
    @media (max-width: 768px) {
        .info-grid { grid-template-columns: 1fr; }
        .request-container { padding: 1rem; }
        .col-lg-8, .col-lg-4 { flex: 0 0 100%; max-width: 100%; }
    }
    
    /* Combined Info Grid */
    .combined-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.25rem;
    }
    
    .info-section {
        background: var(--gray-50);
        border-radius: 12px;
        padding: 1rem;
        border: 1px solid var(--gray-200);
    }
    
    .info-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--primary);
        margin-bottom: 0.75rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--primary);
        display: inline-block;
    }
    
    .info-row {
        display: flex;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
    }
    
    .info-row-label {
        width: 120px;
        font-weight: 600;
        color: var(--gray-600);
        flex-shrink: 0;
    }
    
    .info-row-value {
        flex: 1;
        color: #1e293b;
        word-break: break-word;
    }
    
    .requested-items-list {
        margin-top: 0.5rem;
    }
    
    .requested-item {
        background: white;
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        border: 1px solid var(--gray-200);
    }
    
    .requested-item:last-child {
        margin-bottom: 0;
    }
    
    hr {
        margin: 0.75rem 0;
        border-color: var(--gray-200);
    }
    
    /* Alert styles */
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .available-badge {
        font-size: 0.65rem;
        background: #d1fae5;
        color: #065f46;
        padding: 2px 8px;
        border-radius: 20px;
        display: inline-block;
    }

    /* Ensure right column stays intact */
    .right-column-sticky {
        position: sticky;
        top: 20px;
    }

    /* Clearfix for proper layout */
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
</style>

<div class="request-container">
    <?php if(isset($_GET['success'])): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_GET['success']); ?>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error'])): ?>
        <div class="alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($_GET['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="mb-1 fw-semibold">
                <i class="fas fa-eye me-2 text-primary"></i>
                Request Details: <?php echo htmlspecialchars($request['request_no']); ?>
            </h4>
            <p class="text-muted small mb-0">Complete information about this support request</p>
        </div>
        <div class="d-flex gap-2">
            <a href="all_requests.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-left me-1"></i> Back to All Requests
            </a>
            <a href="print_acknowledgement.php?id=<?php echo $request['id']; ?>" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3">
                <i class="fas fa-print me-1"></i> Print Acknowledgement
            </a>
        </div>
    </div>

    <div class="row">
        <!-- LEFT COLUMN (8) -->
        <div class="col-lg-8">
            <!-- Combined Request Information & Summary Card -->
            <div class="card-modern">
                <div class="card-header-modern header-primary">
                    <i class="fas fa-info-circle"></i> Request Information & Summary
                </div>
                <div class="card-body-modern">
                    <div class="combined-info-grid">
                        <!-- Request Basic Information -->
                        <div class="info-section">
                            <div class="info-section-title"><i class="fas fa-ticket-alt me-1"></i> Request Details</div>
                            <div class="info-row">
                                <span class="info-row-label">Request No:</span>
                                <span class="info-row-value"><strong><?php echo htmlspecialchars($request['request_no']); ?></strong></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Request Type:</span>
                                <span class="info-row-value"><?php echo getRequestTypeIconFull($request['request_type']); ?> <?php echo $request_type_display; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Status:</span>
                                <span class="info-row-value"><?php echo getStatusBadgeFull($request['status']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Priority:</span>
                                <span class="info-row-value"><?php echo getPriorityBadgeFull($request['priority'] ?? 'medium'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Request Date:</span>
                                <span class="info-row-value"><?php echo date('d-m-Y H:i:s', strtotime($request['requested_date'])); ?></span>
                            </div>
                            <?php if($request['estimated_completion_date']): ?>
                            <div class="info-row">
                                <span class="info-row-label">Est. Completion:</span>
                                <span class="info-row-value"><?php echo date('d-m-Y', strtotime($request['estimated_completion_date'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if($request['urgency_level'] && $request['urgency_level'] != 'medium'): ?>
                            <div class="info-row">
                                <span class="info-row-label">Urgency Level:</span>
                                <span class="info-row-value"><?php echo ucfirst($request['urgency_level']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Employee Information -->
                        <div class="info-section">
                            <div class="info-section-title"><i class="fas fa-user me-1"></i> Employee Information</div>
                            <div class="info-row">
                                <span class="info-row-label">Full Name:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['full_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">PF No:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['pf_no']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Designation:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['designation'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Department:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['department'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Job Location:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['job_location'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Phone:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['phone'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-row-label">Email:</span>
                                <span class="info-row-value"><?php echo htmlspecialchars($request['email'] ?? 'N/A'); ?></span>
                            </div>
                        </div>

                        <!-- Requested Items / Devices -->
                        <div class="info-section">
                            <div class="info-section-title"><i class="fas fa-list-alt me-1"></i> Requested Items / Devices</div>
                            
                            <?php if ($request['request_type'] == 'upgrade' && $specific_data): ?>
                                <div class="info-row">
                                    <span class="info-row-label">Current Assignment:</span>
                                    <span class="info-row-value">
                                        <?php if ($specific_data['assignment_no']): ?>
                                            <a href="javascript:void(0)" class="assignment-link" onclick="showAssignmentModal(<?php echo $specific_data['assignment_id']; ?>)">
                                                <i class="fas fa-link"></i> <?php echo htmlspecialchars($specific_data['assignment_no']); ?>
                                            </a>
                                        <?php else: ?>
                                            No assignment found
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-row-label">Required Upgrade:</span>
                                    <span class="info-row-value"><?php echo nl2br(htmlspecialchars($specific_data['required_upgrade'] ?? 'Not specified')); ?></span>
                                </div>
                                <div class="info-row">
                                    <span class="info-row-label">Reason for Upgrade:</span>
                                    <span class="info-row-value"><?php echo nl2br(htmlspecialchars($specific_data['reason'] ?? 'Not specified')); ?></span>
                                </div>
                                <?php if ($specific_data['device_condition']): ?>
                                <div class="info-row">
                                    <span class="info-row-label">Device Condition:</span>
                                    <span class="info-row-value"><?php echo ucfirst(str_replace('_', ' ', $specific_data['device_condition'])); ?></span>
                                </div>
                                <?php endif; ?>
                                
                            <?php elseif ($request['request_type'] == 'device_assign' && count($multiple_devices) > 0): ?>
                                <div class="requested-items-list">
                                    <?php $counter = 1; foreach($multiple_devices as $device): ?>
                                    <div class="requested-item">
                                        <strong>#<?php echo $counter++; ?>: <?php echo htmlspecialchars($device['device_name'] ?? 'N/A'); ?></strong>
                                        <?php if(!empty($device['quantity']) && $device['quantity'] > 1): ?>
                                            <span class="badge bg-secondary ms-1">Qty: <?php echo $device['quantity']; ?></span>
                                        <?php endif; ?>
                                        <div class="small text-muted mt-1">
                                            <?php if(!empty($device['specification'])): ?>
                                                <div><i class="fas fa-microchip"></i> Specs: <?php echo nl2br(htmlspecialchars($device['specification'])); ?></div>
                                            <?php endif; ?>
                                            <?php if(!empty($device['reason'])): ?>
                                                <div><i class="fas fa-quote-left"></i> Reason: <?php echo nl2br(htmlspecialchars($device['reason'])); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                            <?php elseif($request['description']): ?>
                                <div class="info-row">
                                    <span class="info-row-label">Description:</span>
                                    <span class="info-row-value"><?php echo nl2br(htmlspecialchars($request['description'])); ?></span>
                                </div>
                                
                            <?php else: ?>
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-info-circle"></i> No requested items or details available.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Allocated Items / Devices Card -->
            <div class="card-modern">
                <div class="card-header-modern header-success">
                    <i class="fas fa-laptop-code"></i> Allocated Items / Devices
                </div>
                <div class="card-body-modern">
                    <?php if(count($assignments) > 0): ?>
                        <div class="table-responsive">
                            <table class="assignment-table">
                                <thead>
                                    <tr><th>Assignment No</th><th>Item</th><th>Serial Number</th><th>Model</th><th>Version</th><th>Qty</th><th>Allocation Date</th><th>Status</th><th>Action</th></table>
                                </thead>
                                <tbody>
                                    <?php foreach($assignments as $assign): ?>
                                    <tr>
                                        <td><a href="javascript:void(0)" class="assignment-link" onclick="showAssignmentModal(<?php echo $assign['assignment_id']; ?>)"><?php echo htmlspecialchars($assign['assignment_no']); ?></a></td>
                                        <td><strong><?php echo htmlspecialchars($assign['item_name'] ?? 'N/A'); ?></strong><br><small><?php echo htmlspecialchars($assign['item_code'] ?? ''); ?></small></td>
                                        <td class="text-center"><code><?php echo htmlspecialchars($assign['serial_number'] ?? '-'); ?></code></td>
                                        <td class="text-center"><?php echo htmlspecialchars($assign['model_number'] ?? '-'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($assign['version'] ?? '-'); ?></td>
                                        <td class="text-center"><?php echo $assign['quantity'] ?? 1; ?></td>
                                        <td class="text-center"><?php echo !empty($assign['assigned_date']) ? date('d-m-Y', strtotime($assign['assigned_date'])) : '-'; ?></td>
                                        <td class="text-center"><span class="badge bg-success"><?php echo ucfirst($assign['assignment_status'] ?? 'Assigned'); ?></span></td>
                                        <td class="text-center"><button class="btn btn-sm btn-outline-primary" onclick="editAssignment(<?php echo $assign['assignment_id']; ?>)"><i class="fas fa-edit"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-muted text-center py-3"><i class="fas fa-info-circle"></i> No items allocated yet.</div>
                    <?php endif; ?>
                    
                    <!-- Allocate New Device Form -->
                    <?php if(in_array($request['status'], ['pending', 'under_observation', 'processing'])): ?>
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-plus-circle"></i> Allocate New Device</h6>
                        <form method="POST" id="allocateForm">
                            <input type="hidden" name="action" value="allocate_device">
                            <input type="hidden" name="serial_id" id="selected_serial_id">
                            <input type="hidden" name="item_id" id="selected_item_id">
                            <input type="hidden" name="serial_number" id="selected_serial_number">
                            <input type="hidden" name="model_number" id="selected_model_number">
                            <input type="hidden" name="version" id="selected_version">
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Search Device by Serial/Model/Name <span class="text-danger">*</span></label>
                                    <div class="search-container">
                                        <input type="text" id="deviceSearch" class="form-control" placeholder="Type serial number, model, or device name..." autocomplete="off">
                                        <div id="searchResults" class="search-results"></div>
                                    </div>
                                    <div id="selectedDeviceDisplay" class="selected-item-display" style="display:none;">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><i class="fas fa-check-circle text-success"></i> Selected Device:</strong>
                                                <span id="selectedDeviceName"></span>
                                                <div class="mt-1">
                                                    <small><strong>Serial:</strong> <span id="selectedSerial"></span></small><br>
                                                    <small><strong>Model:</strong> <span id="selectedModel"></span></small><br>
                                                    <small><strong>Version:</strong> <span id="selectedVersion"></span></small>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearDeviceSelection()">
                                                <i class="fas fa-times"></i> Change
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Allocation Date <span class="text-danger">*</span></label>
                                    <input type="date" name="allocation_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label">Expected Return Date</label>
                                    <input type="date" name="expected_return_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100" id="allocateBtn" disabled>
                                        <i class="fas fa-check-circle"></i> Allocate Device
                                    </button>
                                </div>
                                
                                <div class="col-md-12">
                                    <label class="form-label">Notes</label>
                                    <textarea name="allocation_notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attachments Card -->
            <?php if(count($attachments) > 0): ?>
            <div class="card-modern">
                <div class="card-header-modern"><i class="fas fa-paperclip"></i> Attachments (<?php echo count($attachments); ?>)</div>
                <div class="card-body-modern">
                    <?php foreach($attachments as $att): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div><i class="fas fa-file-alt text-primary me-2"></i> <?php echo htmlspecialchars($att['file_name']); ?><br><small><?php echo date('d-m-Y H:i', strtotime($att['created_at'])); ?></small></div>
                            <a href="/it-inventory/<?php echo $att['file_path']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Download</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Comments Section -->
            <div class="card-modern">
                <div class="card-header-modern header-dark"><i class="fas fa-comments"></i> Internal Comments (<?php echo count($comments); ?>)</div>
                <div class="card-body-modern">
                    <form method="POST" action="add_comment.php" class="mb-4">
                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                        <textarea name="comment" rows="3" class="form-control mb-2" placeholder="Add internal comment..."></textarea>
                        <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fas fa-paper-plane me-1"></i> Add Comment</button>
                    </form>
                    
                    <?php if(count($comments) > 0): ?>
                        <?php foreach($comments as $comment): ?>
                            <div class="mb-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between mb-2"><strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong><small><?php echo date('d-m-Y H:i', strtotime($comment['created_at'])); ?></small></div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3"><i class="fas fa-comment-dots fa-2x mb-2 opacity-50"></i><p class="mb-0 small">No comments yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN (4) - Update Status, Timeline, Quick Stats -->
        <div class="col-lg-4">
            <div class="right-column-sticky">
                <!-- Update Status Card -->
                <div class="card-modern">
                    <div class="card-header-modern header-warning"><i class="fas fa-edit"></i> Update Status</div>
                    <div class="card-body-modern">
                        <form method="POST" action="view_request.php?id=<?php echo $id; ?>">
                            <input type="hidden" name="action" value="update_status">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending" <?php echo ($request['status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="under_observation" <?php echo ($request['status'] ?? '') == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                                    <option value="processing" <?php echo ($request['status'] ?? '') == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo ($request['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="rejected" <?php echo ($request['status'] ?? '') == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="low" <?php echo ($request['priority'] ?? '') == 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="medium" <?php echo ($request['priority'] ?? '') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="high" <?php echo ($request['priority'] ?? '') == 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="critical" <?php echo ($request['priority'] ?? '') == 'critical' ? 'selected' : ''; ?>>Critical</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Assigned Team/Person</label>
                                <select name="assigned_to_team" class="form-select">
                                    <option value="">-- Select IT Staff / Admin --</option>
                                    <?php foreach($users_list as $user): ?>
                                        <option value="<?php echo htmlspecialchars($user['full_name']); ?>" <?php echo ($request['assigned_to_team'] ?? '') == $user['full_name'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo ucfirst($user['role']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Select the IT staff member handling this request</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Est. Completion Date</label>
                                <input type="date" name="estimated_completion_date" class="form-control" value="<?php echo $request['estimated_completion_date']; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Resolution Notes</label>
                                <textarea name="resolution_notes" rows="3" class="form-control"><?php echo htmlspecialchars($request['resolution_notes'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-2"></i> Update Request</button>
                        </form>
                    </div>
                </div>

                <!-- Timeline Card -->
                <div class="card-modern">
                    <div class="card-header-modern header-dark"><i class="fas fa-history"></i> Timeline</div>
                    <div class="card-body-modern">
                        <div class="timeline">
                            <div class="timeline-item"><div class="timeline-icon bg-success"><i class="fas fa-plus"></i></div><div><strong>Request Created</strong><div class="small text-muted"><?php echo date('d-m-Y H:i', strtotime($request['requested_date'])); ?></div></div></div>
                            <?php if($request['accepted_date']): ?>
                            <div class="timeline-item"><div class="timeline-icon bg-info"><i class="fas fa-eye"></i></div><div><strong>First Reviewed</strong><div class="small text-muted"><?php echo date('d-m-Y H:i', strtotime($request['accepted_date'])); ?></div></div></div>
                            <?php endif; ?>
                            <?php if($request['resolved_date']): ?>
                            <div class="timeline-item"><div class="timeline-icon bg-primary"><i class="fas fa-check-circle"></i></div><div><strong>Resolved</strong><div class="small text-muted"><?php echo date('d-m-Y H:i', strtotime($request['resolved_date'])); ?></div></div></div>
                            <?php endif; ?>
                            <div class="timeline-item"><div class="timeline-icon bg-secondary"><i class="fas fa-flag-checkered"></i></div><div><strong>Current Status</strong><div class="small text-muted"><?php echo ucfirst(str_replace('_', ' ', $request['status'] ?? 'pending')); ?></div></div></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="card-modern">
                    <div class="card-header-modern"><i class="fas fa-chart-bar"></i> Quick Stats</div>
                    <div class="card-body-modern">
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Total Comments</span><strong><?php echo count($comments); ?></strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Total Attachments</span><strong><?php echo count($attachments); ?></strong></div>
                        <div class="d-flex justify-content-between py-2 border-bottom"><span>Allocated Items</span><strong><?php echo count($assignments); ?></strong></div>
                        <div class="d-flex justify-content-between py-2"><span>Days Open</span><strong><?php $created = new DateTime($request['requested_date']); $now = new DateTime(); echo $created->diff($now)->days . ' days'; ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assignment Modal -->
<div id="assignmentModal" class="modal-custom">
    <div class="modal-custom-content">
        <div class="modal-custom-header"><h5><i class="fas fa-laptop-code me-2"></i> Assignment Details</h5><button class="modal-custom-close" onclick="closeAssignmentModal()">&times;</button></div>
        <div class="modal-custom-body" id="assignmentModalBody"><div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading...</p></div></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Available items data
const availableDevices = <?php echo json_encode($availableItemsWithSerials); ?>;

function searchDevices(query) {
    if (!query || query.length < 2) return [];
    const lowerQuery = query.toLowerCase();
    return availableDevices.filter(device => 
        (device.serial_number && device.serial_number.toLowerCase().includes(lowerQuery)) ||
        (device.model_number && device.model_number.toLowerCase().includes(lowerQuery)) ||
        (device.name && device.name.toLowerCase().includes(lowerQuery)) ||
        (device.item_code && device.item_code.toLowerCase().includes(lowerQuery))
    );
}

function renderSearchResults(results) {
    const resultsDiv = document.getElementById('searchResults');
    if (results.length === 0) {
        resultsDiv.innerHTML = '<div class="search-result-item text-muted">No devices found</div>';
        resultsDiv.style.display = 'block';
        return;
    }
    
    resultsDiv.innerHTML = results.map(device => `
        <div class="search-result-item" onclick="selectDevice(${device.serial_id}, ${device.id}, '${escapeHtml(device.serial_number)}', '${escapeHtml(device.model_number)}', '${escapeHtml(device.version)}', '${escapeHtml(device.name)}', '${escapeHtml(device.item_code)}')">
            <strong>${escapeHtml(device.name)}</strong> (${escapeHtml(device.item_code)})
            <br><small class="text-muted">Serial: ${escapeHtml(device.serial_number)} | Model: ${escapeHtml(device.model_number || 'N/A')} | Available</small>
        </div>
    `).join('');
    resultsDiv.style.display = 'block';
}

function selectDevice(serialId, itemId, serialNumber, modelNumber, version, deviceName, itemCode) {
    document.getElementById('selected_serial_id').value = serialId;
    document.getElementById('selected_item_id').value = itemId;
    document.getElementById('selected_serial_number').value = serialNumber;
    document.getElementById('selected_model_number').value = modelNumber;
    document.getElementById('selected_version').value = version;
    
    document.getElementById('selectedDeviceName').innerHTML = `${escapeHtml(deviceName)} (${escapeHtml(itemCode)})`;
    document.getElementById('selectedSerial').innerHTML = escapeHtml(serialNumber);
    document.getElementById('selectedModel').innerHTML = escapeHtml(modelNumber || 'N/A');
    document.getElementById('selectedVersion').innerHTML = escapeHtml(version || 'N/A');
    
    document.getElementById('selectedDeviceDisplay').style.display = 'block';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('deviceSearch').value = `${deviceName} - SN: ${serialNumber}`;
    document.getElementById('allocateBtn').disabled = false;
}

function clearDeviceSelection() {
    document.getElementById('selected_serial_id').value = '';
    document.getElementById('selected_item_id').value = '';
    document.getElementById('selected_serial_number').value = '';
    document.getElementById('selected_model_number').value = '';
    document.getElementById('selected_version').value = '';
    document.getElementById('selectedDeviceDisplay').style.display = 'none';
    document.getElementById('deviceSearch').value = '';
    document.getElementById('allocateBtn').disabled = true;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function editAssignment(id) {
    if (confirm('Update assignment status?')) location.reload();
}

function showAssignmentModal(assignmentId) {
    const modal = document.getElementById('assignmentModal');
    const modalBody = document.getElementById('assignmentModalBody');
    modalBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p>Loading...</p></div>';
    modal.style.display = 'block';
    $.ajax({
        url: 'ajax/get_assignment_details.php',
        method: 'POST',
        data: { assignment_id: assignmentId },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                modalBody.innerHTML = `
                    <div class="modal-detail-item"><div class="modal-detail-label">Assignment No</div><div class="modal-detail-value"><strong>${escapeHtml(data.assignment_no)}</strong></div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Status</div><div class="modal-detail-value"><span class="badge bg-${data.status_class}">${escapeHtml(data.status)}</span></div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Employee</div><div class="modal-detail-value">${escapeHtml(data.employee_name)} (${escapeHtml(data.pf_no)})</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Item</div><div class="modal-detail-value"><strong>${escapeHtml(data.item_name)}</strong> (${escapeHtml(data.item_code)})</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Brand</div><div class="modal-detail-value">${escapeHtml(data.brand)}</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Serial Number</div><div class="modal-detail-value"><code>${escapeHtml(data.serial_number)}</code></div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Model Number</div><div class="modal-detail-value">${escapeHtml(data.model_number)}</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Version</div><div class="modal-detail-value">${escapeHtml(data.version)}</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Assigned Date</div><div class="modal-detail-value">${escapeHtml(data.assigned_date)}</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Expected Return</div><div class="modal-detail-value">${escapeHtml(data.expected_return_date)}</div></div>
                    <div class="modal-detail-item"><div class="modal-detail-label">Notes</div><div class="modal-detail-value">${escapeHtml(data.notes)}</div></div>
                `;
            } else {
                modalBody.innerHTML = `<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle fa-2x"></i><p>${escapeHtml(data.message)}</p></div>`;
            }
        },
        error: function() {
            modalBody.innerHTML = '<div class="text-center py-4 text-danger"><i class="fas fa-exclamation-triangle fa-2x"></i><p>Error loading details</p></div>';
        }
    });
}

function closeAssignmentModal() {
    document.getElementById('assignmentModal').style.display = 'none';
}

window.onclick = function(e) {
    if (e.target == document.getElementById('assignmentModal')) closeAssignmentModal();
}

$(document).ready(function() {
    let timeout;
    $('#deviceSearch').on('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => renderSearchResults(searchDevices($(this).val())), 300);
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#deviceSearch, #searchResults').length) {
            $('#searchResults').hide();
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        $('.alert-success, .alert-danger').fadeOut('slow');
    }, 5000);
});
</script>

<?php include '../../includes/footer.php'; ?>