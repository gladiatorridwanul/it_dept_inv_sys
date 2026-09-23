<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle success message from assignment
$success_id = $_GET['id'] ?? 0;
$show_success = isset($_GET['success']) && $success_id > 0;

if($show_success) {
    $stmt = $pdo->prepare("SELECT a.*, e.full_name as employee_name, i.name as item_name 
                           FROM assignments a 
                           JOIN employees e ON a.employee_id = e.id 
                           JOIN items i ON a.item_id = i.id 
                           WHERE a.id = ?");
    $stmt->execute([$success_id]);
    $new_assignment = $stmt->fetch();
    
    if($new_assignment) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <strong>Assignment Created Successfully!</strong><br>
                        Assignment No: ' . $new_assignment['assignment_no'] . '<br>
                        Employee: ' . htmlspecialchars($new_assignment['employee_name']) . ' | Device: ' . htmlspecialchars($new_assignment['item_name']) . '
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="print.php?id=' . $success_id . '" target="_blank" class="btn btn-sm btn-info me-2">
                            <i class="fas fa-print"></i> Print Acknowledgement
                        </a>
                        <a href="barcode_label.php?id=' . $success_id . '" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-qrcode"></i> Print Barcode
                        </a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// Get filter parameters
$search_barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
$search_pf = isset($_GET['pf_no']) ? trim($_GET['pf_no']) : '';
$search_employee = isset($_GET['employee']) ? trim($_GET['employee']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$return_status_filter = isset($_GET['return_status']) ? $_GET['return_status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$source_filter = isset($_GET['source']) ? $_GET['source'] : '';

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause for assignments
$where_conditions = [];
$params = [];

if(!empty($search_barcode)) {
    $where_conditions[] = "a.assignment_no LIKE ?";
    $params[] = "%$search_barcode%";
}

if(!empty($search_pf)) {
    $where_conditions[] = "e.pf_no LIKE ?";
    $params[] = "%$search_pf%";
}

if(!empty($search_employee)) {
    $where_conditions[] = "(e.full_name LIKE ? OR e.pf_no LIKE ?)";
    $params[] = "%$search_employee%";
    $params[] = "%$search_employee%";
}

if(!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

if(!empty($return_status_filter)) {
    if($return_status_filter == 'return_requested') {
        $where_conditions[] = "a.return_status = 'return_requested'";
    } elseif($return_status_filter == 'returned') {
        $where_conditions[] = "a.return_status = 'returned'";
    } elseif($return_status_filter == 'active') {
        $where_conditions[] = "(a.return_status IS NULL OR a.return_status = 'active')";
        $where_conditions[] = "a.status = 'assigned'";
    }
}

if(!empty($source_filter)) {
    if($source_filter == 'admin') {
        $where_conditions[] = "(a.source IS NULL OR a.source = 'admin')";
    } elseif($source_filter == 'request') {
        $where_conditions[] = "a.source = 'request'";
    }
}

if(!empty($date_from)) {
    $where_conditions[] = "DATE(a.assigned_date) >= ?";
    $params[] = $date_from;
}

if(!empty($date_to)) {
    $where_conditions[] = "DATE(a.assigned_date) <= ?";
    $params[] = $date_to;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM assignments a 
              JOIN employees e ON a.employee_id = e.id 
              JOIN items i ON a.item_id = i.id 
              $where_sql";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get assignments with filters and pagination
$sql = "SELECT a.*, e.full_name, e.pf_no, e.designation, i.name as item_name, i.item_code,
        CASE 
            WHEN a.return_status = 'return_requested' THEN 'return_requested'
            WHEN a.return_status = 'returned' THEN 'returned'
            WHEN a.status = 'returned' THEN 'returned'
            ELSE 'active'
        END as display_return_status,
        CASE 
            WHEN a.source = 'request' THEN 'Public Request'
            WHEN a.source = 'admin' THEN 'IT Admin'
            ELSE 'IT Admin'
        END as source_display
        FROM assignments a 
        JOIN employees e ON a.employee_id = e.id 
        JOIN items i ON a.item_id = i.id 
        $where_sql
        ORDER BY a.id DESC
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Handle single barcode search result for quick view
$assignment_found = null;
if(!empty($search_barcode) && count($assignments) == 1 && $total_records == 1) {
    $assignment_found = $assignments[0];
}

// Statistics
$activeCount = $pdo->query("SELECT COUNT(*) as count FROM assignments WHERE status = 'assigned' AND (return_status IS NULL OR return_status = 'active')")->fetch()['count'];
$returnedCount = $pdo->query("SELECT COUNT(*) as count FROM assignments WHERE status = 'returned' OR return_status = 'returned'")->fetch()['count'];
$damagedCount = $pdo->query("SELECT COUNT(*) as count FROM assignments WHERE status = 'damaged'")->fetch()['count'];
$returnRequestedCount = $pdo->query("SELECT COUNT(*) as count FROM assignments WHERE return_status = 'return_requested'")->fetch()['count'];
$totalAssignments = $pdo->query("SELECT COUNT(*) as count FROM assignments")->fetch()['count'];

// Get request-based assignments count
$requestAssignmentsCount = $pdo->query("SELECT COUNT(*) as count FROM assignments WHERE source = 'request'")->fetch()['count'];

// Get employees for dropdown (for PF search suggestions)
$employees = $pdo->query("SELECT id, full_name, pf_no FROM employees WHERE is_active = 1 ORDER BY full_name LIMIT 50")->fetchAll();

// Build URL parameters for pagination links
$url_params = [];
if($search_barcode) $url_params['barcode'] = $search_barcode;
if($search_pf) $url_params['pf_no'] = $search_pf;
if($search_employee) $url_params['employee'] = $search_employee;
if($status_filter) $url_params['status'] = $status_filter;
if($return_status_filter) $url_params['return_status'] = $return_status_filter;
if($date_from) $url_params['date_from'] = $date_from;
if($date_to) $url_params['date_to'] = $date_to;
if($source_filter) $url_params['source'] = $source_filter;
$query_string = http_build_query($url_params);

// Function to generate barcode if not exists (for public request assignments)
function ensureBarcodeExists($assignment, $pdo) {
    if(empty($assignment['barcode_path']) || !file_exists('../../' . $assignment['barcode_path'])) {
        $barcode_dir = '../../uploads/barcodes/';
        if(!file_exists($barcode_dir)) {
            mkdir($barcode_dir, 0777, true);
        }
        
        $barcode_filename = 'barcode_' . $assignment['assignment_no'] . '.png';
        $barcode_fullpath = $barcode_dir . $barcode_filename;
        
        // Create simple barcode
        $barcode_text = $assignment['assignment_no'];
        $width = 380;
        $height = 75;
        
        $image = imagecreate($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        
        $bars = array();
        for($i = 0; $i < strlen($barcode_text); $i++) {
            $char_code = ord($barcode_text[$i]);
            $bars[] = array('width' => ($char_code % 4) + 1, 'space' => 1);
        }
        
        $x = 10;
        $bar_height = $height - 15;
        
        foreach($bars as $bar) {
            $bar_width = $bar['width'] * 2;
            if($bar_width < 2) $bar_width = 2;
            if($bar_width > 8) $bar_width = 8;
            
            imagefilledrectangle($image, $x, 5, $x + $bar_width - 1, $bar_height, $black);
            $x += $bar_width + $bar['space'];
        }
        
        imagefilledrectangle($image, 5, 5, 8, $bar_height, $black);
        imagefilledrectangle($image, $width - 12, 5, $width - 8, $bar_height, $black);
        
        imagepng($image, $barcode_fullpath);
        imagedestroy($image);
        
        // Update database
        $update_stmt = $pdo->prepare("UPDATE assignments SET barcode_path = ? WHERE id = ?");
        $update_stmt->execute(['uploads/barcodes/' . $barcode_filename, $assignment['id']]);
        
        return 'uploads/barcodes/' . $barcode_filename;
    }
    return $assignment['barcode_path'];
}
?>

<style>
    .stats-card {
        transition: transform 0.3s;
        border-radius: 12px;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: 700;
    }
    .filter-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .barcode-search-box {
        background: white;
        border-radius: 50px;
        padding: 5px;
        border: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        flex: 1;
    }
    .barcode-search-box input {
        border: none;
        background: transparent;
        padding: 10px 20px;
        width: 100%;
        font-family: monospace;
        font-size: 16px;
        letter-spacing: 2px;
    }
    .barcode-search-box input:focus {
        outline: none;
    }
    .barcode-search-box input::placeholder {
        letter-spacing: normal;
        font-family: inherit;
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        white-space: nowrap;
    }
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .action-buttons .btn {
        padding: 4px 8px;
        font-size: 12px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-active { background: #d1fae5; color: #059669; }
    .status-return_requested { background: #fef3c7; color: #d97706; }
    .status-returned { background: #dbeafe; color: #2563eb; }
    .status-damaged { background: #fee2e2; color: #dc2626; }
    .filter-label {
        font-size: 0.7rem;
        font-weight: 600;
        margin-bottom: 4px;
        color: #495057;
    }
    .clear-filters {
        display: flex;
        align-items: flex-end;
    }
    .source-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: 600;
    }
    .source-admin { background: #e0e7ff; color: #4338ca; }
    .source-request { background: #fce7f3; color: #be185d; }
    /* Pagination Styles */
    .pagination-container {
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 12px;
    }
    .pagination {
        display: inline-flex;
        gap: 5px;
        flex-wrap: wrap;
        margin: 0;
        padding: 0;
    }
    .pagination .page-item {
        list-style: none;
        display: inline-block;
    }
    .pagination .page-link {
        display: block;
        padding: 8px 14px;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        color: #0d6efd;
        text-decoration: none;
        transition: all 0.3s;
    }
    .pagination .page-link:hover {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    .pagination .active .page-link {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    .pagination .disabled .page-link {
        color: #6c757d;
        pointer-events: none;
        background-color: #e9ecef;
    }
    .pagination-info {
        font-size: 14px;
        color: #6c757d;
    }
    .generate-barcode-btn {
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.6; }
        100% { opacity: 1; }
    }
    @media (max-width: 768px) {
        .stats-number {
            font-size: 20px;
        }
        .action-buttons {
            flex-direction: column;
        }
        .barcode-search-box {
            width: 100%;
        }
        .clear-filters {
            margin-top: 10px;
        }
        .pagination-container {
            flex-direction: column;
            text-align: center;
        }
        .pagination {
            justify-content: center;
        }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="fas fa-list-alt text-primary"></i> Device Assignments</h2>
                    <p class="text-muted">Track all device assignments, manage returns, and search by barcode/PF</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="assign.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Assignment
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-2 col-6">
            <a href="?<?php echo $query_string ?><?php echo $query_string ? '&' : ''; ?>status=" class="text-decoration-none">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">Total</h6>
                                <h2 class="stats-number mb-0"><?php echo $totalAssignments; ?></h2>
                            </div>
                            <div><i class="fas fa-list fa-2x opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="?<?php echo $query_string ?><?php echo $query_string ? '&' : ''; ?>status=assigned&return_status=active" class="text-decoration-none">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">Active</h6>
                                <h2 class="stats-number mb-0"><?php echo $activeCount; ?></h2>
                            </div>
                            <div><i class="fas fa-check-circle fa-2x opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="?<?php echo $query_string ?><?php echo $query_string ? '&' : ''; ?>return_status=return_requested" class="text-decoration-none">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">Return Req.</h6>
                                <h2 class="stats-number mb-0"><?php echo $returnRequestedCount; ?></h2>
                            </div>
                            <div><i class="fas fa-clock fa-2x opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="?<?php echo $query_string ?><?php echo $query_string ? '&' : ''; ?>return_status=returned" class="text-decoration-none">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">Returned</h6>
                                <h2 class="stats-number mb-0"><?php echo $returnedCount; ?></h2>
                            </div>
                            <div><i class="fas fa-undo-alt fa-2x opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="?<?php echo $query_string ?><?php echo $query_string ? '&' : ''; ?>source=request" class="text-decoration-none">
                <div class="card stats-card bg-purple text-white" style="background: #8b5cf6;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">From Requests</h6>
                                <h2 class="stats-number mb-0"><?php echo $requestAssignmentsCount; ?></h2>
                            </div>
                            <div><i class="fas fa-user-plus fa-2x opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-6">
            <a href="?<?php echo $query_string ?><?php echo $query_string ? '&' : ''; ?>source=admin" class="text-decoration-none">
                <div class="card stats-card bg-secondary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="mb-1">IT Admin</h6>
                                <h2 class="stats-number mb-0"><?php echo $totalAssignments - $requestAssignmentsCount; ?></h2>
                            </div>
                            <div><i class="fas fa-user-shield fa-2x opacity-50"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <!-- Advanced Search & Filter Section -->
    <div class="filter-card">
        <form method="GET" action="list.php" class="row g-3">
            <!-- Row 1: Barcode and PF Quick Search -->
            <div class="col-md-12">
                <div class="d-flex align-items-center justify-content-center flex-wrap gap-2">
                    <div class="barcode-search-box">
                        <i class="fas fa-qrcode ms-3 text-primary"></i>
                        <input type="text" name="barcode" placeholder="Scan or enter barcode (Assignment No)..." 
                               value="<?php echo htmlspecialchars($search_barcode); ?>">
                        <button type="submit" class="btn btn-primary rounded-pill me-1">
                            <i class="fas fa-search"></i> Find
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Row 2: Advanced Filters -->
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-id-card"></i> PF Number</label>
                <input type="text" name="pf_no" class="form-control" placeholder="Enter PF Number..." 
                       value="<?php echo htmlspecialchars($search_pf); ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-user"></i> Employee Name</label>
                <input type="text" name="employee" class="form-control" placeholder="Employee Name..." 
                       value="<?php echo htmlspecialchars($search_employee); ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-tag"></i> Assignment Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="assigned" <?php echo $status_filter == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                    <option value="returned" <?php echo $status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                    <option value="damaged" <?php echo $status_filter == 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-exchange-alt"></i> Return Status</label>
                <select name="return_status" class="form-select">
                    <option value="">All Return Status</option>
                    <option value="active" <?php echo $return_status_filter == 'active' ? 'selected' : ''; ?>>Active (No Return Request)</option>
                    <option value="return_requested" <?php echo $return_status_filter == 'return_requested' ? 'selected' : ''; ?>>Return Requested</option>
                    <option value="returned" <?php echo $return_status_filter == 'returned' ? 'selected' : ''; ?>>Returned</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-users"></i> Source</label>
                <select name="source" class="form-select">
                    <option value="">All Sources</option>
                    <option value="admin" <?php echo $source_filter == 'admin' ? 'selected' : ''; ?>>IT Admin</option>
                    <option value="request" <?php echo $source_filter == 'request' ? 'selected' : ''; ?>>Public Request</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-calendar"></i> Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label"><i class="fas fa-calendar"></i> Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
                <a href="list.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Clear All</a>
                <?php if($search_barcode || $search_pf || $search_employee || $status_filter || $return_status_filter || $date_from || $date_to || $source_filter): ?>
                    <span class="text-muted ms-3"><i class="fas fa-info-circle"></i> Showing filtered results (<?php echo $total_records; ?> assignments found)</span>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Barcode Search Result Alert -->
        <?php if($assignment_found): ?>
            <div class="alert alert-success mt-3 mb-0">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <strong><i class="fas fa-check-circle"></i> Assignment Found!</strong><br>
                        <?php echo htmlspecialchars($assignment_found['assignment_no']); ?> - 
                        <?php echo htmlspecialchars($assignment_found['full_name']); ?> - 
                        <?php echo htmlspecialchars($assignment_found['item_name']); ?>
                        <?php if($assignment_found['source'] == 'request'): ?>
                            <span class="source-badge source-request ms-2">From Public Request</span>
                        <?php endif; ?>
                    </div>
                    <div class="action-buttons mt-2 mt-md-0">
                        <a href="view.php?id=<?php echo $assignment_found['id']; ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="edit.php?id=<?php echo $assignment_found['id']; ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="print.php?id=<?php echo $assignment_found['id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                            <i class="fas fa-print"></i> Print
                        </a>
                        <a href="barcode_label.php?id=<?php echo $assignment_found['id']; ?>" target="_blank" class="btn btn-sm btn-secondary">
                            <i class="fas fa-qrcode"></i> Barcode
                        </a>
                    </div>
                </div>
            </div>
        <?php elseif($search_barcode && !$assignment_found): ?>
            <div class="alert alert-danger mt-3 mb-0">
                <i class="fas fa-exclamation-triangle"></i> No assignment found with code: <?php echo htmlspecialchars($search_barcode); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Assignments Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Assignment History 
                <span class="badge bg-light text-dark ms-2"><?php echo $total_records; ?> records</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Assignment No</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Device</th>
                            <th>Item Code</th>
                            <th>Qty</th>
                            <th>Assigned Date</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Return Status</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($assignments) > 0): ?>
                            <?php foreach($assignments as $assign): 
                                $statusClass = $assign['status'] == 'assigned' ? 'success' : ($assign['status'] == 'returned' ? 'info' : 'warning');
                                $returnStatus = $assign['display_return_status'] ?? 'active';
                                $sourceDisplay = $assign['source_display'] ?? 'IT Admin';
                                $isRequestSource = ($assign['source'] ?? '') == 'request';
                                
                                // Check if barcode exists
                                $hasBarcode = !empty($assign['barcode_path']) && file_exists('../../' . $assign['barcode_path']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($assign['assignment_no']); ?></strong>
                                    <?php if($hasBarcode): ?>
                                        <br><small class="text-muted"><i class="fas fa-check-circle text-success"></i> Barcode ready</small>
                                    <?php elseif($isRequestSource): ?>
                                        <br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Barcode pending</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($assign['full_name']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($assign['designation']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($assign['pf_no']); ?></td>
                                <td><?php echo htmlspecialchars($assign['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($assign['item_code']); ?></td>
                                <td><?php echo $assign['quantity']; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($assign['assigned_date'])); ?></td>
                                <td>
                                    <?php if($isRequestSource): ?>
                                        <span class="source-badge source-request">Public Request</span>
                                    <?php else: ?>
                                        <span class="source-badge source-admin">IT Admin</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <i class="fas fa-<?php echo $assign['status'] == 'assigned' ? 'check' : ($assign['status'] == 'returned' ? 'undo' : 'exclamation'); ?>"></i>
                                        <?php echo ucfirst($assign['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($returnStatus == 'return_requested'): ?>
                                        <span class="status-badge status-return_requested">
                                            <i class="fas fa-clock me-1"></i> Return Requested
                                        </span>
                                    <?php elseif($returnStatus == 'returned'): ?>
                                        <span class="status-badge status-returned">
                                            <i class="fas fa-check-circle me-1"></i> Returned
                                            <?php if($assign['returned_date']): ?>
                                                <br><small><?php echo date('d-m-Y', strtotime($assign['returned_date'])); ?></small>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-circle me-1"></i> Active
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo $assign['id']; ?>" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?id=<?php echo $assign['id']; ?>" class="btn btn-sm btn-warning" title="Edit Assignment">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="print.php?id=<?php echo $assign['id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Print Acknowledgement">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <!-- Barcode Button - Always visible, generates if not exists -->
                                        <a href="barcode_label.php?id=<?php echo $assign['id']; ?>" target="_blank" class="btn btn-sm <?php echo $hasBarcode ? 'btn-secondary' : 'btn-warning generate-barcode-btn'; ?>" title="<?php echo $hasBarcode ? 'Print Barcode' : 'Generate & Print Barcode'; ?>">
                                            <i class="fas fa-qrcode"></i>
                                            <?php if(!$hasBarcode): ?>
                                                <span class="d-none d-md-inline">Generate</span>
                                            <?php endif; ?>
                                        </a>
                                        <?php if($assign['status'] == 'assigned' && $returnStatus != 'return_requested' && $returnStatus != 'returned'): ?>
                                            <a href="../../public/return_device.php?assignment_id=<?php echo $assign['id']; ?>" class="btn btn-sm btn-warning" title="Return Device">
                                                <i class="fas fa-undo-alt"></i>
                                            </a>
                                        <?php elseif($returnStatus == 'return_requested'): ?>
                                            <span class="badge bg-warning text-dark p-2">Pending Approval</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No assignments found matching your criteria.
                                    <?php if($search_barcode || $search_pf || $search_employee || $status_filter || $return_status_filter || $date_from || $date_to || $source_filter): ?>
                                        <br><a href="list.php" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                    <?php endif; ?>
                                </td>
                             </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination Section -->
    <?php if($total_pages > 1): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> assignments
        </div>
        <ul class="pagination">
            <!-- Previous Page Link -->
            <?php if($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo; Previous</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">&laquo; Previous</span>
                </li>
            <?php endif; ?>
            
            <!-- Page Numbers -->
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if($start_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=1">1</a>
                </li>
                <?php if($start_page > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                <?php if($i == $page): ?>
                    <li class="page-item active">
                        <span class="page-link"><?php echo $i; ?></span>
                    </li>
                <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($end_page < $total_pages): ?>
                <?php if($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                </li>
            <?php endif; ?>
            
            <!-- Next Page Link -->
            <?php if($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">Next &raquo;</span>
                    </a>
                </li>
            <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">Next &raquo;</span>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>