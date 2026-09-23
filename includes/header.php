<?php
// includes/header.php

// Include auth (which includes session_fix and database)
require_once dirname(__DIR__) . '/includes/auth.php';

// Debug: Ensure session is active
if (!isset($_SESSION['user_id'])) {
    header("Location: https://it-inventory.bhs-headache.org/modules/login.php");
    exit();
}

// Get current page URL for active menu highlighting
$current_url = $_SERVER['REQUEST_URI'];

// Helper function to check if menu is active - supports multiple paths
function isActive($paths, $current_url) {
    foreach((array)$paths as $path) {
        if(strpos($current_url, $path) !== false) {
            return 'active';
        }
    }
    return '';
}

// Get user info (already from session)
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['role'] ?? 'it_staff';

// Get pending return requests count for badge
$pending_returns_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM return_device_requests rdr
        JOIN requests r ON rdr.request_id = r.id
        WHERE r.status = 'pending'
    ");
    $stmt->execute();
    $pending_returns_count = $stmt->fetch()['count'];
} catch(Exception $e) {
    // Table might not exist yet, ignore
    $pending_returns_count = 0;
}

// Get pending requests count for badge (all pending requests)
$pending_requests_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM requests 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $pending_requests_count = $stmt->fetch()['count'];
} catch(Exception $e) {
    $pending_requests_count = 0;
}

// Get pending damage reports count for badge
$pending_damages_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM damages 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $pending_damages_count = $stmt->fetch()['count'];
} catch(Exception $e) {
    $pending_damages_count = 0;
}

// Get pending unlisted device submissions count for badge
$pending_unlisted_count = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM unlisted_device_submissions 
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $pending_unlisted_count = $stmt->fetch()['count'];
} catch(Exception $e) {
    $pending_unlisted_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        /* ============================================ */
        /* TOP NAVBAR - FIXED AT TOP */
        /* ============================================ */
        .top-navbar {
            background: #1e293b;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            height: 56px;
            padding: 0 1rem;
        }
        
        .top-navbar .navbar-brand {
            font-weight: 600;
            font-size: 1.2rem;
            color: white !important;
        }
        
        .top-navbar .navbar-brand i {
            color: #3b82f6;
            margin-right: 8px;
        }
        
        .sidebar-toggle-btn {
            background: #334155;
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .sidebar-toggle-btn:hover {
            background: #3b82f6;
        }
        
        .user-dropdown-btn {
            background: #334155;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-dropdown-btn:hover {
            background: #3b82f6;
        }
        
        /* ============================================ */
        /* SIDEBAR - FIXED LEFT */
        /* ============================================ */
        .sidebar-wrapper {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: 260px;
            background: #ffffff;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
            border-right: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            z-index: 1020;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        /* Sidebar Closed State */
        body.sidebar-closed .sidebar-wrapper {
            left: -260px;
        }
        
        body.sidebar-closed .main-content-wrapper {
            margin-left: 0;
        }
        
        .sidebar-wrapper::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-wrapper::-webkit-scrollbar-track {
            background: #e2e8f0;
        }
        
        .sidebar-wrapper::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }
        
        /* Sidebar Brand */
        .sidebar-brand {
            padding: 16px 20px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 12px;
            background: #ffffff;
        }
        
        .sidebar-brand h4 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            color: #1e293b;
        }
        
        .sidebar-brand h4 i {
            color: #3b82f6;
            margin-right: 6px;
        }
        
        /* Navigation Links */
        .sidebar-nav {
            padding: 0 0 20px 0;
        }
        
        .sidebar-nav .nav-link {
            color: #475569;
            padding: 10px 18px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 500;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        
        .sidebar-nav .nav-link:hover {
            background: #f8fafc;
            color: #1e293b;
        }
        
        .sidebar-nav .nav-link.active {
            background: #eff6ff;
            color: #3b82f6;
            border-left-color: #3b82f6;
        }
        
        .sidebar-nav .nav-link.active i {
            color: #3b82f6;
        }
        
        .sidebar-nav .nav-link i {
            width: 20px;
            font-size: 14px;
            color: #64748b;
        }
        
        .sidebar-nav .nav-link:hover i {
            color: #3b82f6;
        }
        
        /* Badge for pending items */
        .badge-pending {
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 20px;
            margin-left: auto;
            min-width: 20px;
            text-align: center;
        }
        
        /* Section Headers */
        .sidebar-nav .nav-header {
            padding: 10px 18px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            background: #ffffff;
            margin-top: 4px;
            border-top: 1px solid #f1f5f9;
        }
        
        .sidebar-nav .nav-header:first-of-type {
            border-top: none;
            margin-top: 0;
        }
        
        /* Two Column Menu Layout */
        .menu-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2px;
            padding: 0 8px;
        }
        
        .menu-grid .nav-link {
            padding: 8px 10px;
            font-size: 12px;
            border-radius: 6px;
            margin: 1px 0;
        }
        
        .menu-grid .nav-link i {
            font-size: 12px;
        }
        
        /* ============================================ */
        /* MAIN CONTENT - RIGHT SIDE */
        /* ============================================ */
        .main-content-wrapper {
            margin-left: 260px;
            margin-top: 56px;
            padding: 0;
            min-height: calc(100vh - 56px);
            transition: all 0.3s ease;
            background-color: #f0f2f5;
        }
        
        /* Allow dashboard's container-fluid to work properly */
        .main-content-wrapper > .container-fluid {
            padding: 20px 24px;
        }
        
        /* ============================================ */
        /* CARDS - MATCH EXISTING DASHBOARD STYLES */
        /* ============================================ */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: box-shadow 0.2s;
        }
        
        .card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        /* Bootstrap background color overrides for cards */
        .bg-primary { background: linear-gradient(135deg, #3b82f6, #2563eb) !important; }
        .bg-success { background: linear-gradient(135deg, #10b981, #059669) !important; }
        .bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706) !important; }
        .bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626) !important; }
        .bg-info { background: linear-gradient(135deg, #0dcaf0, #0aa2c0) !important; }
        .bg-secondary { background: linear-gradient(135deg, #6c757d, #5a6268) !important; }
        .bg-dark { background: #1e293b !important; }
        
        /* Opacity helper */
        .opacity-50 { opacity: 0.5; }
        
        /* ============================================ */
        /* SCROLL TO TOP */
        /* ============================================ */
        .scroll-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #3b82f6;
            color: white;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            font-size: 18px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .scroll-top:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }
        
        /* ============================================ */
        /* UTILITY CLASSES */
        /* ============================================ */
        .btn-primary {
            background: #3b82f6;
            border: none;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .text-warning { color: #f59e0b !important; }
        .text-info { color: #0dcaf0 !important; }
        .text-success { color: #198754 !important; }
        .text-danger { color: #dc3545 !important; }
        .text-primary { color: #3b82f6 !important; }
        .text-secondary { color: #6c757d !important; }
        
        /* Status Badges - matches dashboard */
        .badge {
            font-weight: 500;
        }
        
        /* ============================================ */
        /* RESPONSIVE */
        /* ============================================ */
        @media (max-width: 768px) {
            .sidebar-wrapper {
                left: -260px;
            }
            body.sidebar-open .sidebar-wrapper {
                left: 0;
            }
            .main-content-wrapper {
                margin-left: 0;
            }
            .main-content-wrapper > .container-fluid {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <nav class="top-navbar navbar navbar-dark fixed-top">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button">
                    <i class="fas fa-bars"></i>
                </button>
                <a class="navbar-brand ms-3" href="/modules/dashboard.php">
                    <i class="fas fa-laptop-code"></i> IT Inventory System
                </a>
            </div>
            <div class="dropdown">
                <button class="user-dropdown-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($user_name); ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/modules/users/profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                    <li><a class="dropdown-item" href="/modules/users/change_password.php"><i class="fas fa-key me-2"></i> Change Password</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if(canView($pdo, $user_role, 'users')): ?>
                    <li><a class="dropdown-item" href="/modules/users/list.php"><i class="fas fa-users-cog me-2"></i> User Management</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item text-danger" href="/modules/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar - Fixed Left -->
    <div class="sidebar-wrapper" id="sidebarWrapper">
        <div class="sidebar-brand">
            <h4><i class="fas fa-laptop-code"></i> IT Inventory</h4>
        </div>
        
        <div class="sidebar-nav">
            <!-- MAIN NAVIGATION -->
            <div class="nav-header">MAIN</div>
            <?php if(canView($pdo, $user_role, 'dashboard')): ?>
            <a href="/modules/dashboard.php" class="nav-link <?php echo isActive(['dashboard.php'], $current_url); ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <?php endif; ?>
            <a href="/public/index.php" class="nav-link <?php echo isActive(['public/index.php'], $current_url); ?>">
                <i class="fas fa-home"></i> Home
            </a>
            
            <!-- MASTER DATA -->
            <div class="nav-header">MASTER DATA</div>
            <div class="menu-grid">
                <?php if(canView($pdo, $user_role, 'items')): ?>
                <a href="/modules/items/list.php" class="nav-link <?php echo isActive(['items'], $current_url); ?>">
                    <i class="fas fa-box"></i> Items
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'brands')): ?>
                <a href="/modules/brands/list.php" class="nav-link <?php echo isActive(['brands'], $current_url); ?>">
                    <i class="fas fa-trademark"></i> Brands
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'vendors')): ?>
                <a href="/modules/vendors/list.php" class="nav-link <?php echo isActive(['vendors'], $current_url); ?>">
                    <i class="fas fa-truck"></i> Vendors
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'employees')): ?>
                <a href="/modules/employees/list.php" class="nav-link <?php echo isActive(['employees'], $current_url); ?>">
                    <i class="fas fa-users"></i> Employees
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'categories')): ?>
                <a href="/modules/categories/list.php" class="nav-link <?php echo isActive(['categories'], $current_url); ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <?php endif; ?>
            </div>
            
            <!-- STOCK MANAGEMENT -->
            <div class="nav-header">STOCK MANAGEMENT</div>
            <div class="menu-grid">
                <?php if(canCreate($pdo, $user_role, 'stock')): ?>
                <a href="/modules/stock/stock_in.php" class="nav-link <?php echo isActive(['stock_in'], $current_url); ?>">
                    <i class="fas fa-dolly"></i> Stock-In
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'stock')): ?>
                <a href="/modules/stock/stock_list.php" class="nav-link <?php echo isActive(['stock_list'], $current_url); ?>">
                    <i class="fas fa-list"></i> Stock List
                </a>
                <a href="/modules/stock/stock_report.php" class="nav-link <?php echo isActive(['stock_report'], $current_url); ?>">
                    <i class="fas fa-chart-bar"></i> Stock Report
                </a>
                <?php endif; ?>
            </div>
            
            <!-- BILL MANAGEMENT -->
            <div class="nav-header">BILL MANAGEMENT</div>
            <div class="menu-grid">
                <?php if(canCreate($pdo, $user_role, 'bills')): ?>
                <a href="/modules/bills/receive_bill.php" class="nav-link <?php echo isActive(['receive_bill'], $current_url); ?>">
                    <i class="fas fa-download"></i> Receive Bill
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'bills')): ?>
                <a href="/modules/bills/list_bills.php" class="nav-link <?php echo isActive(['list_bills'], $current_url); ?>">
                    <i class="fas fa-list"></i> Bill List
                </a>
                <?php endif; ?>
            </div>
            
            <!-- ASSIGNMENTS -->
            <div class="nav-header">ASSIGNMENTS</div>
            <div class="menu-grid">
                <?php if(canCreate($pdo, $user_role, 'assignments')): ?>
                <a href="/modules/assignments/assign.php" class="nav-link <?php echo isActive(['assignments/assign'], $current_url); ?>">
                    <i class="fas fa-hand-paper"></i> New Assignment
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'assignments')): ?>
                <a href="/modules/assignments/list.php" class="nav-link <?php echo isActive(['assignments/list'], $current_url); ?>">
                    <i class="fas fa-list-alt"></i> Assignment List
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'assignments')): ?>
                <a href="/modules/assignments/regenerate_barcodes.php" class="nav-link <?php echo isActive(['regenerate_barcodes'], $current_url); ?>">
                    <i class="fas fa-qrcode"></i> Regenerate Barcodes
                </a>
                <?php endif; ?>
            </div>
            
            <!-- REQUESTS MANAGEMENT -->
            <div class="nav-header">REQUESTS</div>
            <?php if(canView($pdo, $user_role, 'requests')): ?>
            <a href="/modules/requests/all_requests.php" class="nav-link <?php echo isActive(['all_requests'], $current_url); ?>">
                <i class="fas fa-tasks"></i> All Requests
                <?php if($pending_requests_count > 0): ?>
                    <span class="badge-pending"><?php echo $pending_requests_count; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            
            <!-- RETURN MANAGEMENT -->
            <div class="nav-header">RETURN MANAGEMENT</div>
            <div class="menu-grid">
                <?php if(canView($pdo, $user_role, 'requests')): ?>
                <a href="/modules/returns/return_requests_list.php" class="nav-link <?php echo isActive(['returns/return_requests_list', 'returns'], $current_url); ?>">
                    <i class="fas fa-undo-alt"></i> Return Requests
                    <?php if($pending_returns_count > 0): ?>
                        <span class="badge-pending"><?php echo $pending_returns_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'reports')): ?>
                <a href="/modules/reports/return_report.php" class="nav-link <?php echo isActive(['return_report'], $current_url); ?>">
                    <i class="fas fa-chart-line"></i> Return Report
                </a>
                <?php endif; ?>
            </div>
            
            <!-- DAMAGE MANAGEMENT -->
            <div class="nav-header">DAMAGE MANAGEMENT</div>
            <div class="menu-grid">
                <?php if(canCreate($pdo, $user_role, 'damages')): ?>
                <a href="/modules/damages/damage.php" class="nav-link <?php echo isActive(['damages/damage', 'report_damage'], $current_url); ?>">
                    <i class="fas fa-tools"></i> Report Damage
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'damages')): ?>
                <a href="/modules/damages/list.php" class="nav-link <?php echo isActive(['damages/list'], $current_url); ?>">
                    <i class="fas fa-clipboard-list"></i> Damage List
                    <?php if($pending_damages_count > 0): ?>
                        <span class="badge-pending"><?php echo $pending_damages_count; ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <?php if(canView($pdo, $user_role, 'reports')): ?>
                <a href="/modules/reports/damage_report.php" class="nav-link <?php echo isActive(['damage_report'], $current_url); ?>">
                    <i class="fas fa-chart-line"></i> Damage Report
                </a>
                <?php endif; ?>
            </div>
            
            <!-- UNLISTED DEVICE SUBMISSIONS -->
            <?php if(canView($pdo, $user_role, 'unlisted_devices')): ?>
            <div class="nav-header">DEVICE DISCOVERY</div>
            <a href="/modules/unlisted_devices/review.php" class="nav-link <?php echo isActive(['unlisted_devices'], $current_url); ?>">
                <i class="fas fa-clipboard-list"></i> Unlisted Device Submissions
                <?php if($pending_unlisted_count > 0): ?>
                    <span class="badge-pending"><?php echo $pending_unlisted_count; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            
            <!-- LICENSE & WARRANTY MANAGEMENT -->
            <div class="nav-header">LICENSE & WARRANTY</div>
            <div class="menu-grid">
                <?php if(canView($pdo, $user_role, 'license_warranty') || $user_role == 'admin'): ?>
                <a href="/modules/license_warranty/licenses.php" class="nav-link <?php echo isActive(['license_warranty/licenses'], $current_url); ?>">
                    <i class="fas fa-key"></i> Licenses
                </a>
                <a href="/modules/license_warranty/warranties.php" class="nav-link <?php echo isActive(['license_warranty/warranties'], $current_url); ?>">
                    <i class="fas fa-shield-alt"></i> Warranties
                </a>
                <a href="/modules/license_warranty/tasks.php" class="nav-link <?php echo isActive(['license_warranty/tasks'], $current_url); ?>">
                    <i class="fas fa-tasks"></i> My Tasks
                </a>
                <a href="/modules/license_warranty/reports.php" class="nav-link <?php echo isActive(['license_warranty/reports'], $current_url); ?>">
                    <i class="fas fa-chart-line"></i> L&W Reports
                </a>
                <?php endif; ?>
            </div>
            
            <!-- FINANCE -->
            <div class="nav-header">FINANCE</div>
            <div class="menu-grid">
                <?php if(canView($pdo, $user_role, 'cash_register')): ?>
                <a href="/modules/cash_register/register.php" class="nav-link <?php echo isActive(['cash_register'], $current_url); ?>">
                    <i class="fas fa-cash-register"></i> Cash Register
                </a>
                <?php endif; ?>
            </div>
            
            <!-- TRANSFER MANAGEMENT -->
            <div class="nav-header">TRANSFER MANAGEMENT</div>
            <div class="menu-grid">
                <a href="/modules/transfers/list.php" class="nav-link <?php echo isActive(['transfers'], $current_url); ?>">
                    <i class="fas fa-exchange-alt"></i> Device Transfers
                </a>
                <a href="/modules/transfers/create.php" class="nav-link <?php echo isActive(['transfers/create'], $current_url); ?>">
                    <i class="fas fa-plus"></i> New Transfer
                </a>
            </div>
            
            <!-- REPORTS -->
            <div class="nav-header">REPORTS</div>
            <div class="menu-grid">
                <?php if(canView($pdo, $user_role, 'reports')): ?>
                <a href="/modules/reports/stock_report.php" class="nav-link <?php echo isActive(['reports/stock'], $current_url); ?>">
                    <i class="fas fa-chart-line"></i> Stock Report
                </a>
                <a href="/modules/reports/financial_report.php" class="nav-link <?php echo isActive(['financial_report'], $current_url); ?>">
                    <i class="fas fa-chart-bar"></i> Financial Report
                </a>
                <?php endif; ?>
            </div>
            
            <!-- SYSTEM -->
            <div class="nav-header">SYSTEM</div>
            
            <!-- User Management - active only for user-related pages -->
            <?php 
            $userManagementActive = isActive(['users/list', 'users/add', 'users/edit', 'users/view', 'users/profile', 'users/change_password'], $current_url);
            ?>
            <?php if(canView($pdo, $user_role, 'users')): ?>
            <a href="/modules/users/list.php" class="nav-link <?php echo $userManagementActive; ?>">
                <i class="fas fa-users-cog"></i> User Management
            </a>
            <?php endif; ?>
            
            <!-- Permissions - active only for permissions page -->
            <?php 
            $permissionsActive = isActive(['permissions'], $current_url);
            ?>
            <?php if(canView($pdo, $user_role, 'permissions')): ?>
            <a href="/modules/users/permissions.php" class="nav-link <?php echo $permissionsActive; ?>">
                <i class="fas fa-lock"></i> Permissions
            </a>
            <?php endif; ?>
            
            <!-- Roles - active only for roles page -->
            <?php 
            $rolesActive = isActive(['roles'], $current_url);
            ?>
            <?php if(canView($pdo, $user_role, 'roles')): ?>
            <a href="/modules/users/roles.php" class="nav-link <?php echo $rolesActive; ?>">
                <i class="fas fa-tags"></i> Roles
            </a>
            <?php endif; ?>
            
            <a href="/modules/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content - Right Side (THIS WILL BE CLOSED BY FOOTER) -->
    <div class="main-content-wrapper" id="mainContentWrapper">