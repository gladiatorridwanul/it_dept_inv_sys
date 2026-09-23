<?php
require_once '../../../config/database.php';
session_start();

if(!isset($_POST['id'])) exit('Invalid request');

$id = intval($_POST['id']);
$stmt = $pdo->prepare("SELECT w.*, v.vendor_name as vendor_company, i.name as item_name, c.name as category_name FROM warranties w LEFT JOIN vendors v ON w.vendor_id = v.id LEFT JOIN items i ON w.item_id = i.id LEFT JOIN categories c ON w.category_id = c.id WHERE w.id = ?");
$stmt->execute([$id]);
$warranty = $stmt->fetch();

if($warranty):
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <tr><th width="30%">Device Name</th><td><?php echo htmlspecialchars($warranty['item_name']); ?></td></tr>
        <tr><th>Serial Number</th><td><?php echo htmlspecialchars($warranty['serial_number']); ?></td></tr>
        <tr><th>Warranty Type</th><td><?php echo ucfirst($warranty['warranty_type']); ?></td></tr>
        <tr><th>Start Date</th><td><?php echo date('d-m-Y', strtotime($warranty['warranty_start_date'])); ?></td></tr>
        <tr><th>End Date</th><td><?php echo date('d-m-Y', strtotime($warranty['warranty_end_date'])); ?></td></tr>
        <tr><th>Provider</th><td><?php echo htmlspecialchars($warranty['warranty_provider']); ?></td></tr>
        <tr><th>Provider Phone</th><td><?php echo htmlspecialchars($warranty['provider_phone']); ?></td></tr>
        <tr><th>Provider Email</th><td><?php echo htmlspecialchars($warranty['provider_email']); ?></td></tr>
        <tr><th>Vendor</th><td><?php echo htmlspecialchars($warranty['vendor_company']); ?></td></tr>
        <tr><th>Item</th><td><?php echo htmlspecialchars($warranty['item_name']); ?></td></tr>
        <tr><th>Category</th><td><?php echo htmlspecialchars($warranty['category_name']); ?></td></tr>
        <tr><th>Invoice Number</th><td><?php echo htmlspecialchars($warranty['invoice_number']); ?></td></tr>
        <tr><th>Coverage Details</th><td><?php echo nl2br(htmlspecialchars($warranty['coverage_details'])); ?></td></tr>
        <tr><th>Claim Phone</th><td><?php echo htmlspecialchars($warranty['claim_phone']); ?></td></tr>
        <tr><th>Claim Email</th><td><?php echo htmlspecialchars($warranty['claim_email']); ?></td></tr>
        <tr><th>Claim Website</th><td><?php echo htmlspecialchars($warranty['claim_website']); ?></td></tr>
        <tr><th>Status</th><td><span class="status-badge status-<?php echo $warranty['status']; ?>"><?php echo ucfirst(str_replace('_',' ',$warranty['status'])); ?></span></td></tr>
        <tr><th>Notes</th><td><?php echo nl2br(htmlspecialchars($warranty['notes'])); ?></td></tr>
        <?php if($warranty['documentation_file']): ?>
        <tr><th>Documentation</th><td><a href="../../<?php echo $warranty['documentation_file']; ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-download"></i> Download</a></td></tr>
        <?php endif; ?>
    </table>
</div>
<?php else: ?>
<div class="alert alert-danger">Warranty not found</div>
<?php endif; ?>