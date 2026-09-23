<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic Information
    $company_name = trim($_POST['company_name']);
    $vendor_name = trim($_POST['vendor_name']);
    $office_address = trim($_POST['office_address']);
    $office_phone = trim($_POST['office_phone']);
    $office_email = trim($_POST['office_email']);
    
    // Tax & License Information
    $tin_no = trim($_POST['tin_no']);
    $bin_no = trim($_POST['bin_no']);
    $trade_license_no = trim($_POST['trade_license_no']);
    $gst_no = trim($_POST['gst_no']);
    
    // Primary Contact Person
    $contact_person = trim($_POST['contact_person']);
    $contact_designation = trim($_POST['contact_designation']);
    $contact_phone = trim($_POST['contact_phone']);
    $contact_email = trim($_POST['contact_email']);
    $contact_address = trim($_POST['contact_address']);
    
    // Secondary Contact (Emergency)
    $secondary_contact_person = trim($_POST['secondary_contact_person']);
    $secondary_contact_designation = trim($_POST['secondary_contact_designation']);
    $secondary_contact_phone = trim($_POST['secondary_contact_phone']);
    $secondary_contact_email = trim($_POST['secondary_contact_email']);
    
    // Additional Information
    $website = trim($_POST['website']);
    $notes = trim($_POST['notes']);
    
    // Handle file upload
    $document_path = null;
    if(isset($_FILES['attached_document']) && $_FILES['attached_document']['error'] == 0) {
        $upload_dir = '../../uploads/vendors/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
        $file_ext = strtolower(pathinfo($_FILES['attached_document']['name'], PATHINFO_EXTENSION));
        
        if(in_array($file_ext, $allowed)) {
            $new_filename = 'VENDOR_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['attached_document']['tmp_name'], $destination)) {
                $document_path = 'uploads/vendors/' . $new_filename;
            }
        }
    }
    
    // Validation
    $errors = [];
    if(empty($vendor_name)) $errors[] = "Vendor name is required";
    if(!empty($office_email) && !filter_var($office_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid office email format";
    if(!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid contact person email format";
    if(!empty($office_phone) && !preg_match('/^[0-9]{10,15}$/', preg_replace('/[^0-9]/', '', $office_phone))) $errors[] = "Invalid office phone number";
    if(!empty($contact_phone) && !preg_match('/^[0-9]{10,15}$/', preg_replace('/[^0-9]/', '', $contact_phone))) $errors[] = "Invalid contact person phone number";
    
    // Check if vendor already exists
    if(empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM vendors WHERE vendor_name = ? OR (company_name = ? AND company_name != '')");
        $checkStmt->execute([$vendor_name, $company_name]);
        if($checkStmt->fetch()) {
            $error = "Vendor with this name already exists!";
        } else {
            // Merge address fields for database (keeping backward compatibility)
            $full_address = $office_address;
            
            $stmt = $pdo->prepare("INSERT INTO vendors (
                vendor_name, company_name, office_address, office_phone, office_email,
                tin_no, bin_no, trade_license_no, gst_no,
                contact_person, contact_designation, contact_phone, contact_email, contact_address,
                secondary_contact_person, secondary_contact_designation, 
                secondary_contact_phone, secondary_contact_email,
                website, notes, attached_document, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            
            if($stmt->execute([
                $vendor_name, $company_name, $office_address, $office_phone, $office_email,
                $tin_no, $bin_no, $trade_license_no, $gst_no,
                $contact_person, $contact_designation, $contact_phone, $contact_email, $contact_address,
                $secondary_contact_person, $secondary_contact_designation,
                $secondary_contact_phone, $secondary_contact_email,
                $website, $notes, $document_path
            ])) {
                $success = "Vendor added successfully!";
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ' . $success . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                echo '<script>setTimeout(function(){ window.location.href = "list.php"; }, 1500);</script>';
            } else {
                $error = "Error adding vendor!";
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get statistics
$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM vendors");
$total = $totalStmt->fetch()['total'];
$activeStmt = $pdo->query("SELECT COUNT(*) as active FROM vendors WHERE is_active = 1");
$active = $activeStmt->fetch()['active'];
?>

<style>
    .form-label {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .required-field::after {
        content: " *";
        color: red;
        font-weight: bold;
    }
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .section-title {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #2c3e50;
        border-left: 4px solid #667eea;
    }
    .section-title i {
        margin-right: 8px;
        color: #667eea;
    }
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .upload-area {
        border: 2px dashed #e2e8f0;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: #f8fafc;
    }
    .upload-area:hover {
        border-color: #667eea;
        background: #eff6ff;
    }
    @media (max-width: 768px) {
        .form-label {
            font-size: 0.7rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-plus-circle text-primary"></i> Add New Vendor</h2>
                    <p class="text-muted">Register a new vendor/supplier with complete information</p>
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
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Vendor Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="vendorForm">
                        <!-- Company Information Section -->
                        <div class="section-title">
                            <i class="fas fa-building"></i> Company Information
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Vendor Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                    <input type="text" name="vendor_name" class="form-control" 
                                           placeholder="Enter vendor/supplier name" required
                                           value="<?php echo isset($_POST['vendor_name']) ? htmlspecialchars($_POST['vendor_name']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <input type="text" name="company_name" class="form-control" 
                                           placeholder="Legal company name (if different)"
                                           value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Office Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea name="office_address" rows="2" class="form-control" 
                                              placeholder="Full office address"><?php echo isset($_POST['office_address']) ? htmlspecialchars($_POST['office_address']) : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Office Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                    <input type="tel" name="office_phone" class="form-control" 
                                           placeholder="Office contact number"
                                           value="<?php echo isset($_POST['office_phone']) ? htmlspecialchars($_POST['office_phone']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Office Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="office_email" class="form-control" 
                                           placeholder="office@vendor.com"
                                           value="<?php echo isset($_POST['office_email']) ? htmlspecialchars($_POST['office_email']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tax & License Information -->
                        <div class="section-title">
                            <i class="fas fa-file-invoice"></i> Tax & License Information
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">TIN Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" name="tin_no" class="form-control" 
                                           placeholder="Tax Identification Number"
                                           value="<?php echo isset($_POST['tin_no']) ? htmlspecialchars($_POST['tin_no']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BIN Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="bin_no" class="form-control" 
                                           placeholder="Business Identification Number"
                                           value="<?php echo isset($_POST['bin_no']) ? htmlspecialchars($_POST['bin_no']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trade License No</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-certificate"></i></span>
                                    <input type="text" name="trade_license_no" class="form-control" 
                                           placeholder="Trade license number"
                                           value="<?php echo isset($_POST['trade_license_no']) ? htmlspecialchars($_POST['trade_license_no']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">GST Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-file-invoice-dollar"></i></span>
                                    <input type="text" name="gst_no" class="form-control" 
                                           placeholder="GSTIN (if applicable)"
                                           value="<?php echo isset($_POST['gst_no']) ? htmlspecialchars($_POST['gst_no']) : ''; ?>">
                                </div>
                                <small class="text-muted">15-character GSTIN</small>
                            </div>
                        </div>
                        
                        <!-- Primary Contact Person -->
                        <div class="section-title">
                            <i class="fas fa-user-tie"></i> Primary Contact Person
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="contact_person" class="form-control" 
                                           placeholder="Full name"
                                           value="<?php echo isset($_POST['contact_person']) ? htmlspecialchars($_POST['contact_person']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                    <input type="text" name="contact_designation" class="form-control" 
                                           placeholder="e.g., Sales Manager, Director"
                                           value="<?php echo isset($_POST['contact_designation']) ? htmlspecialchars($_POST['contact_designation']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="contact_phone" class="form-control" 
                                           placeholder="Mobile/Phone number"
                                           value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="contact_email" class="form-control" 
                                           placeholder="contact@vendor.com"
                                           value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address (if different from office)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-home"></i></span>
                                    <textarea name="contact_address" rows="2" class="form-control" 
                                              placeholder="Contact person's address"><?php echo isset($_POST['contact_address']) ? htmlspecialchars($_POST['contact_address']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Secondary/Emergency Contact -->
                        <div class="section-title">
                            <i class="fas fa-phone-alt"></i> Secondary / Emergency Contact
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Person Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="secondary_contact_person" class="form-control" 
                                           placeholder="Full name"
                                           value="<?php echo isset($_POST['secondary_contact_person']) ? htmlspecialchars($_POST['secondary_contact_person']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                    <input type="text" name="secondary_contact_designation" class="form-control" 
                                           placeholder="Designation"
                                           value="<?php echo isset($_POST['secondary_contact_designation']) ? htmlspecialchars($_POST['secondary_contact_designation']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="secondary_contact_phone" class="form-control" 
                                           placeholder="Emergency contact number"
                                           value="<?php echo isset($_POST['secondary_contact_phone']) ? htmlspecialchars($_POST['secondary_contact_phone']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="secondary_contact_email" class="form-control" 
                                           placeholder="Secondary email"
                                           value="<?php echo isset($_POST['secondary_contact_email']) ? htmlspecialchars($_POST['secondary_contact_email']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="section-title">
                            <i class="fas fa-globe"></i> Additional Information
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Website</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                    <input type="url" name="website" class="form-control" 
                                           placeholder="https://www.vendor.com"
                                           value="<?php echo isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Attached Document</label>
                                <div class="upload-area" onclick="document.getElementById('documentInput').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color: #667eea;"></i>
                                    <p class="mb-0 small">Click to upload document</p>
                                    <small class="text-muted">Supported: PDF, DOC, DOCX, JPG, PNG, ZIP (Max 10MB)</small>
                                    <input type="file" name="attached_document" id="documentInput" style="display:none" 
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar">
                                </div>
                                <div id="fileNameDisplay" class="mt-2 text-muted small"></div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notes / Remarks</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                                    <textarea name="notes" rows="3" class="form-control" 
                                              placeholder="Any additional notes about this vendor"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Save Vendor
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
            <!-- Quick Tips Card -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Quick Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Vendor name is required</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> TIN/BIN/Trade License are optional but recommended</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Add at least one contact person</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Upload relevant vendor documents</li>
                        <li><i class="fas fa-check-circle text-success"></i> You can update information later</li>
                    </ul>
                </div>
            </div>
            
            <!-- Statistics Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Vendors:</span>
                        <strong><?php echo $total; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Active Vendors:</span>
                        <strong class="text-success"><?php echo $active; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#vendorForm').on('submit', function() {
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        $('#submitBtn').prop('disabled', true);
    });
    
    $('#documentInput').on('change', function(e) {
        if(e.target.files && e.target.files[0]) {
            var fileName = e.target.files[0].name;
            var fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2);
            $('#fileNameDisplay').html('<i class="fas fa-check-circle text-success"></i> Selected: ' + fileName + ' (' + fileSize + ' MB)');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>