<?php
require_once '../config/database.php';

$success = '';
$error = '';

// Fetch employees for dropdown with quick search
$employees = $pdo->query("SELECT id, pf_no, full_name, designation, job_location, phone, email FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();

// Fetch devices that are currently assigned to employees
$devices = $pdo->query("SELECT a.id as assignment_id, a.assignment_no, i.id as item_id, i.item_code, i.name, i.serial_number, e.full_name as assigned_to 
                       FROM assignments a 
                       JOIN items i ON a.item_id = i.id 
                       JOIN employees e ON a.employee_id = e.id 
                       WHERE a.status = 'assigned' 
                       ORDER BY i.name")->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $assignment_id = $_POST['assignment_id'];
    $handover_to = $_POST['handover_to'];
    $handover_reason = $_POST['handover_reason'];
    $device_condition = $_POST['device_condition'];
    $submission_date = date('Y-m-d H:i:s');
    
    // Get assignment and device details
    $stmt = $pdo->prepare("SELECT a.*, i.name as item_name, i.item_code, i.serial_number, i.price 
                           FROM assignments a 
                           JOIN items i ON a.item_id = i.id 
                           WHERE a.id = ? AND a.employee_id = ?");
    $stmt->execute([$assignment_id, $employee_id]);
    $assignment = $stmt->fetch();
    
    if($assignment) {
        $request_no = generateNumber('HND-', 'requests', 'request_no');
        $description = "Device Handover Request\n";
        $description .= "Assignment No: " . $assignment['assignment_no'] . "\n";
        $description .= "Device: " . $assignment['item_name'] . "\n";
        $description .= "Item Code: " . $assignment['item_code'] . "\n";
        $description .= "Serial: " . $assignment['serial_number'] . "\n";
        $description .= "Handover To: $handover_to\n";
        $description .= "Device Condition: $device_condition\n";
        $description .= "Reason: $handover_reason";
        
        $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, requested_date, status, notes) 
                              VALUES (?, ?, 'handover', ?, ?, 'pending', ?)");
        
        if($stmt->execute([$request_no, $employee_id, $description, $submission_date, $device_condition])) {
            $success = "Handover request submitted successfully! Request No: " . $request_no;
        } else {
            $error = "Error submitting request!";
        }
    } else {
        $error = "Invalid assignment selected!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Handover Request - IT Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .navbar { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); box-shadow: 0 2px 20px rgba(0,0,0,0.08); padding: 1rem 0; position: fixed; width: 100%; top: 0; z-index: 1000; }
        .navbar-brand { font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .nav-link { font-weight: 500; color: #4a5568; transition: all 0.3s; margin: 0 0.5rem; }
        .nav-link:hover { color: #667eea; transform: translateY(-2px); }
        .btn-login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 8px 24px; border-radius: 50px; font-weight: 600; transition: all 0.3s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); color: white; }
        .main-content { padding-top: 80px; min-height: calc(100vh - 200px); }
        .form-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); margin-bottom: 30px; }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { font-size: 1.8rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .form-header .underline { width: 80px; height: 4px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); margin: 15px auto 0; border-radius: 2px; }
        .form-label { font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-control, .form-select { border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 15px; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-submit { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 35px; border-radius: 50px; font-weight: 600; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(59,130,246,0.4); }
        .btn-reset { background: #e2e8f0; color: #475569; border: none; padding: 12px 35px; border-radius: 50px; font-weight: 600; margin-left: 15px; transition: all 0.3s; }
        .btn-reset:hover { background: #cbd5e1; }
        footer { background: #1e293b; color: white; padding: 30px 0; margin-top: 60px; }
        .copyright { text-align: center; color: #94a3b8; font-size: 0.9rem; }
        .info-card { background: #f8fafc; border-radius: 12px; padding: 15px; margin-top: 15px; border-left: 4px solid #3b82f6; display: none; }
        .select2-container--default .select2-selection--single { border: 2px solid #e2e8f0; border-radius: 12px; height: 48px; padding: 5px; }
        .select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 36px; }
        @media (max-width: 768px) { .form-card { padding: 20px; } .btn-submit, .btn-reset { width: 100%; margin: 5px 0; } }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-laptop-code"></i> IT Inventory</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php#requests">Submit Request</a></li>
                    <li class="nav-item"><a class="btn btn-login" href="../modules/login.php"><i class="fas fa-lock"></i> Staff Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-handshake"></i> Device Handover Request</h2>
                    <div class="underline"></div>
                    <p class="text-muted mt-2">Request to handover device to another employee or department</p>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success text-center"><?php echo $success; ?></div>
                    <div class="text-center"><a href="index.php" class="btn btn-submit">Submit Another Request</a></div>
                <?php else: ?>
                    <form method="POST" id="handoverForm">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-user"></i> Select Employee *</label>
                                <select name="employee_id" class="form-select select2-employee" id="employeeSelect" required>
                                    <option value="">-- Search Employee --</option>
                                    <?php foreach($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" 
                                            data-pf="<?php echo $emp['pf_no']; ?>"
                                            data-name="<?php echo htmlspecialchars($emp['full_name']); ?>"
                                            data-designation="<?php echo $emp['designation']; ?>"
                                            data-location="<?php echo $emp['job_location']; ?>"
                                            data-phone="<?php echo $emp['phone']; ?>"
                                            data-email="<?php echo $emp['email']; ?>">
                                        <?php echo $emp['pf_no'] . ' - ' . htmlspecialchars($emp['full_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-laptop"></i> Select Device to Handover *</label>
                                <select name="assignment_id" class="form-select" id="deviceSelect" required disabled>
                                    <option value="">-- First Select Employee --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Employee Information Display -->
                        <div id="employeeInfo" class="info-card" style="display:none;">
                            <div class="row">
                                <div class="col-md-3"><small class="text-muted">PF Number</small><br><strong id="displayPf"></strong></div>
                                <div class="col-md-3"><small class="text-muted">Full Name</small><br><strong id="displayName"></strong></div>
                                <div class="col-md-3"><small class="text-muted">Designation</small><br><span id="displayDesignation"></span></div>
                                <div class="col-md-3"><small class="text-muted">Job Location</small><br><span id="displayLocation"></span></div>
                                <div class="col-md-3 mt-2"><small class="text-muted">Phone</small><br><span id="displayPhone"></span></div>
                                <div class="col-md-3 mt-2"><small class="text-muted">Email</small><br><span id="displayEmail"></span></div>
                            </div>
                        </div>

                        <!-- Device Information Display -->
                        <div id="deviceInfo" class="info-card" style="display:none;">
                            <div class="row">
                                <div class="col-md-4"><small class="text-muted">Item Code</small><br><strong id="displayItemCode"></strong></div>
                                <div class="col-md-4"><small class="text-muted">Device Name</small><br><span id="displayDeviceName"></span></div>
                                <div class="col-md-4"><small class="text-muted">Serial Number</small><br><span id="displaySerial"></span></div>
                                <div class="col-md-6 mt-2"><small class="text-muted">Assignment No</small><br><span id="displayAssignmentNo"></span></div>
                                <div class="col-md-6 mt-2"><small class="text-muted">Assigned Date</small><br><span id="displayAssignedDate"></span></div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-user-friends"></i> Handover To (Name/Department) *</label>
                            <input type="text" name="handover_to" class="form-control" required placeholder="Enter person/department name">
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-clipboard-list"></i> Device Condition *</label>
                            <select name="device_condition" class="form-select" required>
                                <option value="good">Good - Working perfectly</option>
                                <option value="minor_issues">Minor Issues - Needs small repair</option>
                                <option value="major_issues">Major Issues - Needs significant repair</option>
                                <option value="damaged">Damaged - Not working</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-question-circle"></i> Reason for Handover *</label>
                            <textarea name="handover_reason" rows="3" class="form-control" required placeholder="Why is this handover needed?"></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Request</button>
                            <button type="reset" class="btn-reset"><i class="fas fa-undo"></i> Reset</button>
                            <a href="index.php" class="btn-reset"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <div class="container"><div class="copyright"><p>&copy; <?php echo date('Y'); ?> IT Inventory Management System. All rights reserved.</p></div></div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for employee search
            $('.select2-employee').select2({
                placeholder: "Search employee by PF No, Name, or Phone",
                allowClear: true,
                width: '100%'
            });
            
            // Store devices data
            let devicesData = {};
            
            // Fetch devices for selected employee
            $('#employeeSelect').on('change', function() {
                const employeeId = $(this).val();
                const selectedOption = $(this).find('option:selected');
                
                if(employeeId) {
                    // Show employee info
                    $('#displayPf').text(selectedOption.data('pf'));
                    $('#displayName').text(selectedOption.data('name'));
                    $('#displayDesignation').text(selectedOption.data('designation') || 'N/A');
                    $('#displayLocation').text(selectedOption.data('location') || 'N/A');
                    $('#displayPhone').text(selectedOption.data('phone') || 'N/A');
                    $('#displayEmail').text(selectedOption.data('email') || 'N/A');
                    $('#employeeInfo').show();
                    
                    // Fetch devices for this employee
                    $.ajax({
                        url: 'get_employee_devices.php',
                        type: 'POST',
                        data: { employee_id: employeeId },
                        dataType: 'json',
                        success: function(response) {
                            const deviceSelect = $('#deviceSelect');
                            deviceSelect.empty();
                            deviceSelect.append('<option value="">-- Select Device --</option>');
                            
                            if(response.success && response.devices.length > 0) {
                                devicesData = response.devices;
                                $.each(response.devices, function(index, device) {
                                    deviceSelect.append('<option value="' + device.assignment_id + '" ' +
                                        'data-item-code="' + device.item_code + '" ' +
                                        'data-item-name="' + device.name + '" ' +
                                        'data-serial="' + (device.serial_number || 'N/A') + '" ' +
                                        'data-assignment-no="' + device.assignment_no + '" ' +
                                        'data-assigned-date="' + device.assigned_date + '">' +
                                        device.item_code + ' - ' + device.name + ' (Assigned: ' + device.assigned_date + ')</option>');
                                });
                                deviceSelect.prop('disabled', false);
                            } else {
                                deviceSelect.append('<option value="">No devices assigned to this employee</option>');
                                deviceSelect.prop('disabled', true);
                                $('#deviceInfo').hide();
                            }
                        },
                        error: function() {
                            alert('Error loading devices. Please try again.');
                        }
                    });
                } else {
                    $('#employeeInfo').hide();
                    $('#deviceInfo').hide();
                    $('#deviceSelect').prop('disabled', true);
                    $('#deviceSelect').html('<option value="">-- First Select Employee --</option>');
                }
            });
            
            // Show device info when device selected
            $('#deviceSelect').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const assignmentId = $(this).val();
                
                if(assignmentId) {
                    $('#displayItemCode').text(selectedOption.data('item-code'));
                    $('#displayDeviceName').text(selectedOption.data('item-name'));
                    $('#displaySerial').text(selectedOption.data('serial'));
                    $('#displayAssignmentNo').text(selectedOption.data('assignment-no'));
                    $('#displayAssignedDate').text(selectedOption.data('assigned-date'));
                    $('#deviceInfo').show();
                } else {
                    $('#deviceInfo').hide();
                }
            });
        });
    </script>
</body>
</html>