<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_name = $_POST['doctor_name'];
    $doctor_specialization = $_POST['doctor_specialization'];
    $department = $_POST['department'];
    $device_id = $_POST['device_id'];
    $purchase_date = $_POST['purchase_date'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $total_amount = $quantity * $unit_price;
    $support_contact = $_POST['support_contact'];
    $warranty_until = $_POST['warranty_until'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("INSERT INTO doctor_purchases (doctor_name, doctor_specialization, department, device_id, 
                          purchase_date, quantity, unit_price, total_amount, support_contact, warranty_until, notes, created_by) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if($stmt->execute([$doctor_name, $doctor_specialization, $department, $device_id, $purchase_date, 
                     $quantity, $unit_price, $total_amount, $support_contact, $warranty_until, $notes, $_SESSION['user_id']])) {
        echo '<div class="alert alert-success">Doctor purchase recorded successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error recording purchase!</div>';
    }
}

$devices = $pdo->query("SELECT * FROM items WHERE is_active=1 ORDER BY name")->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-user-md"></i> Doctor Purchase & Support</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Doctor Name *</label>
                        <input type="text" name="doctor_name" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Specialization</label>
                        <input type="text" name="doctor_specialization" class="form-control" placeholder="Cardiologist, Neurologist, etc.">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Department</label>
                        <input type="text" name="department" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Device/Item *</label>
                        <select name="device_id" class="form-control" required>
                            <option value="">Select Device</option>
                            <?php foreach($devices as $device): ?>
                            <option value="<?php echo $device['id']; ?>">
                                <?php echo htmlspecialchars($device['name'] . ' - ' . $device['item_code']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Purchase Date</label>
                        <input type="date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Quantity</label>
                        <input type="number" name="quantity" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Unit Price (₹)</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label>Total Amount</label>
                        <input type="text" class="form-control" id="totalAmount" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Support Contact</label>
                        <input type="text" name="support_contact" class="form-control" placeholder="Phone/Email for support">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Warranty Until</label>
                        <input type="date" name="warranty_until" class="form-control">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Notes</label>
                        <textarea name="notes" rows="2" class="form-control"></textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Purchase</button>
                <a href="list_purchases.php" class="btn btn-secondary">View Purchases</a>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelector('[name="quantity"], [name="unit_price"]').addEventListener('input', function() {
    var qty = document.querySelector('[name="quantity"]').value || 0;
    var price = document.querySelector('[name="unit_price"]').value || 0;
    document.getElementById('totalAmount').value = (qty * price).toFixed(2);
});
</script>

<?php include '../../includes/footer.php'; ?>