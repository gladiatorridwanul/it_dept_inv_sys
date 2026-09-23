<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_item_id = $_POST['old_item_id'];
    $new_item_id = $_POST['new_item_id'];
    $vendor_id = $_POST['vendor_id'];
    $quantity = $_POST['quantity'];
    $replacement_date = $_POST['replacement_date'];
    $payment_required = isset($_POST['payment_required']) ? 1 : 0;
    $payment_amount = $_POST['payment_amount'] ?? 0;
    $notes = $_POST['notes'];
    
    $pdo->beginTransaction();
    
    try {
        // Insert replacement record
        $stmt = $pdo->prepare("INSERT INTO stock_replacements (old_item_id, new_item_id, vendor_id, quantity, replacement_date, 
                              payment_required, payment_amount, notes, created_by) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$old_item_id, $new_item_id, $vendor_id, $quantity, $replacement_date, 
                       $payment_required, $payment_amount, $notes, $_SESSION['user_id']]);
        
        // Update old item quantity
        $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty - ? WHERE id = ?");
        $stmt->execute([$quantity, $old_item_id]);
        
        // Update new item quantity
        $stmt = $pdo->prepare("UPDATE items SET current_qty = current_qty + ? WHERE id = ?");
        $stmt->execute([$quantity, $new_item_id]);
        
        $pdo->commit();
        echo '<div class="alert alert-success">Replacement processed successfully!</div>';
    } catch(Exception $e) {
        $pdo->rollBack();
        echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    }
}

$items = $pdo->query("SELECT * FROM items WHERE is_active=1 ORDER BY name")->fetchAll();
$vendors = $pdo->query("SELECT * FROM vendors WHERE is_active=1 ORDER BY name")->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-exchange-alt"></i> Stock Replacement</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Old/Faulty Item *</label>
                        <select name="old_item_id" class="form-control" required>
                            <option value="">Select Item</option>
                            <?php foreach($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['name'] . ' - ' . $item['item_code'] . ' (Qty: ' . $item['current_qty'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>New/Replacement Item *</label>
                        <select name="new_item_id" class="form-control" required>
                            <option value="">Select Item</option>
                            <?php foreach($items as $item): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['name'] . ' - ' . $item['item_code']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Vendor *</label>
                        <select name="vendor_id" class="form-control" required>
                            <option value="">Select Vendor</option>
                            <?php foreach($vendors as $vendor): ?>
                            <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Quantity *</label>
                        <input type="number" name="quantity" class="form-control" required min="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Replacement Date *</label>
                        <input type="date" name="replacement_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="payment_required" class="form-check-input" id="paymentRequired">
                            <label class="form-check-label" for="paymentRequired">Payment Required</label>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3" id="paymentAmountDiv" style="display:none;">
                        <label>Payment Amount</label>
                        <input type="number" step="0.01" name="payment_amount" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Notes</label>
                        <textarea name="notes" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Process Replacement</button>
                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('paymentRequired').addEventListener('change', function() {
    document.getElementById('paymentAmountDiv').style.display = this.checked ? 'block' : 'none';
});
</script>

<?php include '../../includes/footer.php'; ?>