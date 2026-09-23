<?php
require_once '../../config/database.php';
session_start();

if(!isset($_GET['submission_id'])) {
    exit('<div class="alert alert-danger">Invalid request</div>');
}

$submission_id = intval($_GET['submission_id']);

// Get all devices for this submission
$stmt = $pdo->prepare("
    SELECT d.*, it.name as item_type_name
    FROM submission_devices d
    LEFT JOIN item_types it ON d.item_type_id = it.id
    WHERE d.submission_id = ?
    ORDER BY d.id ASC
");
$stmt->execute([$submission_id]);
$devices = $stmt->fetchAll();

if(empty($devices)) {
    echo '<div class="alert alert-info">No devices found in this submission.</div>';
    exit();
}

foreach($devices as $device):
?>
<div class="device-item">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="device-name">
                <?php echo htmlspecialchars($device['device_name']); ?>
                <?php if($device['item_type_name']): ?>
                <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($device['item_type_name']); ?></span>
                <?php endif; ?>
            </div>
            <div class="row mt-2">
                <div class="col-md-4">
                    <small class="text-muted">Brand:</small><br>
                    <?php echo htmlspecialchars($device['brand_name']) ?: 'N/A'; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Model:</small><br>
                    <?php echo htmlspecialchars($device['model_number']) ?: 'N/A'; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Serial Number:</small><br>
                    <code><?php echo htmlspecialchars($device['serial_number']) ?: 'N/A'; ?></code>
                </div>
            </div>
            <?php if($device['specification']): ?>
            <div class="mt-2">
                <small class="text-muted">Specifications:</small><br>
                <?php echo nl2br(htmlspecialchars($device['specification'])); ?>
            </div>
            <?php endif; ?>
            <div class="row mt-2">
                <div class="col-md-4">
                    <small class="text-muted">Purchase Date:</small><br>
                    <?php echo $device['purchase_date'] ? date('d M Y', strtotime($device['purchase_date'])) : 'N/A'; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Assigned Date:</small><br>
                    <?php echo $device['assigned_date'] ? date('d M Y', strtotime($device['assigned_date'])) : 'N/A'; ?>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Condition:</small><br>
                    <?php echo ucfirst(str_replace('_', ' ', $device['current_condition'])); ?>
                </div>
            </div>
            <?php if($device['assigned_by']): ?>
            <div class="mt-2">
                <small class="text-muted">Assigned By:</small><br>
                <?php echo htmlspecialchars($device['assigned_by']); ?>
            </div>
            <?php endif; ?>
            <?php if($device['notes']): ?>
            <div class="mt-2">
                <small class="text-muted">Notes:</small><br>
                <?php echo nl2br(htmlspecialchars($device['notes'])); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if($device['supporting_document']): ?>
        <div>
            <a href="../../<?php echo $device['supporting_document']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-alt me-1"></i> View Doc
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if($device['status'] == 'added_to_stock' && $device['new_item_id']): 
        // Get item code if available
        $item_stmt = $pdo->prepare("SELECT item_code FROM items WHERE id = ?");
        $item_stmt->execute([$device['new_item_id']]);
        $item = $item_stmt->fetch();
    ?>
    <div class="alert alert-success mt-2 mb-0">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Added to Inventory:</strong> This device has been added to IT stock with code 
        <code><?php echo htmlspecialchars($item['item_code'] ?? 'N/A'); ?></code>
    </div>
    <?php endif; ?>
    
    <?php if($device['admin_notes']): ?>
    <div class="alert alert-secondary mt-2 mb-0">
        <small><strong>Admin Note:</strong> <?php echo nl2br(htmlspecialchars($device['admin_notes'])); ?></small>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>