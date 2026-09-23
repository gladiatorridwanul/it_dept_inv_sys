<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$bill_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? 'select';

// Get cash register for current month
$current_month = date('Y-m-01');
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ? AND is_closed = 0");
$stmt->execute([$current_month]);
$cashRegister = $stmt->fetch();

// Function to get cash register balance
function getCashRegisterBalance($pdo, $month_year = null) {
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
    return $stmt->fetch();
}

// Function to generate number
function generateNumber($prefix, $table, $column) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) as max_num FROM $table WHERE $column LIKE ?");
    $stmt->execute([$prefix . '%']);
    $row = $stmt->fetch();
    $next = ($row['max_num'] ?? 0) + 1;
    return $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
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
    if($type == 'cash_out' || $type == 'bill_payment') {
        $stmt = $pdo->prepare("
            UPDATE cash_register 
            SET total_cash_out = total_cash_out + ?, 
                closing_balance = closing_balance - ?,
                remaining_balance = remaining_balance - ?
            WHERE id = ?
        ");
        return $stmt->execute([$amount, $amount, $amount, $cash_register_id]);
    }
    return false;
}

$cashData = getCashRegisterBalance($pdo, $current_month);
$cash_balance = $cashData['available_balance'] ?? 0;
$active_cash_register_id = $cashData['id'] ?? null;
$is_cash_register_closed = $cashData['is_closed'] ?? false;

// Initialize variables
$error = '';
$payment_mode = 'cash'; // Default value

// If no bill_id provided, show bill selection with enhanced UI
if($bill_id == 0 && $action == 'select') {
    // Get pending bills (bills with balance > 0 OR status not paid)
    $pending_bills = $pdo->query("
        SELECT b.*, v.vendor_name, v.office_phone as vendor_phone, v.office_email as vendor_email
        FROM bills b 
        JOIN vendors v ON b.vendor_id = v.id 
        WHERE (b.balance_amount > 0 OR b.status != 'paid')
        ORDER BY b.bill_date DESC
    ")->fetchAll();
    
    // Get total due amount
    $dueStmt = $pdo->query("SELECT COALESCE(SUM(balance_amount), 0) as total FROM bills WHERE balance_amount > 0");
    $total_due = $dueStmt->fetch()['total'];
    
    // Get recently paid bills (last 30 days) from payment_slips
    $recent_payments = $pdo->query("
        SELECT ps.*, b.bill_no, v.vendor_name, ps.payment_amount
        FROM payment_slips ps
        JOIN bills b ON ps.bill_id = b.id
        JOIN vendors v ON b.vendor_id = v.id
        WHERE ps.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY ps.created_at DESC LIMIT 10
    ")->fetchAll();
    ?>
    
    <style>
        .bill-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border-left: 4px solid;
        }
        .bill-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card.bg-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.bg-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.bg-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.bg-danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    </style>
    
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h2><i class="fas fa-credit-card text-primary"></i> Make Payment</h2>
                        <p class="text-muted">Process vendor bill payments and track payment history</p>
                    </div>
                    <div class="mt-2 mt-md-0">
                        <a href="list_bills.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-list"></i> All Bills
                        </a>
                        <a href="receive_bill.php" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> New Bill
                        </a>
                        <a href="../cash_register/register.php" class="btn btn-outline-info">
                            <i class="fas fa-cash-register"></i> Cash Register
                        </a>
                    </div>
                </div>
                <hr>
            </div>
        </div>
        
        <!-- Cash Register Alert -->
        <?php if(!$active_cash_register_id): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>No active cash register found!</strong> Please set up the cash register for <?php echo date('F Y'); ?> before processing payments.
            <a href="../cash_register/register.php" class="alert-link">Go to Cash Register</a>
        </div>
        <?php elseif($is_cash_register_closed): ?>
        <div class="alert alert-danger mb-4">
            <i class="fas fa-lock"></i> 
            <strong>Cash register is closed!</strong> The current month's cash register has been closed. Cannot process new payments.
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">Pending Bills</h6>
                            <h2 class="mb-0"><?php echo count($pending_bills); ?></h2>
                        </div>
                        <div><i class="fas fa-clock fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card bg-success">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">Total Due Amount</h6>
                            <h2 class="mb-0">৳<?php echo number_format($total_due, 2); ?></h2>
                        </div>
                        <div><i class="fas fa-money-bill-wave fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card bg-info">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">Cash Register Balance</h6>
                            <h2 class="mb-0">৳<?php echo number_format($cash_balance, 2); ?></h2>
                        </div>
                        <div><i class="fas fa-cash-register fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card bg-warning">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="mb-1">Total Bills Value</h6>
                            <h2 class="mb-0">৳<?php 
                                $totalStmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills");
                                echo number_format($totalStmt->fetch()['total'], 2);
                            ?></h2>
                        </div>
                        <div><i class="fas fa-file-invoice fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Pending Bills Section -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Pending Bills to Pay</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($pending_bills) > 0): ?>
                            <div class="row">
                                <?php foreach($pending_bills as $bill_item): 
                                    $paidPercentage = ($bill_item['total_amount'] > 0) ? ($bill_item['paid_amount'] / $bill_item['total_amount']) * 100 : 0;
                                    $statusColor = $bill_item['balance_amount'] <= 0 ? 'success' : ($bill_item['paid_amount'] > 0 ? 'warning' : 'danger');
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card bill-card" style="border-left-color: <?php echo $statusColor == 'success' ? '#28a745' : ($statusColor == 'warning' ? '#ffc107' : '#dc3545'); ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($bill_item['vendor_name']); ?></h6>
                                                    <small class="text-muted"><?php echo $bill_item['bill_no']; ?></small>
                                                </div>
                                                <span class="badge bg-<?php echo $statusColor; ?>">
                                                    <?php echo $bill_item['balance_amount'] <= 0 ? 'Paid' : ($bill_item['paid_amount'] > 0 ? 'Partial' : 'Unpaid'); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mt-2">
                                                <div class="progress mb-2" style="height: 5px;">
                                                    <div class="progress-bar bg-<?php echo $statusColor; ?>" style="width: <?php echo $paidPercentage; ?>%"></div>
                                                </div>
                                                <div class="d-flex justify-content-between small">
                                                    <span>Total: ৳<?php echo number_format($bill_item['total_amount'], 2); ?></span>
                                                    <span>Paid: ৳<?php echo number_format($bill_item['paid_amount'], 2); ?></span>
                                                    <span class="text-danger">Due: ৳<?php echo number_format($bill_item['balance_amount'], 2); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> <?php echo date('d-M-Y', strtotime($bill_item['bill_date'])); ?>
                                                </small>
                                                <a href="make_payment.php?id=<?php echo $bill_item['id']; ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-money-bill"></i> Pay Now
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <h5>No Pending Bills</h5>
                                <p class="mb-0">All bills have been paid. <a href="receive_bill.php">Receive a new bill</a> to process payment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="receive_bill.php" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Receive New Bill
                            </a>
                            <a href="list_bills.php" class="btn btn-outline-info">
                                <i class="fas fa-list"></i> View All Bills
                            </a>
                            <a href="../cash_register/register.php" class="btn btn-outline-success">
                                <i class="fas fa-cash-register"></i> Cash Register
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Payments</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if(count($recent_payments) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($recent_payments as $payment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($payment['vendor_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $payment['bill_no']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <span class="fw-bold text-success">৳<?php echo number_format($payment['payment_amount'], 2); ?></span>
                                            <br>
                                            <small class="text-muted"><?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?></small>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="generate_payment_slip.php?slip_id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                            <i class="fas fa-print"></i> Receipt
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No recent payments</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    include '../../includes/footer.php';
    exit();
}

// ============================================
// MAKE PAYMENT FOR SPECIFIC BILL
// ============================================

// Get bill details with all related info
$stmt = $pdo->prepare("
    SELECT b.*, v.vendor_name, v.gst_no, v.office_email as email, v.office_phone as phone, v.office_address as address,
           s.invoice_no as stock_invoice, s.purchase_date as stock_purchase_date
    FROM bills b 
    JOIN vendors v ON b.vendor_id = v.id 
    LEFT JOIN stock_in s ON b.stock_in_id = s.id
    WHERE b.id = ?
");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if(!$bill) {
    echo '<div class="alert alert-danger">Bill not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get payment history for this bill from payment_slips
$paymentHistory = $pdo->prepare("
    SELECT ps.*, ps.payment_amount as amount
    FROM payment_slips ps
    WHERE ps.bill_id = ? 
    ORDER BY ps.created_at DESC
");
$paymentHistory->execute([$bill_id]);
$paymentHistory = $paymentHistory->fetchAll();

// Process payment
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_amount = floatval($_POST['payment_amount'] ?? 0);
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $payment_mode = $_POST['payment_mode'] ?? 'cash';
    $cheque_no = trim($_POST['cheque_no'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $reference_no = trim($_POST['reference_no'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    if($payment_amount <= 0) $errors[] = "Payment amount must be greater than 0";
    if($payment_amount > $bill['balance_amount']) $errors[] = "Payment amount cannot exceed balance due of ৳" . number_format($bill['balance_amount'], 2);
    if(empty($payment_date)) $errors[] = "Payment date is required";
    if(empty($payment_mode)) $errors[] = "Payment mode is required";
    
    // Check cash register balance for cash payments
    if($payment_mode == 'cash') {
        if(!$active_cash_register_id) {
            $errors[] = "No active cash register found for current month. Please set up cash register first.";
        } elseif($is_cash_register_closed) {
            $errors[] = "Current month cash register is closed. Cannot process cash payment.";
        } elseif($cash_balance < $payment_amount) {
            $errors[] = "Insufficient cash balance! Available: ৳" . number_format($cash_balance, 2) . ", Required: ৳" . number_format($payment_amount, 2);
        }
    }
    
    if(empty($errors)) {
        $pdo->beginTransaction();
        
        try {
            // Generate payment slip number
            $slip_no = generateNumber('SLIP-', 'payment_slips', 'slip_no');
            
            // Insert payment record into payment_slips table
            $stmt = $pdo->prepare("
                INSERT INTO payment_slips (slip_no, bill_id, vendor_id, payment_amount, payment_date, payment_mode, 
                                          cheque_no, bank_name, transaction_id, notes, generated_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$slip_no, $bill_id, $bill['vendor_id'], $payment_amount, $payment_date, $payment_mode, 
                           $cheque_no, $bank_name, $transaction_id, $notes, $_SESSION['user_id']]);
            $slip_id = $pdo->lastInsertId();
            
            // Update bill
            $new_paid = $bill['paid_amount'] + $payment_amount;
            $new_balance = $bill['total_amount'] - $new_paid;
            $status = $new_balance <= 0 ? 'paid' : 'partial';
            
            $stmt = $pdo->prepare("
                UPDATE bills SET paid_amount = ?, balance_amount = ?, status = ?, 
                                payment_date = ?, payment_mode = ?, payment_count = COALESCE(payment_count, 0) + 1 
                WHERE id = ?
            ");
            $stmt->execute([$new_paid, $new_balance, $status, $payment_date, $payment_mode, $bill_id]);
            
            // Generate acknowledgement number
            $ack_no = generateNumber('ACK-', 'payment_acknowledgements', 'acknowledgement_no');
            $stmt = $pdo->prepare("
                INSERT INTO payment_acknowledgements (acknowledgement_no, payment_slip_id, vendor_id, acknowledgement_date, created_by) 
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$ack_no, $slip_id, $bill['vendor_id'], $_SESSION['user_id']]);
            
            // Update cash register for cash payments
            if($payment_mode == 'cash' && $active_cash_register_id) {
                $description = "Bill payment for Bill No: " . $bill['bill_no'] . " - Vendor: " . $bill['vendor_name'];
                addCashRegisterTransaction($pdo, $active_cash_register_id, 'bill_payment', $payment_amount, $description, $bill_id, 'bill_payment', $_SESSION['user_id']);
                updateCashRegisterBalance($pdo, $active_cash_register_id, $payment_amount, 'bill_payment');
            }
            
            $pdo->commit();
            
            // Success response with SweetAlert
            echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
            echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Payment Processed!",
                        html: "Payment of <strong>৳' . number_format($payment_amount, 2) . '</strong> recorded successfully.<br>Slip No: ' . $slip_no . '<br>Acknowledgement No: ' . $ack_no . '",
                        confirmButtonColor: "#3085d6",
                        confirmButtonText: "View Receipt"
                    }).then((result) => {
                        if(result.isConfirmed) {
                            window.location.href = "generate_payment_slip.php?slip_id=' . $slip_id . '";
                        } else {
                            window.location.href = "list_bills.php";
                        }
                    });
                  </script>';
            exit();
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Database Error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<style>
    .payment-summary {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
    }
    .payment-history {
        max-height: 300px;
        overflow-y: auto;
    }
    .history-item {
        border-left: 3px solid #28a745;
        margin-bottom: 10px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
    }
    .balance-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .balance-card.low-balance { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .balance-card.critical-balance { background: linear-gradient(135deg, #f12711 0%, #f5af19 100%); }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-credit-card text-success"></i> Make Payment</h2>
                    <p class="text-muted">Record payment against bill <strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong></p>
                </div>
                <div>
                    <a href="make_payment.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bills
                    </a>
                    <a href="../cash_register/register.php" class="btn btn-outline-info ms-2">
                        <i class="fas fa-cash-register"></i> Cash Register
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <?php if(isset($error) && $error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Cash Balance Alert -->
    <?php if($active_cash_register_id && !$is_cash_register_closed && $cash_balance < $bill['balance_amount']): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Low Cash Balance!</strong> Cash register balance (৳<?php echo number_format($cash_balance, 2); ?>) is less than the bill amount. 
            <a href="../cash_register/register.php">Add cash to register</a>
        </div>
    <?php endif; ?>
    
    <?php if(!$active_cash_register_id): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>No active cash register found!</strong> Please set up the cash register for <?php echo date('F Y'); ?> before processing payments.
            <a href="../cash_register/register.php" class="alert-link">Go to Cash Register</a>
        </div>
    <?php elseif($is_cash_register_closed): ?>
        <div class="alert alert-danger">
            <i class="fas fa-lock"></i> 
            <strong>Cash register is closed!</strong> The current month's cash register has been closed. Cannot process new payments.
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Payment Form -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave"></i> Payment Details</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="paymentForm">
                        <!-- Amount Section -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Payment Amount <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">৳</span>
                                    <input type="number" step="0.01" name="payment_amount" class="form-control" 
                                           max="<?php echo $bill['balance_amount']; ?>" required id="paymentAmount"
                                           value="<?php echo $bill['balance_amount']; ?>">
                                </div>
                                <small>Maximum: ৳<?php echo number_format($bill['balance_amount'], 2); ?></small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <!-- Payment Mode -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                                <select name="payment_mode" class="form-select" required id="paymentMode">
                                    <option value="cash">💵 Cash</option>
                                    <option value="cheque">📝 Cheque</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                </select>
                                <?php if($active_cash_register_id && !$is_cash_register_closed): ?>
                                    <small class="text-muted">Available Cash Balance: ৳<?php echo number_format($cash_balance, 2); ?></small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="chequeDiv" style="display:none;">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" name="cheque_no" class="form-control" placeholder="Cheque No.">
                            </div>
                        </div>
                        
                        <div class="row" id="bankDetails" style="display:none;">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" placeholder="Bank Name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" name="transaction_id" class="form-control" placeholder="Transaction/UTR No.">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" name="reference_no" class="form-control" placeholder="Payment Reference / Invoice No">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notes / Remarks</label>
                            <textarea name="notes" rows="2" class="form-control" placeholder="Additional notes about this payment..."></textarea>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-success btn-lg" id="submitBtn" <?php echo (!$active_cash_register_id || $is_cash_register_closed) ? 'disabled' : ''; ?>>
                                <i class="fas fa-check-circle"></i> Process Payment
                            </button>
                            <a href="list_bills.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Bill Summary & Payment History -->
        <div class="col-lg-5">
            <!-- Cash Balance Card -->
            <div class="balance-card <?php echo ($cash_balance < 50000) ? (($cash_balance < 10000) ? 'critical-balance' : 'low-balance') : ''; ?>">
                <div class="text-center">
                    <h5><i class="fas fa-cash-register"></i> Cash Register Balance</h5>
                    <h2 class="mb-0">৳ <?php echo number_format($cash_balance, 2); ?></h2>
                    <small>
                        <?php 
                        if(!$active_cash_register_id) echo "No active cash register found!";
                        elseif($is_cash_register_closed) echo "⚠️ Cash Register Closed!";
                        elseif($cash_balance < 10000) echo "⚠️ Critical: Low Balance!";
                        elseif($cash_balance < 50000) echo "⚠️ Warning: Balance getting low";
                        else echo "Available for payments";
                        ?>
                    </small>
                </div>
            </div>
            
            <!-- Bill Summary Card -->
            <div class="payment-summary mb-3">
                <h5><i class="fas fa-file-invoice"></i> Bill Summary</h5>
                <hr class="bg-light">
                <div class="row">
                    <div class="col-6">Bill No:</div>
                    <div class="col-6 text-end fw-bold"><?php echo htmlspecialchars($bill['bill_no']); ?></div>
                    
                    <div class="col-6 mt-2">Vendor:</div>
                    <div class="col-6 text-end mt-2"><?php echo htmlspecialchars($bill['vendor_name']); ?></div>
                    
                    <div class="col-6 mt-2">Bill Date:</div>
                    <div class="col-6 text-end mt-2"><?php echo date('d-M-Y', strtotime($bill['bill_date'])); ?></div>
                    
                    <?php if($bill['due_date']): ?>
                    <div class="col-6 mt-2">Due Date:</div>
                    <div class="col-6 text-end mt-2"><?php echo date('d-M-Y', strtotime($bill['due_date'])); ?></div>
                    <?php endif; ?>
                    
                    <div class="col-6 mt-3">Total Amount:</div>
                    <div class="col-6 text-end mt-3">৳<?php echo number_format($bill['total_amount'], 2); ?></div>
                    
                    <div class="col-6">Paid Amount:</div>
                    <div class="col-6 text-end text-warning">৳<?php echo number_format($bill['paid_amount'], 2); ?></div>
                    
                    <div class="col-6 fw-bold">Balance Due:</div>
                    <div class="col-6 text-end fw-bold">৳<?php echo number_format($bill['balance_amount'], 2); ?></div>
                </div>
            </div>
            
            <!-- Payment History -->
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Payment History</h5>
                </div>
                <div class="card-body payment-history">
                    <?php if(count($paymentHistory) > 0): ?>
                        <?php foreach($paymentHistory as $payment): ?>
                        <div class="history-item">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <strong>৳<?php echo number_format($payment['payment_amount'], 2); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> <?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-secondary"><?php echo ucfirst($payment['payment_mode']); ?></span>
                                    <br>
                                    <small class="text-muted">
                                        Slip: <?php echo htmlspecialchars($payment['slip_no']); ?>
                                    </small>
                                </div>
                            </div>
                            <?php if($payment['cheque_no']): ?>
                                <div><small>Cheque: <?php echo htmlspecialchars($payment['cheque_no']); ?></small></div>
                            <?php endif; ?>
                            <?php if($payment['transaction_id']): ?>
                                <div><small>Transaction: <?php echo htmlspecialchars($payment['transaction_id']); ?></small></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No payments recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Vendor Contact Info -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-address-card"></i> Vendor Contact</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($bill['phone'] ?? 'N/A'); ?></p>
                    <p class="mb-1"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($bill['email'] ?? 'N/A'); ?></p>
                    <?php if($bill['gst_no']): ?>
                        <p class="mb-0"><i class="fas fa-building"></i> GST: <?php echo htmlspecialchars($bill['gst_no']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle payment mode fields
    $('#paymentMode').change(function() {
        var mode = $(this).val();
        $('#chequeDiv, #bankDetails').hide();
        if(mode == 'cheque') {
            $('#chequeDiv').show();
        }
        if(mode == 'bank_transfer') {
            $('#bankDetails').show();
        }
        
        // Show cash balance warning for cash mode
        if(mode == 'cash') {
            <?php if($active_cash_register_id && !$is_cash_register_closed): ?>
            var cashBalance = <?php echo $cash_balance; ?>;
            var amount = parseFloat($('#paymentAmount').val()) || 0;
            if(amount > cashBalance) {
                $('#paymentAmount').val(cashBalance);
                alert('Cash balance is ৳' + cashBalance.toFixed(2) + '. Payment amount adjusted.');
            }
            <?php endif; ?>
        }
    });
    
    // Trigger on load
    $('#paymentMode').trigger('change');
    
    // Validate payment amount
    $('#paymentAmount').on('input', function() {
        var max = parseFloat($(this).attr('max'));
        var val = parseFloat($(this).val());
        if(val > max) {
            $(this).val(max);
            alert('Payment amount cannot exceed balance due!');
        }
        if(val < 0) {
            $(this).val(0);
        }
        
        // Check cash balance for cash mode
        if($('#paymentMode').val() == 'cash') {
            <?php if($active_cash_register_id && !$is_cash_register_closed): ?>
            var cashBalance = <?php echo $cash_balance; ?>;
            if(val > cashBalance) {
                $(this).val(cashBalance);
                alert('Payment amount adjusted to available cash balance: ৳' + cashBalance.toFixed(2));
            }
            <?php endif; ?>
        }
    });
    
    // Form submission
    $('#paymentForm').on('submit', function() {
        var paymentAmount = parseFloat($('#paymentAmount').val()) || 0;
        var maxAmount = parseFloat($('#paymentAmount').attr('max')) || 0;
        var paymentMode = $('#paymentMode').val();
        
        if(paymentAmount <= 0) {
            alert('Please enter a valid payment amount greater than 0');
            return false;
        }
        if(paymentAmount > maxAmount) {
            alert('Payment amount cannot exceed the balance due!');
            return false;
        }
        
        <?php if($active_cash_register_id && !$is_cash_register_closed): ?>
        if(paymentMode == 'cash') {
            var cashBalance = <?php echo $cash_balance; ?>;
            if(paymentAmount > cashBalance) {
                alert('Insufficient cash balance! Available: ৳' + cashBalance.toFixed(2));
                return false;
            }
        }
        <?php endif; ?>
        
        $('#submitBtn').html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        $('#submitBtn').prop('disabled', true);
    });
});
</script>

<?php include '../../includes/footer.php'; ?>