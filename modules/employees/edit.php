<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$id]);
$employee = $stmt->fetch();

if(!$employee) {
    redirect('list.php');
}

// Fetch distinct departments, companies, and designations from database for dropdowns
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$companies = $pdo->query("SELECT DISTINCT company FROM employees WHERE company IS NOT NULL AND company != '' ORDER BY company")->fetchAll(PDO::FETCH_COLUMN);
$designations = $pdo->query("SELECT DISTINCT designation FROM employees WHERE designation IS NOT NULL AND designation != '' ORDER BY designation")->fetchAll(PDO::FETCH_COLUMN);

$success = false;
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
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
    if(empty($full_name)) $errors[] = "Employee Name is required";
    if(empty($email)) $errors[] = "Email is required";
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if(!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) $errors[] = "Phone number must be 11 digits";
    
    // Check if email exists for another employee
    $checkStmt = $pdo->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
    $checkStmt->execute([$email, $id]);
    if($checkStmt->fetch()) {
        $errors[] = "Email address already exists for another employee!";
    }
    
    // Validate joining date format (01-SEP-2019)
    $joining_date_formatted = null;
    if(!empty($joining_date)) {
        $date_obj = DateTime::createFromFormat('d-M-Y', $joining_date);
        if($date_obj) {
            $joining_date_formatted = $date_obj->format('Y-m-d');
        } else {
            $errors[] = "Joining Date must be in format: 01-SEP-2019";
        }
    } else {
        $joining_date_formatted = $employee['joining_date'];
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("UPDATE employees SET full_name=?, designation=?, job_location=?, company=?, department=?, phone=?, email=?, joining_date=? WHERE id=?");
        
        if($stmt->execute([$full_name, $designation, $job_location, $company, $department, $phone, $email, $joining_date_formatted, $id])) {
            $success = true;
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Employee updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            // Refresh employee data
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
        } else {
            $error = "Error updating employee!";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Format joining date for display
$display_joining_date = $employee['joining_date'] ? date('d-M-Y', strtotime($employee['joining_date'])) : '';
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
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .dynamic-option {
        font-size: 0.9rem;
    }
    .existing-badge {
        display: inline-block;
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        margin-left: 5px;
        color: #6c757d;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-user-edit text-warning"></i> Edit Employee</h2>
                    <p class="text-muted">Update employee information</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="assignments.php?id=<?php echo $employee['id']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-laptop"></i> View Assets
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
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Employee Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="employeeForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">PF Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($employee['pf_no']); ?>" readonly disabled>
                                    <input type="hidden" name="pf_no" value="<?php echo $employee['pf_no']; ?>">
                                </div>
                                <small class="text-muted">PF Number cannot be changed</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Employee Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="full_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($employee['full_name']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                    <select name="designation" class="form-select">
                                        <option value="">Select Designation</option>
                                        <optgroup label="Common Designations">
                                            <option value="Software Engineer" <?php echo $employee['designation'] == 'Software Engineer' ? 'selected' : ''; ?>>Software Engineer</option>
                                            <option value="Senior Software Engineer" <?php echo $employee['designation'] == 'Senior Software Engineer' ? 'selected' : ''; ?>>Senior Software Engineer</option>
                                            <option value="Team Lead" <?php echo $employee['designation'] == 'Team Lead' ? 'selected' : ''; ?>>Team Lead</option>
                                            <option value="Project Manager" <?php echo $employee['designation'] == 'Project Manager' ? 'selected' : ''; ?>>Project Manager</option>
                                            <option value="HR Executive" <?php echo $employee['designation'] == 'HR Executive' ? 'selected' : ''; ?>>HR Executive</option>
                                            <option value="HR Manager" <?php echo $employee['designation'] == 'HR Manager' ? 'selected' : ''; ?>>HR Manager</option>
                                            <option value="Accountant" <?php echo $employee['designation'] == 'Accountant' ? 'selected' : ''; ?>>Accountant</option>
                                            <option value="Finance Manager" <?php echo $employee['designation'] == 'Finance Manager' ? 'selected' : ''; ?>>Finance Manager</option>
                                            <option value="Network Administrator" <?php echo $employee['designation'] == 'Network Administrator' ? 'selected' : ''; ?>>Network Administrator</option>
                                            <option value="System Administrator" <?php echo $employee['designation'] == 'System Administrator' ? 'selected' : ''; ?>>System Administrator</option>
                                            <option value="IT Support" <?php echo $employee['designation'] == 'IT Support' ? 'selected' : ''; ?>>IT Support</option>
                                            <option value="Sales Executive" <?php echo $employee['designation'] == 'Sales Executive' ? 'selected' : ''; ?>>Sales Executive</option>
                                            <option value="Marketing Specialist" <?php echo $employee['designation'] == 'Marketing Specialist' ? 'selected' : ''; ?>>Marketing Specialist</option>
                                            <option value="Operations Manager" <?php echo $employee['designation'] == 'Operations Manager' ? 'selected' : ''; ?>>Operations Manager</option>
                                        </optgroup>
                                        <?php if(count($designations) > 0): ?>
                                        <optgroup label="Existing Designations from Database">
                                            <?php foreach($designations as $desig): 
                                                // Skip if already in common list to avoid duplication
                                                $commonDesignations = ['Software Engineer', 'Senior Software Engineer', 'Team Lead', 'Project Manager', 'HR Executive', 'HR Manager', 'Accountant', 'Finance Manager', 'Network Administrator', 'System Administrator', 'IT Support', 'Sales Executive', 'Marketing Specialist', 'Operations Manager'];
                                                if(in_array($desig, $commonDesignations)) continue;
                                            ?>
                                                <option value="<?php echo htmlspecialchars($desig); ?>" class="dynamic-option" <?php echo $employee['designation'] == $desig ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($desig); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select from existing or common options</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <select name="department" class="form-select">
                                        <option value="">Select Department</option>
                                        <optgroup label="Common Departments">
                                            <option value="IT" <?php echo $employee['department'] == 'IT' ? 'selected' : ''; ?>>Information Technology</option>
                                            <option value="HR" <?php echo $employee['department'] == 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                                            <option value="Finance" <?php echo $employee['department'] == 'Finance' ? 'selected' : ''; ?>>Finance & Accounts</option>
                                            <option value="Sales" <?php echo $employee['department'] == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                            <option value="Marketing" <?php echo $employee['department'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                            <option value="Operations" <?php echo $employee['department'] == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                            <option value="Administration" <?php echo $employee['department'] == 'Administration' ? 'selected' : ''; ?>>Administration</option>
                                            <option value="Customer Support" <?php echo $employee['department'] == 'Customer Support' ? 'selected' : ''; ?>>Customer Support</option>
                                            <option value="R&D" <?php echo $employee['department'] == 'R&D' ? 'selected' : ''; ?>>Research & Development</option>
                                        </optgroup>
                                        <?php if(count($departments) > 0): ?>
                                        <optgroup label="Existing Departments from Database">
                                            <?php foreach($departments as $dept):
                                                $commonDepts = ['IT', 'HR', 'Finance', 'Sales', 'Marketing', 'Operations', 'Administration', 'Customer Support', 'R&D'];
                                                if(in_array($dept, $commonDepts)) continue;
                                            ?>
                                                <option value="<?php echo htmlspecialchars($dept); ?>" class="dynamic-option" <?php echo $employee['department'] == $dept ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select from existing or common options</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Job Location</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" name="job_location" class="form-control"
                                           value="<?php echo htmlspecialchars($employee['job_location']); ?>"
                                           placeholder="e.g., Corporate Office, Dhaka, Chattogram">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <select name="company" class="form-select">
                                        <option value="">Select Company</option>
                                        <optgroup label="Common Companies">
                                            <option value="UniMed Limited" <?php echo ($employee['company'] ?? '') == 'UniMed Limited' ? 'selected' : ''; ?>>UniMed Limited</option>
                                            <option value="UniHealth Limited" <?php echo ($employee['company'] ?? '') == 'UniHealth Limited' ? 'selected' : ''; ?>>UniHealth Limited</option>
                                            <option value="UniMed UniHealth Pharmaceuticals Limited" <?php echo ($employee['company'] ?? '') == 'UniMed UniHealth Pharmaceuticals Limited' ? 'selected' : ''; ?>>UniMed UniHealth Pharmaceuticals Limited</option>
                                            <option value="BioMed Diagnostic Limited" <?php echo ($employee['company'] ?? '') == 'BioMed Diagnostic Limited' ? 'selected' : ''; ?>>BioMed Diagnostic Limited</option>
                                            <option value="BioMed Pharmacy Limited" <?php echo ($employee['company'] ?? '') == 'BioMed Pharmacy Limited' ? 'selected' : ''; ?>>BioMed Pharmacy Limited</option>
                                            <option value="UniMed UniHealth Fine Chemicals Limited" <?php echo ($employee['company'] ?? '') == 'UniMed UniHealth Fine Chemicals Limited' ? 'selected' : ''; ?>>UniMed UniHealth Fine Chemicals Limited</option>
                                            <option value="UniAgrovet Limited" <?php echo ($employee['company'] ?? '') == 'UniAgrovet Limited' ? 'selected' : ''; ?>>UniAgrovet Limited</option>
                                        </optgroup>
                                        <?php if(count($companies) > 0): ?>
                                        <optgroup label="Existing Companies from Database">
                                            <?php foreach($companies as $comp):
                                                $commonCompanies = ['UniMed Limited', 'UniHealth Limited', 'UniMed UniHealth Pharmaceuticals Limited', 'BioMed Diagnostic Limited', 'BioMed Pharmacy Limited', 'UniMed UniHealth Fine Chemicals Limited', 'UniAgrovet Limited'];
                                                if(in_array($comp, $commonCompanies)) continue;
                                            ?>
                                                <option value="<?php echo htmlspecialchars($comp); ?>" class="dynamic-option" <?php echo ($employee['company'] ?? '') == $comp ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($comp); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <small class="text-muted">Select from existing or common options</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="phone" class="form-control" 
                                           pattern="[0-9]{11}" maxlength="11"
                                           value="<?php echo htmlspecialchars($employee['phone']); ?>"
                                           placeholder="01929993029">
                                </div>
                                <small class="text-muted">11-digit mobile number</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" required
                                           value="<?php echo htmlspecialchars($employee['email']); ?>"
                                           placeholder="employee@company.com">
                                </div>
                                <small class="text-muted">Must be unique across all employees</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Joining Date</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    <input type="text" name="joining_date" class="form-control datepicker" 
                                           placeholder="01-SEP-2019"
                                           value="<?php echo $display_joining_date; ?>">
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
                                <i class="fas fa-save"></i> Update Employee
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
            <div class="card shadow-sm info-card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($employee['full_name']); ?></h5>
                    <hr class="bg-light">
                    <p class="mb-1"><i class="fas fa-id-card"></i> <strong>PF No:</strong> <?php echo $employee['pf_no']; ?></p>
                    <p class="mb-1"><i class="fas fa-briefcase"></i> <strong>Status:</strong> 
                        <span class="badge bg-<?php echo $employee['is_active'] ? 'success' : 'danger'; ?>">
                            <?php echo $employee['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </p>
                    <?php if($employee['designation']): ?>
                    <p class="mb-0 mt-2"><i class="fas fa-tag"></i> <strong>Current Designation:</strong> <?php echo htmlspecialchars($employee['designation']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="assignments.php?id=<?php echo $employee['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-laptop"></i> View Assigned Devices
                        </a>
                        <a href="../assignments/assign.php?emp_id=<?php echo $employee['id']; ?>" class="btn btn-outline-success">
                            <i class="fas fa-plus"></i> Assign New Device
                        </a>
                        <a href="../requests/my_requests.php?emp_id=<?php echo $employee['id']; ?>" class="btn btn-outline-info">
                            <i class="fas fa-tasks"></i> View Requests
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-light text-dark">
                    <h6 class="mb-0"><i class="fas fa-database"></i> Database Reference Values</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <strong>Departments in DB:</strong>
                        <div class="mt-1">
                            <?php foreach(array_slice($departments, 0, 5) as $dept): ?>
                                <span class="badge bg-secondary me-1 mb-1"><?php echo htmlspecialchars($dept); ?></span>
                            <?php endforeach; ?>
                            <?php if(count($departments) > 5): ?>
                                <span class="badge bg-light text-dark">+<?php echo count($departments) - 5; ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <strong>Companies in DB:</strong>
                        <div class="mt-1">
                            <?php foreach(array_slice($companies, 0, 4) as $comp): ?>
                                <div><i class="fas fa-building-o text-info"></i> <?php echo htmlspecialchars($comp); ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="text-muted mt-2">
                        <i class="fas fa-info-circle"></i> Dropdowns show existing values for data consistency
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

// Form validation
document.getElementById('employeeForm')?.addEventListener('submit', function(e) {
    var phone = document.querySelector('input[name="phone"]').value;
    if(phone && !/^\d{11}$/.test(phone)) {
        alert('Phone number must be exactly 11 digits');
        e.preventDefault();
        return false;
    }
    
    var email = document.querySelector('input[name="email"]').value;
    if(email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address');
        e.preventDefault();
        return false;
    }
    
    var fullName = document.querySelector('input[name="full_name"]').value.trim();
    if(fullName === "") {
        alert('Employee Name is required');
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

<?php include '../../includes/footer.php'; ?>