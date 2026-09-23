<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Helper function for redirect
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . $url);
        exit();
    }
}

// Get current month and year
$current_month = date('Y-m-01');
$selected_month = $_GET['month'] ?? $current_month;

// Get previous month for balance carry forward
$previous_month = date('Y-m-01', strtotime($selected_month . ' -1 month'));
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ?");
$stmt->execute([$previous_month]);
$prev_register = $stmt->fetch();

// Get or create cash register for the selected month
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ?");
$stmt->execute([$selected_month]);
$register = $stmt->fetch();

if(!$register) {
    // Create new register for this month
    $opening_balance = $prev_register ? ($prev_register['closing_balance'] ?? 0) : 0;
    $stmt = $pdo->prepare("INSERT INTO cash_register (month_year, opening_balance, monthly_budget, created_by) 
                          VALUES (?, ?, 0, ?)");
    $stmt->execute([$selected_month, $opening_balance, $_SESSION['user_id']]);
    
    // Fetch the newly created register
    $stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ?");
    $stmt->execute([$selected_month]);
    $register = $stmt->fetch();
}

// Calculate totals from bills table for this month
$month_start = date('Y-m-01', strtotime($selected_month));
$month_end = date('Y-m-t', strtotime($selected_month));

// Get ALL bills for this month
$stmt = $pdo->prepare("
    SELECT b.*, v.vendor_name 
    FROM bills b 
    JOIN vendors v ON b.vendor_id = v.id 
    WHERE b.bill_date BETWEEN ? AND ?
    ORDER BY b.bill_date DESC
");
$stmt->execute([$month_start, $month_end]);
$monthly_bills = $stmt->fetchAll();

// Calculate bill totals
$total_bills_amount = 0;
$total_bills_paid = 0;
$total_bills_balance = 0;
foreach($monthly_bills as $bill) {
    $total_bills_amount += $bill['total_amount'];
    $total_bills_paid += $bill['paid_amount'];
    $total_bills_balance += $bill['balance_amount'];
}

// Get Cash In transactions (from cash_register_transactions)
$cash_in_transactions = $pdo->prepare("
    SELECT crt.*, u.full_name as created_by_name
    FROM cash_register_transactions crt
    LEFT JOIN users u ON crt.created_by = u.id
    WHERE crt.cash_register_id = ? AND crt.transaction_type = 'cash_in'
    ORDER BY crt.id DESC
");
$cash_in_transactions->execute([$register['id']]);
$cash_in_transactions = $cash_in_transactions->fetchAll();

// Get Bill Payment transactions
$bill_payments = $pdo->prepare("
    SELECT crt.*, b.bill_no, v.vendor_name, ps.payment_mode, ps.cheque_no,
           u.full_name as created_by_name
    FROM cash_register_transactions crt
    LEFT JOIN bills b ON crt.reference_id = b.id AND crt.reference_type = 'bill_payment'
    LEFT JOIN vendors v ON b.vendor_id = v.id
    LEFT JOIN payment_slips ps ON b.id = ps.bill_id
    LEFT JOIN users u ON crt.created_by = u.id
    WHERE crt.cash_register_id = ? AND crt.transaction_type = 'bill_payment'
    ORDER BY crt.id DESC
");
$bill_payments->execute([$register['id']]);
$bill_payments = $bill_payments->fetchAll();

// Calculate total cash in from transactions
$total_cash_in = array_sum(array_column($cash_in_transactions, 'amount'));
$total_bill_payments = array_sum(array_column($bill_payments, 'amount'));

// Calculate register totals
$register_total_cash_in = $register['total_cash_in'] ?? 0;
$register_total_purchases = $register['total_purchases'] ?? 0;
$register_total_cash_out = $register['total_cash_out'] ?? 0;

// Calculate closing balance
$closing_balance = ($register['opening_balance'] ?? 0) + $total_cash_in - $total_bill_payments;
$remaining_budget = ($register['monthly_budget'] ?? 0) - $total_bill_payments;

// Get available months for navigation
$months = $pdo->query("SELECT DISTINCT month_year FROM cash_register ORDER BY month_year DESC")->fetchAll();

// Helper function to format date
function formatTransactionDate($trans) {
    if(isset($trans['created_at']) && $trans['created_at']) {
        return date('d-m-Y H:i', strtotime($trans['created_at']));
    }
    return date('d-m-Y H:i');
}

// Handle POST requests
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if($action === 'update_budget') {
        $monthly_budget = $_POST['monthly_budget'] ?? 0;
        
        $stmt = $pdo->prepare("UPDATE cash_register SET monthly_budget = ? WHERE id = ?");
        $stmt->execute([$monthly_budget, $register['id']]);
        
        $_SESSION['success_message'] = "Budget settings updated successfully!";
        redirect("register.php?month=" . urlencode($selected_month));
        
    } elseif($action === 'add_cash_in') {
        $amount = $_POST['amount'] ?? 0;
        $description = $_POST['description'] ?? '';
        $iou_number = $_POST['iou_number'] ?? null;
        
        if($amount <= 0) {
            $_SESSION['error_message'] = "Amount must be greater than 0";
        } else {
            // Generate IOU number if not provided
            if(empty($iou_number)) {
                $iou_number = 'CASH-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            $full_description = "$iou_number - $description";
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO cash_register_transactions (cash_register_id, transaction_type, amount, description, reference_id, reference_type, created_by) 
                    VALUES (?, 'cash_in', ?, ?, NULL, 'cash_in', ?)
                ");
                $stmt->execute([$register['id'], $amount, $full_description, $_SESSION['user_id']]);
                
                $stmt = $pdo->prepare("UPDATE cash_register SET total_cash_in = total_cash_in + ? WHERE id = ?");
                $stmt->execute([$amount, $register['id']]);
                
                $pdo->commit();
                $_SESSION['success_message'] = "Cash In added successfully! Reference: $iou_number";
            } catch(Exception $e) {
                $pdo->rollBack();
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
            }
        }
        redirect("register.php?month=" . urlencode($selected_month));
    } elseif($action === 'close_month') {
        $closing_note = $_POST['closing_note'] ?? '';
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE cash_register 
                SET is_closed = 1, closed_date = NOW(), closed_by = ?, closing_note = ?,
                    closing_balance = ?
                WHERE id = ? AND is_closed = 0
            ");
            $stmt->execute([$_SESSION['user_id'], $closing_note, $closing_balance, $register['id']]);
            
            if($stmt->rowCount() > 0) {
                $_SESSION['success_message'] = "Month closed successfully! Closing Balance: ৳" . number_format($closing_balance, 2);
            } else {
                $_SESSION['error_message'] = "Month already closed or not found!";
            }
            $pdo->commit();
        } catch(Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        redirect("register.php?month=" . urlencode($selected_month));
    }
}

// Check for messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<style>
    .stat-card {
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        color: white;
        transition: transform 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-card.bg-primary { background: linear-gradient(135deg, #3b82f6, #1e40af); }
    .stat-card.bg-success { background: linear-gradient(135deg, #10b981, #059669); }
    .stat-card.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .stat-card.bg-info { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
    .stat-card.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .stat-card.bg-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .transaction-table th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    .card-header-custom {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .ref-number {
        font-family: monospace;
        font-size: 11px;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .bill-row-paid { border-left: 4px solid #10b981; }
    .bill-row-pending { border-left: 4px solid #ef4444; }
    .bill-row-partial { border-left: 4px solid #f59e0b; }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-cash-register text-primary"></i> Cash Register</h2>
            <p class="text-muted">Manage monthly budget, cash inflows, and track all bill payments</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-calendar"></i> <?php echo date('F Y', strtotime($selected_month)); ?>
                </button>
                <ul class="dropdown-menu">
                    <?php foreach($months as $m): ?>
                    <li><a class="dropdown-item" href="?month=<?php echo $m['month_year']; ?>"><?php echo date('F Y', strtotime($m['month_year'])); ?></a></li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?month=<?php echo date('Y-m-01'); ?>">Current Month</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-3 col-6">
            <div class="stat-card bg-primary">
                <i class="fas fa-chart-line fa-2x mb-2"></i>
                <div class="stat-number">৳<?php echo number_format($register['opening_balance'] ?? 0, 2); ?></div>
                <small>Opening Balance</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card bg-success">
                <i class="fas fa-arrow-down fa-2x mb-2"></i>
                <div class="stat-number">৳<?php echo number_format($total_cash_in, 2); ?></div>
                <small>Total Cash In</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card bg-warning">
                <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                <div class="stat-number">৳<?php echo number_format($total_bill_payments, 2); ?></div>
                <small>Bill Payments</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card bg-info">
                <i class="fas fa-wallet fa-2x mb-2"></i>
                <div class="stat-number">৳<?php echo number_format($closing_balance, 2); ?></div>
                <small>Closing Balance</small>
            </div>
        </div>
    </div>
    
    <div class="row mb-4 g-3">
        <div class="col-md-4">
            <div class="stat-card bg-purple">
                <i class="fas fa-chart-simple fa-2x mb-2"></i>
                <div class="stat-number">৳<?php echo number_format($register['monthly_budget'] ?? 0, 2); ?></div>
                <small>Monthly Budget</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card <?php echo $remaining_budget >= 0 ? 'bg-success' : 'bg-danger'; ?>">
                <i class="fas fa-coins fa-2x mb-2"></i>
                <div class="stat-number">৳<?php echo number_format($remaining_budget, 2); ?></div>
                <small>Remaining Budget</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-secondary">
                <i class="fas fa-file-invoice fa-2x mb-2"></i>
                <div class="stat-number"><?php echo count($monthly_bills); ?></div>
                <small>Total Bills This Month</small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column - Budget & Cash In Forms -->
        <div class="col-lg-4">
            <!-- Budget Settings -->
            <div class="card shadow-sm mb-4">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Budget Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_budget">
                        <div class="mb-3">
                            <label class="form-label">Opening Balance</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="text" class="form-control" value="<?php echo number_format($register['opening_balance'] ?? 0, 2); ?>" readonly disabled>
                            </div>
                            <small class="text-muted">Carried forward from previous month</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Monthly Budget (BDT)</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" name="monthly_budget" class="form-control" value="<?php echo $register['monthly_budget'] ?? 0; ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" <?php echo ($register['is_closed'] ?? 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-save"></i> Update Budget
                        </button>
                        <?php if($register['is_closed'] ?? 0): ?>
                        <div class="alert alert-warning mt-3 mb-0 small">
                            <i class="fas fa-lock"></i> This month is locked. Budget cannot be modified.
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Cash In Form -->
            <div class="card shadow-sm mb-4">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <h5 class="mb-0"><i class="fas fa-arrow-down"></i> Cash In</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_cash_in">
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" name="iou_number" class="form-control" placeholder="Leave empty for auto-generation" value="CASH-<?php echo date('Ymd'); ?>-">
                            <small class="text-muted">Unique reference number</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (BDT)</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" step="0.01" name="amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description / Purpose</label>
                            <textarea name="description" rows="2" class="form-control" placeholder="e.g., Received from sales, Refund, Bank Deposit, etc." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100" <?php echo ($register['is_closed'] ?? 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-arrow-down"></i> Add Cash In
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Close Month -->
            <?php if(!($register['is_closed'] ?? 0)): ?>
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Close Month</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small">
                        <strong>Closing Summary:</strong><br>
                        Opening Balance: ৳<?php echo number_format($register['opening_balance'] ?? 0, 2); ?><br>
                        Total Cash In: ৳<?php echo number_format($total_cash_in, 2); ?><br>
                        Total Bill Payments: ৳<?php echo number_format($total_bill_payments, 2); ?><br>
                        <hr class="my-2">
                        <strong>Closing Balance: ৳<?php echo number_format($closing_balance, 2); ?></strong>
                    </div>
                    <p class="small text-muted">Closing the month will lock all transactions. This action cannot be undone.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to close this month? This action cannot be undone.')">
                        <input type="hidden" name="action" value="close_month">
                        <div class="mb-3">
                            <label class="form-label">Closing Note (Optional)</label>
                            <textarea name="closing_note" rows="2" class="form-control" placeholder="Add any notes about this month's closing..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-lock"></i> Close Month
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow-sm bg-light">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Month Closed</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                    <p>This month has been closed.</p>
                    <small class="text-muted">Closed on: <?php echo date('d-m-Y H:i', strtotime($register['closed_date'])); ?></small>
                    <?php if($register['closing_note']): ?>
                    <p class="mt-2 small"><strong>Closing Note:</strong> <?php echo htmlspecialchars($register['closing_note']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Right Column - Transaction Lists -->
        <div class="col-lg-8">
            <!-- Monthly Bills Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #3b82f6, #1e40af);">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Bills This Month (<?php echo date('F Y', strtotime($selected_month)); ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bill No</th>
                                    <th>Vendor</th>
                                    <th>Bill Date</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Paid</th>
                                    <th class="text-end">Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($monthly_bills) > 0): ?>
                                    <?php foreach($monthly_bills as $bill): ?>
                                        <?php
                                        $statusClass = $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'partial' ? 'warning' : 'danger');
                                        ?>
                                        <tr class="bill-row-<?php echo $bill['status']; ?>">
                                            <td><strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($bill['vendor_name']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td>
                                            <td class="text-end">৳<?php echo number_format($bill['total_amount'], 2); ?></td>
                                            <td class="text-end text-success">৳<?php echo number_format($bill['paid_amount'], 2); ?></td>
                                            <td class="text-end text-danger">৳<?php echo number_format($bill['balance_amount'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($bill['status']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No bills found for this month</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if(count($monthly_bills) > 0): ?>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Totals:</td>
                                    <td class="text-end">৳<?php echo number_format($total_bills_amount, 2); ?></td>
                                    <td class="text-end text-success">৳<?php echo number_format($total_bills_paid, 2); ?></td>
                                    <td class="text-end text-danger">৳<?php echo number_format($total_bills_balance, 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                         </div>
                    </div>
                </div>
            </div>
            
            <!-- Cash In Transactions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <h5 class="mb-0"><i class="fas fa-arrow-down"></i> Cash In Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Reference No</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($cash_in_transactions) > 0): ?>
                                    <?php foreach($cash_in_transactions as $trans): ?>
                                    <tr>
                                        <td><?php echo formatTransactionDate($trans); ?></td>
                                        <td><span class="ref-number"><?php echo htmlspecialchars(preg_replace('/ - .*$/', '', $trans['description'])); ?></span></td>
                                        <td><?php echo htmlspecialchars(preg_replace('/^[^-]+ - /', '', $trans['description'])); ?></td>
                                        <td class="text-end text-success">+ ৳<?php echo number_format($trans['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($trans['created_by_name'] ?? 'System'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No cash in transactions for this month</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if(count($cash_in_transactions) > 0): ?>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total Cash In:</td>
                                    <td class="text-end text-success">+ ৳<?php echo number_format($total_cash_in, 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                         </div>
                    </div>
                </div>
            </div>
            
            <!-- Bill Payment Transactions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header-custom" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Bill Payment Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Bill No</th>
                                    <th>Vendor</th>
                                    <th>Payment Mode</th>
                                    <th class="text-end">Amount</th>
                                    <th>Cheque No</th>
                                    <th>Recorded By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($bill_payments) > 0): ?>
                                    <?php foreach($bill_payments as $trans): ?>
                                    <tr>
                                        <td><?php echo formatTransactionDate($trans); ?></td>
                                        <td><span class="ref-number"><?php echo htmlspecialchars($trans['bill_no'] ?? 'N/A'); ?></span></td>
                                        <td><?php echo htmlspecialchars($trans['vendor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo ucfirst($trans['payment_mode'] ?? 'Cash'); ?></td>
                                        <td class="text-end text-danger">- ৳<?php echo number_format($trans['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($trans['cheque_no'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($trans['created_by_name'] ?? 'System'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <p>No bill payment transactions for this month</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <?php if(count($bill_payments) > 0): ?>
                            <tfoot class="table-light">
                                <tr class="fw-bold">
                                    <td colspan="4" class="text-end">Total Bill Payments:</td>
                                    <td class="text-end text-danger">- ৳<?php echo number_format($total_bill_payments, 2); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                            <?php endif; ?>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>

<?php include '../../includes/footer.php'; ?>