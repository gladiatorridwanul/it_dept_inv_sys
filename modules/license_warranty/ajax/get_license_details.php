<?php
require_once '../../../config/database.php';
session_start();

if(!isset($_POST['id'])) exit('Invalid request');

$id = intval($_POST['id']);
$stmt = $pdo->prepare("SELECT l.*, v.vendor_name as vendor_company, i.name as item_name, c.name as category_name, sc.name as sub_category_name FROM licenses l LEFT JOIN vendors v ON l.vendor_id = v.id LEFT JOIN items i ON l.item_id = i.id LEFT JOIN categories c ON l.category_id = c.id LEFT JOIN sub_categories sc ON l.sub_category_id = sc.id WHERE l.id = ?");
$stmt->execute([$id]);
$license = $stmt->fetch();

if($license):
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <tr><th width="30%">Software Name</th><td><?php echo htmlspecialchars($license['software_name']); ?></td></tr>
        <tr><th>License Key</th><td><code><?php echo htmlspecialchars($license['license_key']); ?></code></td></tr>
        <tr><th>Version</th><td><?php echo htmlspecialchars($license['version']); ?></td></tr>
        <tr><th>License Type</th><td><?php echo ucfirst($license['license_type']); ?></td></tr>
        <tr><th>Purchase Date</th><td><?php echo date('d-m-Y', strtotime($license['purchase_date'])); ?></td></tr>
        <tr><th>Expiry Date</th><td><?php echo date('d-m-Y', strtotime($license['expiry_date'])); ?></td></tr>
        <tr><th>Cost</th><td><?php echo number_format($license['cost'], 2); ?> BDT</td></tr>
        <tr><th>Seats</th><td><?php echo $license['seats']; ?> (Used: <?php echo $license['used_seats']; ?>)</td></tr>
        <tr><th>Status</th><td><span class="status-badge status-<?php echo $license['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$license['status'])); ?></span></td></tr>
        <tr><th>Vendor</th><td><?php echo htmlspecialchars($license['vendor_name'] ?? $license['vendor_company']); ?></td></tr>
        <tr><th>Item</th><td><?php echo htmlspecialchars($license['item_name']); ?></td></tr>
        <tr><th>Category</th><td><?php echo htmlspecialchars($license['category_name']); ?></td></tr>
        <tr><th>Sub Category</th><td><?php echo htmlspecialchars($license['sub_category_name']); ?></td></tr>
        <tr><th>Invoice Number</th><td><?php echo htmlspecialchars($license['invoice_number']); ?></td></tr>
        <tr><th>PO Number</th><td><?php echo htmlspecialchars($license['po_number']); ?></td></tr>
        <tr><th>Support Contact</th><td><?php echo htmlspecialchars($license['support_contact']); ?></td></tr>
        <tr><th>Support Email</th><td><?php echo htmlspecialchars($license['support_email']); ?></td></tr>
        <tr><th>Notes</th><td><?php echo nl2br(htmlspecialchars($license['notes'])); ?></td></tr>
        <?php if($license['documentation_file']): ?>
        <tr><th>Documentation</th><td><a href="../../<?php echo $license['documentation_file']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Download</a></td></tr>
        <?php endif; ?>
    </table>
</div>
<?php else: ?>
<div class="alert alert-danger">License not found</div>
<?php endif; ?>