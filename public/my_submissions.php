<?php
// Include session fix FIRST - Same as index.php
require_once dirname(__DIR__) . '/config/session_fix.php';
require_once dirname(__DIR__) . '/config/database.php';

// Check login status - Same as index.php
$isLoggedIn = false;
$userName = '';
$userRole = '';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
    $userRole = $_SESSION['role'] ?? 'staff';
}

// Clear any stored employee session if reset flag is set
if(isset($_GET['reset_employee']) && $_GET['reset_employee'] == '1') {
    unset($_SESSION['employee_id']);
    unset($_SESSION['employee_name']);
    unset($_SESSION['employee_pf']);
    unset($_SESSION['employee_designation']);
    unset($_SESSION['employee_department']);
    session_regenerate_id(true);
    header('Location: my_submissions.php');
    exit();
}

// Get employee ID from session or URL (but URL only for initial verification)
$employee_id = $_SESSION['employee_id'] ?? 0;

// Only use URL parameter if no session exists (initial verification)
if(!$employee_id && isset($_GET['employee_id'])) {
    $employee_id = intval($_GET['employee_id']);
}

$employee_info = null;
if($employee_id > 0) {
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department FROM employees WHERE id = ? AND is_active = 1");
    $stmt->execute([$employee_id]);
    $employee_info = $stmt->fetch();
    if($employee_info) {
        $_SESSION['employee_id'] = $employee_info['id'];
        $_SESSION['employee_name'] = $employee_info['full_name'];
        $_SESSION['employee_pf'] = $employee_info['pf_no'];
        $_SESSION['employee_designation'] = $employee_info['designation'];
        $_SESSION['employee_department'] = $employee_info['department'];
    } else {
        unset($_SESSION['employee_id']);
        $employee_id = 0;
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$sort_by = $_GET['sort_by'] ?? 'created_at_desc';

// Build query conditions
$conditions = [];
$params = [];

if($employee_id > 0) {
    $conditions[] = "r.employee_id = ?";
    $params[] = $employee_id;
}

if($status_filter != 'all') {
    $conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if($type_filter != 'all') {
    $conditions[] = "r.request_type = ?";
    $params[] = $type_filter;
}

$where_clause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "WHERE 1=0";

// Set order by
switch($sort_by) {
    case 'created_at_asc':
        $order_by = "r.created_at ASC";
        break;
    case 'status_asc':
        $order_by = "r.status ASC";
        break;
    case 'status_desc':
        $order_by = "r.status DESC";
        break;
    default:
        $order_by = "r.created_at DESC";
}

// Get submissions
$submissions = [];
if($employee_id > 0) {
    $query = "
        SELECT r.*, 
               e.full_name as employee_name,
               e.pf_no,
               e.designation,
               e.department,
               CASE 
                   WHEN r.request_type = 'device_assign' THEN 'Device Assignment'
                   WHEN r.request_type = 'software_access' THEN 'Software Access'
                   WHEN r.request_type = 'return_device' THEN 'Device Return'
                   WHEN r.request_type = 'upgrade' THEN 'Upgrade Request'
                   WHEN r.request_type = 'accessories' THEN 'Accessories Request'
                   WHEN r.request_type = 'technical_support' THEN 'Technical Support'
                   ELSE r.request_type
               END as request_type_display
        FROM requests r
        JOIN employees e ON r.employee_id = e.id
        $where_clause
        ORDER BY $order_by
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $submissions = $stmt->fetchAll();
}

// Get statistics
$stats = [];
if($employee_id > 0) {
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN r.status = 'under_observation' THEN 1 ELSE 0 END) as under_observation,
            SUM(CASE WHEN r.status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN r.request_type = 'device_assign' THEN 1 ELSE 0 END) as device_assign,
            SUM(CASE WHEN r.request_type = 'software_access' THEN 1 ELSE 0 END) as software_access,
            SUM(CASE WHEN r.request_type = 'return_device' THEN 1 ELSE 0 END) as return_device,
            SUM(CASE WHEN r.request_type = 'upgrade' THEN 1 ELSE 0 END) as upgrade,
            SUM(CASE WHEN r.request_type = 'accessories' THEN 1 ELSE 0 END) as accessories,
            SUM(CASE WHEN r.request_type = 'technical_support' THEN 1 ELSE 0 END) as report_issue
        FROM requests r
        $where_clause
    ";
    $stmt = $pdo->prepare($stats_query);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if employee is verified
$employee_found = ($employee_info !== null);

// Handle AJAX request for employee verification
if(isset($_GET['action']) && $_GET['action'] == 'verify_employee' && isset($_GET['pf_no'])) {
    header('Content-Type: application/json');
    $pf_no = trim($_GET['pf_no']);
    
    $stmt = $pdo->prepare("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE pf_no = ? AND is_active = 1");
    $stmt->execute([$pf_no]);
    $employee = $stmt->fetch();
    
    if($employee) {
        echo json_encode(['success' => true, 'data' => $employee]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found with PF Number: ' . $pf_no]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>My Submissions - IT Support</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', 'Roboto', Arial, sans-serif; background: #f5f7fa; }
        
        .navbar { background: #ffffff; box-shadow: 0 2px 15px rgba(0,0,0,0.08); padding: 0.6rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; }
        .navbar-brand { font-size: 1.3rem; font-weight: 700; color: #3b82f6; }
        .navbar-brand i { color: #3b82f6; margin-right: 8px; }
        
        .navbar-toggler { border: none; padding: 0; }
        .navbar-toggler:focus { box-shadow: none; outline: none; }
        .navbar-toggler-icon { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important; }
        
        .nav-link { font-weight: 500; color: #4b5563; transition: all 0.3s ease; margin: 0 0.6rem; font-size: 0.9rem; }
        .nav-link:hover { color: #3b82f6; transform: translateY(-2px); }
        
        .btn-dashboard, .btn-logout { 
            border: none; 
            padding: 6px 20px; 
            border-radius: 30px; 
            font-weight: 600; 
            font-size: 0.85rem; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn-dashboard { background: #10b981; color: white; }
        .btn-dashboard:hover { background: #059669; color: white; }
        .btn-logout { background: #ef4444; color: white; }
        .btn-logout:hover { background: #dc2626; color: white; }
        
        .main-content { padding-top: 75px; min-height: calc(100vh - 80px); }
        .form-card { 
            background: white; 
            border-radius: 20px; 
            padding: 30px; 
            max-width: 1400px; 
            margin: 0 auto 30px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
        }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { 
            font-size: 1.6rem; 
            font-weight: 700; 
            color: #1f2937; 
            margin-bottom: 8px; 
        }
        .form-header .underline { 
            width: 50px; 
            height: 3px; 
            background: #3b82f6; 
            margin: 12px auto 0; 
            border-radius: 3px; 
        }
        .form-header p { font-size: 0.85rem; color: #6b7280; margin-top: 10px; }
        
        .form-label { 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 6px; 
            font-size: 0.85rem; 
        }
        .form-control, .form-select { 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            padding: 10px 15px; 
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus { 
            border-color: #3b82f6; 
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1); 
            outline: none; 
        }
        
        .btn-submit, .btn-refresh { 
            display: inline-block;
            padding: 10px 30px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-submit:hover, .btn-refresh:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
            color: white;
        }
        .btn-submit:active, .btn-refresh:active { transform: translateY(0px); }
        
        .btn-reset { 
            background: #e2e8f0; 
            color: #475569; 
            padding: 10px 30px; 
            border-radius: 40px; 
            font-weight: 600; 
            border: none; 
            margin-left: 15px; 
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-reset:hover { background: #cbd5e1; color: #1f2937; }
        
        .btn-new-verification {
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 8px 20px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-new-verification:hover {
            background: #d97706;
            transform: translateY(-2px);
            color: white;
        }
        
        .btn-refresh-page {
            background: #10b981;
            color: white;
            border: none;
            border-radius: 40px;
            padding: 8px 20px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
        }
        .btn-refresh-page:hover {
            background: #059669;
            transform: translateY(-2px);
            color: white;
        }
        
        footer { background: #1f2937; color: white; padding: 18px 0; margin-top: 40px; }
        .footer-content { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .copyright { color: #9ca3af; font-size: 0.7rem; }
        .footer-login { text-align: right; }
        .btn-footer-login { 
            background: transparent; 
            border: 1px solid #4b5563; 
            color: #9ca3af; 
            padding: 5px 16px; 
            border-radius: 30px; 
            font-size: 0.7rem; 
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }
        .btn-footer-login:hover { background: #3b82f6; border-color: #3b82f6; color: white; }
        
        .employee-info-card { 
            background: #eff6ff; 
            border-radius: 12px; 
            padding: 15px; 
            margin-bottom: 20px; 
            border-left: 4px solid #3b82f6; 
            position: relative;
        }
        
        .employee-info-card .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid #dbeafe;
        }
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            height: 100%;
        }
        .stats-card:hover { transform: translateY(-3px); }
        .stats-number { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .stats-label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .status-badge {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-under_observation { background: #dbeafe; color: #2563eb; }
        .status-processing { background: #e0e7ff; color: #4338ca; }
        .status-completed { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 12px; padding: 20px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: none; border-radius: 12px; padding: 15px; }
        .alert-info { background: #eff6ff; color: #1e40af; border: none; border-radius: 12px; padding: 15px; }
        
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table th { background: #f8fafc; font-weight: 600; font-size: 13px; white-space: nowrap; }
        .table td { font-size: 13px; vertical-align: middle; }
        
        .request-details {
            font-size: 11px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        @media (max-width: 576px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.8rem; padding: 8px 0; text-align: center; }
            .btn-dashboard, .btn-logout { padding: 6px 16px; font-size: 0.75rem; margin: 5px 0; display: inline-block; width: auto; }
            .navbar-nav { text-align: center; padding: 10px 0; }
            .navbar-nav .nav-item { margin: 5px 0; }
            
            .form-card { padding: 20px; margin: 0 15px 30px; }
            .form-header h2 { font-size: 1.3rem; }
            .form-header p { font-size: 0.7rem; }
            .form-label { font-size: 0.75rem; }
            .form-control, .form-select { font-size: 0.75rem; padding: 8px 12px; }
            
            .btn-submit, .btn-reset, .btn-new-verification, .btn-refresh-page { 
                width: 100%; 
                margin: 5px 0; 
                padding: 8px 20px; 
                font-size: 0.75rem; 
                display: block;
                margin-left: 0;
            }
            .btn-reset, .btn-refresh-page { margin-left: 0; }
            
            .main-content { padding-top: 68px; }
            
            .footer-content { flex-direction: row; justify-content: space-between; }
            .copyright p { font-size: 0.6rem; margin: 0; }
            .btn-footer-login { padding: 4px 12px; font-size: 0.6rem; }
            .footer-login { text-align: right; }
            
            .employee-info-card { padding: 10px; font-size: 0.7rem; }
            .employee-info-card .button-group { flex-direction: column; gap: 8px; margin-top: 10px; }
            .stats-card { padding: 10px; }
            .stats-number { font-size: 20px; }
            .stats-label { font-size: 9px; }
            
            .table th, .table td { font-size: 11px; }
            .table td .btn-sm { padding: 3px 6px; font-size: 10px; }
        }
        
        @media (min-width: 577px) and (max-width: 992px) {
            .form-card { margin: 0 20px 30px; }
        }
        
        html { scroll-behavior: smooth; }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .fa-spin-custom {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <!-- Navbar - Exactly same as index.php -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-laptop-code"></i> IT Inventory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php#requests">Request Form</a></li>
                    <li class="nav-item"><a class="nav-link" href="track_request.php"><i class="fas fa-search me-1"></i> Track Request</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_submissions.php"><i class="fas fa-clipboard-list me-1"></i> My Reports</a></li>
                    <?php if($isLoggedIn): ?>
                        <li class="nav-item ms-2"><a class="btn btn-dashboard" href="../modules/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
                        <li class="nav-item ms-2"><a class="btn btn-logout" href="../modules/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-clipboard-list"></i> My Submissions</h2>
                    <div class="underline"></div>
                    <p>View and track all your IT support requests</p>
                </div>

                <?php if(!$employee_found): ?>
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-user"></i> Select Your Name *</label>
                        <div class="row">
                            <div class="col-md-8 mb-2 mb-md-0">
                                <input type="text" id="pf_no_search" class="form-control" placeholder="Enter your PF Number to verify" autocomplete="off">
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="verifyPfBtn" class="btn btn-primary w-100" style="border-radius: 40px; padding: 10px;">Verify PF</button>
                            </div>
                        </div>
                        <div id="employee_info_display" style="display:none;" class="mt-3"></div>
                        <input type="hidden" id="selected_employee_id" value="">
                        <input type="hidden" id="employee_verified" value="0">
                        <small class="text-muted mt-2 d-block">Enter your PF Number (e.g., 100003) to verify your identity and view your submissions</small>
                    </div>
                    
                <?php elseif(empty($submissions)): ?>
                    <?php if($employee_info): ?>
                    <div class="employee-info-card mb-4">
                        <div class="row">
                            <div class="col-md-3 mb-2"><strong>PF No:</strong> <?php echo htmlspecialchars($employee_info['pf_no']); ?></div>
                            <div class="col-md-5 mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($employee_info['full_name']); ?></div>
                            <div class="col-md-4 mb-2"><strong>Designation:</strong> <?php echo htmlspecialchars($employee_info['designation'] ?? 'N/A'); ?></div>
                            <div class="col-md-4 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($employee_info['department'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="button-group">
                            <button type="button" class="btn-new-verification" onclick="resetEmployee()">
                                <i class="fas fa-exchange-alt me-1"></i> New Verification
                            </button>
                            <button type="button" class="btn-refresh-page" onclick="refreshPage()">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="alert-info text-center py-4">
                        <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                        <h5>No submissions found</h5>
                        <p class="mb-0">You haven't submitted any requests yet.</p>
                        <a href="index.php#requests" class="btn-submit mt-3 d-inline-block" style="text-decoration: none;">Submit a Request</a>
                    </div>
                    
                <?php else: ?>
                    
                    <?php if($employee_info): ?>
                    <div class="employee-info-card mb-4">
                        <div class="row">
                            <div class="col-md-3 mb-2"><strong>PF No:</strong> <?php echo htmlspecialchars($employee_info['pf_no']); ?></div>
                            <div class="col-md-5 mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($employee_info['full_name']); ?></div>
                            <div class="col-md-4 mb-2"><strong>Designation:</strong> <?php echo htmlspecialchars($employee_info['designation'] ?? 'N/A'); ?></div>
                            <div class="col-md-4 mb-2"><strong>Department:</strong> <?php echo htmlspecialchars($employee_info['department'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="button-group">
                            <button type="button" class="btn-new-verification" onclick="resetEmployee()">
                                <i class="fas fa-exchange-alt me-1"></i> New Verification
                            </button>
                            <button type="button" class="btn-refresh-page" onclick="refreshPage()">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="stats-card border-start border-4 border-primary">
                                <div class="stats-number text-primary"><?php echo number_format($stats['total'] ?? 0); ?></div>
                                <div class="stats-label">Total Requests</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stats-card border-start border-4 border-warning">
                                <div class="stats-number text-warning"><?php echo number_format($stats['pending'] ?? 0); ?></div>
                                <div class="stats-label">Pending</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stats-card border-start border-4 border-info">
                                <div class="stats-number text-info"><?php echo number_format(($stats['processing'] ?? 0) + ($stats['under_observation'] ?? 0)); ?></div>
                                <div class="stats-label">In Progress</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="stats-card border-start border-4 border-success">
                                <div class="stats-number text-success"><?php echo number_format($stats['completed'] ?? 0); ?></div>
                                <div class="stats-label">Completed</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-card">
                        <h5 class="mb-3"><i class="fas fa-filter me-2"></i> Filter Requests</h5>
                        <form method="GET" class="row g-3" id="filterForm">
                            <input type="hidden" name="employee_id" value="<?php echo $employee_id; ?>">
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="under_observation" <?php echo $status_filter == 'under_observation' ? 'selected' : ''; ?>>Under Observation</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Request Type</label>
                                <select name="type" class="form-select">
                                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <option value="device_assign" <?php echo $type_filter == 'device_assign' ? 'selected' : ''; ?>>Device Assignment</option>
                                    <option value="software_access" <?php echo $type_filter == 'software_access' ? 'selected' : ''; ?>>Software Access</option>
                                    <option value="return_device" <?php echo $type_filter == 'return_device' ? 'selected' : ''; ?>>Device Return</option>
                                    <option value="upgrade" <?php echo $type_filter == 'upgrade' ? 'selected' : ''; ?>>Upgrade Request</option>
                                    <option value="accessories" <?php echo $type_filter == 'accessories' ? 'selected' : ''; ?>>Accessories Request</option>
                                    <option value="technical_support" <?php echo $type_filter == 'technical_support' ? 'selected' : ''; ?>>Technical Support</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sort By</label>
                                <select name="sort_by" class="form-select">
                                    <option value="created_at_desc" <?php echo $sort_by == 'created_at_desc' ? 'selected' : ''; ?>>Latest First</option>
                                    <option value="created_at_asc" <?php echo $sort_by == 'created_at_asc' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="status_asc" <?php echo $sort_by == 'status_asc' ? 'selected' : ''; ?>>Status (A-Z)</option>
                                    <option value="status_desc" <?php echo $sort_by == 'status_desc' ? 'selected' : ''; ?>>Status (Z-A)</option>
                                </select>
                            </div>
                            <div class="col-md-12 text-end">
                                <button type="submit" class="btn-submit"><i class="fas fa-search me-2"></i> Apply Filters</button>
                                <a href="my_submissions.php?employee_id=<?php echo $employee_id; ?>" class="btn-reset"><i class="fas fa-undo me-2"></i> Reset Filters</a>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i> Your Requests (<?php echo count($submissions); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Request No</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th width="100">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($submissions as $sub): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($sub['request_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($sub['request_type_display']); ?></td>
                                            <td>
                                                <?php 
                                                    $desc = htmlspecialchars($sub['description'] ?? '');
                                                    echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                                ?>
                                                <?php if($sub['urgency_level'] && $sub['urgency_level'] != 'medium'): ?>
                                                    <div class="request-details">
                                                        <i class="fas fa-flag"></i> Priority: <?php echo ucfirst($sub['urgency_level']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d-m-Y', strtotime($sub['requested_date'])); ?></td>
                                            <td>
                                                <?php
                                                    $status_class = 'status-' . str_replace('_', '-', $sub['status']);
                                                    $status_text = ucfirst(str_replace('_', ' ', $sub['status']));
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <a href="track_request.php?request_no=<?php echo urlencode($sub['request_no']); ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Track
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> IT Inventory Management System. All rights reserved.</p>
                </div>
                <div class="footer-login">
                    <?php if(!$isLoggedIn): ?>
                        <a href="../modules/login.php" class="btn-footer-login">
                            <i class="fas fa-lock me-1"></i> Staff Login
                        </a>
                    <?php else: ?>
                        <span style="font-size: 0.7rem; color: #9ca3af;">
                            <i class="fas fa-user-check me-1"></i> Logged in as <?php echo htmlspecialchars($userName); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function resetEmployee() {
            if(confirm('Start new verification? This will clear the current employee data and show the verification form.')) {
                window.location.href = 'my_submissions.php?reset_employee=1';
            }
        }
        
        function refreshPage() {
            var refreshBtn = document.querySelector('.btn-refresh-page');
            if(refreshBtn) {
                var originalHtml = refreshBtn.innerHTML;
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
                var url = window.location.pathname + '?employee_id=<?php echo $employee_id; ?>&_t=' + new Date().getTime();
                window.location.href = url;
            } else {
                window.location.reload(true);
            }
        }
        
        function escapeHtml(str) {
            if(!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if(m === '&') return '&amp;';
                if(m === '<') return '&lt;';
                if(m === '>') return '&gt;';
                return m;
            });
        }
        
        $(document).ready(function() {
            <?php if(!$employee_found): ?>
            $('#verifyPfBtn').click(function() {
                var pfNo = $('#pf_no_search').val().trim();
                if(!pfNo) { alert('Please enter your PF Number'); return; }
                
                $('#verifyPfBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');
                
                $.ajax({
                    url: window.location.href + '?action=verify_employee&pf_no=' + encodeURIComponent(pfNo),
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            var emp = response.data;
                            $('#employee_info_display').html('<div class="employee-info-card"><div class="row"><div class="col-md-6 mb-2"><strong>PF No:</strong> ' + escapeHtml(emp.pf_no) + '</div><div class="col-md-6 mb-2"><strong>Name:</strong> ' + escapeHtml(emp.full_name) + '</div><div class="col-md-6 mb-2"><strong>Designation:</strong> ' + escapeHtml(emp.designation || 'N/A') + '</div><div class="col-md-6 mb-2"><strong>Department:</strong> ' + escapeHtml(emp.department || 'N/A') + '</div></div></div>').show();
                            $('#selected_employee_id').val(emp.id);
                            $('#employee_verified').val('1');
                            $('#pf_no_search').prop('readonly', true);
                            $('#verifyPfBtn').html('<i class="fas fa-check"></i> Verified').removeClass('btn-primary').addClass('btn-success');
                            
                            window.location.href = 'my_submissions.php?employee_id=' + emp.id + '&_t=' + new Date().getTime();
                        } else {
                            alert(response.message);
                            $('#employee_info_display').hide();
                            $('#selected_employee_id').val('');
                            $('#employee_verified').val('0');
                            $('#verifyPfBtn').prop('disabled', false).html('Verify PF').removeClass('btn-success').addClass('btn-primary');
                        }
                    },
                    error: function() {
                        alert('Error verifying employee. Please try again.');
                        $('#verifyPfBtn').prop('disabled', false).html('Verify PF').removeClass('btn-success').addClass('btn-primary');
                    }
                });
            });
            
            $('#pf_no_search').keypress(function(e) {
                if(e.which == 13) { $('#verifyPfBtn').click(); return false; }
            });
            <?php endif; ?>
            
            document.querySelectorAll('.navbar-nav .nav-link').forEach(function(link) {
                link.addEventListener('click', function() {
                    const navbarCollapse = document.querySelector('.navbar-collapse');
                    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                });
            });
        });
    </script>
</body>
</html>