<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$bill_id = $_GET['bill_id'] ?? 0;

// Get bill details
$stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name 
                       FROM bills b 
                       JOIN vendors v ON b.vendor_id=v.id 
                       WHERE b.id = ?");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if(!$bill) {
    echo '<div class="alert alert-danger">Bill not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get current cash register
$current_month = date('Y-m-01');
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ?");
$stmt->execute([$current_month]);
$register = $stmt->fetch();

// Check if can pay
$canPay = false;
$errorMessage = '';

if(!$register) {
    $errorMessage = 'No cash register found for current month!';
} elseif($register['is_closed']) {
    $errorMessage = 'Cash register is closed for this month!';
} elseif($register['is_locked']) {
    $errorMessage = 'Cash register is locked! ' . ($register['lock_reason'] ?: 'Insufficient balance');
} elseif($register['remaining_balance'] < $bill['balance_amount']) {
    $errorMessage = 'Insufficient balance! Available: ₹' . number_format($register['remaining_balance'], 2);
} else {
    $canPay = true;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && $canPay) {
    $payment_amount = $_POST['payment_amount'];
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $cheque_no = $_POST['cheque_no'] ?? null;
    
    $pdo->beginTransaction();
    
    try {
        // Update bill
        $new_paid = $bill['paid_amount'] + $payment_amount;
        $new_balance = $bill['total_amount'] - $new_paid;
        $status = $new_balance <= 0 ? 'paid' : 'partial';
        
        $stmt = $pdo->prepare("UPDATE bills SET paid_amount = ?, balance_amount = ?, status = ?, payment_date = ? WHERE id = ?");
        $stmt->execute([$new_paid, $new_balance, $status, $payment_date, $bill_id]);
        
        // Update cash register
        $new_remaining = $register['remaining_balance'] - $payment_amount;
        $new_total_out = $register['total_cash_out'] + $payment_amount;
        $new_total_purchases = $register['total_purchases'] + $payment_amount;
        $new_closing = $register['opening_balance'] + $register['total_cash_in'] - $new_total_out;
        
        // Check if remaining balance is zero or negative
        $is_locked = ($new_remaining <= 0) ? 1 : 0;
        $lock_reason = ($new_remaining <= 0) ? 'Zero balance reached' : NULL;
        
        $stmt = $pdo->prepare("UPDATE cash_register SET 
                              remaining_balance = ?,
                              total_cash_out = ?,
                              total_purchases = ?,
                              closing_balance = ?,
                              is_locked = ?,
                              lock_reason = ?
                              WHERE id = ?");
        $stmt->execute([$new_remaining, $new_total_out, $new_total_purchases, $new_closing, $is_locked, $lock_reason, $register['id']]);
        
        // Add to monthly purchase details
        $stmt = $pdo->prepare("INSERT INTO monthly_purchase_details 
                              (cash_register_id, bill_id, vendor_id, purchase_amount, payment_status, paid_amount, payment_date) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$register['id'], $bill_id, $bill['vendor_id'], $payment_amount, $status, $payment_amount, $payment_date]);
        
        // Add transaction record
        $stmt = $pdo->prepare("INSERT INTO cash_register_transactions 
                              (cash_register_id, transaction_type, amount, description, reference_id, reference_type, created_by) 
                              VALUES (?, 'bill_payment', ?, ?, ?, 'bill', ?)");
        $stmt->execute([$register['id'], $payment_amount, "Payment for Bill: " . $bill['bill_no'], $bill_id, $_SESSION['user_id']]);
        
        $pdo->commit();
        
        echo '<div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Payment successful!
                <br>Amount Paid: ₹' . number_format($payment_amount, 2) . '
                <br>Remaining Balance: ₹' . number_format($new_remaining, 2) . '
              </div>';
        
        if($is_locked) {
            echo '<div class="alert alert-warning">
                    <i class="fas fa-lock"></i> Cash register is now locked due to zero balance!
                  </div>';
        }
        
        echo '<a href="register.php" class="btn btn-primary">Back to Cash Register</a>';
        
    } catch(Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-money-bill-wave"></i> Pay Bill from Cash Register</h2>
            <hr>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Bill Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <strong>Bill No:</strong> <?php echo $bill['bill_no']; ?>
                </div>
                <div class="col-md-4">
                    <strong>Vendor:</strong> <?php echo htmlspecialchars($bill['vendor_name']); ?>
                </div>
                <div class="col-md-4">
                    <strong>Bill Date:</strong> <?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?>
                </div>
                <div class="col-md-4 mt-2">
                    <strong>Total Amount:</strong> ₹<?php echo number_format($bill['total_amount'], 2); ?>
                </div>
                <div class="col-md-4 mt-2">
                    <strong>Already Paid:</strong> ₹<?php echo number_format($bill['paid_amount'], 2); ?>
                </div>
                <div class="col-md-4 mt-2">
                    <strong class="text-danger">Balance Due:</strong> ₹<?php echo number_format($bill['balance_amount'], 2); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if(!$canPay): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> Cannot process payment: <?php echo $errorMessage; ?>
    </div>
    <a href="register.php" class="btn btn-secondary">Back to Cash Register</a>
    <?php else: ?>
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Payment Details</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Available Balance:</strong> ₹<?php echo number_format($register['remaining_balance'], 2); ?>
            </div>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Payment Amount *</label>
                        <input type="number" step="0.01" name="payment_amount" class="form-control" 
                               value="<?php echo $bill['balance_amount']; ?>" max="<?php echo min($bill['balance_amount'], $register['remaining_balance']); ?>" required>
                        <small>Max: ₹<?php echo number_format(min($bill['balance_amount'], $register['remaining_balance']), 2); ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Payment Date *</label>
                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Payment Mode *</label>
                        <select name="payment_mode" class="form-control" required id="paymentMode">
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="chequeDiv" style="display:none;">
                        <label>Cheque Number</label>
                        <input type="text" name="cheque_no" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Confirm Payment</button>
                <a href="register.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
$('#paymentMode').change(function() {
    $('#chequeDiv').toggle($(this).val() == 'cheque');
});
</script>

<?php include '../../includes/footer.php'; ?>