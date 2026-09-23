<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$success = false;
$error = '';

// Fetch distinct departments, companies, and designations from database for dropdowns
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$companies = $pdo->query("SELECT DISTINCT company FROM employees WHERE company IS NOT NULL AND company != '' ORDER BY company")->fetchAll(PDO::FETCH_COLUMN);
$designations = $pdo->query("SELECT DISTINCT designation FROM employees WHERE designation IS NOT NULL AND designation != '' ORDER BY designation")->fetchAll(PDO::FETCH_COLUMN);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pf_no = trim($_POST['pf_no']);
    $full_name = trim($_POST['full_name']);
    $designation = trim($_POST['designation']);
    $job_location = trim($_POST['job_location']);
    $company = trim($_POST['company']);
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $joining_date = $_POST['joining_date'];
    
    // Validate
    $errors = [];
    if(empty($pf_no)) $errors[] = "PF Number is required";
    if(empty($full_name)) $errors[] = "Employee Name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if(!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) $errors[] = "Phone number must be 11 digits";
    
    // Validate joining date format (01-SEP-2019)
    $joining_date_formatted = null;
    if(!empty($joining_date)) {
        $date_obj = DateTime::createFromFormat('d-M-Y', $joining_date);
        if($date_obj) {
            $joining_date_formatted = $date_obj->format('Y-m-d');
        } else {
            $errors[] = "Joining Date must be in format: 01-SEP-2019";
        }
    }
    
    if(empty($errors)) {
        // Check if PF No already exists
        $stmt = $pdo->prepare("SELECT id FROM employees WHERE pf_no = ?");
        $stmt->execute([$pf_no]);
        if($stmt->fetch()) {
            $error = "PF Number already exists! Please use a unique PF Number.";
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
            $stmt->execute([$email]);
            if($stmt->fetch()) {
                $error = "Email address already exists! Please use a different email.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO employees (pf_no, full_name, designation, job_location, company, department, phone, email, joining_date, created_at) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                
                if($stmt->execute([$pf_no, $full_name, $designation, $job_location, $company, $department, $phone, $email, $joining_date_formatted])) {
                    $success = true;
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> Employee added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    echo '<script>setTimeout(function(){ window.location.href = "list.php"; }, 1500);</script>';
                } else {
                    $error = "Error adding employee!";
                }
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    .required-field::after {
        content: " *";
        color: red;
        font-weight: bold;
    }
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .preview-card {
        background: #f8f9fa;
        border-left: 4px solid #28a745;
    }
    .dynamic-option {
        font-size: 0.9rem;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-user-plus text-primary"></i> Add New Employee</h2>
                    <p class="text-muted">Fill in the employee details to register them in the system</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Employee Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="employeeForm" onsubmit="return validateForm()">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">PF Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" name="pf_no" id="pf_no" class="form-control" 
                                           placeholder="e.g., 100003 or EMP001" required
                                           value="<?php echo isset($_POST['pf_no']) ? htmlspecialchars($_POST['pf_no']) : ''; ?>">
                                </div>
                                <small class="text-muted">Unique identifier - This cannot be duplicated</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Employee Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="full_name" id="full_name" class="form-control" 
                                           placeholder="Full name as per official records" required
                                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                    <select name="designation" class="form-select">
                                        <option value="">Select Designation</option>
                                        <optgroup label="Common Designations">
                                            <option value="Software Engineer">Software Engineer</option>
                                            <option value="Senior Software Engineer">Senior Software Engineer</option>
                                            <option value="Team Lead">Team Lead</option>
                                            <option value="Project Manager">Project Manager</option>
                                            <option value="HR Executive">HR Executive</option>
                                            <option value="HR Manager">HR Manager</option>
                                            <option value="Accountant">Accountant</option>
                                            <option value="Finance Manager">Finance Manager</option>
                                            <option value="Network Administrator">Network Administrator</option>
                                            <option value="System Administrator">System Administrator</option>
                                            <option value="IT Support">IT Support</option>
                                            <option value="Sales Executive">Sales Executive</option>
                                            <option value="Marketing Specialist">Marketing Specialist</option>
                                            <option value="Operations Manager">Operations Manager</option>
                                        </optgroup>
                                        <?php if(count($designations) > 0): ?>
                                        <optgroup label="Existing Designations">
                                            <?php foreach($designations as $desig): ?>
                                                <option value="<?php echo htmlspecialchars($desig); ?>" class="dynamic-option">
                                                    <?php echo htmlspecialchars($desig); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select from existing or choose common option</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <select name="department" class="form-select">
                                        <option value="">Select Department</option>
                                        <optgroup label="Common Departments">
                                            <option value="IT">Information Technology</option>
                                            <option value="HR">Human Resources</option>
                                            <option value="Finance">Finance & Accounts</option>
                                            <option value="Sales">Sales</option>
                                            <option value="Marketing">Marketing</option>
                                            <option value="Operations">Operations</option>
                                            <option value="Administration">Administration</option>
                                            <option value="Customer Support">Customer Support</option>
                                            <option value="R&D">Research & Development</option>
                                        </optgroup>
                                        <?php if(count($departments) > 0): ?>
                                        <optgroup label="Existing Departments">
                                            <?php foreach($departments as $dept): ?>
                                                <option value="<?php echo htmlspecialchars($dept); ?>" class="dynamic-option">
                                                    <?php echo htmlspecialchars($dept); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select from existing or choose common option</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Job Location</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" name="job_location" class="form-control" 
                                           placeholder="e.g., Corporate Office, Dhaka, Chattogram"
                                           value="<?php echo isset($_POST['job_location']) ? htmlspecialchars($_POST['job_location']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <select name="company" class="form-select">
                                        <option value="">Select Company</option>
                                        <optgroup label="Common Companies">
                                            <option value="UniMed Limited">UniMed Limited</option>
                                            <option value="UniHealth Limited">UniHealth Limited</option>
                                            <option value="UniMed UniHealth Pharmaceuticals Limited">UniMed UniHealth Pharmaceuticals Limited</option>
                                            <option value="BioMed Diagnostic Limited">BioMed Diagnostic Limited</option>
                                            <option value="BioMed Pharmacy Limited">BioMed Pharmacy Limited</option>
                                            <option value="UniMed UniHealth Fine Chemicals Limited">UniMed UniHealth Fine Chemicals Limited</option>
                                            <option value="UniAgrovet Limited">UniAgrovet Limited</option>
                                        </optgroup>
                                        <?php if(count($companies) > 0): ?>
                                        <optgroup label="Existing Companies">
                                            <?php foreach($companies as $comp): ?>
                                                <option value="<?php echo htmlspecialchars($comp); ?>" class="dynamic-option">
                                                    <?php echo htmlspecialchars($comp); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select from existing or choose common option</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="phone" id="phone" class="form-control" 
                                           placeholder="11-digit mobile number"
                                           pattern="[0-9]{11}" maxlength="11"
                                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                </div>
                                <small class="text-muted">Example: 01929993029 (11 digits)</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" id="email" class="form-control" 
                                           placeholder="employee@company.com" required
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                </div>
                                <small class="text-muted">Official email address - Must be unique</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="text" name="joining_date" class="form-control datepicker" 
                                           placeholder="01-SEP-2019"
                                           value="<?php echo isset($_POST['joining_date']) ? htmlspecialchars($_POST['joining_date']) : ''; ?>">
                                </div>
                                <small class="text-muted">Format: <strong>DD-MON-YYYY</strong> (e.g., 01-SEP-2019)</small>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Save Employee
                            </button>
                            <a href="list.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm preview-card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Quick Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> PF Number must be unique - duplicate not allowed</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Email address must be unique across all employees</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Use official email address</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Joining Date format: <code>DD-MON-YYYY</code></li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Phone number: 11 digits only</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> All fields marked with * are mandatory</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Dropdowns show existing values for consistency</li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Stats</h5>
                </div>
                <div class="card-body">
                    <?php
                    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
                    $total = $totalStmt->fetch()['total'];
                    $activeStmt = $pdo->query("SELECT COUNT(*) as active FROM employees WHERE is_active = 1");
                    $active = $activeStmt->fetch()['active'];
                    ?>
                    <div class="d-flex justify-content-between">
                        <span>Total Employees:</span>
                        <strong><?php echo $total; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Active Employees:</span>
                        <strong class="text-success"><?php echo $active; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Departments:</span>
                        <strong><?php echo count($departments); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <span>Companies:</span>
                        <strong><?php echo count($companies); ?></strong>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-database"></i> Existing Records</h5>
                </div>
                <div class="card-body">
                    <div class="small">
                        <strong>Departments (<?php echo count($departments); ?>):</strong>
                        <div class="mb-2 mt-1" style="max-height: 80px; overflow-y: auto;">
                            <?php foreach(array_slice($departments, 0, 8) as $dept): ?>
                                <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($dept); ?></span>
                            <?php endforeach; ?>
                            <?php if(count($departments) > 8): ?>
                                <span class="badge bg-light text-dark">+<?php echo count($departments) - 8; ?> more</span>
                            <?php endif; ?>
                        </div>
                        <strong>Companies (<?php echo count($companies); ?>):</strong>
                        <div class="mt-1" style="max-height: 60px; overflow-y: auto;">
                            <?php foreach(array_slice($companies, 0, 5) as $comp): ?>
                                <div><i class="fas fa-building-o text-info"></i> <?php echo htmlspecialchars($comp); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
$(function() {
    $(".datepicker").datepicker({
        dateFormat: "dd-M-yy",
        changeMonth: true,
        changeYear: true,
        yearRange: "1990:2030",
        onClose: function(dateText, inst) {
            var parts = dateText.split('-');
            if(parts.length === 3) {
                parts[1] = parts[1].toUpperCase();
                $(this).val(parts.join('-'));
            }
        }
    });
});

function validateForm() {
    var phone = document.getElementById('phone').value;
    if(phone && !/^\d{11}$/.test(phone)) {
        alert('Phone number must be exactly 11 digits');
        document.getElementById('phone').focus();
        return false;
    }
    
    var pf_no = document.getElementById('pf_no').value.trim();
    if(pf_no === "") {
        alert('PF Number is required');
        document.getElementById('pf_no').focus();
        return false;
    }
    
    var full_name = document.getElementById('full_name').value.trim();
    if(full_name === "") {
        alert('Employee Name is required');
        document.getElementById('full_name').focus();
        return false;
    }
    
    var email = document.getElementById('email').value.trim();
    if(email === "") {
        alert('Email Address is required');
        document.getElementById('email').focus();
        return false;
    }
    
    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(!emailPattern.test(email)) {
        alert('Please enter a valid email address');
        document.getElementById('email').focus();
        return false;
    }
    
    return true;
}
</script>

<?php include '../../includes/footer.php'; ?>