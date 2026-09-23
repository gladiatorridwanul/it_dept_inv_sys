<?php
// Include session fix FIRST
require_once dirname(__DIR__) . '/config/session_fix.php';
require_once dirname(__DIR__) . '/config/database.php';

// Check login status
$isLoggedIn = false;
$userName = '';
$userRole = '';

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $isLoggedIn = true;
    $userName = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';
    $userRole = $_SESSION['role'] ?? 'staff';
}

// Get unlisted device submissions counts for alert
try {
    $unlistedPending = 0;
    $unlistedTotal = 0;
    $unlistedAdded = 0;
    try {
        $unlistedPending = $pdo->query("SELECT COUNT(*) as count FROM unlisted_device_submissions WHERE status = 'pending'")->fetch()['count'];
        $unlistedTotal = $pdo->query("SELECT COUNT(*) as count FROM unlisted_device_submissions")->fetch()['count'];
        $unlistedAdded = $pdo->query("SELECT COUNT(*) as count FROM unlisted_device_submissions WHERE status = 'added_to_stock'")->fetch()['count'];
    } catch(Exception $e) {
        $unlistedPending = 0;
        $unlistedTotal = 0;
        $unlistedAdded = 0;
    }
} catch(Exception $e) {
    $unlistedPending = 0;
    $unlistedTotal = 0;
    $unlistedAdded = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>IT Support Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', 'Roboto', Arial, sans-serif; background: #f5f7fa; }
        
        /* Navbar */
        .navbar { 
            background: #ffffff; 
            box-shadow: 0 2px 15px rgba(0,0,0,0.08); 
            padding: 0.6rem 0; 
            position: fixed; 
            width: 100%; 
            top: 0; 
            z-index: 1000; 
        }
        .navbar-brand { font-size: 1.3rem; font-weight: 700; color: #3b82f6; }
        .navbar-brand i { color: #3b82f6; margin-right: 8px; }
        
        /* Black burger menu icon */
        .navbar-toggler {
            border: none;
            padding: 0;
        }
        .navbar-toggler:focus {
            box-shadow: none;
            outline: none;
        }
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
        }
        
        .nav-link { 
            font-weight: 500; 
            color: #4b5563; 
            transition: all 0.3s ease; 
            margin: 0 0.6rem; 
            font-size: 0.9rem; 
        }
        .nav-link:hover { color: #3b82f6; transform: translateY(-2px); }
        
        /* Only buttons have pointer/finger cursor, not entire card */
        .request-card { 
            background: white; 
            border-radius: 20px; 
            padding: 24px 20px; 
            text-align: center; 
            transition: all 0.3s ease; 
            height: 100%; 
            border: 1px solid #e5e7eb; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            cursor: default;
        }
        .request-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.1); 
            border-color: #cbd5e1;
        }
        .request-icon { 
            width: 65px; 
            height: 65px; 
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            border-radius: 18px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 16px; 
            color: #3b82f6; 
            font-size: 26px; 
            transition: all 0.3s ease; 
        }
        .request-card:hover .request-icon { 
            background: #3b82f6; 
            color: white; 
            transform: scale(1.05); 
        }
        .request-card h4 { 
            font-size: 1.1rem; 
            font-weight: 700; 
            color: #1f2937; 
            margin-bottom: 8px; 
        }
        .request-card p { 
            font-size: 0.85rem; 
            color: #6b7280; 
            margin: 0 0 18px 0; 
            line-height: 1.5; 
            flex-grow: 1;
        }
        
        /* Button inside card - only element with finger cursor */
        .card-btn {
            display: inline-block;
            padding: 10px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            margin-top: 5px;
        }
        .card-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
            color: white;
        }
        .card-btn:active {
            transform: translateY(0px);
        }
        
        /* Highlighted card for Unlisted Device */
        .request-card-highlight { 
            background: linear-gradient(135deg, #fff 0%, #eff6ff 100%); 
            border: 2px solid #3b82f6; 
        }
        .request-card-highlight .request-icon { 
            background: #3b82f6; 
            color: white; 
        }
        .request-card-highlight .card-btn {
            background: #1e40af;
        }
        
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
        
        .main-content { padding-top: 75px; min-height: 100vh; }
        .section-title { text-align: center; margin-bottom: 30px; }
        .section-title h2 { font-size: 1.6rem; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
        .section-title .underline { width: 50px; height: 3px; background: #3b82f6; margin: 0 auto; border-radius: 3px; }
        .section-title p { font-size: 0.85rem; color: #6b7280; margin-top: 10px; }
        
        /* Alert for IT Staff */
        .alert-pending {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 25px;
        }
        .alert-pending i { color: #f59e0b; font-size: 1rem; }
        .alert-pending .alert-link { color: #d97706; font-weight: 600; text-decoration: none; }
        .alert-pending .alert-link:hover { text-decoration: underline; }
        
        /* Footer */
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
        .btn-footer-login:hover { 
            background: #3b82f6; 
            border-color: #3b82f6; 
            color: white; 
        }
        
        /* ============================================ */
        /* MOBILE STYLES - Smaller fonts */
        /* ============================================ */
        @media (max-width: 576px) {
            /* Navbar */
            .navbar-brand { font-size: 1rem; }
            .nav-link { 
                font-size: 0.8rem; 
                padding: 8px 0;
                text-align: center;
            }
            .btn-dashboard, .btn-logout { 
                padding: 6px 16px; 
                font-size: 0.75rem;
                margin: 5px 0;
                display: inline-block;
                width: auto;
            }
            
            /* Mobile menu items - center aligned */
            .navbar-nav {
                text-align: center;
                padding: 10px 0;
            }
            .navbar-nav .nav-item {
                margin: 5px 0;
            }
            
            /* Section Title */
            .section-title h2 { font-size: 1.3rem; }
            .section-title p { font-size: 0.7rem; }
            
            /* Cards */
            .request-card { padding: 18px 12px; }
            .request-icon { width: 50px; height: 50px; font-size: 20px; margin-bottom: 12px; }
            .request-card h4 { font-size: 0.85rem; margin-bottom: 5px; }
            .request-card p { font-size: 0.65rem; margin-bottom: 12px; }
            .card-btn { 
                padding: 7px 16px; 
                font-size: 0.7rem; 
            }
            
            /* Alert */
            .alert-pending { 
                padding: 10px 12px; 
                font-size: 0.7rem;
            }
            .alert-pending .alert-link { font-size: 0.7rem; }
            .btn-close { transform: scale(0.8); }
            
            /* Footer */
            .copyright p { font-size: 0.6rem; margin: 0; }
            .btn-footer-login { padding: 4px 12px; font-size: 0.6rem; }
            
            /* 2 cards per row on mobile */
            .row.g-4 .col-md-6.col-lg-4 { flex: 0 0 auto; width: 50%; }
            .g-4 { --bs-gutter-y: 1rem; --bs-gutter-x: 0.75rem; }
        }
        
        /* Tablet: 3 cards per row */
        @media (min-width: 577px) and (max-width: 992px) {
            .row.g-4 .col-md-6.col-lg-4 { flex: 0 0 auto; width: 33.333%; }
            .request-card { padding: 22px 16px; }
            .request-icon { width: 60px; height: 60px; font-size: 24px; }
            .request-card h4 { font-size: 1rem; }
            .request-card p { font-size: 0.75rem; }
            .card-btn { padding: 8px 20px; font-size: 0.75rem; }
        }
        
        /* Desktop: 4 cards per row */
        @media (min-width: 993px) {
            .row.g-4 .col-md-6.col-lg-4 { flex: 0 0 auto; width: 25%; }
        }
        
        /* Large Desktop */
        @media (min-width: 1400px) {
            .container { max-width: 1320px; }
            .row.g-4 .col-md-6.col-lg-4 { flex: 0 0 auto; width: 25%; }
        }
        
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
        
        /* Remove focus outline for better mobile touch */
        .card-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.3);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-laptop-code"></i> IT Inventory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#requests">Request Form</a></li>
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
        <!-- Request Forms Section -->
        <div id="requests" class="container">
            <div class="section-title">
                <h2>Submit a Request</h2>
                <div class="underline"></div>
                <p>Select the type of request you need assistance with</p>
            </div>
            <div class="row g-4">
                <!-- Report an Issue -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card">
                        <div class="request-icon"><i class="fas fa-exclamation-triangle"></i></div>
                        <h4>Report an Issue</h4>
                        <p>Technical problems, printer issues, IT-related problems</p>
                        <a href="report_issue.php" class="card-btn"><i class="fas fa-paper-plane me-1"></i> Submit Request</a>
                    </div>
                </div>
                
                <!-- Software & Access Request -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card">
                        <div class="request-icon"><i class="fas fa-key"></i></div>
                        <h4>Software & Access</h4>
                        <p>Software installation, VPN, email, ERP access</p>
                        <a href="software_access.php" class="card-btn"><i class="fas fa-paper-plane me-1"></i> Submit Request</a>
                    </div>
                </div>
                
                <!-- Request Upgrade -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card">
                        <div class="request-icon"><i class="fas fa-arrow-up"></i></div>
                        <h4>Request Upgrade</h4>
                        <p>Hardware or software upgrade for your device</p>
                        <a href="upgrade_request.php" class="card-btn"><i class="fas fa-paper-plane me-1"></i> Submit Request</a>
                    </div>
                </div>
                
                <!-- IT Accessories Request -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card">
                        <div class="request-icon"><i class="fas fa-mouse"></i></div>
                        <h4>IT Accessories</h4>
                        <p>Mouse, keyboard, headphone, cables, etc.</p>
                        <a href="accessories_request.php" class="card-btn"><i class="fas fa-paper-plane me-1"></i> Submit Request</a>
                    </div>
                </div>
                
                <!-- Multiple Device Assignment -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card">
                        <div class="request-icon"><i class="fas fa-laptop-house"></i></div>
                        <h4>Multiple Device Assign</h4>
                        <p>Request multiple devices at once for a project</p>
                        <a href="device_assign.php" class="card-btn"><i class="fas fa-paper-plane me-1"></i> Submit Request</a>
                    </div>
                </div>
                
                <!-- Report Unlisted Device (Highlighted) -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card request-card-highlight">
                        <div class="request-icon"><i class="fas fa-microchip"></i></div>
                        <h4>Report Unlisted Device</h4>
                        <p>Submit devices assigned to you that are not in IT inventory</p>
                        <a href="submit_unlisted_device.php" class="card-btn"><i class="fas fa-upload me-1"></i> Submit Device</a>
                    </div>
                </div>
                
                <!-- Return Device -->
                <div class="col-md-6 col-lg-4">
                    <div class="request-card">
                        <div class="request-icon"><i class="fas fa-undo-alt"></i></div>
                        <h4>Return Device</h4>
                        <p>Return device when leaving or transferring</p>
                        <a href="return_device.php" class="card-btn"><i class="fas fa-paper-plane me-1"></i> Submit Request</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alert for IT Staff about pending unlisted submissions -->
        <?php if($unlistedPending > 0 && ($userRole == 'admin' || $userRole == 'it_staff')): ?>
        <div class="container mt-4 mb-2">
            <div class="alert-pending alert-dismissible fade show" role="alert">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <i class="fas fa-clipboard-list me-2"></i>
                        <strong><?php echo $unlistedPending; ?> pending unlisted device submission(s)</strong> waiting for your review.
                        <a href="../modules/unlisted_devices/review.php" class="alert-link ms-2">Click here to review →</a>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
    <script>
        // Auto-hide alert after 8 seconds
        setTimeout(function() {
            const alert = document.querySelector('.alert-pending');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 8000);
        
        // Close mobile menu after clicking a link (optional)
        document.querySelectorAll('.navbar-nav .nav-link').forEach(function(link) {
            link.addEventListener('click', function() {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse && navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            });
        });
    </script>
</body>
</html>