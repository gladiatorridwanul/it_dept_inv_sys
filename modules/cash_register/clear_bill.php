<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name 
                       FROM bills b 
                       JOIN vendors v ON b.vendor_id=v.id 
                       WHERE b.id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if(!$bill) {
    redirect('register.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_amount = $_POST['payment_amount'];
    $payment_mode = $_POST['payment_mode'];
    $cheque_no = $_POST['cheque_no'] ?? null;
    $payment_date = $_POST['payment_date'];
    
    $pdo->beginTransaction();
    
    try {
        $new_paid = $bill['paid_amount'] + $payment_amount;
        $new_balance = $bill['total_amount'] - $new_paid;
        $status = $new_balance <= 0 ? 'paid' : 'partial';
        
        // Update bill
        $stmt = $pdo->prepare("UPDATE bills SET paid_amount = ?, balance_amount = ?, status = ?, 
                              payment_date = ?, payment_mode = ?, cheque_no = ? WHERE id = ?");
        $stmt->execute([$new_paid, $new_balance, $status, $payment_date, $payment_mode, $cheque_no, $id]);
        
        // Update cash register
        $current_month = date('Y-m-01');
        $stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ? AND is_closed = 0");
        $stmt->execute([$current_month]);
        $register = $stmt->fetch();
        
        if($register) {
            $stmt = $pdo->prepare("UPDATE cash_register SET total_cash_out = total_cash_out + ?, 
                                  closing_balance = opening_balance + total_cash_in - total_cash_out - ? 
                                  WHERE id = ?");
            $stmt->execute([$payment_amount, $payment_amount, $register['id']]);
        }
        
        $pdo->commit();
        echo '<div class="alert alert-success">Bill payment recorded successfully!</div>';
        redirect('register.php');
    } catch(Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}
?>

<div class="container-fluid">
    <h2><i class="fas fa-money-bill-wave"></i> Clear Bill</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Bill Details:</strong><br>
                Bill No: <?php echo $bill['bill_no']; ?><br>
                Vendor: <?php echo $bill['vendor_name']; ?><br>
                Total Amount: ₹<?php echo number_format($bill['total_amount'], 2); ?><br>
                Paid Amount: ₹<?php echo number_format($bill['paid_amount'], 2); ?><br>
                Balance Due: ₹<?php echo number_format($bill['balance_amount'], 2); ?>
            </div>
            
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Payment Amount *</label>
                        <input type="number" step="0.01" name="payment_amount" class="form-control" 
                               value="<?php echo $bill['balance_amount']; ?>" required>
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
                <button type="submit" class="btn btn-success">Process Payment</button>
                <a href="register.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('paymentMode').addEventListener('change', function() {
    document.getElementById('chequeDiv').style.display = this.value == 'cheque' ? 'block' : 'none';
});
</script>

<?php include '../../includes/footer.php'; ?>