<?php
// modules/unlisted_devices/ajax/get_submission_devices.php
require_once '../../../config/database.php';
require_once '../../../config/session_fix.php';

// Restore session if needed
if (!isset($_SESSION['user_id'])) {
    restoreSession();
}

// Check authentication
if(!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'it_staff')) {
    echo '<div class="alert alert-danger">Unauthorized access</div>';
    exit;
}

$submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;

if(!$submission_id) {
    echo '<div class="alert alert-danger">Invalid submission ID</div>';
    exit;
}

// Get devices for this submission
$stmt = $pdo->prepare("
    SELECT sd.*, 
           i.name as existing_item_name,
           i.item_code as existing_item_code,
           i.id as existing_item_id,
           i.price as existing_item_price,
           i.warranty_period_months as existing_item_warranty,
           t.name as type_name,
           c.name as category_name,
           sc.name as sub_category_name,
           b.name as brand_name
    FROM submission_devices sd
    LEFT JOIN items i ON sd.new_item_id = i.id
    LEFT JOIN item_types t ON sd.item_type_id = t.id
    LEFT JOIN categories c ON sd.final_category_id = c.id
    LEFT JOIN categories sc ON sd.final_sub_category_id = sc.id
    LEFT JOIN brands b ON sd.final_brand_id = b.id
    WHERE sd.submission_id = ?
    ORDER BY sd.id
");
$stmt->execute([$submission_id]);
$devices = $stmt->fetchAll();

if(empty($devices)) {
    echo '<div class="alert alert-info">No devices found in this submission.</div>';
    exit;
}

// Get all categories for dropdowns
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY name")->fetchAll();
$all_sub = $pdo->query("SELECT id, name, parent_id FROM categories WHERE is_active = 1 AND parent_id IS NOT NULL ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
$item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get existing items
$existing_items = $pdo->query("
    SELECT id, name, item_code, brand_id, category_id, sub_category_id, type_id, price, warranty_period_months 
    FROM items 
    WHERE is_active = 1 
    ORDER BY item_code, name
")->fetchAll();

// Build sub-category lookup
$sub_lookup = [];
foreach($all_sub as $sub) {
    $sub_lookup[$sub['parent_id']][] = $sub;
}

// Store existing items data for JavaScript - CRITICAL
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

foreach($devices as $device):
    $status_class = $device['status'] ?? 'pending';
    $is_existing = !empty($device['existing_item_id']) || !empty($device['new_item_id']);
    $existing_item_id = $device['new_item_id'] ?? $device['existing_item_id'] ?? 0;
    $item_name_display = $device['existing_item_name'] ?? '';
    $item_code_display = $device['existing_item_code'] ?? '';
    $item_price_display = $device['existing_item_price'] ?? 0;
    $item_warranty_display = $device['existing_item_warranty'] ?? 12;
    $is_added_to_stock = ($device['status'] == 'added_to_stock');
    
    $existing_item_details = null;
    if($existing_item_id) {
        foreach($existing_items as $item) {
            if($item['id'] == $existing_item_id) {
                $existing_item_details = $item;
                break;
            }
        }
    }
    
    $has_linked_item = ($existing_item_details !== null);
?>
<div class="device-card" id="device-card-<?php echo $device['id']; ?>">
    <div class="device-header">
        <div>
            <strong><?php echo htmlspecialchars($device['device_name']); ?></strong>
            <?php if($device['brand_name']): ?>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($device['brand_name']); ?></span>
            <?php endif; ?>
            <?php if($device['model_number']): ?>
                <span class="badge bg-light text-dark">Model: <?php echo htmlspecialchars($device['model_number']); ?></span>
            <?php endif; ?>
            <?php if($device['serial_number']): ?>
                <span class="badge bg-light text-dark">SN: <?php echo htmlspecialchars($device['serial_number']); ?></span>
            <?php endif; ?>
        </div>
        <div>
            <span class="status-badge status-<?php echo $status_class; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $status_class)); ?>
            </span>
            <?php if($is_added_to_stock): ?>
                <span class="badge bg-success ms-2"><i class="fas fa-check-circle"></i> In Stock</span>
            <?php elseif($has_linked_item): ?>
                <span class="badge bg-info ms-2"><i class="fas fa-link"></i> Linked</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="device-body">
        <!-- View Mode -->
        <div id="view-mode-<?php echo $device['id']; ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row"><span class="info-label">Device Name:</span> <?php echo htmlspecialchars($device['device_name']); ?></div>
                    <?php if($device['brand_name']): ?>
                    <div class="info-row"><span class="info-label">Brand:</span> <?php echo htmlspecialchars($device['brand_name']); ?></div>
                    <?php endif; ?>
                    <?php if($device['model_number']): ?>
                    <div class="info-row"><span class="info-label">Model:</span> <?php echo htmlspecialchars($device['model_number']); ?></div>
                    <?php endif; ?>
                    <?php if($device['serial_number']): ?>
                    <div class="info-row"><span class="info-label">Serial:</span> <code><?php echo htmlspecialchars($device['serial_number']); ?></code></div>
                    <?php endif; ?>
                    <?php if($device['specification']): ?>
                    <div class="info-row"><span class="info-label">Specs:</span> <?php echo nl2br(htmlspecialchars($device['specification'])); ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <?php if($device['item_type_id'] || $device['type_name']): ?>
                    <div class="info-row"><span class="info-label">Type:</span> <?php echo htmlspecialchars($device['type_name'] ?? 'N/A'); ?></div>
                    <?php endif; ?>
                    <?php if($device['final_category_id'] || $device['category_name']): ?>
                    <div class="info-row"><span class="info-label">Category:</span> <?php echo htmlspecialchars($device['category_name'] ?? 'N/A'); ?></div>
                    <?php endif; ?>
                    <?php if($device['final_sub_category_id'] || $device['sub_category_name']): ?>
                    <div class="info-row"><span class="info-label">Sub-Category:</span> <?php echo htmlspecialchars($device['sub_category_name'] ?? 'N/A'); ?></div>
                    <?php endif; ?>
                    <?php if($device['final_brand_id'] || $device['brand_name']): ?>
                    <div class="info-row"><span class="info-label">Brand (System):</span> <?php echo htmlspecialchars($device['brand_name'] ?? 'N/A'); ?></div>
                    <?php endif; ?>
                    <div class="info-row"><span class="info-label">Condition:</span> <?php echo ucfirst($device['current_condition'] ?? 'Good'); ?></div>
                    <?php if($device['final_price'] || $device['price']): ?>
                    <div class="info-row"><span class="info-label">Price:</span> <strong>৳<?php echo number_format($device['final_price'] ?? $device['price'], 2); ?></strong></div>
                    <?php endif; ?>
                    <?php if($device['final_warranty_months']): ?>
                    <div class="info-row"><span class="info-label">Warranty:</span> <?php echo $device['final_warranty_months']; ?> months</div>
                    <?php endif; ?>
                    <?php if($device['final_quantity']): ?>
                    <div class="info-row"><span class="info-label">Quantity:</span> <?php echo $device['final_quantity']; ?></div>
                    <?php endif; ?>
                    <?php if($device['assigned_date']): ?>
                    <div class="info-row"><span class="info-label">Assigned Date:</span> <?php echo date('d-m-Y', strtotime($device['assigned_date'])); ?></div>
                    <?php endif; ?>
                    <?php if($device['assigned_by']): ?>
                    <div class="info-row"><span class="info-label">Assigned By:</span> <?php echo htmlspecialchars($device['assigned_by']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if($has_linked_item && $existing_item_details): ?>
            <div class="existing-item-tag" style="background: #3b82f6; margin-top: 15px; padding: 10px 15px; border-radius: 8px;">
                <i class="fas fa-link me-2"></i> 
                <strong>Linked to existing item:</strong> 
                <span style="background: rgba(255,255,255,0.2); padding: 2px 10px; border-radius: 4px; margin: 0 5px;">
                    <?php echo htmlspecialchars($existing_item_details['item_code']); ?>
                </span>
                <strong><?php echo htmlspecialchars($existing_item_details['name']); ?></strong>
                <?php if($existing_item_details['price'] > 0): ?>
                    <span class="badge bg-light text-dark ms-2">৳<?php echo number_format($existing_item_details['price'], 2); ?></span>
                <?php endif; ?>
                <?php if($existing_item_details['warranty_period_months']): ?>
                    <span class="badge bg-light text-dark ms-1"><?php echo $existing_item_details['warranty_period_months']; ?> months warranty</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if($device['admin_notes']): ?>
            <div class="mt-2 small text-muted"><i class="fas fa-sticky-note"></i> <?php echo nl2br(htmlspecialchars($device['admin_notes'])); ?></div>
            <?php endif; ?>
            
            <div class="mt-3">
                <div class="device-action-buttons">
                    <?php if(!$is_added_to_stock): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="toggleEdit(<?php echo $device['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    <?php else: ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Added to Stock - Locked</span>
                    <?php endif; ?>
                    
                    <?php if($status_class != 'added_to_stock' && $status_class != 'rejected'): ?>
                        <button class="btn btn-sm btn-outline-success" onclick="approveDevice(<?php echo $device['id']; ?>)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-sm btn-outline-warning" onclick="addDeviceToStock(<?php echo $device['id']; ?>)">
                            <i class="fas fa-box"></i> Add to Stock
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="rejectDevice(<?php echo $device['id']; ?>)">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    <?php endif; ?>
                    
                    <?php if($status_class == 'added_to_stock'): ?>
                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Added to Stock</span>
                    <?php endif; ?>
                    
                    <?php if($status_class == 'rejected'): ?>
                        <span class="badge bg-danger"><i class="fas fa-times-circle"></i> Rejected</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Edit Mode -->
        <div id="edit-mode-<?php echo $device['id']; ?>" style="display:none;">
            <?php if(!$is_added_to_stock): ?>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Device Name</label>
                    <input type="text" class="form-control form-control-sm" id="name_<?php echo $device['id']; ?>" value="<?php echo htmlspecialchars($device['device_name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Brand</label>
                    <input type="text" class="form-control form-control-sm" id="brand_<?php echo $device['id']; ?>" value="<?php echo htmlspecialchars($device['brand_name'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Model Number</label>
                    <input type="text" class="form-control form-control-sm" id="model_<?php echo $device['id']; ?>" value="<?php echo htmlspecialchars($device['model_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-control form-control-sm" id="serial_<?php echo $device['id']; ?>" value="<?php echo htmlspecialchars($device['serial_number'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Condition</label>
                    <select class="form-select form-select-sm" id="condition_<?php echo $device['id']; ?>">
                        <option value="good" <?php echo ($device['current_condition'] ?? '') == 'good' ? 'selected' : ''; ?>>Good</option>
                        <option value="minor_damage" <?php echo ($device['current_condition'] ?? '') == 'minor_damage' ? 'selected' : ''; ?>>Minor Damage</option>
                        <option value="major_damage" <?php echo ($device['current_condition'] ?? '') == 'major_damage' ? 'selected' : ''; ?>>Major Damage</option>
                        <option value="not_working" <?php echo ($device['current_condition'] ?? '') == 'not_working' ? 'selected' : ''; ?>>Not Working</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Specifications</label>
                    <textarea class="form-control form-control-sm" id="spec_<?php echo $device['id']; ?>" rows="2"><?php echo htmlspecialchars($device['specification'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Item Type</label>
                    <select class="form-select form-select-sm" id="item_type_<?php echo $device['id']; ?>">
                        <option value="">-- Select --</option>
                        <?php foreach($item_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($device['item_type_id'] ?? '') == $type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select class="form-select form-select-sm" id="category_<?php echo $device['id']; ?>" onchange="loadSubCategories(<?php echo $device['id']; ?>, this.value)">
                        <option value="">-- Select --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($device['final_category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Sub-Category</label>
                    <select class="form-select form-select-sm" id="subcat_<?php echo $device['id']; ?>">
                        <option value="">-- Select --</option>
                        <?php 
                        $parent_id = $device['final_category_id'] ?? 0;
                        if($parent_id && isset($sub_lookup[$parent_id])):
                            foreach($sub_lookup[$parent_id] as $sub):
                        ?>
                            <option value="<?php echo $sub['id']; ?>" <?php echo ($device['final_sub_category_id'] ?? '') == $sub['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sub['name']); ?></option>
                        <?php endforeach; endif; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Brand (System)</label>
                    <select class="form-select form-select-sm" id="brand_id_<?php echo $device['id']; ?>">
                        <option value="">-- Select --</option>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo ($device['final_brand_id'] ?? '') == $brand['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Warranty (Months)</label>
                    <input type="number" class="form-control form-control-sm" id="warranty_<?php echo $device['id']; ?>" value="<?php echo $device['final_warranty_months'] ?? 12; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price (BDT)</label>
                    <input type="number" class="form-control form-control-sm" id="price_<?php echo $device['id']; ?>" value="<?php echo $device['final_price'] ?? $device['price'] ?? ''; ?>" step="0.01">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control form-control-sm" id="qty_<?php echo $device['id']; ?>" value="<?php echo $device['final_quantity'] ?? 1; ?>" min="1">
                </div>
                <div class="col-md-12" style="position: relative;">
                    <label class="form-label">Existing Item (Search to link)</label>
                    <input type="text" class="form-control form-control-sm existing-item-search" 
                           id="existing_item_search_<?php echo $device['id']; ?>" 
                           data-device-id="<?php echo $device['id']; ?>"
                           placeholder="🔍 Type to search existing item by name or code..."
                           value="<?php echo $has_linked_item && $existing_item_details ? htmlspecialchars($existing_item_details['item_code'] . ' - ' . $existing_item_details['name']) : ''; ?>"
                           autocomplete="off"
                           style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; width: 100%;">
                    <div class="existing-item-results" id="existing_item_results_<?php echo $device['id']; ?>" 
                         style="display: none; position: absolute; z-index: 9999; background: white; border: 2px solid #e2e8f0; border-radius: 8px; max-height: 250px; overflow-y: auto; width: 100%; margin-top: 4px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    </div>
                    <input type="hidden" id="existing_item_hidden_<?php echo $device['id']; ?>" value="<?php echo $existing_item_id; ?>">
                    <div id="existing_item_tag_<?php echo $device['id']; ?>" style="display: <?php echo $has_linked_item ? 'block' : 'none'; ?>; margin-top: 8px;">
                        <span class="existing-item-tag">
                            <i class="fas fa-link"></i> Linked to: <strong id="existing_item_name_<?php echo $device['id']; ?>">
                                <?php echo $has_linked_item && $existing_item_details ? htmlspecialchars($existing_item_details['item_code'] . ' - ' . $existing_item_details['name']) : htmlspecialchars($item_name_display); ?>
                            </strong>
                            <button type="button" class="remove-link" onclick="clearExistingItemLink(<?php echo $device['id']; ?>)">✕</button>
                        </span>
                    </div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control form-control-sm" id="notes_<?php echo $device['id']; ?>" rows="2"><?php echo htmlspecialchars($device['notes'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-12 mt-2 text-end">
                    <button class="btn btn-sm btn-primary" onclick="saveDevice(<?php echo $device['id']; ?>)"><i class="fas fa-save"></i> Save</button>
                    <button class="btn btn-sm btn-secondary" onclick="cancelEdit(<?php echo $device['id']; ?>)"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-lock me-2"></i> 
                    This device has been <strong>added to stock</strong> and cannot be edited.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>