<?php
// Force remove restrictive CSP
if(headers_sent() === false) {
    header_remove('Content-Security-Policy');
    header_remove('X-Content-Security-Policy');
    header_remove('X-WebKit-CSP');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data: blob:; style-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; img-src 'self' data: https:;");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use the proper auth system - FIXED
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Make sure $pdo is available globally
global $pdo;

$upload_dir = '../../uploads/bills/';
if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

function generateNumber($prefix, $table, $column) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) as max_num FROM $table WHERE $column LIKE ?");
        $stmt->execute([$prefix . '%']);
        $row = $stmt->fetch();
        $next = ($row['max_num'] ?? 0) + 1;
        return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
    } catch(Exception $e) {
        return $prefix . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}

// Get data
$vendors = $pdo->query("SELECT * FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();

// Fix: Use COALESCE to handle NULL prices properly
$existing_items = $pdo->query("SELECT id, item_code, name, COALESCE(regular_price, price, 0) as price, current_qty FROM items WHERE is_active = 1 ORDER BY name")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
$item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get cash register balance
$current_month = date('Y-m-01');
$cash_balance = 0;
try {
    $stmt = $pdo->prepare("SELECT (opening_balance + total_cash_in - total_cash_out - total_purchases) as balance FROM cash_register WHERE month_year = ? AND is_closed = 0");
    $stmt->execute([$current_month]);
    $cash_balance = $stmt->fetch()['balance'] ?? 0;
} catch(Exception $e) { $cash_balance = 0; }

$success_message = '';
$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vendor_id = $_POST['vendor_id'] ?? 0;
    $bill_date = $_POST['bill_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? null;
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $payment_mode = $_POST['payment_mode'] ?? null;
    $cheque_no = $_POST['cheque_no'] ?? null;
    $remarks = $_POST['remarks'] ?? '';
    
    $bill_items = json_decode($_POST['bill_items'] ?? '[]', true);
    $new_items = json_decode($_POST['new_items'] ?? '[]', true);
    
    if(empty($bill_items)) {
        $error_message = "Please add at least one item to the bill.";
    } else {
        $pdo->beginTransaction();
        try {
            $bill_no = generateNumber('BILL-', 'bills', 'bill_no');
            $total_amount = 0;
            foreach($bill_items as $item) $total_amount += floatval($item['quantity']) * floatval($item['price']);
            
            $stmt = $pdo->prepare("INSERT INTO bills (bill_no, vendor_id, bill_date, due_date, total_amount, paid_amount, balance_amount, status, remarks, stock_items_count, created_by) VALUES (?, ?, ?, ?, ?, 0, ?, 'pending', ?, ?, ?)");
            $stmt->execute([$bill_no, $vendor_id, $bill_date, $due_date, $total_amount, $total_amount, $remarks, count($bill_items), $_SESSION['user_id']]);
            $bill_id = $pdo->lastInsertId();
            
            $item_id_mapping = [];
            foreach($new_items as $new_item) {
                $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(item_code, 5) AS UNSIGNED)) as max_num FROM items WHERE item_code LIKE 'ITM-%'");
                $stmt->execute();
                $next = ($stmt->fetch()['max_num'] ?? 0) + 1;
                $item_code = 'ITM-' . str_pad($next, 6, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO items (item_code, name, specification, category_id, brand_id, type_id, regular_price, price, warranty_period, min_qty, current_qty, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, 1, ?)");
                $stmt->execute([$item_code, $new_item['name'], $new_item['specification'] ?? '', $new_item['category_id'] ?? null, $new_item['brand_id'] ?? null, $new_item['type_id'] ?? null, $new_item['price'], $new_item['price'], $new_item['warranty_period'] ?? 12, $_SESSION['user_id']]);
                $item_id = $pdo->lastInsertId();
                $item_id_mapping[$new_item['temp_id']] = $item_id;
                
                if(!empty($new_item['serials'])) {
                    foreach($new_item['serials'] as $serial_data) {
                        if(!empty($serial_data['serial_number'])) {
                            $stmt = $pdo->prepare("INSERT INTO item_serial_numbers (item_id, serial_number, model_number, version, is_assigned) VALUES (?, ?, ?, ?, 0)");
                            $stmt->execute([$item_id, $serial_data['serial_number'], $serial_data['model_number'] ?? '', $serial_data['version'] ?? '']);
                        }
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
                $stmt->execute([intval($new_item['quantity']), $item_id]);
            }
            
            foreach($bill_items as $bill_item) {
                $item_id = ($bill_item['is_new'] == 1 && isset($item_id_mapping[$bill_item['temp_id']])) ? $item_id_mapping[$bill_item['temp_id']] : $bill_item['item_id'];
                $quantity = intval($bill_item['quantity']);
                $unit_price = floatval($bill_item['price']);
                $total_price = $quantity * $unit_price;
                
                $stmt = $pdo->prepare("INSERT INTO bill_items (bill_id, item_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$bill_id, $item_id, $quantity, $unit_price, $total_price]);
                
                if($bill_item['is_new'] != 1) {
                    $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
                    $stmt->execute([$quantity, $item_id]);
                }
            }
            
            if($payment_status == 'paid') {
                $slip_no = generateNumber('SLIP-', 'payment_slips', 'slip_no');
                $stmt = $pdo->prepare("INSERT INTO payment_slips (slip_no, bill_id, vendor_id, payment_amount, payment_date, payment_mode, cheque_no, generated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$slip_no, $bill_id, $vendor_id, $total_amount, $bill_date, $payment_mode, $cheque_no, $_SESSION['user_id']]);
                
                $stmt = $pdo->prepare("UPDATE bills SET status = 'paid', paid_amount = ?, balance_amount = 0, payment_date = ?, payment_mode = ?, payment_count = 1 WHERE id = ?");
                $stmt->execute([$total_amount, $bill_date, $payment_mode, $bill_id]);
            }
            
            $pdo->commit();
            $success_message = "Bill created successfully! Bill No: " . htmlspecialchars($bill_no);
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error_message = "Error: " . $e->getMessage();
        }
    }
}
?>

<!-- Add New Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Add New Item (Temporary)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Item Name <span class="text-danger">*</span></label>
                        <input type="text" id="item_name" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select id="category_id" class="form-select">
                            <option value="">-- Select Category --</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Brand</label>
                        <select id="brand_id" class="form-select">
                            <option value="">-- Select Brand --</option>
                            <?php foreach($brands as $brand): ?>
                                <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Item Type</label>
                        <select id="type_id" class="form-select">
                            <option value="">-- Select Type --</option>
                            <?php foreach($item_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Specifications</label>
                        <textarea id="specification" rows="2" class="form-control" placeholder="RAM, Processor, Storage, etc."></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" id="item_quantity" class="form-control" min="1" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Unit Price (BDT) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" id="regular_price" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warranty (Months)</label>
                        <input type="number" id="warranty_period" class="form-control" value="12">
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Serial Numbers (Optional):</strong> Each serial number represents one unit.
                </div>
                <h6 class="mt-3"><i class="fas fa-qrcode me-2"></i> Serial Numbers</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr><th width="35%">Serial Number</th><th width="35%">Model Number</th><th width="20%">Version</th><th width="10%">Action</th></tr>
                        </thead>
                        <tbody id="serialBody">
                            <tr class="serial-row">
                                <td><input type="text" class="form-control form-control-sm serial-input" placeholder="Enter Serial"></td>
                                <td><input type="text" class="form-control form-control-sm model-input" placeholder="Model Number"></td>
                                <td><input type="text" class="form-control form-control-sm version-input" placeholder="Version"></td>
                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-serial" disabled><i class="fas fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot><tr><td colspan="4"><button type="button" class="btn btn-success btn-sm" id="addSerialBtn"><i class="fas fa-plus"></i> Add Another Serial</button></td></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveItemBtn"><i class="fas fa-save"></i> Add to Bill (Temporary)</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Existing Item Modal - with Search Input and Serial Numbers (like stock_in.php) -->
<div class="modal fade" id="addExistingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-box me-2"></i> Add Existing Item</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Search Section -->
                <div class="mb-3">
                    <label class="form-label">Search & Select Item <span class="text-danger">*</span></label>
                    <input type="text" id="itemSearchInput" class="form-control" placeholder="Type to search for items by name or item code...">
                    <div id="itemSearchResults" class="mt-2" style="max-height: 250px; overflow-y: auto; display: none; border: 1px solid #e2e8f0; border-radius: 8px; padding: 5px;">
                        <!-- Results will be populated here -->
                    </div>
                    <small class="text-muted">Type to search for items by name or item code</small>
                    <input type="hidden" id="selectedItemId" value="">
                    <input type="hidden" id="selectedItemName" value="">
                    <input type="hidden" id="selectedItemCode" value="">
                    <input type="hidden" id="selectedItemPrice" value="">
                </div>
                
                <!-- Selected Item Display -->
                <div id="selectedItemDisplay" style="display: none;" class="alert alert-info">
                    <strong>Selected:</strong> <span id="selectedItemDisplayText"></span>
                    <button type="button" class="btn-close float-end" onclick="clearSelection()" style="position: relative; top: -2px;"></button>
                </div>
                
                <!-- Quantity & Price -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" id="existingQty" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unit Price (BDT)</label>
                        <input type="number" step="0.01" id="existingPrice" class="form-control">
                        <small class="text-muted">Leave empty to use default price</small>
                    </div>
                </div>
                
                <!-- Serial Numbers Section -->
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Serial Numbers (Optional):</strong> Add serial numbers for the items being added.
                </div>
                <h6 class="mt-3"><i class="fas fa-qrcode me-2"></i> Serial Numbers for this Stock</h6>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr><th width="35%">Serial Number</th><th width="35%">Model Number</th><th width="20%">Version</th><th width="10%">Action</th></tr>
                        </thead>
                        <tbody id="existingSerialBody">
                            <tr class="existing-serial-row">
                                <td><input type="text" class="form-control form-control-sm existing-serial-input" placeholder="Enter Serial Number"></td>
                                <td><input type="text" class="form-control form-control-sm existing-model-input" placeholder="Model Number"></td>
                                <td><input type="text" class="form-control form-control-sm existing-version-input" placeholder="Version"></td>
                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-existing-serial" disabled><i class="fas fa-trash"></i></button></td>
                            </tr>
                        </tbody>
                        <tfoot><tr><td colspan="4"><button type="button" class="btn btn-success btn-sm" id="addExistingSerialBtn"><i class="fas fa-plus"></i> Add Another Serial</button></td></tr></tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmExistingBtn"><i class="fas fa-plus"></i> Add to Bill</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-file-invoice-dollar text-primary"></i> Create Bill / Purchase</h2>
                    <p class="text-muted mb-0">Create new purchase bill with multiple items - Items saved ONLY when you click "Create Bill"</p>
                </div>
                <div>
                    <span class="badge bg-warning p-2"><i class="fas fa-exclamation-triangle me-1"></i> Items are temporary until saved</span>
                </div>
            </div>
            <hr class="mt-2">
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <hr class="my-2">
            <a href="list_bills.php" class="btn btn-sm btn-success"><i class="fas fa-list"></i> View Bills</a>
            <a href="create_bill.php" class="btn btn-sm btn-outline-success"><i class="fas fa-plus"></i> New Bill</a>
        </div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Main Row: Left Form (8 cols) + Right Sidebar (4 cols) -->
    <div class="row">
        <!-- LEFT COLUMN - Bill Form (8 columns) -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i> Bill Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="billForm">
                        <input type="hidden" name="bill_items" id="bill_items_input">
                        <input type="hidden" name="new_items" id="new_items_input">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select name="vendor_id" id="vendor_id" class="form-select" required>
                                    <option value="">-- Select Vendor --</option>
                                    <?php foreach($vendors as $vendor): ?>
                                        <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['vendor_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bill Number</label>
                                <input type="text" class="form-control" value="Auto-generated on save" readonly>
                                <small class="text-muted">Auto-generated when saved</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Bill Date</label>
                                <input type="date" name="bill_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Payment Status</label>
                                <select name="payment_status" id="paymentStatus" class="form-select">
                                    <option value="pending">⏳ Pending - Will Pay Later</option>
                                    <option value="paid">✅ Paid - Process Payment Now</option>
                                </select>
                            </div>
                            
                            <div id="paymentDetails" style="display: none;" class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" id="paymentMode" class="form-select">
                                        <option value="cash">💵 Cash</option>
                                        <option value="cheque">📝 Cheque</option>
                                        <option value="bank_transfer">🏦 Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="chequeDiv" style="display:none;">
                                    <label class="form-label">Cheque Number</label>
                                    <input type="text" name="cheque_no" class="form-control">
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Remarks / Notes</label>
                                <textarea name="remarks" rows="3" class="form-control" placeholder="Any additional information about this purchase..."></textarea>
                            </div>
                        </div>
                        
                        <!-- Items Section -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-boxes text-success me-2"></i> Items (Temporary List)</h5>
                                <div>
                                    <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="addExistingItemBtn">
                                        <i class="fas fa-plus me-1"></i> Add Existing Item
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm" id="showAddItemModalBtn">
                                        <i class="fas fa-plus-circle me-1"></i> Add New Item
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="35%">Item / Device</th>
                                            <th width="15%">Quantity</th>
                                            <th width="20%">Unit Price (BDT)</th>
                                            <th width="20%">Total Price (BDT)</th>
                                            <th width="10%">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsBody">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox me-2"></i> No items added. Click "Add Existing Item" or "Add New Item" to start.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Total Amount Box -->
                        <div class="row mt-3">
                            <div class="col-md-5 offset-md-7">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span><strong>Subtotal:</strong></span>
                                            <span><strong id="subtotal">0.00</strong> BDT</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between">
                                            <span><strong class="fs-5">Total Amount:</strong></span>
                                            <span><strong class="text-success fs-4" id="grandTotal">0.00</strong> BDT</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg px-4" id="submitBtn">
                                <i class="fas fa-save me-2"></i> Create Bill (Save All Items)
                            </button>
                            <a href="list_bills.php" class="btn btn-secondary btn-lg px-4">
                                <i class="fas fa-times me-2"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- RIGHT COLUMN - Sidebar (4 columns) -->
        <div class="col-lg-4">
            <!-- Instructions Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Instructions</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Items are <strong>temporary</strong> until you click "Create Bill"</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> No database writes until final submission</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> No double stock entries!</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Edit quantities/prices before saving</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> You can add both new and existing items</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Paid status generates payment acknowledgement</li>
                    </ul>
                </div>
            </div>
            
            <!-- Cash Balance Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-cash-register me-2"></i> Cash Register Balance</h5>
                </div>
                <div class="card-body text-center">
                    <h2 class="mb-0 text-success">৳ <?php echo number_format(floatval($cash_balance), 2); ?></h2>
                    <small class="text-muted">Available for payments</small>
                    <hr>
                    <a href="../cash_register/register.php" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-cash-register"></i> Manage Cash Register
                    </a>
                </div>
            </div>
            
            <!-- Recent Bills Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Recent Bills</h5>
                </div>
                <div class="card-body" style="max-height: 280px; overflow-y: auto;">
                    <?php
                    $recent_bills = $pdo->query("SELECT bill_no, total_amount, bill_date, status FROM bills ORDER BY id DESC LIMIT 5");
                    if($recent_bills->rowCount() > 0):
                        foreach($recent_bills as $bill):
                            $status_badge = $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'partial' ? 'warning' : 'danger');
                    ?>
                    <div class="border-bottom pb-2 mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong>
                            <span class="badge bg-<?php echo $status_badge; ?>"><?php echo ucfirst($bill['status']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted"><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></small>
                            <small class="text-success fw-bold">৳<?php echo number_format(floatval($bill['total_amount']), 2); ?></small>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                    <p class="text-muted text-center mb-0">No recent bills</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global variables
var temporaryBillItems = [];
var temporaryNewItems = [];
var nextTempId = 1;
var existingItems = <?php echo json_encode($existing_items); ?>;

function escapeHtml(text) {
    if(!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

function calculateAllTotals() {
    var subtotal = 0;
    for(var i = 0; i < temporaryBillItems.length; i++) {
        subtotal += temporaryBillItems[i].quantity * temporaryBillItems[i].price;
    }
    $('#subtotal').text(subtotal.toFixed(2));
    $('#grandTotal').text(subtotal.toFixed(2));
}

function renderItems() {
    var tbody = $('#itemsBody');
    tbody.empty();
    
    if(temporaryBillItems.length === 0) {
        tbody.html('<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox me-2"></i> No items added. Click "Add Existing Item" or "Add New Item" to start. </td></tr>');
        calculateAllTotals();
        return;
    }
    
    for(var i = 0; i < temporaryBillItems.length; i++) {
        var item = temporaryBillItems[i];
        var row = $('<tr class="item-row">');
        
        var nameCell = $('<td>');
        if(item.is_new) {
            nameCell.html('<span class="badge bg-warning me-1">NEW</span> ' + escapeHtml(item.name));
        } else {
            nameCell.html('<i class="fas fa-box text-secondary me-1"></i> ' + escapeHtml(item.item_code + ' - ' + item.name));
        }
        row.append(nameCell);
        
        var qtyCell = $('<td>');
        var qtyInput = $('<input type="number" class="form-control form-control-sm qty" min="1" value="' + item.quantity + '">');
        qtyInput.on('change', (function(idx) {
            return function() {
                temporaryBillItems[idx].quantity = parseInt($(this).val()) || 1;
                renderItems();
            };
        })(i));
        qtyCell.append(qtyInput);
        row.append(qtyCell);
        
        var priceCell = $('<td>');
        var priceInput = $('<input type="number" step="0.01" class="form-control form-control-sm price" value="' + item.price + '">');
        priceInput.on('change', (function(idx) {
            return function() {
                temporaryBillItems[idx].price = parseFloat($(this).val()) || 0;
                renderItems();
            };
        })(i));
        priceCell.append(priceInput);
        row.append(priceCell);
        
        var totalCell = $('<td>');
        totalCell.html('<strong class="text-success">৳' + (item.quantity * item.price).toFixed(2) + '</strong>');
        row.append(totalCell);
        
        var actionCell = $('<td class="text-center">');
        var deleteBtn = $('<button type="button" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>');
        deleteBtn.on('click', (function(idx) {
            return function() {
                var itemToDelete = temporaryBillItems[idx];
                if(itemToDelete.is_new) {
                    for(var j = 0; j < temporaryNewItems.length; j++) {
                        if(temporaryNewItems[j].temp_id === itemToDelete.temp_id) {
                            temporaryNewItems.splice(j, 1);
                            break;
                        }
                    }
                }
                temporaryBillItems.splice(idx, 1);
                renderItems();
            };
        })(i));
        actionCell.append(deleteBtn);
        row.append(actionCell);
        
        tbody.append(row);
    }
    calculateAllTotals();
}

// ============================================
// SEARCH FUNCTIONALITY (same as stock_in.php)
// ============================================
var searchTimeout = null;

function performSearch(query) {
    var resultsContainer = $('#itemSearchResults');
    
    if(query.length < 1) {
        resultsContainer.hide();
        return;
    }
    
    query = query.toLowerCase().trim();
    var results = [];
    
    for(var i = 0; i < existingItems.length; i++) {
        var item = existingItems[i];
        var searchText = (item.item_code + ' ' + item.name).toLowerCase();
        if(searchText.indexOf(query) !== -1) {
            results.push(item);
        }
    }
    
    if(results.length === 0) {
        resultsContainer.html('<div class="text-center text-muted py-3"><i class="fas fa-search me-2"></i> No items found matching "' + escapeHtml(query) + '"</div>');
        resultsContainer.show();
        return;
    }
    
    var html = '';
    for(var i = 0; i < results.length && i < 20; i++) {
        var item = results[i];
        var price = parseFloat(item.price) || 0;
        html += '<div class="search-result-item" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f1f1; transition: background 0.2s;" onmouseover="this.style.background=\'#eff6ff\'" onmouseout="this.style.background=\'white\'" onclick="selectItem(' + item.id + ', \'' + escapeHtml(item.name) + '\', \'' + escapeHtml(item.item_code) + '\', ' + price + ')">';
        html += '<div><strong>' + escapeHtml(item.item_code) + '</strong> - ' + escapeHtml(item.name) + '</div>';
        html += '<div class="text-muted" style="font-size: 12px;">Stock: ' + item.current_qty + ' | Price: ৳' + price.toFixed(2) + '</div>';
        html += '</div>';
    }
    if(results.length > 20) {
        html += '<div class="text-center text-muted py-2" style="font-size: 12px;">Showing first 20 of ' + results.length + ' results</div>';
    }
    resultsContainer.html(html);
    resultsContainer.show();
}

// Select item from search results
function selectItem(id, name, code, price) {
    $('#selectedItemId').val(id);
    $('#selectedItemName').val(name);
    $('#selectedItemCode').val(code);
    $('#selectedItemPrice').val(price);
    $('#selectedItemDisplayText').text(code + ' - ' + name + ' (Price: ৳' + price.toFixed(2) + ')');
    $('#selectedItemDisplay').show();
    $('#itemSearchResults').hide();
    $('#itemSearchInput').val('');
    
    // Auto-fill price
    if(price > 0) {
        $('#existingPrice').val(price);
    }
}

// Clear selection
function clearSelection() {
    $('#selectedItemId').val('');
    $('#selectedItemName').val('');
    $('#selectedItemCode').val('');
    $('#selectedItemPrice').val('');
    $('#selectedItemDisplay').hide();
    $('#itemSearchInput').val('');
    $('#existingPrice').val('');
}

// ============================================
// MODAL FUNCTIONS
// ============================================

// Show Add New Item Modal
$('#showAddItemModalBtn').click(function() {
    $('#item_name').val('');
    $('#category_id').val('');
    $('#brand_id').val('');
    $('#type_id').val('');
    $('#specification').val('');
    $('#item_quantity').val('1');
    $('#regular_price').val('');
    $('#warranty_period').val('12');
    
    $('#serialBody').html('<tr class="serial-row">' +
        '<td><input type="text" class="form-control form-control-sm serial-input" placeholder="Enter Serial Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm model-input" placeholder="Model Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm version-input" placeholder="Version"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-serial" disabled><i class="fas fa-trash"></i></button></td>' +
        '</tr>');
    
    $('#addItemModal').modal('show');
});

// Add Serial Row for New Item
$('#addSerialBtn').click(function() {
    var newRow = '<tr class="serial-row">' +
        '<td><input type="text" class="form-control form-control-sm serial-input" placeholder="Enter Serial Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm model-input" placeholder="Model Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm version-input" placeholder="Version"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-serial"><i class="fas fa-trash"></i></button></td>' +
        '</tr>';
    $('#serialBody').append(newRow);
});

// Remove serial row for New Item
$(document).on('click', '.remove-serial', function() {
    if($('.serial-row').length > 1) {
        $(this).closest('.serial-row').remove();
    }
});

// Save New Item
$('#saveItemBtn').click(function() {
    var name = $('#item_name').val().trim();
    var catId = $('#category_id').val();
    var price = parseFloat($('#regular_price').val());
    var qty = parseInt($('#item_quantity').val()) || 1;
    var spec = $('#specification').val();
    var brandId = $('#brand_id').val();
    var typeId = $('#type_id').val();
    var warranty = parseInt($('#warranty_period').val()) || 12;
    
    var serials = [];
    $('.serial-input').each(function(idx) {
        var sn = $(this).val().trim();
        if(sn) {
            serials.push({
                serial_number: sn,
                model_number: $('.model-input').eq(idx).val() || '',
                version: $('.version-input').eq(idx).val() || ''
            });
        }
    });
    
    if(!name) { alert('Please enter Item Name'); return; }
    if(!catId) { alert('Please select Category'); return; }
    if(isNaN(price) || price <= 0) { alert('Please enter a valid Unit Price'); return; }
    
    var tid = nextTempId++;
    
    temporaryNewItems.push({
        temp_id: tid, name: name, category_id: catId, brand_id: brandId || null,
        type_id: typeId || null, specification: spec, price: price, quantity: qty,
        warranty_period: warranty, serials: serials
    });
    
    temporaryBillItems.push({
        temp_id: tid, item_id: null, name: name, quantity: qty, price: price, is_new: true
    });
    
    renderItems();
    $('#addItemModal').modal('hide');
});

// ============================================
// ADD EXISTING ITEM - with Search and Serials
// ============================================

// Open Add Existing Modal
$('#addExistingItemBtn').click(function() {
    // Reset selection
    $('#selectedItemId').val('');
    $('#selectedItemName').val('');
    $('#selectedItemCode').val('');
    $('#selectedItemPrice').val('');
    $('#selectedItemDisplay').hide();
    $('#itemSearchInput').val('');
    $('#existingQty').val('1');
    $('#existingPrice').val('');
    $('#itemSearchResults').hide();
    
    // Reset serial rows for existing items
    $('#existingSerialBody').html('<tr class="existing-serial-row">' +
        '<td><input type="text" class="form-control form-control-sm existing-serial-input" placeholder="Enter Serial Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm existing-model-input" placeholder="Model Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm existing-version-input" placeholder="Version"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-existing-serial" disabled><i class="fas fa-trash"></i></button></td>' +
        '</tr>');
    
    $('#addExistingModal').modal('show');
    
    // Focus on search input after modal is shown
    setTimeout(function() {
        $('#itemSearchInput').focus();
    }, 300);
});

// Add Serial Row for Existing Item
$('#addExistingSerialBtn').click(function() {
    var newRow = '<tr class="existing-serial-row">' +
        '<td><input type="text" class="form-control form-control-sm existing-serial-input" placeholder="Enter Serial Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm existing-model-input" placeholder="Model Number"></td>' +
        '<td><input type="text" class="form-control form-control-sm existing-version-input" placeholder="Version"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-danger btn-sm remove-existing-serial"><i class="fas fa-trash"></i></button></td>' +
        '</tr>';
    $('#existingSerialBody').append(newRow);
});

// Remove serial row for Existing Item
$(document).on('click', '.remove-existing-serial', function() {
    if($('.existing-serial-row').length > 1) {
        $(this).closest('.existing-serial-row').remove();
    }
});

// Search input handler with debounce
$('#itemSearchInput').on('input', function() {
    var query = $(this).val();
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        performSearch(query);
    }, 300);
});

// Handle Enter key in search input
$('#itemSearchInput').on('keypress', function(e) {
    if(e.which === 13) {
        e.preventDefault();
        var firstResult = $('#itemSearchResults .search-result-item:first');
        if(firstResult.length) {
            firstResult.click();
        }
    }
});

// Confirm Existing Item
$('#confirmExistingBtn').click(function() {
    var itemId = $('#selectedItemId').val();
    var itemName = $('#selectedItemName').val();
    var itemCode = $('#selectedItemCode').val();
    var defaultPrice = parseFloat($('#selectedItemPrice').val()) || 0;
    var qty = parseInt($('#existingQty').val()) || 1;
    var customPrice = $('#existingPrice').val();
    var price = customPrice ? parseFloat(customPrice) : defaultPrice;
    
    // Get serial numbers from existing item modal
    var serials = [];
    $('.existing-serial-input').each(function(idx) {
        var sn = $(this).val().trim();
        if(sn) {
            serials.push({
                serial_number: sn,
                model_number: $('.existing-model-input').eq(idx).val() || '',
                version: $('.existing-version-input').eq(idx).val() || ''
            });
        }
    });
    
    if(!itemId) { alert('Please search and select an item'); return; }
    if(qty <= 0) { alert('Please enter a valid quantity'); return; }
    if(isNaN(price) || price <= 0) { alert('Please enter a valid unit price'); return; }
    
    temporaryBillItems.push({
        temp_id: null, 
        item_id: parseInt(itemId),
        item_code: itemCode,
        name: itemName,
        quantity: qty, 
        price: price, 
        is_new: false,
        serials: serials
    });
    
    renderItems();
    $('#addExistingModal').modal('hide');
});

// Form submit
$('#billForm').submit(function(e) {
    if(temporaryBillItems.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the bill.');
        return false;
    }
    
    if(!$('#vendor_id').val()) {
        e.preventDefault();
        alert('Please select a vendor.');
        return false;
    }
    
    var billData = [];
    for(var i = 0; i < temporaryBillItems.length; i++) {
        billData.push({
            item_id: temporaryBillItems[i].item_id,
            temp_id: temporaryBillItems[i].temp_id,
            quantity: temporaryBillItems[i].quantity,
            price: temporaryBillItems[i].price,
            is_new: temporaryBillItems[i].is_new ? 1 : 0,
            serials: temporaryBillItems[i].serials || []
        });
    }
    
    $('#bill_items_input').val(JSON.stringify(billData));
    $('#new_items_input').val(JSON.stringify(temporaryNewItems));
    
    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Creating Bill...');
    return true;
});

// Payment toggle
$('#paymentStatus').change(function() {
    if($(this).val() === 'paid') {
        $('#paymentDetails').show();
    } else {
        $('#paymentDetails').hide();
        $('#chequeDiv').hide();
    }
});

$('#paymentMode').change(function() {
    if($(this).val() === 'cheque') {
        $('#chequeDiv').show();
    } else {
        $('#chequeDiv').hide();
    }
});

renderItems();
</script>

<style>
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.card-header {
    border-radius: 12px 12px 0 0;
    padding: 12px 20px;
}
.form-label {
    font-weight: 600;
    font-size: 0.75rem;
    margin-bottom: 5px;
    color: #495057;
}
.btn-sm {
    padding: 5px 10px;
}
.modal-lg {
    max-width: 800px;
}
.item-row td {
    vertical-align: middle;
}
.badge-warning {
    background-color: #ffc107;
    color: #000;
}
/* Ensure proper spacing between columns */
.row {
    margin-left: -12px;
    margin-right: -12px;
}
.col-lg-8, .col-lg-4 {
    padding-left: 12px;
    padding-right: 12px;
}

/* Search results styling */
#itemSearchResults {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.search-result-item:hover {
    background: #eff6ff !important;
}
#itemSearchInput:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
</style>

<?php include '../../includes/footer.php'; ?>