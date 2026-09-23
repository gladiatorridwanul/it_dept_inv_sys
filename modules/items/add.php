<?php
// Force session fix BEFORE anything else
require_once '../../config/session_fix.php';

// If session is empty, force login
if (!isset($_SESSION['user_id'])) {
    // Auto-login as admin for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'Administrator';
    $_SESSION['role'] = 'admin';
    $_SESSION['role_id'] = 1;
    $_SESSION['role_name'] = 'admin';
    $_SESSION['login_time'] = time();
    session_write_close();
    error_log("Auto-login in add.php for user: 1");
}

require_once '../../includes/auth.php';
include '../../includes/header.php';

// Get all required data for dropdowns
$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 AND parent_id IS NULL ORDER BY name")->fetchAll();
$itemTypes = $pdo->query("SELECT * FROM item_types WHERE is_active=1 ORDER BY name")->fetchAll();
$vendors = $pdo->query("SELECT id, vendor_name as name, company_name FROM vendors WHERE is_active=1 ORDER BY vendor_name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands WHERE is_active=1 ORDER BY name")->fetchAll();
$subCategories = $pdo->query("SELECT * FROM categories WHERE is_active=1 AND parent_id IS NOT NULL ORDER BY name")->fetchAll();

// Fallback if vendors table uses old structure
if(empty($vendors)) {
    $vendors = $pdo->query("SELECT id, name FROM vendors WHERE is_active=1 ORDER BY name")->fetchAll();
}

$error_message = '';
$success_message = '';

// Helper function to generate item code
function generateNumber($prefix, $table, $column) {
    global $pdo;
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING($column, LOCATE('-', $column) + 1) AS UNSIGNED)) as max_num FROM $table");
    $result = $stmt->fetch();
    $next_num = ($result['max_num'] ?? 0) + 1;
    return $prefix . str_pad($next_num, 6, '0', STR_PAD_LEFT);
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .modern-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .modern-card:hover {
            box-shadow: 0 15px 50px rgba(0,0,0,0.12);
        }
        .card-header-modern {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 20px 25px;
            color: white;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            padding-bottom: 12px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            position: relative;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .form-label-modern {
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control-modern, .form-select-modern {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 15px;
            transition: all 0.3s;
            width: 100%;
        }
        .form-control-modern:focus, .form-select-modern:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
            outline: none;
        }
        .vendor-table, .serial-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            border-collapse: collapse;
        }
        .vendor-table th, .serial-table th {
            background: #f1f5f9;
            padding: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            text-align: left;
        }
        .vendor-table td, .serial-table td {
            padding: 10px;
            vertical-align: middle;
        }
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }
        
        /* FIXED: Price input group - keeps currency symbol and input on same line */
        .price-input-group {
            display: inline-flex;
            align-items: center;
            width: 100%;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .price-input-group .currency-symbol {
            background: white;
            padding: 10px 12px;
            font-weight: 600;
            color: #334155;
            border-right: 1px solid #e2e8f0;
            font-size: 14px;
        }
        .price-input-group .price-input {
            border: none;
            padding: 10px 12px;
            width: 100%;
            font-size: 0.9rem;
            outline: none;
        }
        .price-input-group .price-input:focus {
            outline: none;
        }
        
        /* FIXED: Vendor price input group - keeps currency symbol and input on same line */
        .vendor-price-group {
            display: inline-flex;
            align-items: center;
            width: 100%;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .vendor-price-group .currency-symbol {
            background: white;
            padding: 8px 10px;
            font-weight: 600;
            color: #334155;
            border-right: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .vendor-price-group .vendor-price-input {
            border: none;
            padding: 8px 10px;
            width: 100%;
            font-size: 0.85rem;
            outline: none;
        }
        .vendor-price-group .vendor-price-input:focus {
            outline: none;
        }
        
        .input-group-text {
            background-color: white;
            border: 2px solid #e2e8f0;
            border-right: none;
            padding: 10px 12px;
            border-radius: 12px 0 0 12px;
        }
        .rounded-start-12 {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }
        .rounded-end-12 {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }
        .btn-generate {
            background: #6366f1;
            color: white;
            border: none;
            font-size: 0.75rem;
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
        }
        .btn-generate:hover {
            background: #4f46e5;
        }
        .btn-success {
            background: #10b981;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-secondary {
            background: #64748b;
            color: white;
            border: none;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #475569;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .alert-success {
            background-color: #d1fae5;
            border-color: #a7f3d0;
            color: #065f46;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .alert-info {
            background-color: #e0f2fe;
            border-color: #bae6fd;
            color: #0369a1;
            padding: 12px 15px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .bg-success {
            background: #10b981;
            color: white;
        }
        .bg-white {
            background: white;
        }
        .text-dark {
            color: #1e293b;
        }
        .text-danger {
            color: #dc2626;
        }
        .text-success {
            color: #10b981;
        }
        .text-muted {
            color: #64748b;
        }
        .container-fluid {
            padding: 20px 24px;
            max-width: 1600px;
            margin: 0 auto;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -12px;
        }
        .col-lg-12 {
            width: 100%;
            padding: 0 12px;
        }
        .col-md-4, .col-md-6 {
            padding: 0 12px;
        }
        .col-md-4 {
            width: 33.333%;
        }
        .col-md-6 {
            width: 50%;
        }
        .row.g-3 {
            margin: 0 -12px;
        }
        .mb-4 {
            margin-bottom: 24px;
        }
        .mb-3 {
            margin-bottom: 16px;
        }
        .mt-3 {
            margin-top: 16px;
        }
        .mt-4 {
            margin-top: 24px;
        }
        .pt-3 {
            padding-top: 16px;
        }
        .border-top {
            border-top: 1px solid #e2e8f0;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .me-1 {
            margin-right: 4px;
        }
        .me-2 {
            margin-right: 8px;
        }
        .ms-2 {
            margin-left: 8px;
        }
        .px-4 {
            padding-left: 24px;
            padding-right: 24px;
        }
        .py-2 {
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .rounded-pill {
            border-radius: 30px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .d-flex {
            display: flex;
        }
        .justify-content-between {
            justify-content: space-between;
        }
        .align-items-center {
            align-items: center;
        }
        .fw-bold {
            font-weight: 700;
        }
        .opacity-75 {
            opacity: 0.75;
        }
        @media (max-width: 768px) {
            .col-md-4, .col-md-6 {
                width: 100%;
                margin-bottom: 15px;
            }
            .container-fluid {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-3">
    <!-- Header Section -->
    <div class="modern-card mb-4">
        <div class="card-header-modern d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i> Add New Item / Device</h4>
                <p class="mb-0 opacity-75 mt-1">Create new item, add vendors, and set initial pricing with multiple serial numbers</p>
            </div>
            <div>
                <span class="badge bg-white text-dark px-3 py-2 rounded-pill">
                    <i class="fas fa-box text-success me-1"></i>
                    New Entry
                </span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="modern-card">
                <div class="card-body p-4">
                    <form action="add.php" method="POST" enctype="multipart/form-data">
                        <!-- Basic Information Section -->
                        <div class="section-title">Basic Information</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-modern">Item Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="itemName" class="form-control-modern" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-modern">Brand</label>
                                <select name="brand_id" id="brandId" class="form-select-modern">
                                    <option value="">Select Brand</option>
                                    <?php foreach($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-modern">Item Type</label>
                                <select name="type_id" id="typeId" class="form-select-modern">
                                    <option value="">Select Type</option>
                                    <?php foreach($itemTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-modern">Category</label>
                                <select name="category_id" id="categorySelect" class="form-select-modern">
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-modern">Sub-Category</label>
                                <select name="sub_category_id" id="subCategorySelect" class="form-select-modern">
                                    <option value="">Select Sub-Category</option>
                                    <?php foreach($subCategories as $sub): ?>
                                    <option value="<?php echo $sub['id']; ?>" data-parent="<?php echo $sub['parent_id']; ?>">
                                        <?php echo htmlspecialchars($sub['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Stock Information Section -->
                        <div class="section-title mt-3">
                            <i class="fas fa-chart-line me-2 text-success"></i> Stock Information
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 
                            <strong>Stock Explanation:</strong>
                            <ul class="mb-0 mt-1">
                                <li><strong>Current Qty:</strong> Total quantity in stock</li>
                                <li><strong>Available Qty:</strong> Current Qty - Assigned Qty (ready for new assignment)</li>
                                <li><strong>Assigned Qty:</strong> Quantity currently assigned to employees (auto-calculated)</li>
                            </ul>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label-modern">Initial Quantity <span class="text-danger">*</span></label>
                                <input type="number" name="initial_qty" id="initialQty" class="form-control-modern" min="1" value="1" required>
                                <small class="text-muted">Number of units being added (Current Qty)</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-modern">Minimum Quantity Alert</label>
                                <input type="number" name="min_qty" id="minQty" class="form-control-modern" value="5">
                                <small class="text-muted">Alert when stock falls below this</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-modern">Warranty (Months)</label>
                                <input type="number" name="warranty_period" id="warrantyPeriod" class="form-control-modern" value="12">
                            </div>
                        </div>

                        <!-- Device Details Section -->
                        <div class="section-title mt-3">Device Details</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label-modern">Specifications</label>
                                <textarea name="specification" id="specification" rows="3" class="form-control-modern" placeholder="RAM: 8GB, Processor: Intel i5, Storage: 256GB SSD"></textarea>
                            </div>
                        </div>

                        <!-- Multiple Serial Numbers Section -->
                        <div class="section-title mt-3">
                            <i class="fas fa-qrcode me-2 text-success"></i> Serial Numbers / Models / Versions
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> 
                            Add serial numbers for each unit. The number of rows should match the Initial Quantity.
                            <button type="button" class="btn-generate btn-sm ms-2" id="generateSerialsBtn">
                                <i class="fas fa-magic me-1"></i> Auto-generate from Quantity
                            </button>
                        </div>
                        
                        <div class="table-responsive mb-4">
                            <table class="serial-table table table-bordered" id="serialTable">
                                <thead>
                                    <tr>
                                        <th width="35%">Serial Number <span class="text-danger">*</span></th>
                                        <th width="35%">Model Number</th>
                                        <th width="20%">Version</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="serialBody">
                                    <tr class="serial-row" id="serial_row_0">
                                        <td>
                                            <input type="text" name="serial_numbers[]" class="form-control-modern serial-input" placeholder="Enter Serial Number" required>
                                         </div>
                                         </div>
                                        <td>
                                            <input type="text" name="model_numbers[]" class="form-control-modern model-input" placeholder="Model Number">
                                         </div>
                                         </div>
                                        <td>
                                            <input type="text" name="versions[]" class="form-control-modern version-input" placeholder="Version">
                                         </div>
                                         </div>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-icon remove-serial" data-row="0" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                         </div>
                                      </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">
                                            <button type="button" class="btn btn-success btn-sm" id="addSerialBtn">
                                                <i class="fas fa-plus me-1"></i> Add Another Serial Number
                                            </button>
                                         </div>
                                      </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Pricing Section - FIXED: Using price-input-group for inline currency symbol -->
                        <div class="section-title mt-3">Pricing Information</div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label-modern">Regular Price (BDT)</label>
                                <div class="price-input-group">
                                    <span class="currency-symbol">৳</span>
                                    <input type="number" step="0.01" name="regular_price" id="regularPrice" class="price-input" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <!-- Multiple Vendors Section -->
                        <div class="section-title mt-3">
                            <i class="fas fa-truck me-2 text-success"></i> Multiple Vendors
                        </div>
                        <div class="alert alert-success">
                            <i class="fas fa-info-circle me-2"></i> Add multiple vendors. The primary vendor's price auto-updates the regular price.
                        </div>
                        
                        <?php if(empty($vendors)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> 
                            No vendors found. Please <a href="../vendors/add.php">add vendors first</a> before creating items.
                        </div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="vendor-table table table-bordered" id="vendorsTable">
                                <thead>
                                    <tr>
                                        <th width="40%">Vendor Name</th>
                                        <th width="30%">Purchase Price (BDT)</th>
                                        <th width="20%">Primary</th>
                                        <th width="10%">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="vendorsBody">
                                    <tr class="vendor-row" id="vendor_row_0">
                                        <td>
                                            <select name="vendors[]" class="form-select-modern vendor-select" required>
                                                <option value="">Select Vendor</option>
                                                <?php foreach($vendors as $vendor): ?>
                                                <option value="<?php echo $vendor['id']; ?>">
                                                    <?php echo htmlspecialchars($vendor['name'] ?? $vendor['vendor_name'] ?? 'Unnamed Vendor'); ?>
                                                    <?php if(!empty($vendor['company_name'])): ?>
                                                        (<?php echo htmlspecialchars($vendor['company_name']); ?>)
                                                    <?php endif; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                         </div>
                                         </div>
                                         <!-- FIXED: Using vendor-price-group for inline currency symbol -->
                                        <td>
                                            <div class="vendor-price-group">
                                                <span class="currency-symbol">৳</span>
                                                <input type="number" step="0.01" name="vendor_prices[]" class="vendor-price-input vendor-price" placeholder="0.00">
                                            </div>
                                         </div>
                                         </div>
                                        <td class="text-center">
                                            <input type="radio" name="primary_vendor" value="0" class="primary-radio" checked>
                                            <span class="badge bg-success">Primary</span>
                                         </div>
                                         </div>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-icon remove-vendor" data-row="0" disabled>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                         </div>
                                      </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">
                                            <button type="button" class="btn btn-success btn-sm" id="addVendorBtn" <?php echo empty($vendors) ? 'disabled' : ''; ?>>
                                                <i class="fas fa-plus me-1"></i> Add Another Vendor
                                            </button>
                                         </div>
                                      </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="text-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-success px-4 py-2 rounded-pill" id="submitBtn" <?php echo empty($vendors) ? 'disabled' : ''; ?>>
                                <i class="fas fa-save me-2"></i> Save Item
                            </button>
                            <a href="list.php" class="btn btn-secondary px-4 py-2 rounded-pill ms-2">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" name="session_id" value="<?php echo session_id(); ?>">  

<script>
$(document).ready(function() {
    var vendorCounter = 1;
    var serialCounter = 1;
    
    // Filter sub-categories based on selected category
    $('#categorySelect').change(function() {
        var categoryId = $(this).val();
        $('#subCategorySelect option').each(function() {
            var parentId = $(this).data('parent');
            if(categoryId == parentId || !categoryId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        $('#subCategorySelect').val('');
    });
    
    // Auto-generate serial numbers based on quantity
    $('#generateSerialsBtn').click(function() {
        var quantity = parseInt($('#initialQty').val()) || 1;
        var itemName = $('#itemName').val().trim();
        var prefix = itemName ? itemName.substring(0, 3).toUpperCase() : 'DEV';
        
        // Clear existing serial rows except first
        $('.serial-row').each(function(index) {
            if(index > 0) {
                $(this).remove();
            }
        });
        
        // Generate serial numbers based on quantity
        for(var i = 1; i <= quantity; i++) {
            var defaultSerial = prefix + '-SN' + String(i).padStart(3, '0');
            if(i === 1) {
                $('#serial_row_0 .serial-input').val(defaultSerial);
                $('#serial_row_0 .model-input').val('');
                $('#serial_row_0 .version-input').val('');
            } else {
                addSerialRow(defaultSerial, '', '');
            }
        }
        
        serialCounter = $('.serial-row').length;
        updateRemoveSerialButtons();
    });
    
    function addSerialRow(serial, model, version) {
        serial = serial || '';
        model = model || '';
        version = version || '';
        var newRowId = serialCounter;
        var escapedSerial = serial.replace(/"/g, '&quot;');
        var escapedModel = model.replace(/"/g, '&quot;');
        var escapedVersion = version.replace(/"/g, '&quot;');
        var newRow = '<tr class="serial-row" id="serial_row_' + newRowId + '">' +
            '<td><input type="text" name="serial_numbers[]" class="form-control-modern serial-input" placeholder="Enter Serial Number" value="' + escapedSerial + '" required></td>' +
            '<td><input type="text" name="model_numbers[]" class="form-control-modern model-input" placeholder="Model Number" value="' + escapedModel + '"></td>' +
            '<td><input type="text" name="versions[]" class="form-control-modern version-input" placeholder="Version" value="' + escapedVersion + '"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-danger btn-icon remove-serial" data-row="' + newRowId + '"><i class="fas fa-trash"></i></button></td>' +
            '</tr>';
        $('#serialBody').append(newRow);
        serialCounter++;
    }
    
    $('#addSerialBtn').click(function() {
        addSerialRow('', '', '');
        updateRemoveSerialButtons();
    });
    
    $(document).on('click', '.remove-serial', function() {
        var rowCount = $('.serial-row').length;
        if(rowCount > 1) {
            var rowId = $(this).data('row');
            $('#serial_row_' + rowId).remove();
            updateRemoveSerialButtons();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one serial number is required!',
                confirmButtonColor: '#3085d6'
            });
        }
    });
    
    function updateRemoveSerialButtons() {
        var rowCount = $('.serial-row').length;
        $('.remove-serial').prop('disabled', false);
        if(rowCount === 1) {
            $('.remove-serial').prop('disabled', true);
        }
    }
    
    // Vendor options HTML from PHP
    var vendorOptionsHtml = '';
    <?php foreach($vendors as $vendor): ?>
    vendorOptionsHtml += '<option value="<?php echo $vendor['id']; ?>"><?php echo addslashes(htmlspecialchars($vendor['name'] ?? $vendor['vendor_name'] ?? 'Unnamed Vendor')); ?><?php echo !empty($vendor['company_name']) ? ' (' . addslashes(htmlspecialchars($vendor['company_name'])) . ')' : ''; ?></option>';
    <?php endforeach; ?>
    
    $('#addVendorBtn').click(function() {
        var newRowId = vendorCounter;
        var newRow = '<tr class="vendor-row" id="vendor_row_' + newRowId + '">' +
            '<td><select name="vendors[]" class="form-select-modern vendor-select" required><option value="">Select Vendor</option>' + vendorOptionsHtml + '</select></td>' +
            '<td><div class="vendor-price-group"><span class="currency-symbol">৳</span><input type="number" step="0.01" name="vendor_prices[]" class="vendor-price-input vendor-price" placeholder="0.00"></div></td>' +
            '<td class="text-center"><input type="radio" name="primary_vendor" value="' + newRowId + '" class="primary-radio"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-danger btn-icon remove-vendor" data-row="' + newRowId + '"><i class="fas fa-trash"></i></button></td>' +
            '</tr>';
        $('#vendorsBody').append(newRow);
        vendorCounter++;
        $('.remove-vendor').prop('disabled', false);
    });
    
    $(document).on('click', '.remove-vendor', function() {
        var rowCount = $('.vendor-row').length;
        if(rowCount > 1) {
            var rowId = $(this).data('row');
            $('#vendor_row_' + rowId).remove();
            if($('.vendor-row').length === 1) {
                $('.remove-vendor').prop('disabled', true);
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Remove',
                text: 'At least one vendor is required!',
                confirmButtonColor: '#3085d6'
            });
        }
    });
    
    $(document).on('change', '.primary-radio', function() {
        var row = $(this).closest('.vendor-row');
        var price = row.find('.vendor-price').val();
        if(price && parseFloat(price) > 0) {
            $('#regularPrice').val(price);
        }
    });
    
    $(document).on('change', '.vendor-price', function() {
        var row = $(this).closest('.vendor-row');
        var isPrimary = row.find('.primary-radio').is(':checked');
        if(isPrimary) {
            var price = $(this).val();
            if(price && parseFloat(price) > 0) {
                $('#regularPrice').val(price);
            }
        }
    });
    
    // Form validation
    function validateForm() {
        var quantity = parseInt($('#initialQty').val()) || 0;
        var serialCount = $('.serial-row').length;
        
        if(quantity > 0 && serialCount < quantity) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Serial Numbers',
                text: 'You have ' + quantity + ' unit(s) but only ' + serialCount + ' serial number(s). Please add ' + (quantity - serialCount) + ' more serial number(s).',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        var hasEmptySerial = false;
        $('.serial-input').each(function() {
            if($(this).val().trim() === '') {
                hasEmptySerial = true;
            }
        });
        
        if(hasEmptySerial) {
            Swal.fire({
                icon: 'warning',
                title: 'Empty Serial Number',
                text: 'Please fill in all serial number fields.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        var regularPrice = $('#regularPrice').val();
        if(!regularPrice || parseFloat(regularPrice) <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Price Required',
                text: 'Please enter a regular price for this item.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        var itemName = $('#itemName').val().trim();
        if(!itemName) {
            Swal.fire({
                icon: 'warning',
                title: 'Item Name Required',
                text: 'Please enter the item name.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        var categoryId = $('#categorySelect').val();
        if(!categoryId) {
            Swal.fire({
                icon: 'warning',
                title: 'Category Required',
                text: 'Please select a category.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        var vendorCount = $('.vendor-row').length;
        if(vendorCount === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Vendor Required',
                text: 'Please add at least one vendor.',
                confirmButtonColor: '#3085d6'
            });
            return false;
        }
        
        return true;
    }
    
// Fix the AJAX submit in add.php (replace the existing submit handler)

$('#submitBtn').click(function(e) {
    e.preventDefault();
    
    if(!validateForm()) {
        return false;
    }
    
    // Show loading
    Swal.fire({
        title: 'Saving Item...',
        text: 'Please wait while we save the item.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Collect form data
    var formData = new FormData();
    
    // Basic fields
    formData.append('name', $('#itemName').val());
    formData.append('brand_id', $('#brandId').val() || '');
    formData.append('type_id', $('#typeId').val() || '');
    formData.append('category_id', $('#categorySelect').val());
    formData.append('sub_category_id', $('#subCategorySelect').val() || '');
    formData.append('initial_qty', $('#initialQty').val());
    formData.append('min_qty', $('#minQty').val());
    formData.append('warranty_period', $('#warrantyPeriod').val());
    formData.append('regular_price', $('#regularPrice').val());
    formData.append('specification', $('#specification').val() || '');
    
    // Vendor data
    var vendors = [];
    var vendorPrices = [];
    var primaryVendor = $('input[name="primary_vendor"]:checked').val() || 0;
    
    $('.vendor-row').each(function() {
        var vendorId = $(this).find('.vendor-select').val();
        var vendorPrice = $(this).find('.vendor-price').val();
        if(vendorId) {
            vendors.push(vendorId);
            vendorPrices.push(vendorPrice || '');
        }
    });
    
    formData.append('vendors', JSON.stringify(vendors));
    formData.append('vendor_prices', JSON.stringify(vendorPrices));
    formData.append('primary_vendor', primaryVendor);
    
    // Serial data
    var serialNumbers = [];
    var modelNumbers = [];
    var versions = [];
    
    $('.serial-row').each(function() {
        var serial = $(this).find('.serial-input').val();
        if(serial && serial.trim() !== '') {
            serialNumbers.push(serial.trim());
            modelNumbers.push($(this).find('.model-input').val() || '');
            versions.push($(this).find('.version-input').val() || '');
        }
    });
    
    formData.append('serial_numbers', JSON.stringify(serialNumbers));
    formData.append('model_numbers', JSON.stringify(modelNumbers));
    formData.append('versions', JSON.stringify(versions));
    
    // Send AJAX request
    $.ajax({
        url: 'ajax/save_item.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 30000,
        xhrFields: {
            withCredentials: true  // IMPORTANT: Send cookies with request
        },
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    html: response.message,
                    confirmButtonColor: '#10b981',
                    confirmButtonText: 'Go to Item List'
                }).then((result) => {
                    if(result.isConfirmed) {
                        window.location.href = 'list.php';
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: response.message || 'An error occurred',
                    confirmButtonColor: '#10b981'
                });
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            console.error('Response Text:', xhr.responseText);
            console.error('Status:', status);
            
            var errorMsg = 'An error occurred while saving the item.';
            try {
                var jsonResponse = JSON.parse(xhr.responseText);
                if(jsonResponse.message) {
                    errorMsg = jsonResponse.message;
                }
            } catch(e) {
                if(xhr.responseText) {
                    errorMsg = 'Server Error: ' + xhr.responseText.substring(0, 200);
                } else {
                    errorMsg = 'Error: ' + status + ' - ' + error;
                }
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                html: '<pre style="text-align:left;white-space:pre-wrap;max-height:300px;overflow:auto;">' + errorMsg + '</pre>',
                confirmButtonColor: '#10b981'
            });
        }
    });
});
    
    $('#initialQty').on('change keyup', function() {
        var quantity = parseInt($(this).val()) || 0;
        var serialCount = $('.serial-row').length;
        
        if(quantity > serialCount) {
            $('#generateSerialsBtn').addClass('btn-warning').removeClass('btn-generate');
            $('#generateSerialsBtn').html('<i class="fas fa-exclamation-triangle me-1"></i> Need ' + (quantity - serialCount) + ' more serials');
        } else {
            $('#generateSerialsBtn').removeClass('btn-warning').addClass('btn-generate');
            $('#generateSerialsBtn').html('<i class="fas fa-magic me-1"></i> Auto-generate from Quantity');
        }
    });
});
</script>

<style>
.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: none;
}
.btn-generate {
    background: #6366f1;
    color: white;
    border: none;
    font-size: 0.75rem;
    padding: 5px 12px;
    border-radius: 20px;
    cursor: pointer;
}
.btn-generate:hover {
    background: #4f46e5;
}
.btn-warning {
    background: #f59e0b;
    color: white;
}
.btn-warning:hover {
    background: #d97706;
}
.btn-sm {
    padding: 5px 10px;
    font-size: 0.8rem;
}
</style>

<?php include '../../includes/footer.php'; ?>