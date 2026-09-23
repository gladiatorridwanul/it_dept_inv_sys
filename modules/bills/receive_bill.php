<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$upload_dir = '../../uploads/bills/';
if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// Get active vendors
$vendors = $pdo->query("SELECT * FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();

// Helper function to generate number
function generateNumber($prefix, $table, $column) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) as max_num FROM $table WHERE $column LIKE ?");
    $stmt->execute([$prefix . '%']);
    $row = $stmt->fetch();
    $next = ($row['max_num'] ?? 0) + 1;
    return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
}

// Function to get current cash register balance and details
function getCashRegisterDetails($pdo, $month_year = null) {
    if(!$month_year) {
        $month_year = date('Y-m-01');
    }
    
    $stmt = $pdo->prepare("
        SELECT cr.*, 
               (cr.opening_balance + cr.total_cash_in - cr.total_cash_out - cr.total_purchases) as available_balance
        FROM cash_register cr
        WHERE cr.month_year = ? AND cr.is_closed = 0
    ");
    $stmt->execute([$month_year]);
    $cashRegister = $stmt->fetch();
    
    if($cashRegister) {
        return [
            'register_id' => $cashRegister['id'],
            'opening_balance' => $cashRegister['opening_balance'],
            'available_balance' => $cashRegister['available_balance'],
            'monthly_budget' => $cashRegister['monthly_budget'],
            'remaining_budget' => $cashRegister['monthly_budget'] - $cashRegister['total_purchases'],
            'total_cash_in' => $cashRegister['total_cash_in'],
            'total_cash_out' => $cashRegister['total_cash_out'],
            'total_purchases' => $cashRegister['total_purchases'],
            'is_closed' => $cashRegister['is_closed']
        ];
    }
    return [
        'register_id' => null,
        'available_balance' => 0,
        'is_closed' => false
    ];
}

// Function to add cash register transaction
function addCashRegisterTransaction($pdo, $cash_register_id, $transaction_type, $amount, $description, $reference_id, $reference_type, $user_id) {
    $stmt = $pdo->prepare("
        INSERT INTO cash_register_transactions (cash_register_id, transaction_type, amount, description, reference_id, reference_type, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$cash_register_id, $transaction_type, $amount, $description, $reference_id, $reference_type, $user_id]);
}

// Function to update cash register balance
function updateCashRegisterBalance($pdo, $cash_register_id, $amount, $type) {
    if($type == 'cash_out' || $type == 'purchase' || $type == 'bill_payment') {
        $stmt = $pdo->prepare("
            UPDATE cash_register 
            SET total_cash_out = total_cash_out + ?, 
                closing_balance = closing_balance - ?
            WHERE id = ?
        ");
        return $stmt->execute([$amount, $amount, $cash_register_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE cash_register 
            SET total_cash_in = total_cash_in + ?, 
                closing_balance = closing_balance + ?
            WHERE id = ?
        ");
        return $stmt->execute([$amount, $amount, $cash_register_id]);
    }
}

// Get current month cash register
$current_month = date('Y-m-01');
$cashData = getCashRegisterDetails($pdo, $current_month);
$cash_balance = $cashData['available_balance'];
$active_cash_register_id = $cashData['register_id'];
$is_cash_register_closed = $cashData['is_closed'];

$error = '';
$success = '';
$selected_vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;

// Handle POST for creating bill
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $vendor_id = filter_input(INPUT_POST, 'vendor_id', FILTER_VALIDATE_INT);
    $stock_in_id = filter_input(INPUT_POST, 'stock_in_id', FILTER_VALIDATE_INT);
    $bill_date = $_POST['bill_date'] ?? date('Y-m-d');
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $total_amount = filter_input(INPUT_POST, 'total_amount', FILTER_VALIDATE_FLOAT);
    $remarks = trim($_POST['remarks'] ?? '');
    $bill_number = trim($_POST['bill_number'] ?? '');
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $payment_mode = $_POST['payment_mode'] ?? 'cash';
    $cheque_no = $_POST['cheque_no'] ?? null;
    $notes = trim($_POST['notes'] ?? $remarks);
    
    // Validation
    $errors = [];
    if(!$vendor_id) $errors[] = "Please select a vendor";
    if(!$stock_in_id) $errors[] = "Please select a stock entry";
    if(!$bill_date) $errors[] = "Bill date is required";
    if(!$total_amount || $total_amount <= 0) $errors[] = "Total amount must be greater than 0";
    
    // Check cash balance if payment status is paid
    if($payment_status == 'paid') {
        if(!$active_cash_register_id) {
            $errors[] = "No active cash register found. Please set up cash register first.";
        } elseif($is_cash_register_closed) {
            $errors[] = "Current month cash register is closed. Cannot process payment.";
        } elseif($cash_balance < $total_amount) {
            $errors[] = "Insufficient cash balance! Available: ৳" . number_format($cash_balance, 2) . ". Required: ৳" . number_format($total_amount, 2);
        }
    }
    
    // Handle file upload
    $bill_attachment = null;
    $attachment_type = null;
    
    if(isset($_FILES['bill_attachment']) && $_FILES['bill_attachment']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
        $ext = strtolower(pathinfo($_FILES['bill_attachment']['name'], PATHINFO_EXTENSION));
        $file_size = $_FILES['bill_attachment']['size'];
        
        if(!in_array($ext, $allowed)) {
            $errors[] = "Invalid file type. Allowed: JPG, PNG, PDF";
        } elseif($file_size > 5242880) {
            $errors[] = "File size too large. Max 5MB";
        } else {
            $new_filename = 'BILL_' . date('Ymd_His') . '_' . rand(1000, 9999) . '.' . $ext;
            if(move_uploaded_file($_FILES['bill_attachment']['tmp_name'], $upload_dir . $new_filename)) {
                $bill_attachment = 'uploads/bills/' . $new_filename;
                $attachment_type = $ext;
            } else {
                $errors[] = "Failed to upload file";
            }
        }
    }
    
    if(empty($errors)) {
        // Check if stock entry is still available - FIX: Include ALL conditions
        $checkStmt = $pdo->prepare("
            SELECT s.*, v.vendor_name 
            FROM stock_in s 
            JOIN vendors v ON s.vendor_id = v.id 
            WHERE s.id = ? 
            AND (
                s.bill_id IS NULL 
                OR s.bill_id = 0 
                OR s.bill_status IS NULL 
                OR s.bill_status = '' 
                OR s.bill_status = 'pending'
                OR s.bill_status = 'unbilled'
                OR s.bill_status = '0'
                OR s.bill_status = 'null'
            )
        ");
        $checkStmt->execute([$stock_in_id]);
        $stockEntry = $checkStmt->fetch();
        
        if(!$stockEntry) {
            $error = "This stock entry already has a bill associated with it!";
        } else {
            $bill_no = $bill_number ?: generateNumber('BILL-', 'bills', 'bill_no');
            
            // Get item count and total from stock_in_items
            $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total_price) as total FROM stock_in_items WHERE stock_in_id = ?");
            $stmt->execute([$stock_in_id]);
            $item_data = $stmt->fetch();
            $item_count = $item_data['count'] ?? 0;
            $calculated_total = $item_data['total'] ?? $total_amount;
            
            $pdo->beginTransaction();
            
            try {
                // Insert bill
                $stmt = $pdo->prepare("
                    INSERT INTO bills (bill_no, vendor_id, stock_in_id, bill_date, due_date, total_amount, paid_amount, balance_amount, 
                                      status, bill_attachment, attachment_type, remarks, notes, stock_items_count, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'pending', ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $bill_no, $vendor_id, $stock_in_id, $bill_date, $due_date, 
                    $calculated_total, $calculated_total, $bill_attachment, $attachment_type, 
                    $remarks, $notes, $item_count, $_SESSION['user_id']
                ]);
                $bill_id = $pdo->lastInsertId();
                
                // Update stock_in with bill_id
                $updateStmt = $pdo->prepare("UPDATE stock_in SET bill_id = ?, bill_status = 'received' WHERE id = ?");
                $updateStmt->execute([$bill_id, $stock_in_id]);
                
                $payment_slip_id = null;
                $slip_no = null;
                
                // If payment status is paid, process payment
                if($payment_status == 'paid') {
                    $slip_no = generateNumber('SLIP-', 'payment_slips', 'slip_no');
                    $payment_date = date('Y-m-d');
                    
                    // Insert payment slip
                    $stmt = $pdo->prepare("
                        INSERT INTO payment_slips (slip_no, bill_id, vendor_id, payment_amount, payment_date, payment_mode, cheque_no, generated_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$slip_no, $bill_id, $vendor_id, $calculated_total, $payment_date, $payment_mode, $cheque_no, $_SESSION['user_id']]);
                    $payment_slip_id = $pdo->lastInsertId();
                    
                    // Update bill status to paid
                    $stmt = $pdo->prepare("
                        UPDATE bills 
                        SET status = 'paid', paid_amount = ?, balance_amount = 0, 
                            payment_date = ?, payment_mode = ?, cash_register_id = ?, payment_count = payment_count + 1
                        WHERE id = ?
                    ");
                    $stmt->execute([$calculated_total, $payment_date, $payment_mode, $active_cash_register_id, $bill_id]);
                    
                    // Add cash register transaction
                    $description = "Bill payment for Bill No: " . $bill_no . " - Vendor: " . $stockEntry['vendor_name'];
                    addCashRegisterTransaction($pdo, $active_cash_register_id, 'bill_payment', $calculated_total, $description, $bill_id, 'bill_payment', $_SESSION['user_id']);
                    updateCashRegisterBalance($pdo, $active_cash_register_id, $calculated_total, 'bill_payment');
                    
                    // Generate acknowledgement
                    $ack_no = generateNumber('ACK-', 'payment_acknowledgements', 'acknowledgement_no');
                    $stmt = $pdo->prepare("
                        INSERT INTO payment_acknowledgements (acknowledgement_no, payment_slip_id, vendor_id, acknowledgement_date, created_by)
                        VALUES (?, ?, ?, NOW(), ?)
                    ");
                    $stmt->execute([$ack_no, $payment_slip_id, $vendor_id, $_SESSION['user_id']]);
                }
                
                $pdo->commit();
                
                $success_msg = "Bill received successfully! Bill No: " . $bill_no;
                if($payment_status == 'paid') {
                    $new_balance = $cash_balance - $calculated_total;
                    $success_msg .= " | Payment completed: ৳" . number_format($calculated_total, 2);
                    $success_msg .= " | Remaining Balance: ৳" . number_format($new_balance, 2);
                    $success_msg .= " | Payment Slip: " . $slip_no;
                }
                
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle"></i> ' . $success_msg . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
                
                if($payment_status == 'paid' && isset($payment_slip_id)) {
                    echo '<div class="mt-2 mb-3">
                            <a href="generate_payment_slip.php?id=' . $payment_slip_id . '" class="btn btn-info" target="_blank">
                                <i class="fas fa-print"></i> Print Payment Slip
                            </a>
                            <a href="../cash_register/register.php" class="btn btn-primary ms-2">
                                <i class="fas fa-cash-register"></i> View Cash Register
                            </a>
                            <a href="list_bills.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-list"></i> View All Bills
                            </a>
                          </div>';
                } else {
                    echo '<script>setTimeout(function(){ window.location.href = "list_bills.php"; }, 2000);</script>';
                }
                
            } catch(Exception $e) {
                $pdo->rollBack();
                $error = "Database Error: " . $e->getMessage();
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<style>
    .form-label { font-weight: 600; margin-bottom: 0.5rem; }
    .required-field::after { content: " *"; color: red; font-weight: bold; }
    .summary-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    .balance-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
    .balance-card.low-balance { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .balance-card.critical-balance { background: linear-gradient(135deg, #f12711 0%, #f5af19 100%); }
    .vendor-card { cursor: pointer; transition: all 0.3s; border: 2px solid transparent; }
    .vendor-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .vendor-card.active { border-color: #007bff; background: #f0f7ff; }
    .invoice-item { cursor: pointer; transition: all 0.2s; }
    .invoice-item:hover { background: #e8f4f8; }
    .invoice-item.selected { background: #d1ecf1; border-left: 3px solid #17a2b8; }
</style>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-file-invoice-dollar text-primary"></i> Receive Bill from Vendor</h2>
                    <p class="text-muted">Record vendor bills against stock purchases and make payments</p>
                </div>
                <div>
                    <a href="list_bills.php" class="btn btn-outline-secondary"><i class="fas fa-list"></i> All Bills</a>
                    <a href="../cash_register/register.php" class="btn btn-outline-primary ms-2"><i class="fas fa-cash-register"></i> Cash Register</a>
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
            <!-- Cash Register Balance Alert -->
            <?php if(!$active_cash_register_id): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>No active cash register found!</strong> Please set up the cash register for <?php echo date('F Y'); ?> before processing bills.
                <a href="../cash_register/register.php" class="alert-link">Go to Cash Register</a>
            </div>
            <?php elseif($is_cash_register_closed): ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-lock"></i> 
                <strong>Cash register is closed!</strong> The current month's cash register has been closed. Cannot process new bill payments.
            </div>
            <?php elseif($cash_balance < 5000): ?>
            <div class="alert alert-warning mb-4">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Low Cash Balance!</strong> Available balance: ৳<?php echo number_format($cash_balance, 2); ?>.
                Please add cash in from the cash register before making payments.
            </div>
            <?php endif; ?>
            
            <!-- Vendor Selection Cards -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-building"></i> Step 1: Select Vendor</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="vendorCards">
                        <?php foreach($vendors as $vendor): ?>
                        <?php
                        // FIX: Check ALL conditions for pending invoices
                        $pendingInvoices = $pdo->prepare("
                            SELECT COUNT(*) as count FROM stock_in 
                            WHERE vendor_id = ? 
                            AND (
                                bill_id IS NULL 
                                OR bill_id = 0 
                                OR bill_status IS NULL 
                                OR bill_status = '' 
                                OR bill_status = 'pending'
                                OR bill_status = 'unbilled'
                                OR bill_status = '0'
                                OR bill_status = 'null'
                            )
                        ");
                        $pendingInvoices->execute([$vendor['id']]);
                        $pendingCount = $pendingInvoices->fetch()['count'];
                        ?>
                        <div class="col-md-4 col-sm-6 mb-3">
                            <div class="card vendor-card h-100 <?php echo ($selected_vendor_id == $vendor['id']) ? 'active border-primary' : ''; ?>" 
                                 data-vendor-id="<?php echo $vendor['id']; ?>">
                                <div class="card-body text-center">
                                    <i class="fas fa-building fa-2x text-primary mb-2"></i>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($vendor['vendor_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($vendor['company_name'] ?? ''); ?></small>
                                    <div class="mt-2">
                                        <span class="badge bg-warning"><?php echo $pendingCount; ?> Pending</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Invoice Selection -->
            <div class="card shadow-sm" id="invoiceCard" style="<?php echo !$selected_vendor_id ? 'display:none;' : ''; ?>">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Step 2: Select Invoice / Stock Entry</h5>
                </div>
                <div class="card-body">
                    <div id="invoiceList">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2">Loading invoices...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bill Form -->
            <div class="card shadow-sm mt-4" id="billFormCard" style="display:none;">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Step 3: Bill Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="billForm">
                        <input type="hidden" name="vendor_id" id="selectedVendorId">
                        <input type="hidden" name="stock_in_id" id="selectedStockId">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bill Number (Optional)</label>
                                <input type="text" name="bill_number" class="form-control" 
                                       placeholder="Leave empty for auto-generation" id="billNumber">
                                <small class="text-muted">Auto-generated if left empty</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Bill Date</label>
                                <input type="date" name="bill_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required id="billDate">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-control" 
                                       value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" id="dueDate">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Total Amount (BDT)</label>
                                <div class="input-group">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" name="total_amount" class="form-control" 
                                           id="totalAmount" required placeholder="0.00" readonly style="background:#f8f9fa;">
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label required-field">Payment Status</label>
                                <select name="payment_status" class="form-select" id="paymentStatus">
                                    <option value="pending">Pending - Pay Later</option>
                                    <option value="paid" <?php echo (!$active_cash_register_id || $is_cash_register_closed || $cash_balance <= 0) ? 'disabled' : ''; ?>>
                                        Paid - Pay Now <?php echo ($active_cash_register_id && !$is_cash_register_closed) ? '(Balance: ৳' . number_format($cash_balance, 2) . ')' : ''; ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="paymentModeDiv" style="display:none;">
                                <label class="form-label">Payment Mode</label>
                                <select name="payment_mode" class="form-select" id="paymentMode">
                                    <option value="cash">💵 Cash</option>
                                    <option value="cheque">📝 Cheque</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="chequeDiv" style="display:none;">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" name="cheque_no" class="form-control" placeholder="Enter cheque number">
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Upload Bill Document</label>
                                <input type="file" name="bill_attachment" class="form-control" 
                                       accept=".pdf,.jpg,.jpeg,.png,.webp" id="fileInput">
                                <small class="text-muted">Supported: PDF, JPG, PNG (Max 5MB)</small>
                                <div id="filePreview" class="mt-2"></div>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Remarks / Notes</label>
                                <textarea name="remarks" rows="2" class="form-control" 
                                          placeholder="Additional notes about this bill..." id="remarks"></textarea>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary"><i class="fas fa-undo"></i> Reset</button>
                            <button type="submit" class="btn btn-primary" id="submitBtn" <?php echo (!$active_cash_register_id || $is_cash_register_closed) ? 'disabled' : ''; ?>>
                                <i class="fas fa-save"></i> Save Bill
                            </button>
                            <a href="list_bills.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Cash Balance Card -->
            <div class="balance-card <?php echo ($cash_balance < 50000) ? (($cash_balance < 10000) ? 'critical-balance' : 'low-balance') : ''; ?>">
                <h5><i class="fas fa-money-bill-wave"></i> Cash Register Balance</h5>
                <hr class="bg-light">
                <div class="text-center">
                    <h2 class="mb-0">৳ <?php echo number_format($cash_balance, 2); ?></h2>
                    <small class="text-light">
                        <?php 
                        if(!$active_cash_register_id) echo "No active cash register found!";
                        elseif($is_cash_register_closed) echo "<span class='text-warning'>⚠️ Cash Register Closed!</span>";
                        elseif($cash_balance < 10000) echo "<span class='text-warning'>⚠️ Critical: Low Balance!</span>";
                        elseif($cash_balance < 50000) echo "<span class='text-info'>⚠️ Warning: Balance getting low</span>";
                        else echo "Available for payments";
                        ?>
                    </small>
                </div>
            </div>
            
            <!-- Quick Stats Card -->
            <div class="summary-card">
                <h5><i class="fas fa-chart-line"></i> Quick Stats</h5>
                <hr class="bg-light">
                <div class="d-flex justify-content-between mb-2">
                    <span>Pending Bills:</span>
                    <strong><?php $pendingStmt = $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'pending'"); echo $pendingStmt->fetch()['count']; ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Bills Value:</span>
                    <strong>৳<?php $totalStmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills"); echo number_format($totalStmt->fetch()['total'], 2); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Unbilled Stock Entries:</span>
                    <strong><?php 
                        $unbilledStmt = $pdo->query("
                            SELECT COUNT(*) as count FROM stock_in 
                            WHERE bill_id IS NULL 
                            OR bill_id = 0 
                            OR bill_status IS NULL 
                            OR bill_status = '' 
                            OR bill_status = 'pending'
                            OR bill_status = 'unbilled'
                            OR bill_status = '0'
                            OR bill_status = 'null'
                        "); 
                        echo $unbilledStmt->fetch()['count']; 
                    ?></strong>
                </div>
                <hr class="bg-light">
                <div class="d-flex justify-content-between">
                    <span>Monthly Budget:</span>
                    <strong>৳<?php echo number_format($cashData['monthly_budget'] ?? 0, 2); ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Remaining Budget:</span>
                    <strong class="<?php echo $cashData['remaining_budget'] >= 0 ? 'text-light' : 'text-warning'; ?>">
                        ৳<?php echo number_format($cashData['remaining_budget'] ?? 0, 2); ?>
                    </strong>
                </div>
            </div>
            
            <!-- Selected Invoice Details -->
            <div class="card shadow-sm" id="selectedInfoCard" style="display:none;">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-box"></i> Selected Invoice Details</h5>
                </div>
                <div class="card-body">
                    <div id="selectedInfo"></div>
                </div>
            </div>
            
            <!-- Instructions Card -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Instructions</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> First create stock entry from Stock In module</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Select vendor to see pending invoices</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Choose invoice to auto-fill details</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Upload bill document as proof</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Select "Paid" to process payment now</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success"></i> Cash payments require sufficient cash balance</li>
                        <li><i class="fas fa-check-circle text-success"></i> Payment slip can be printed immediately</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let selectedVendorId = <?php echo $selected_vendor_id; ?>;
    let cashBalance = <?php echo $cash_balance; ?>;
    let hasCashRegister = <?php echo $active_cash_register_id ? 'true' : 'false'; ?>;
    let isCashRegisterClosed = <?php echo $is_cash_register_closed ? 'true' : 'false'; ?>;
    
    $('.vendor-card').click(function() {
        $('.vendor-card').removeClass('active border-primary');
        $(this).addClass('active border-primary');
        selectedVendorId = $(this).data('vendor-id');
        $('#selectedVendorId').val(selectedVendorId);
        loadInvoices(selectedVendorId);
        $('#invoiceCard').show();
    });
    
    function loadInvoices(vendorId) {
        $('#invoiceList').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading invoices...</p></div>');
        $.ajax({
            url: 'get_vendor_invoices.php',
            method: 'GET',
            data: { vendor_id: vendorId },
            dataType: 'json',
            success: function(response) {
                console.log('Response received:', response);
                if(response.success && response.invoices && response.invoices.length > 0) {
                    var html = '<div class="list-group">';
                    $.each(response.invoices, function(i, invoice) {
                        html += '<div class="list-group-item list-group-item-action invoice-item" data-stock-id="' + invoice.id + '" data-invoice-no="' + invoice.invoice_no + '" data-amount="' + invoice.total_amount + '" data-date="' + invoice.purchase_date + '">';
                        html += '<div class="d-flex justify-content-between align-items-center">';
                        html += '<div><strong><i class="fas fa-file-invoice"></i> ' + invoice.invoice_no + '</strong><br><small class="text-muted">Date: ' + invoice.purchase_date + '</small></div>';
                        html += '<div class="text-end"><strong class="text-primary">৳' + parseFloat(invoice.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) + '</strong><br><span class="badge bg-secondary">Pending Bill</span></div>';
                        html += '</div></div>';
                    });
                    html += '</div>';
                    $('#invoiceList').html(html);
                } else {
                    var msg = response.message || 'No pending invoices found for this vendor.';
                    $('#invoiceList').html('<div class="alert alert-warning text-center">' + msg + '</div>');
                    // Debug: Check if there are any stock entries for this vendor
                    $.ajax({
                        url: 'debug_vendor_invoices.php',
                        method: 'GET',
                        data: { vendor_id: selectedVendorId },
                        dataType: 'json',
                        success: function(debugData) {
                            console.log('Debug data:', debugData);
                        }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#invoiceList').html('<div class="alert alert-danger text-center">Error loading invoices. Please check console for details.</div>');
            }
        });
    }
    
    $(document).on('click', '.invoice-item', function() {
        $('.invoice-item').removeClass('selected');
        $(this).addClass('selected');
        var stockId = $(this).data('stock-id');
        var invoiceNo = $(this).data('invoice-no');
        var amount = $(this).data('amount');
        var purchaseDate = $(this).data('date');
        $('#selectedStockId').val(stockId);
        $('#totalAmount').val(amount);
        $('#billNumber').val('BILL-' + invoiceNo);
        $('#paymentStatus option').prop('disabled', false);
        $('#selectedInfo').find('.alert').remove();
        if(!hasCashRegister) {
            $('#paymentStatus option[value="paid"]').prop('disabled', true);
            $('#paymentStatus').val('pending').trigger('change');
            $('#selectedInfo').append('<div class="alert alert-warning mt-2 small"><i class="fas fa-exclamation-triangle"></i> No active cash register found. Payment must be marked as pending.</div>');
        } else if(isCashRegisterClosed) {
            $('#paymentStatus option[value="paid"]').prop('disabled', true);
            $('#paymentStatus').val('pending').trigger('change');
            $('#selectedInfo').append('<div class="alert alert-warning mt-2 small"><i class="fas fa-exclamation-triangle"></i> Cash register is closed. Payment must be marked as pending.</div>');
        } else if(amount > cashBalance) {
            $('#paymentStatus option[value="paid"]').prop('disabled', true);
            $('#paymentStatus').val('pending').trigger('change');
            $('#selectedInfo').append('<div class="alert alert-warning mt-2 small"><i class="fas fa-exclamation-triangle"></i> Insufficient cash balance (Available: ৳' + cashBalance.toLocaleString('en-IN', {minimumFractionDigits: 2}) + '). Payment must be marked as pending.</div>');
        }
        var infoHtml = `
            <p class="mb-2"><strong><i class="fas fa-file-invoice"></i> Invoice No:</strong> ${invoiceNo}</p>
            <p class="mb-2"><strong><i class="fas fa-calendar"></i> Purchase Date:</strong> ${purchaseDate}</p>
            <p class="mb-2"><strong><i class="fas fa-money-bill-wave"></i> Total Amount:</strong> ৳${parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
            <hr>
            <p class="mb-0 text-success"><i class="fas fa-check-circle"></i> Ready to receive bill</p>
        `;
        $('#selectedInfo').html(infoHtml);
        $('#selectedInfoCard').show();
        $('#billFormCard').show();
    });
    
    if(selectedVendorId) {
        $('#selectedVendorId').val(selectedVendorId);
        loadInvoices(selectedVendorId);
        $('#invoiceCard').show();
        $('.vendor-card').each(function() {
            if($(this).data('vendor-id') == selectedVendorId) $(this).addClass('active border-primary');
        });
    }
    
    $('#paymentStatus').change(function() {
        if($(this).val() == 'paid') {
            var amount = parseFloat($('#totalAmount').val()) || 0;
            if(!hasCashRegister) { alert('No active cash register found.'); $(this).val('pending'); return; }
            if(isCashRegisterClosed) { alert('Cash register is closed.'); $(this).val('pending'); return; }
            if($('#paymentMode').val() == 'cash' && amount > cashBalance) {
                alert('Insufficient cash balance! Available: ৳' + cashBalance.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                $(this).val('pending'); return;
            }
            $('#paymentModeDiv').show();
            if($('#paymentMode').val() == 'cheque') $('#chequeDiv').show();
        } else {
            $('#paymentModeDiv').hide();
            $('#chequeDiv').hide();
        }
    });
    
    $('#paymentMode').change(function() {
        if($(this).val() == 'cheque') $('#chequeDiv').show();
        else $('#chequeDiv').hide();
        if($(this).val() == 'cash' && $('#paymentStatus').val() == 'paid') {
            var amount = parseFloat($('#totalAmount').val()) || 0;
            if(amount > cashBalance) {
                alert('Insufficient cash balance!');
                $('#paymentStatus').val('pending');
                $('#paymentModeDiv').hide();
            }
        }
    });
    
    $('#fileInput').change(function(e) {
        var file = e.target.files[0];
        if(file) {
            var fileType = file.type;
            var fileSize = (file.size / 1024 / 1024).toFixed(2);
            if(fileType.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) { $('#filePreview').html('<img src="' + e.target.result + '" class="img-thumbnail mt-2" style="max-height: 100px;">'); };
                reader.readAsDataURL(file);
            } else {
                $('#filePreview').html('<div class="alert alert-info mt-2 small"><i class="fas fa-file-pdf"></i> ' + file.name + ' (' + fileSize + ' MB)</div>');
            }
        }
    });
    
    $('#billForm').on('submit', function(e) {
        var vendor = $('#selectedVendorId').val();
        var stock = $('#selectedStockId').val();
        var amount = parseFloat($('#totalAmount').val());
        var paymentStatus = $('#paymentStatus').val();
        var paymentMode = $('#paymentMode').val();
        if(!vendor) { e.preventDefault(); alert('Please select a vendor'); return false; }
        if(!stock) { e.preventDefault(); alert('Please select an invoice'); return false; }
        if(!amount || amount <= 0) { e.preventDefault(); alert('Please enter a valid total amount'); return false; }
        if(paymentStatus == 'paid') {
            if(!hasCashRegister) { e.preventDefault(); alert('No active cash register found.'); return false; }
            if(isCashRegisterClosed) { e.preventDefault(); alert('Cash register is closed.'); return false; }
            if(paymentMode == 'cash' && amount > cashBalance) {
                e.preventDefault();
                alert('Insufficient cash balance! Available: ৳' + cashBalance.toLocaleString('en-IN', {minimumFractionDigits: 2}) + ', Required: ৳' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
                return false;
            }
        }
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);
    });
    
    $('#paymentStatus').trigger('change');
});
</script>

<?php include '../../includes/footer.php'; ?>