<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT v.*, 
                              (SELECT COUNT(*) FROM bills WHERE vendor_id = v.id) as bill_count,
                              (SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE vendor_id = v.id) as total_purchase
                       FROM vendors v WHERE v.id = ?");
$stmt->execute([$id]);
$vendor = $stmt->fetch();

if(!$vendor) {
    redirect('list.php');
}

$error = '';
$success = '';

// Handle Active/De-Active toggle
if(isset($_GET['toggle']) && isset($_GET['id'])) {
    $toggleId = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE vendors SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$toggleId]);
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> Vendor status updated!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
    // Refresh vendor data
    $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
    $stmt->execute([$id]);
    $vendor = $stmt->fetch();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic Information
    $vendor_name = trim($_POST['vendor_name']);
    $company_name = trim($_POST['company_name']);
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
    $document_path = $vendor['attached_document'];
    if(isset($_FILES['attached_document']) && $_FILES['attached_document']['error'] == 0) {
        $upload_dir = '../../uploads/vendors/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
        $file_ext = strtolower(pathinfo($_FILES['attached_document']['name'], PATHINFO_EXTENSION));
        
        if(in_array($file_ext, $allowed)) {
            // Delete old file if exists
            if($document_path && file_exists('../../' . $document_path)) {
                unlink('../../' . $document_path);
            }
            
            $new_filename = 'VENDOR_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['attached_document']['tmp_name'], $destination)) {
                $document_path = 'uploads/vendors/' . $new_filename;
            }
        }
    }
    
    // Delete document if requested
    if(isset($_POST['delete_document']) && $_POST['delete_document'] == 1 && $document_path) {
        if(file_exists('../../' . $document_path)) {
            unlink('../../' . $document_path);
        }
        $document_path = null;
    }
    
    // Validation
    $errors = [];
    if(empty($vendor_name)) $errors[] = "Vendor name is required";
    if(!empty($office_email) && !filter_var($office_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid office email format";
    if(!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid contact person email format";
    if(!empty($office_phone) && !preg_match('/^[0-9]{10,15}$/', preg_replace('/[^0-9]/', '', $office_phone))) $errors[] = "Invalid office phone number";
    if(!empty($contact_phone) && !preg_match('/^[0-9]{10,15}$/', preg_replace('/[^0-9]/', '', $contact_phone))) $errors[] = "Invalid contact person phone number";
    
    // Check if name exists for another vendor
    if(empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM vendors WHERE (vendor_name = ? OR (company_name = ? AND company_name != '')) AND id != ?");
        $checkStmt->execute([$vendor_name, $company_name, $id]);
        if($checkStmt->fetch()) {
            $error = "Another vendor with this name already exists!";
        } else {
            $stmt = $pdo->prepare("UPDATE vendors SET 
                vendor_name = ?, company_name = ?, office_address = ?, office_phone = ?, office_email = ?,
                tin_no = ?, bin_no = ?, trade_license_no = ?, gst_no = ?,
                contact_person = ?, contact_designation = ?, contact_phone = ?, contact_email = ?, contact_address = ?,
                secondary_contact_person = ?, secondary_contact_designation = ?, 
                secondary_contact_phone = ?, secondary_contact_email = ?,
                website = ?, notes = ?, attached_document = ?
                WHERE id = ?");
            
            if($stmt->execute([
                $vendor_name, $company_name, $office_address, $office_phone, $office_email,
                $tin_no, $bin_no, $trade_license_no, $gst_no,
                $contact_person, $contact_designation, $contact_phone, $contact_email, $contact_address,
                $secondary_contact_person, $secondary_contact_designation,
                $secondary_contact_phone, $secondary_contact_email,
                $website, $notes, $document_path, $id
            ])) {
                $success = "Vendor updated successfully!";
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ' . $success . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                // Refresh vendor data
                $stmt = $pdo->prepare("SELECT * FROM vendors WHERE id = ?");
                $stmt->execute([$id]);
                $vendor = $stmt->fetch();
            } else {
                $error = "Error updating vendor!";
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
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .section-title {
        background: #f8f9fa;
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        color: #2c3e50;
        border-left: 4px solid #11998e;
    }
    .section-title i {
        margin-right: 8px;
        color: #11998e;
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
        border-color: #11998e;
        background: #eff6ff;
    }
    .current-document {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 10px;
        margin-top: 10px;
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
                    <h2><i class="fas fa-edit text-warning"></i> Edit Vendor</h2>
                    <p class="text-muted">Update vendor information</p>
                </div>
                <div>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <a href="../bills/list_bills.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-outline-info">
                        <i class="fas fa-file-invoice"></i> View Bills
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
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Vendor Details</h5>
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
                                    <input type="text" name="vendor_name" class="form-control" required
                                           value="<?php echo htmlspecialchars($vendor['vendor_name'] ?? $vendor['name']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Company Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                    <input type="text" name="company_name" class="form-control" 
                                           placeholder="Legal company name (if different)"
                                           value="<?php echo htmlspecialchars($vendor['company_name'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Office Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea name="office_address" rows="2" class="form-control" 
                                              placeholder="Full office address"><?php echo htmlspecialchars($vendor['office_address'] ?? $vendor['address']); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Office Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                                    <input type="tel" name="office_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['office_phone'] ?? $vendor['phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Office Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="office_email" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['office_email'] ?? $vendor['email']); ?>">
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
                                           value="<?php echo htmlspecialchars($vendor['tin_no'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">BIN Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="bin_no" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['bin_no'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Trade License No</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-certificate"></i></span>
                                    <input type="text" name="trade_license_no" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['trade_license_no'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">GST Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-file-invoice-dollar"></i></span>
                                    <input type="text" name="gst_no" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['gst_no'] ?? ''); ?>">
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
                                           value="<?php echo htmlspecialchars($vendor['contact_person'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                    <input type="text" name="contact_designation" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['contact_designation'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="contact_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['contact_phone'] ?? $vendor['phone']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="contact_email" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['contact_email'] ?? $vendor['email']); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address (if different from office)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-home"></i></span>
                                    <textarea name="contact_address" rows="2" class="form-control"><?php echo htmlspecialchars($vendor['contact_address'] ?? ''); ?></textarea>
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
                                           value="<?php echo htmlspecialchars($vendor['secondary_contact_person'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Designation</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                                    <input type="text" name="secondary_contact_designation" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['secondary_contact_designation'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" name="secondary_contact_phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['secondary_contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="secondary_contact_email" class="form-control" 
                                           value="<?php echo htmlspecialchars($vendor['secondary_contact_email'] ?? ''); ?>">
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
                                           value="<?php echo htmlspecialchars($vendor['website'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Attached Document</label>
                                <div class="upload-area" onclick="document.getElementById('documentInput').click()">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2" style="color: #11998e;"></i>
                                    <p class="mb-0 small">Click to upload new document</p>
                                    <small class="text-muted">Supported: PDF, DOC, DOCX, JPG, PNG, ZIP</small>
                                    <input type="file" name="attached_document" id="documentInput" style="display:none" 
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip,.rar">
                                </div>
                                <div id="fileNameDisplay" class="mt-2 text-muted small"></div>
                                
                                <?php if(!empty($vendor['attached_document'])): ?>
                                <div class="current-document">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-file-alt text-success me-2"></i>
                                            <strong>Current Document:</strong>
                                            <span class="small"><?php echo basename($vendor['attached_document']); ?></span>
                                        </div>
                                        <div>
                                            <a href="/it-inventory/<?php echo $vendor['attached_document']; ?>" target="_blank" class="btn btn-sm btn-info me-1">
                                                <i class="fas fa-download"></i> View
                                            </a>
                                            <label class="btn btn-sm btn-danger">
                                                <input type="checkbox" name="delete_document" value="1" style="display:none;" onchange="this.checked ? confirm('Delete this document?') : null">
                                                <i class="fas fa-trash"></i> Delete
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notes / Remarks</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sticky-note"></i></span>
                                    <textarea name="notes" rows="3" class="form-control"><?php echo htmlspecialchars($vendor['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Update Vendor
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
            <!-- Vendor Summary Card -->
            <div class="card shadow-sm info-card mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-line"></i> Vendor Summary</h5>
                    <hr class="bg-light">
                    <p class="mb-2"><i class="fas fa-tag"></i> <strong>Status:</strong> 
                        <span class="badge bg-<?php echo $vendor['is_active'] ? 'success' : 'danger'; ?>">
                            <?php echo $vendor['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </p>
                    <p class="mb-2"><i class="fas fa-file-invoice"></i> <strong>Total Bills:</strong> <?php echo $vendor['bill_count']; ?></p>
                    <p class="mb-0"><i class="fas fa-money-bill-wave"></i> <strong>Total Purchase:</strong> ৳<?php echo number_format($vendor['total_purchase'], 2); ?></p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if($vendor['is_active']): ?>
                            <a href="?toggle=1&id=<?php echo $vendor['id']; ?>" class="btn btn-outline-warning" 
                               onclick="return confirm('Deactivate this vendor?')">
                                <i class="fas fa-ban"></i> Deactivate Vendor
                            </a>
                        <?php else: ?>
                            <a href="?toggle=1&id=<?php echo $vendor['id']; ?>" class="btn btn-outline-success"
                               onclick="return confirm('Activate this vendor?')">
                                <i class="fas fa-check"></i> Activate Vendor
                            </a>
                        <?php endif; ?>
                        <a href="../bills/receive_bill.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-download"></i> Receive Bill
                        </a>
                        <a href="../bills/list_bills.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-outline-info">
                            <i class="fas fa-list"></i> View All Bills
                        </a>
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
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Updating...');
        $('#submitBtn').prop('disabled', true);
    });
    
    $('#documentInput').on('change', function(e) {
        if(e.target.files && e.target.files[0]) {
            var fileName = e.target.files[0].name;
            var fileSize = (e.target.files[0].size / 1024 / 1024).toFixed(2);
            $('#fileNameDisplay').html('<i class="fas fa-check-circle text-success"></i> New file selected: ' + fileName + ' (' + fileSize + ' MB)');
        }
    });
    
    // Handle document deletion confirmation
    $('input[name="delete_document"]').on('change', function() {
        if($(this).is(':checked')) {
            if(confirm('Are you sure you want to delete the attached document? This action cannot be undone.')) {
                $(this).val('1');
                $(this).closest('.current-document').fadeOut();
            } else {
                $(this).prop('checked', false);
            }
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>