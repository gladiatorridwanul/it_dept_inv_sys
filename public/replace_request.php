<?php
require_once '../config/database.php';
require_once '../includes/request_functions.php';

$success = '';
$error = '';

// Create upload directory
$upload_dir = 'uploads/replacements/';
if(!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$employees = $pdo->query("SELECT id, pf_no, full_name, designation, department, phone, email FROM employees WHERE is_active=1 ORDER BY full_name")->fetchAll();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = $_POST['employee_id'];
    $assignment_id = $_POST['assignment_id'];
    $faulty_device_id = $_POST['faulty_device_id'];
    $replacement_needed = $_POST['replacement_needed'];
    $fault_type = $_POST['fault_type'];
    $issue_description = $_POST['issue_description'];
    $submission_date = date('Y-m-d H:i:s');
    
    // Handle file upload
    $attachment = null;
    if(isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $filename = $_FILES['attachment']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = 'REPLACE_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['attachment']['tmp_name'], $destination)) {
                $attachment = 'uploads/replacements/' . $new_filename;
            }
        }
    }
    
    $stmt = $pdo->prepare("SELECT a.*, i.name as item_name, i.item_code, i.serial_number, i.specification 
                           FROM assignments a 
                           JOIN items i ON a.item_id = i.id 
                           WHERE a.id = ? AND a.employee_id = ?");
    $stmt->execute([$assignment_id, $employee_id]);
    $assignment = $stmt->fetch();
    
    if($assignment) {
        $request_no = generateNumber('RPL-', 'requests', 'request_no');
        
        $request_data = json_encode([
            'assignment_no' => $assignment['assignment_no'],
            'faulty_device_name' => $assignment['item_name'],
            'faulty_item_code' => $assignment['item_code'],
            'faulty_serial' => $assignment['serial_number'],
            'fault_type' => $fault_type
        ]);
        
        $description = "Device Replacement Request\n";
        $description .= "Assignment No: " . $assignment['assignment_no'] . "\n";
        $description .= "Faulty Device: " . $assignment['item_name'] . "\n";
        $description .= "Item Code: " . $assignment['item_code'] . "\n";
        $description .= "Serial Number: " . $assignment['serial_number'] . "\n";
        $description .= "Fault Type: $fault_type\n";
        $description .= "Issue Description: $issue_description\n";
        $description .= "Replacement Needed: $replacement_needed";
        
        $pdo->beginTransaction();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO requests (request_no, employee_id, request_type, description, request_data, urgency_level, requested_date, status, notes) 
                                  VALUES (?, ?, 'replace', ?, ?, 'high', ?, 'pending', ?)");
            $stmt->execute([$request_no, $employee_id, $description, $request_data, $submission_date, $attachment]);
            $request_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO replacement_requests (request_id, faulty_device_id, replacement_device_id, issue_description, replacement_needed, fault_type) 
                                  VALUES (?, ?, NULL, ?, ?, ?)");
            $stmt->execute([$request_id, $assignment['item_id'], $issue_description, $replacement_needed, $fault_type]);
            
            $pdo->commit();
            $success = "Replacement request submitted successfully! Request No: " . $request_no;
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
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
    <title>Device Replacement Request</title>
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
        .form-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 20px 40px rgba(0,0,0,0.08); margin-bottom: 30px; max-width: 900px; margin: 0 auto 30px; }
        .form-header { text-align: center; margin-bottom: 30px; }
        .form-header h2 { font-size: 1.8rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .form-header .underline { width: 80px; height: 4px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); margin: 15px auto 0; border-radius: 2px; }
        .form-label { font-weight: 600; color: #334155; margin-bottom: 8px; }
        .form-control, .form-select { border: 2px solid #e2e8f0; border-radius: 12px; padding: 12px 15px; transition: all 0.3s; background: white; color: #1e293b; width: 100%; }
        .form-control:focus, .form-select:focus { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.1); outline: none; }
        .btn-submit { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 12px 35px; border-radius: 50px; font-weight: 600; transition: all 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(245,158,11,0.4); }
        .btn-reset { background: #e2e8f0; color: #475569; border: none; padding: 12px 35px; border-radius: 50px; font-weight: 600; margin-left: 15px; transition: all 0.3s; }
        .btn-reset:hover { background: #cbd5e1; }
        footer { background: #1e293b; color: white; padding: 30px 0; margin-top: 60px; }
        .copyright { text-align: center; color: #94a3b8; font-size: 0.9rem; }
        .device-card { background: #fffbeb; border-radius: 12px; padding: 15px; margin: 15px 0; display: none; border-left: 4px solid #f59e0b; }
        .upload-area { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s; background: white; }
        .upload-area:hover { border-color: #f59e0b; background: #fffbeb; }
        
        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            height: 48px !important;
            background: white !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 44px !important;
            color: #1e293b !important;
            padding-left: 15px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px !important;
            right: 10px !important;
        }
        .select2-dropdown {
            border: 2px solid #e2e8f0 !important;
            border-radius: 12px !important;
            background: white !important;
        }
        .select2-search__field {
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            margin: 8px !important;
            width: calc(100% - 16px) !important;
        }
        .select2-results__option {
            padding: 10px 15px !important;
            color: #1e293b !important;
            background: white !important;
        }
        .select2-results__option--highlighted {
            background: #f1f5f9 !important;
            color: #1e293b !important;
        }
        
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
                    <li class="nav-item"><a class="nav-link" href="track_request.php">Track Request</a></li>
                    <li class="nav-item"><a class="btn btn-login" href="../modules/login.php"><i class="fas fa-lock"></i> Staff Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-exchange-alt"></i> Device Replacement Request</h2>
                    <div class="underline"></div>
                    <p class="text-muted mt-2">Request replacement for faulty or damaged devices</p>
                </div>

                <?php if($success): ?>
                    <div class="alert alert-success text-center"><?php echo $success; ?></div>
                    <div class="text-center"><a href="index.php" class="btn btn-submit">Submit Another Request</a></div>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Employee Selection - EMPTY by default, search by PF or Name -->
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-user"></i> Select Employee *</label>
                                <select name="employee_id" class="form-select select2-employee" id="employeeSelect" required style="width: 100%;">
                                    <!-- EMPTY by default - no options shown until search -->
                                </select>
                                <small class="text-muted">Type employee name or PF number to search</small>
                            </div>
                            <!-- Faulty Device Selection - Shows only assigned devices -->
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-microchip"></i> Faulty Device *</label>
                                <select name="assignment_id" class="form-select" id="deviceSelect" required disabled style="width: 100%;">
                                    <option value="">-- First Select Employee --</option>
                                </select>
                                <small class="text-muted">Shows devices currently assigned to this employee</small>
                            </div>
                        </div>

                        <!-- Device Information Display -->
                        <div id="deviceInfo" class="device-card">
                            <div class="row">
                                <div class="col-md-4"><small class="text-muted">Item Code</small><br><strong id="displayItemCode"></strong></div>
                                <div class="col-md-4"><small class="text-muted">Device Name</small><br><span id="displayDeviceName"></span></div>
                                <div class="col-md-4"><small class="text-muted">Serial Number</small><br><span id="displaySerial"></span></div>
                                <div class="col-md-12 mt-2"><small class="text-muted">Specifications</small><br><span id="displaySpecs"></span></div>
                                <div class="col-md-6 mt-2"><small class="text-muted">Assignment No</small><br><span id="displayAssignmentNo"></span></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label"><i class="fas fa-bug"></i> Fault Type *</label>
                                <select name="fault_type" class="form-select" required>
                                    <option value="">Select Fault Type</option>
                                    <option value="hardware">🖥️ Hardware Failure</option>
                                    <option value="software">💻 Software Issue</option>
                                    <option value="physical">🔨 Physical Damage</option>
                                    <option value="power">⚡ Power Issue</option>
                                    <option value="network">🌐 Network/Connectivity</option>
                                    <option value="other">❓ Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-exclamation-triangle"></i> Issue Description *</label>
                            <textarea name="issue_description" rows="3" class="form-control" required placeholder="Describe the issue with the device in detail..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-box"></i> Replacement Needed *</label>
                            <textarea name="replacement_needed" rows="2" class="form-control" required placeholder="Specify the replacement device/model/configuration required"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label"><i class="fas fa-paperclip"></i> Upload Supporting Document</label>
                            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color: #f59e0b;"></i>
                                <p>Click to upload or drag and drop</p>
                                <small class="text-muted">Supported: JPG, PNG, PDF, DOC (Max 5MB)</small>
                                <input type="file" name="attachment" id="fileInput" style="display:none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                            </div>
                            <div id="fileName" class="mt-2 text-muted small"></div>
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
        <div class="container"><div class="copyright"><p>&copy; <?php echo date('Y'); ?> IT Inventory Management System</p></div></div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for employee search - EMPTY by default, search by PF or Name
            $('.select2-employee').select2({
                placeholder: "Type employee name or PF number to search",
                allowClear: true,
                width: '100%',
                minimumInputLength: 1,
                ajax: {
                    url: 'search_employees.php',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            term: params.term
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                },
                templateResult: formatEmployeeResult,
                templateSelection: formatEmployeeSelection
            });
            
            function formatEmployeeResult(employee) {
                if (employee.loading) return employee.text;
                if (!employee.id) return employee.text;
                return $('<span>' + employee.text + '</span>');
            }
            
            function formatEmployeeSelection(employee) {
                if (!employee.id) return employee.text;
                return employee.text;
            }
            
            // Employee selection handler - fetch assigned devices only
            $('#employeeSelect').on('change', function() {
                const employeeId = $(this).val();
                if(employeeId) {
                    $.ajax({
                        url: 'get_assigned_devices.php',
                        type: 'POST',
                        data: { employee_id: employeeId },
                        dataType: 'json',
                        success: function(response) {
                            const deviceSelect = $('#deviceSelect');
                            deviceSelect.empty();
                            deviceSelect.append('<option value="">-- Select Faulty Device --</option>');
                            if(response.success && response.devices.length > 0) {
                                $.each(response.devices, function(index, device) {
                                    deviceSelect.append('<option value="' + device.assignment_id + '" ' +
                                        'data-item-code="' + device.item_code + '" ' +
                                        'data-item-name="' + device.name + '" ' +
                                        'data-serial="' + (device.serial_number || 'N/A') + '" ' +
                                        'data-specs="' + (device.specification || 'N/A') + '" ' +
                                        'data-assignment-no="' + device.assignment_no + '">' +
                                        device.item_code + ' - ' + device.name + '</option>');
                                });
                                deviceSelect.prop('disabled', false);
                            } else {
                                deviceSelect.append('<option value="">No assigned devices found</option>');
                                deviceSelect.prop('disabled', true);
                                $('#deviceInfo').hide();
                            }
                        },
                        error: function() {
                            alert('Error loading devices. Please try again.');
                        }
                    });
                } else {
                    $('#deviceInfo').hide();
                    $('#deviceSelect').prop('disabled', true);
                    $('#deviceSelect').html('<option value="">-- First Select Employee --</option>');
                }
            });
            
            // Show device info when device selected
            $('#deviceSelect').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                if($(this).val()) {
                    $('#displayItemCode').text(selectedOption.data('item-code'));
                    $('#displayDeviceName').text(selectedOption.data('item-name'));
                    $('#displaySerial').text(selectedOption.data('serial'));
                    $('#displaySpecs').text(selectedOption.data('specs') || 'No specifications available');
                    $('#displayAssignmentNo').text(selectedOption.data('assignment-no'));
                    $('#deviceInfo').show();
                } else {
                    $('#deviceInfo').hide();
                }
            });
            
            // File upload handler
            $('#fileInput').on('change', function() {
                if(this.files && this.files[0]) {
                    $('#fileName').text('Selected: ' + this.files[0].name);
                }
            });
        });
    </script>
</body>
</html>