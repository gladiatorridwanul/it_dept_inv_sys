<?php
require_once '../../config/database.php';
require_once '../../includes/auth.php';
include '../../includes/header.php';

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'it_staff')) {
    header("Location: ../dashboard.php");
    exit();
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT s.*, e.pf_no, e.full_name, e.designation, e.department, e.phone, e.email,
          (SELECT COUNT(*) FROM submission_devices WHERE submission_id = s.id) as device_count
          FROM unlisted_device_submissions s
          JOIN employees e ON s.employee_id = e.id
          WHERE 1=1";
$params = [];

if($status_filter != 'all') {
    $query .= " AND s.status = ?";
    $params[] = $status_filter;
}
if(!empty($search)) {
    $query .= " AND (s.submission_no LIKE ? OR e.full_name LIKE ? OR e.pf_no LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}
$query .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll();

$count_pending = $pdo->query("SELECT COUNT(*) FROM unlisted_device_submissions WHERE status = 'pending'")->fetchColumn();
$count_approved = $pdo->query("SELECT COUNT(*) FROM unlisted_device_submissions WHERE status = 'approved'")->fetchColumn();
$count_added = $pdo->query("SELECT COUNT(*) FROM unlisted_device_submissions WHERE status = 'added_to_stock'")->fetchColumn();
$count_rejected = $pdo->query("SELECT COUNT(*) FROM unlisted_device_submissions WHERE status = 'rejected'")->fetchColumn();

$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY name")->fetchAll();
$all_sub = $pdo->query("SELECT id, name, parent_id FROM categories WHERE is_active = 1 AND parent_id IS NOT NULL ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
$item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// CRITICAL FIX: Get existing items and build data array for JavaScript
$existing_items = $pdo->query("
    SELECT id, name, item_code, brand_id, category_id, sub_category_id, type_id, price, warranty_period_months 
    FROM items 
    WHERE is_active = 1 
    ORDER BY item_code, name
")->fetchAll();

// Build the data array for JavaScript - THIS MUST BE DONE BEFORE THE SCRIPT
$existing_items_data = [];
foreach($existing_items as $item) {
    $existing_items_data[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'item_code' => $item['item_code'],
        'brand_id' => $item['brand_id'],
        'category_id' => $item['category_id'],
        'sub_category_id' => $item['sub_category_id'],
        'type_id' => $item['type_id'],
        'price' => $item['price'],
        'warranty_period_months' => $item['warranty_period_months']
    ];
}

$sub_lookup = [];
foreach($all_sub as $sub) {
    $sub_lookup[$sub['parent_id']][] = $sub;
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Inter', sans-serif; }
        .stat-card { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
        .device-card { background: white; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e5e7eb; overflow: hidden; }
        .device-header { background: #f8f9fa; padding: 12px 20px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .device-body { padding: 20px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dbeafe; color: #2563eb; }
        .status-added_to_stock { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .section-header { cursor: pointer; }
        .section-header:hover { background-color: #f9fafb; }
        .rotate-icon { transition: transform 0.3s; }
        .rotate-icon.collapsed { transform: rotate(-90deg); }
        .info-row { margin-bottom: 8px; }
        .info-label { font-weight: 600; width: 140px; display: inline-block; }
        .existing-item-tag {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
            margin-top: 10px;
        }
        .existing-item-tag i { margin-right: 5px; }
        .existing-item-tag .remove-link {
            background: none;
            border: none;
            color: white;
            margin-left: 8px;
            cursor: pointer;
            font-size: 12px;
        }
        .existing-item-tag .remove-link:hover { color: #fca5a5; }
        .device-action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .device-locked {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
        .device-locked i { color: #ef4444; margin-right: 8px; }
        
        /* Search results styling */
        .existing-item-search {
            border: 2px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            width: 100% !important;
            font-size: 0.9rem !important;
        }
        .existing-item-search:focus {
            border-color: #10b981 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1) !important;
        }
        .existing-item-results {
            position: absolute;
            z-index: 9999;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
            width: calc(100% - 0px);
            margin-top: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .existing-item-result {
            padding: 10px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
            font-size: 0.85rem;
        }
        .existing-item-result:hover {
            background: #f1f5f9;
        }
        .existing-item-result .item-code {
            font-weight: 600;
            color: #3b82f6;
        }
        .existing-item-result .item-name {
            color: #1e293b;
        }
        .existing-item-result .item-price {
            color: #10b981;
            font-weight: 600;
            float: right;
        }
        .existing-item-result .item-warranty {
            color: #64748b;
            font-size: 0.7rem;
            margin-left: 10px;
        }
        .select2-container--open { z-index: 9999 !important; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-list me-2"></i> Unlisted Device Submissions</h2>
        <a href="../../public/submit_unlisted_device.php" target="_blank" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Public Submission Form</a>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3 mb-3"><div class="stat-card"><h6 class="text-muted mb-1">Pending Review</h6><h3><?php echo $count_pending; ?></h3></div></div>
        <div class="col-md-3 mb-3"><div class="stat-card"><h6 class="text-muted mb-1">Approved</h6><h3><?php echo $count_approved; ?></h3></div></div>
        <div class="col-md-3 mb-3"><div class="stat-card"><h6 class="text-muted mb-1">Added to Stock</h6><h3><?php echo $count_added; ?></h3></div></div>
        <div class="col-md-3 mb-3"><div class="stat-card"><h6 class="text-muted mb-1">Rejected</h6><h3><?php echo $count_rejected; ?></h3></div></div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3"><label>Status</label><select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="added_to_stock" <?php echo $status_filter == 'added_to_stock' ? 'selected' : ''; ?>>Added to Stock</option>
                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select></div>
                <div class="col-md-7"><label>Search</label><input type="text" name="search" class="form-control" placeholder="Search by name, PF no, or submission no..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary w-100 mt-4"><i class="fas fa-search"></i> Search</button></div>
            </form>
        </div>
    </div>
    
    <?php if(empty($submissions)): ?>
        <div class="alert alert-info text-center">No submissions found.</div>
    <?php endif; ?>
    
    <?php foreach($submissions as $sub): ?>
    <div class="card mb-4" id="submission-card-<?php echo $sub['id']; ?>">
        <div class="card-header bg-white d-flex justify-content-between section-header" onclick="toggleSubmission(<?php echo $sub['id']; ?>)">
            <div><strong><?php echo htmlspecialchars($sub['submission_no']); ?></strong> - <?php echo htmlspecialchars($sub['full_name']); ?> (<?php echo htmlspecialchars($sub['pf_no']); ?>) - <?php echo $sub['device_count']; ?> devices - <?php echo date('d M Y', strtotime($sub['created_at'])); ?></div>
            <div><span class="status-badge status-<?php echo $sub['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?></span> <i class="fas fa-chevron-down rotate-icon" id="toggle-icon-<?php echo $sub['id']; ?>"></i></div>
        </div>
        <div class="card-body" id="submission-content-<?php echo $sub['id']; ?>" style="display: none;">
            <div class="row mb-3">
                <div class="col-md-4"><strong>PF No:</strong> <?php echo htmlspecialchars($sub['pf_no']); ?></div>
                <div class="col-md-4"><strong>Name:</strong> <?php echo htmlspecialchars($sub['full_name']); ?></div>
                <div class="col-md-4"><strong>Designation:</strong> <?php echo htmlspecialchars($sub['designation']); ?></div>
                <div class="col-md-4"><strong>Department:</strong> <?php echo htmlspecialchars($sub['department']); ?></div>
                <div class="col-md-4"><strong>Phone:</strong> <?php echo htmlspecialchars($sub['phone']); ?></div>
                <div class="col-md-4"><strong>Email:</strong> <?php echo htmlspecialchars($sub['email']); ?></div>
            </div>
            
            <h5>Devices</h5>
            <div id="devices-container-<?php echo $sub['id']; ?>"><div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading devices...</div></div>
            
            <?php if($sub['submission_notes']): ?>
            <div class="alert alert-info mt-3"><?php echo nl2br(htmlspecialchars($sub['submission_notes'])); ?></div>
            <?php endif; ?>
            
            <div class="mt-3"><label class="fw-bold">Admin Notes</label><textarea class="form-control" id="admin-notes-<?php echo $sub['id']; ?>" rows="2"><?php echo htmlspecialchars($sub['admin_notes'] ?? ''); ?></textarea></div>
            
            <?php if($sub['status'] == 'pending'): ?>
            <div class="mt-3 text-end">
                <button class="btn btn-outline-success" onclick="processSubmission(<?php echo $sub['id']; ?>, 'approve')"><i class="fas fa-check-circle me-1"></i> Approve All</button>
                <button class="btn btn-danger" onclick="processSubmission(<?php echo $sub['id']; ?>, 'reject')"><i class="fas fa-times-circle me-1"></i> Reject All</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
// ============================================
// CRITICAL FIX: Pass items data from PHP to JavaScript
// ============================================
var subCatData = <?php echo json_encode($sub_lookup); ?>;
var existingItemsData = <?php echo json_encode($existing_items_data); ?>;

// Debug: Log the data to console
console.log('=== Debug: Existing Items Data ===');
console.log('Total items: ' + (existingItemsData ? existingItemsData.length : 0));
console.log('Items:', existingItemsData);

// ============================================
// CORE FUNCTIONS - EXPOSED GLOBALLY
// ============================================

function toggleSubmission(id) {
    var content = $('#submission-content-' + id);
    var icon = $('#toggle-icon-' + id);
    if(content.is(':visible')) { 
        content.slideUp(); 
        icon.removeClass('collapsed'); 
    } else { 
        content.slideDown(); 
        icon.addClass('collapsed'); 
        loadDevices(id); 
    }
}

function loadDevices(id) {
    $('#devices-container-' + id).html('<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Loading devices...</div>');
    $.ajax({ 
        url: 'ajax/get_submission_devices.php', 
        method: 'GET', 
        data: {submission_id: id}, 
        success: function(r) { 
            $('#devices-container-' + id).html(r); 
            setTimeout(function() {
                initializeExistingItemSearch();
            }, 500);
        },
        error: function(xhr, status, error) { 
            $('#devices-container-' + id).html('<div class="alert alert-danger">Error loading devices: ' + error + '</div>'); 
        }
    });
}

// ============================================
// SEARCHABLE EXISTING ITEM WITH REAL-TIME SEARCH
// ============================================

function initializeExistingItemSearch() {
    console.log('=== initializeExistingItemSearch called ===');
    console.log('existingItemsData:', existingItemsData);
    console.log('Total items available:', existingItemsData ? existingItemsData.length : 0);
    
    if (!existingItemsData || existingItemsData.length === 0) {
        console.warn('No existing items data found! Please check the database.');
        return;
    }
    
    $('.existing-item-search').each(function() {
        var $input = $(this);
        var deviceId = $input.data('device-id');
        var resultsContainer = $('#existing_item_results_' + deviceId);
        var hiddenField = $('#existing_item_hidden_' + deviceId);
        var tagContainer = $('#existing_item_tag_' + deviceId);
        var tagName = $('#existing_item_name_' + deviceId);
        
        // Skip if already initialized
        if ($input.hasClass('search-initialized')) {
            return;
        }
        $input.addClass('search-initialized');
        
        // Get items from the global variable
        var items = existingItemsData || [];
        
        // Input event - search as user types
        $input.on('input focus', function() {
            var searchTerm = $(this).val().toLowerCase().trim();
            
            // If search term is empty, hide results
            if (searchTerm.length < 1) {
                resultsContainer.hide();
                return;
            }
            
            console.log('Searching for: "' + searchTerm + '" in ' + items.length + ' items');
            
            // Filter items
            var matches = [];
            $.each(items, function(index, item) {
                var searchText = (item.item_code + ' ' + item.name).toLowerCase();
                if (searchText.indexOf(searchTerm) !== -1) {
                    matches.push(item);
                }
            });
            
            console.log('Found ' + matches.length + ' matches');
            
            // Show results
            if (matches.length > 0) {
                var html = '';
                $.each(matches, function(index, item) {
                    html += '<div class="existing-item-result" data-item-id="' + item.id + '" ';
                    html += 'data-category="' + (item.category_id || '') + '" ';
                    html += 'data-subcategory="' + (item.sub_category_id || '') + '" ';
                    html += 'data-brand="' + (item.brand_id || '') + '" ';
                    html += 'data-price="' + (item.price || '') + '" ';
                    html += 'data-type="' + (item.type_id || '') + '" ';
                    html += 'data-warranty="' + (item.warranty_period_months || 12) + '" ';
                    html += '>';
                    html += '<span class="item-code">' + htmlspecialchars(item.item_code) + '</span>';
                    html += ' <span class="item-name">' + htmlspecialchars(item.name) + '</span>';
                    if (item.price > 0) {
                        html += ' <span class="item-price">৳' + parseFloat(item.price).toFixed(2) + '</span>';
                    }
                    if (item.warranty_period_months) {
                        html += ' <span class="item-warranty">' + item.warranty_period_months + 'm warranty</span>';
                    }
                    html += '</div>';
                });
                resultsContainer.html(html);
                resultsContainer.show();
                
                // Click event for each result
                resultsContainer.find('.existing-item-result').off('click').on('click', function() {
                    var itemId = $(this).data('item-id');
                    var categoryId = $(this).data('category');
                    var subCategoryId = $(this).data('subcategory');
                    var brandId = $(this).data('brand');
                    var itemPrice = $(this).data('price');
                    var itemTypeId = $(this).data('type');
                    var warrantyMonths = $(this).data('warranty') || 12;
                    
                    // Find the full item details
                    var selectedItem = null;
                    $.each(items, function(index, item) {
                        if (item.id == itemId) {
                            selectedItem = item;
                            return false;
                        }
                    });
                    
                    if (selectedItem) {
                        console.log('Selected item:', selectedItem);
                        
                        // Set the input value
                        $input.val(selectedItem.item_code + ' - ' + selectedItem.name);
                        hiddenField.val(itemId);
                        
                        // Auto-fill fields
                        if (categoryId) {
                            $('#category_' + deviceId).val(categoryId).trigger('change');
                            loadSubCategories(deviceId, categoryId);
                            if (subCategoryId) {
                                setTimeout(function() {
                                    $('#subcat_' + deviceId).val(subCategoryId);
                                }, 500);
                            }
                        }
                        if (brandId) {
                            $('#brand_id_' + deviceId).val(brandId);
                        }
                        if (itemTypeId) {
                            $('#item_type_' + deviceId).val(itemTypeId);
                        }
                        if (itemPrice && itemPrice > 0) {
                            $('#price_' + deviceId).val(itemPrice);
                        }
                        if (warrantyMonths) {
                            $('#warranty_' + deviceId).val(warrantyMonths);
                        }
                        
                        // Show tag
                        tagContainer.show();
                        tagName.text(selectedItem.item_code + ' - ' + selectedItem.name);
                        
                        resultsContainer.hide();
                        
                        Swal.fire({
                            icon: 'success',
                            title: '✅ Item Linked!',
                            html: 'Successfully linked to:<br><strong>' + selectedItem.item_code + '</strong><br>' + selectedItem.name,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            } else {
                resultsContainer.html('<div style="padding: 10px 14px; color: #94a3b8;">No items found matching "<strong>' + htmlspecialchars(searchTerm) + '</strong>"</div>');
                resultsContainer.show();
            }
        });
        
        // Hide results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.existing-item-search, .existing-item-results').length) {
                resultsContainer.hide();
            }
        });
        
        // Handle keydown events
        $input.on('keydown', function(e) {
            if (e.key === 'Escape') {
                resultsContainer.hide();
                $(this).blur();
            }
            if (e.key === 'Enter') {
                var firstResult = resultsContainer.find('.existing-item-result:first');
                if (firstResult.length) {
                    firstResult.click();
                }
                resultsContainer.hide();
            }
            if (e.key === 'ArrowDown') {
                var firstResult = resultsContainer.find('.existing-item-result:first');
                if (firstResult.length) {
                    firstResult.focus();
                }
            }
        });
    });
}

// HTML special characters function
function htmlspecialchars(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}

function loadSubCategories(deviceId, categoryId) {
    if (!categoryId) return;
    var subcatSelect = $('#subcat_' + deviceId);
    subcatSelect.html('<option value="">-- Select Sub Category --</option>');
    if (subCatData[categoryId]) {
        subCatData[categoryId].forEach(function(sub) {
            subcatSelect.append('<option value="' + sub.id + '">' + sub.name + '</option>');
        });
    }
}

function toggleEdit(did) {
    var isAddedToStock = $('#device-card-' + did).find('.status-badge').hasClass('status-added_to_stock');
    if (isAddedToStock) {
        Swal.fire({
            icon: 'warning',
            title: 'Locked',
            text: 'This device has already been added to stock and cannot be edited.',
            timer: 2000,
            showConfirmButton: false
        });
        return;
    }
    
    if($('#edit-mode-' + did).is(':visible')) { 
        saveDevice(did); 
    } else { 
        $('#view-mode-' + did).hide(); 
        $('#edit-mode-' + did).show(); 
        setTimeout(function() {
            // Re-initialize search
            initializeExistingItemSearch();
        }, 300);
    }
}

function cancelEdit(did) {
    $('#view-mode-' + did).show();
    $('#edit-mode-' + did).hide();
}

function saveDevice(did) {
    var existingItemId = $('#existing_item_hidden_' + did).val();
    
    var formData = {
        device_id: did,
        device_name: $('#name_' + did).val() || '',
        brand_name: $('#brand_' + did).val() || '',
        model_number: $('#model_' + did).val() || '',
        serial_number: $('#serial_' + did).val() || '',
        specification: $('#spec_' + did).val() || '',
        purchase_date: $('#purchase_' + did).val() || '',
        assigned_date: $('#assigned_date_' + did).val() || '',
        assigned_by: $('#assigned_by_' + did).val() || '',
        current_condition: $('#condition_' + did).val() || 'good',
        notes: $('#notes_' + did).val() || '',
        item_type_id: $('#item_type_' + did).val() || '',
        category_id: $('#category_' + did).val() || '',
        sub_category_id: $('#subcat_' + did).val() || '',
        brand_id: $('#brand_id_' + did).val() || '',
        warranty_months: $('#warranty_' + did).val() || 12,
        price: $('#price_' + did).val() || '',
        quantity: $('#qty_' + did).val() || 1,
        existing_item_id: existingItemId || ''
    };
    
    console.log('Saving device data:', formData);
    
    var btn = $('#device-card-' + did).find('.btn-outline-primary');
    var orig = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
    
    $.ajax({
        url: 'ajax/save_device_changes.php',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(res) {
            btn.html(orig).prop('disabled', false);
            if(res.success) {
                Swal.fire('Success', res.message, 'success').then(() => {
                    location.reload();
                });
            } else { 
                Swal.fire('Error', res.message || 'Failed to save device', 'error'); 
            }
        },
        error: function(xhr, status, error) {
            btn.html(orig).prop('disabled', false);
            var errorMsg = 'Failed to save device';
            try {
                var response = JSON.parse(xhr.responseText);
                if(response.message) {
                    errorMsg = response.message;
                }
            } catch(e) {}
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

function approveDevice(did) {
    var isAddedToStock = $('#device-card-' + did).find('.status-badge').hasClass('status-added_to_stock');
    if (isAddedToStock) {
        Swal.fire({
            icon: 'info',
            title: 'Already in Stock',
            text: 'This device has already been added to stock.',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }
    
    Swal.fire({
        title: 'Approve Device',
        text: 'Are you sure you want to approve this device?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: 'ajax/process_device.php',
                method: 'POST',
                data: { device_id: did, action: 'approve' },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('Success', res.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to approve device', 'error');
                }
            });
        }
    });
}

function addDeviceToStock(did) {
    var isAddedToStock = $('#device-card-' + did).find('.status-badge').hasClass('status-added_to_stock');
    if (isAddedToStock) {
        Swal.fire({
            icon: 'info',
            title: 'Already in Stock',
            text: 'This device has already been added to stock.',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }
    
    var existingItemId = $('#existing_item_hidden_' + did).val();
    var currentPrice = $('#price_' + did).val();
    var itemName = $('#existing_item_name_' + did).text();
    var isLinked = existingItemId && existingItemId > 0;
    
    Swal.fire({
        title: isLinked ? 'Add to Existing Item Stock' : 'Add New Item to Stock',
        html: '<div class="text-start">' +
            (isLinked ? '<div class="alert alert-success mt-2 small"><i class="fas fa-link"></i> <strong>Linked to: ' + itemName + '</strong><br>This will only update quantity, not create a new item.</div>' : 
                        '<div class="alert alert-info mt-2 small"><i class="fas fa-info-circle"></i> <strong>New Item:</strong> This will create a new item in the inventory.</div>') +
            '<div class="mb-3 mt-3"><label class="form-label fw-bold">Quantity</label><input id="qty" class="form-control" value="1" min="1" type="number"></div>' +
            '<div class="mb-3"><label class="form-label fw-bold">Price (BDT) <span class="text-danger">*</span></label>' +
            '<input id="price" class="form-control" placeholder="0.00" step="0.01" value="' + (currentPrice || '') + '" required></div>' +
            '<div class="mb-3"><label class="form-label fw-bold">Admin Notes</label><textarea id="dnotes" class="form-control" rows="2" placeholder="Optional notes..."></textarea></div>' +
            '</div>',
        showCancelButton: true,
        confirmButtonText: 'Confirm Add to Stock',
        preConfirm: () => {
            var qty = document.getElementById('qty')?.value || 1;
            var price = document.getElementById('price')?.value;
            if (!price || price <= 0) {
                Swal.showValidationMessage('Price is required!');
                return false;
            }
            return { qty: qty, price: price, notes: document.getElementById('dnotes')?.value || '' };
        }
    }).then((result) => {
        if(result.isConfirmed && result.value) {
            $.ajax({
                url: 'ajax/process_device.php',
                method: 'POST',
                data: { 
                    device_id: did, 
                    action: 'add_to_stock', 
                    quantity: result.value.qty, 
                    price: result.value.price,
                    admin_notes: result.value.notes
                },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('Success!', res.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to add device to stock', 'error');
                }
            });
        }
    });
}

function rejectDevice(did) {
    var isAddedToStock = $('#device-card-' + did).find('.status-badge').hasClass('status-added_to_stock');
    if (isAddedToStock) {
        Swal.fire({
            icon: 'info',
            title: 'Already in Stock',
            text: 'This device has already been added to stock and cannot be rejected.',
            timer: 1500,
            showConfirmButton: false
        });
        return;
    }
    
    Swal.fire({
        title: 'Reject Device',
        html: '<div class="mb-3"><label class="fw-bold">Rejection Reason</label><textarea id="reason" class="form-control mt-1" rows="3" placeholder="Enter reason for rejection..."></textarea></div>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Reject',
        preConfirm: () => {
            var reason = document.getElementById('reason')?.value;
            if (!reason) {
                Swal.showValidationMessage('Please provide a rejection reason!');
                return false;
            }
            return { reason: reason };
        }
    }).then((result) => {
        if(result.isConfirmed && result.value) {
            $.ajax({
                url: 'ajax/process_device.php',
                method: 'POST',
                data: { device_id: did, action: 'reject', admin_notes: result.value.reason },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('Success', res.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to reject device', 'error');
                }
            });
        }
    });
}

function processSubmission(id, action) {
    var notes = $('#admin-notes-' + id).val();
    var actionText = action === 'approve' ? 'Approve All' : 'Reject All';
    
    var htmlContent = '<div class="text-start">' +
        '<div class="mb-3"><strong>Action:</strong> ' + actionText + '</div>' +
        '<div class="mb-3"><label class="fw-bold">Admin Notes</label><textarea id="pn" class="form-control mt-1" rows="3">' + (notes || '') + '</textarea></div>' +
        '</div>';
    
    Swal.fire({
        title: 'Process Submission',
        html: htmlContent,
        showCancelButton: true,
        confirmButtonText: 'Confirm',
        preConfirm: () => {
            return fetch('ajax/process_submission.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'submission_id=' + id + '&action=' + action + '&admin_notes=' + encodeURIComponent($('#pn').val())
            }).then(response => response.json());
        }
    }).then((result) => { 
        if(result.isConfirmed && result.value.success) { 
            Swal.fire('Success', result.value.message, 'success').then(() => location.reload()); 
        } else if(result.isConfirmed && result.value && !result.value.success) {
            Swal.fire('Error', result.value.message, 'error');
        }
    });
}

function clearExistingItemLink(deviceId) {
    $('#existing_item_search_' + deviceId).val('');
    $('#existing_item_hidden_' + deviceId).val('');
    $('#existing_item_tag_' + deviceId).hide();
    $('#existing_item_name_' + deviceId).text('');
    $('#existing_item_results_' + deviceId).hide();
    
    Swal.fire({
        icon: 'info',
        title: 'Link Removed',
        text: 'Existing item link has been removed.',
        timer: 2000,
        showConfirmButton: false
    });
}

// ============================================
// EXPOSE FUNCTIONS TO GLOBAL SCOPE
// ============================================
window.toggleSubmission = toggleSubmission;
window.loadDevices = loadDevices;
window.initializeExistingItemSearch = initializeExistingItemSearch;
window.loadSubCategories = loadSubCategories;
window.toggleEdit = toggleEdit;
window.cancelEdit = cancelEdit;
window.saveDevice = saveDevice;
window.approveDevice = approveDevice;
window.addDeviceToStock = addDeviceToStock;
window.rejectDevice = rejectDevice;
window.processSubmission = processSubmission;
window.clearExistingItemLink = clearExistingItemLink;
window.htmlspecialchars = htmlspecialchars;

// ============================================
// INITIALIZE ON PAGE LOAD
// ============================================
$(document).ready(function() {
    console.log('=== Document Ready ===');
    console.log('existingItemsData length:', existingItemsData ? existingItemsData.length : 0);
    setTimeout(function() {
        initializeExistingItemSearch();
    }, 800);
});
</script>

<?php include '../../includes/footer.php'; ?>