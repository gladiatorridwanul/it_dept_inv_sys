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

$request_no = $_GET['request_no'] ?? '';
$request = null;
$error = '';

if(!empty($request_no)) {
    $stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department 
                           FROM requests r 
                           JOIN employees e ON r.employee_id = e.id 
                           WHERE r.request_no = ?");
    $stmt->execute([$request_no]);
    $request = $stmt->fetch();
    
    if(!$request) {
        $error = "Request not found. Please check your request number.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Track Request - IT Support</title>
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
        .track-card { 
            background: white; 
            border-radius: 20px; 
            padding: 30px; 
            max-width: 1000px; 
            margin: 0 auto 30px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
        }
        .track-header { text-align: center; margin-bottom: 30px; }
        .track-header h2 { 
            font-size: 1.6rem; 
            font-weight: 700; 
            color: #1f2937; 
            margin-bottom: 8px; 
        }
        .track-header .underline { 
            width: 50px; 
            height: 3px; 
            background: #3b82f6; 
            margin: 12px auto 0; 
            border-radius: 3px; 
        }
        .track-header p { font-size: 0.85rem; color: #6b7280; margin-top: 10px; }
        
        .form-label { 
            font-weight: 600; 
            color: #334155; 
            margin-bottom: 6px; 
            font-size: 0.85rem; 
        }
        
        .btn-submit { 
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
        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
            color: white;
        }
        .btn-submit:active { transform: translateY(0px); }
        
        .search-box { 
            background: #f8fafc; 
            border-radius: 60px; 
            padding: 5px; 
            border: 1px solid #e2e8f0; 
            display: flex;
        }
        .search-box input { 
            border: none; 
            background: transparent; 
            padding: 10px 20px; 
            width: calc(100% - 120px);
            font-size: 0.85rem;
        }
        .search-box input:focus { outline: none; }
        .search-box button { 
            border-radius: 50px; 
            padding: 8px 25px; 
            white-space: nowrap;
        }
        
        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .info-label { width: 140px; font-weight: 600; color: #475569; font-size: 0.85rem; }
        .info-value { flex: 1; color: #1e293b; font-size: 0.85rem; }
        
        .status-badge { display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-under_observation { background: #dbeafe; color: #2563eb; }
        .status-processing { background: #e0e7ff; color: #4338ca; }
        .status-completed { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        
        .timeline { position: relative; padding: 20px 0; }
        .timeline-step { display: flex; margin-bottom: 30px; position: relative; }
        .timeline-icon { 
            width: 45px; 
            height: 45px; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 20px; 
            z-index: 2; 
            background: #e2e8f0;
            color: #94a3b8;
        }
        .timeline-icon.completed { background: #10b981; color: white; }
        .timeline-icon.current { background: #3b82f6; color: white; box-shadow: 0 0 0 5px rgba(59,130,246,0.2); }
        .timeline-icon.pending { background: #e2e8f0; color: #94a3b8; }
        .timeline-content h4 { font-size: 0.9rem; font-weight: 600; margin-bottom: 5px; }
        .timeline-content p { font-size: 0.75rem; color: #64748b; margin: 0; }
        .timeline-line { position: absolute; left: 21px; top: 45px; width: 2px; height: calc(100% - 45px); background: #e2e8f0; z-index: 1; }
        
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
        
        .alert-success { background: #d1fae5; color: #065f46; border: none; border-radius: 12px; padding: 20px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: none; border-radius: 12px; padding: 15px; }
        .alert-info { background: #eff6ff; color: #1e40af; border: none; border-radius: 12px; padding: 15px; }
        .alert-warning { background: #fef3c7; color: #d97706; border: none; border-radius: 12px; padding: 15px; }
        
        .bg-light { background: #f8fafc !important; border-radius: 16px; }
        .badge { font-size: 10px; padding: 4px 10px; border-radius: 20px; }
        
        @media (max-width: 576px) {
            .navbar-brand { font-size: 1rem; }
            .nav-link { font-size: 0.8rem; padding: 8px 0; text-align: center; }
            .btn-dashboard, .btn-logout { padding: 6px 16px; font-size: 0.75rem; margin: 5px 0; display: inline-block; width: auto; }
            .navbar-nav { text-align: center; padding: 10px 0; }
            .navbar-nav .nav-item { margin: 5px 0; }
            
            .track-card { padding: 20px; margin: 0 15px 30px; }
            .track-header h2 { font-size: 1.3rem; }
            .track-header p { font-size: 0.7rem; }
            
            .btn-submit { width: 100%; margin: 5px 0; padding: 8px 20px; font-size: 0.75rem; }
            
            .main-content { padding-top: 68px; }
            
            .search-box {
                flex-direction: column;
                border-radius: 16px;
            }
            .search-box input {
                width: 100%;
                border-radius: 12px;
                margin-bottom: 8px;
                padding: 10px 15px;
            }
            .search-box button {
                width: 100%;
                border-radius: 12px;
            }
            
            .info-label { width: 110px; font-size: 0.75rem; }
            .info-value { font-size: 0.75rem; }
            
            .footer-content { flex-direction: row; justify-content: space-between; }
            .copyright p { font-size: 0.6rem; margin: 0; }
            .btn-footer-login { padding: 4px 12px; font-size: 0.6rem; }
            .footer-login { text-align: right; }
            
            .timeline-icon { width: 38px; height: 38px; margin-right: 15px; font-size: 14px; }
            .timeline-line { left: 18px; top: 38px; }
            .timeline-content h4 { font-size: 0.8rem; }
            .timeline-content p { font-size: 0.65rem; }
        }
        
        @media (min-width: 577px) and (max-width: 992px) {
            .track-card { margin: 0 20px 30px; }
        }
        
        html { scroll-behavior: smooth; }
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
            <div class="track-card">
                <div class="track-header">
                    <h2><i class="fas fa-search"></i> Track Your Request</h2>
                    <div class="underline"></div>
                    <p>Enter your request number to check status</p>
                </div>

                <form method="GET" class="mb-4">
                    <div class="search-box">
                        <input type="text" name="request_no" class="flex-grow-1" 
                               placeholder="Enter Request Number (e.g., ACC-20241201123456123)" 
                               value="<?php echo htmlspecialchars($request_no); ?>">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-search"></i> Track Request
                        </button>
                    </div>
                </form>

                <?php if($error): ?>
                    <div class="alert-danger text-center">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?>
                    </div>
                <?php elseif($request): 
                    $status_class = 'status-' . str_replace('_', '-', $request['status']);
                    $status_text = ucfirst(str_replace('_', ' ', $request['status']));
                    
                    $status_order = ['pending', 'under_observation', 'processing', 'completed'];
                    $current_index = array_search($request['status'], $status_order);
                    if($request['status'] == 'rejected') $current_index = -1;
                ?>
                    <div class="alert-success text-center mb-4">
                        <i class="fas fa-check-circle me-2"></i> Request Found!
                    </div>

                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="info-row"><div class="info-label">Request No:</div><div class="info-value"><strong><?php echo htmlspecialchars($request['request_no']); ?></strong></div></div>
                                <div class="info-row"><div class="info-label">Request Type:</div><div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $request['request_type'])); ?></div></div>
                                <div class="info-row"><div class="info-label">Request Date:</div><div class="info-value"><?php echo date('d-m-Y h:i A', strtotime($request['requested_date'])); ?></div></div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-row"><div class="info-label">Employee:</div><div class="info-value"><?php echo htmlspecialchars($request['full_name']); ?></div></div>
                                <div class="info-row"><div class="info-label">PF No:</div><div class="info-value"><?php echo htmlspecialchars($request['pf_no']); ?></div></div>
                                <div class="info-row"><div class="info-label">Current Status:</div><div class="info-value"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="timeline">
                        <div class="timeline-line" style="<?php echo $current_index >= 0 ? 'height: ' . ($current_index * 55 + 25) . 'px;' : 'display:none;'; ?>"></div>
                        
                        <div class="timeline-step">
                            <div class="timeline-icon <?php echo in_array($request['status'], ['pending', 'under_observation', 'processing', 'completed']) ? 'completed' : 'pending'; ?>">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Request Submitted</h4>
                                <p>Your request has been received and is pending review</p>
                                <?php if($request['status'] == 'pending'): ?><span class="badge bg-warning mt-1">Current</span><?php endif; ?>
                            </div>
                        </div>

                        <div class="timeline-step">
                            <div class="timeline-icon <?php echo in_array($request['status'], ['under_observation', 'processing', 'completed']) ? 'completed' : 'pending'; ?>">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Under Observation</h4>
                                <p>IT team is reviewing your request</p>
                                <?php if($request['status'] == 'under_observation'): ?><span class="badge bg-info mt-1">Current</span><?php endif; ?>
                            </div>
                        </div>

                        <div class="timeline-step">
                            <div class="timeline-icon <?php echo in_array($request['status'], ['processing', 'completed']) ? 'completed' : 'pending'; ?>">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Processing</h4>
                                <p>Your request is being processed by the IT team</p>
                                <?php if($request['status'] == 'processing'): ?><span class="badge bg-primary mt-1">Current</span><?php endif; ?>
                            </div>
                        </div>

                        <div class="timeline-step">
                            <div class="timeline-icon <?php echo $request['status'] == 'completed' ? 'completed' : 'pending'; ?>">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Completed</h4>
                                <p>Your request has been completed successfully</p>
                                <?php if($request['status'] == 'completed'): ?><span class="badge bg-success mt-1">Current</span><?php endif; ?>
                            </div>
                        </div>

                        <?php if($request['status'] == 'rejected'): ?>
                        <div class="timeline-step">
                            <div class="timeline-icon" style="background: #dc2626; color: white;">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="timeline-content">
                                <h4>Rejected</h4>
                                <p>Your request has been rejected. Please contact IT department for details.</p>
                                <?php if($request['resolution_notes']): ?>
                                <div class="alert-danger mt-2 small p-2" style="margin-top: 8px;">Reason: <?php echo nl2br(htmlspecialchars($request['resolution_notes'])); ?></div>
                                <?php endif; ?>
                                <span class="badge bg-danger mt-1">Current</span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if($request['status'] == 'completed' && $request['resolution_notes']): ?>
                    <div class="alert-success mt-3">
                        <i class="fas fa-check-circle me-2"></i> <strong>Resolution:</strong><br>
                        <?php echo nl2br(htmlspecialchars($request['resolution_notes'])); ?>
                    </div>
                    <?php endif; ?>

                    <?php if($request['estimated_completion_date'] && $request['status'] != 'completed' && $request['status'] != 'rejected'): ?>
                    <div class="alert-info mt-3">
                        <i class="fas fa-calendar me-2"></i> <strong>Estimated Completion Date:</strong> <?php echo date('d-m-Y', strtotime($request['estimated_completion_date'])); ?>
                    </div>
                    <?php endif; ?>

                <?php elseif($request_no): ?>
                    <div class="alert-warning text-center">
                        <i class="fas fa-search me-2"></i> Please enter a request number to track.
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
        $(document).ready(function() {
            // Close mobile menu after clicking a link
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