<?php
require_once '../../../config/database.php';
require_once '../../../config/session_fix.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$id = intval($_POST['id']);

try {
    // Get damage details with all related information
    $query = "
        SELECT 
            d.*,
            i.name as item_name,
            i.item_code,
            i.price as item_price,
            i.brand as item_brand,
            i.serial_number as item_serial,
            i.model_number as item_model,
            i.version as item_version,
            i.specification as item_specification,
            e.full_name as employee_name,
            e.pf_no,
            e.department,
            e.designation,
            e.phone as employee_phone,
            e.email as employee_email,
            u.username as reported_by_name,
            au.username as approved_by_name,
            r.full_name as reported_employee_name
        FROM damages d
        LEFT JOIN items i ON d.item_id = i.id
        LEFT JOIN employees e ON d.employee_id = e.id
        LEFT JOIN users u ON d.reported_by = u.id
        LEFT JOIN users au ON d.approved_by = au.id
        LEFT JOIN employees r ON d.reported_by = r.id
        WHERE d.id = ?
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);
    $damage = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$damage) {
        echo '<div class="alert alert-danger">Damage record not found.</div>';
        exit();
    }
    
    // Build the details HTML
    ?>
    <div class="container-fluid">
        <!-- Basic Information -->
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-info-circle me-2"></i>Basic Information</div>
            <div class="detail-row">
                <div class="detail-label">Damage No:</div>
                <div class="detail-value"><strong><?php echo htmlspecialchars($damage['damage_no'] ?? 'N/A'); ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Reported Date:</div>
                <div class="detail-value"><?php echo date('d-m-Y H:i', strtotime($damage['created_at'] ?? 'now')); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Damage Date:</div>
                <div class="detail-value"><?php echo date('d-m-Y', strtotime($damage['damage_date'] ?? 'now')); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Reported By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['reported_by_name'] ?? 'N/A'); ?></div>
            </div>
        </div>

        <!-- Item Information -->
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-laptop me-2"></i>Item Information</div>
            <div class="detail-row">
                <div class="detail-label">Item Name:</div>
                <div class="detail-value"><strong><?php echo htmlspecialchars($damage['item_name'] ?? 'N/A'); ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Item Code:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['item_code'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Brand:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['item_brand'] ?? 'N/A'); ?></div>
            </div>
            <?php if (!empty($damage['item_serial'])): ?>
            <div class="detail-row">
                <div class="detail-label">Serial Number:</div>
                <div class="detail-value"><code><?php echo htmlspecialchars($damage['item_serial']); ?></code></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($damage['item_model'])): ?>
            <div class="detail-row">
                <div class="detail-label">Model Number:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['item_model']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($damage['item_version'])): ?>
            <div class="detail-row">
                <div class="detail-label">Version:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['item_version']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($damage['item_specification'])): ?>
            <div class="detail-row">
                <div class="detail-label">Specification:</div>
                <div class="detail-value"><?php echo nl2br(htmlspecialchars($damage['item_specification'])); ?></div>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <div class="detail-label">Item Price:</div>
                <div class="detail-value"><strong><?php echo number_format($damage['item_price'] ?? 0, 2); ?> TK</strong></div>
            </div>
        </div>

        <!-- Damage Details -->
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-exclamation-triangle me-2"></i>Damage Details</div>
            <div class="detail-row">
                <div class="detail-label">Damage Type:</div>
                <div class="detail-value">
                    <span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $damage['damage_type'] ?? 'N/A')); ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Severity:</div>
                <div class="detail-value">
                    <span class="status-badge severity-<?php echo $damage['damage_severity'] ?? 'minor'; ?>">
                        <?php echo ucfirst($damage['damage_severity'] ?? 'Minor'); ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge status-<?php echo $damage['status'] ?? 'pending'; ?>">
                        <?php echo ucfirst($damage['status'] ?? 'Pending'); ?>
                    </span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Description:</div>
                <div class="detail-value"><?php echo nl2br(htmlspecialchars($damage['damage_description'] ?? 'No description')); ?></div>
            </div>
        </div>

        <!-- Cost Information -->
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-money-bill-wave me-2"></i>Cost Information</div>
            <div class="detail-row">
                <div class="detail-label">Estimated Cost:</div>
                <div class="detail-value"><strong><?php echo number_format($damage['estimated_cost'] ?? 0, 2); ?> TK</strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Actual Cost:</div>
                <div class="detail-value"><strong><?php echo number_format($damage['actual_cost'] ?? 0, 2); ?> TK</strong></div>
            </div>
        </div>

        <!-- Employee Information (if applicable) -->
        <?php if (!empty($damage['employee_name'])): ?>
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-user me-2"></i>Employee Information</div>
            <div class="detail-row">
                <div class="detail-label">Employee Name:</div>
                <div class="detail-value"><strong><?php echo htmlspecialchars($damage['employee_name']); ?></strong></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">PF No:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['pf_no'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Department:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['department'] ?? 'N/A'); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Designation:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['designation'] ?? 'N/A'); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Approval Information -->
        <?php if (!empty($damage['approved_by_name'])): ?>
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-check-circle me-2"></i>Approval Information</div>
            <div class="detail-row">
                <div class="detail-label">Approved By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['approved_by_name']); ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Approved Date:</div>
                <div class="detail-value"><?php echo date('d-m-Y', strtotime($damage['approved_date'] ?? 'now')); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Repair Information -->
        <?php if (!empty($damage['repair_notes'])): ?>
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-wrench me-2"></i>Repair Information</div>
            <div class="detail-row">
                <div class="detail-label">Repair Notes:</div>
                <div class="detail-value"><?php echo nl2br(htmlspecialchars($damage['repair_notes'])); ?></div>
            </div>
            <?php if (!empty($damage['repaired_by'])): ?>
            <div class="detail-row">
                <div class="detail-label">Repaired By:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['repaired_by']); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($damage['repaired_date'])): ?>
            <div class="detail-row">
                <div class="detail-label">Repaired Date:</div>
                <div class="detail-value"><?php echo date('d-m-Y', strtotime($damage['repaired_date'])); ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($damage['replacement_item_id'])): ?>
            <div class="detail-row">
                <div class="detail-label">Replacement Item ID:</div>
                <div class="detail-value"><?php echo htmlspecialchars($damage['replacement_item_id']); ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Attachment -->
        <?php if (!empty($damage['attachment_file'])): ?>
        <div class="detail-section">
            <div class="detail-section-title"><i class="fas fa-paperclip me-2"></i>Attachment</div>
            <div class="detail-row">
                <div class="detail-label">File:</div>
                <div class="detail-value">
                    <a href="../../<?php echo htmlspecialchars($damage['attachment_file']); ?>" target="_blank" class="attachment-link btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i> Download Attachment
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <style>
        .detail-row {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .detail-label {
            width: 140px;
            font-weight: 600;
            color: #475569;
            flex-shrink: 0;
        }
        .detail-value {
            flex: 1;
            color: #1e293b;
        }
        .detail-section {
            margin-bottom: 15px;
        }
        .detail-section-title {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e2e8f0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dbeafe; color: #2563eb; }
        .status-repaired { background: #d1fae5; color: #059669; }
        .status-replaced { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .severity-minor { background: #d1fae5; color: #059669; }
        .severity-moderate { background: #fef3c7; color: #d97706; }
        .severity-severe { background: #fed7aa; color: #ea580c; }
        .severity-critical { background: #fee2e2; color: #dc2626; }
        .modal-body {
            padding: 20px 25px;
            max-height: 70vh;
            overflow-y: auto;
        }
        .attachment-link {
            text-decoration: none;
        }
        .badge {
            font-size: 12px;
            padding: 4px 10px;
        }
    </style>
    <?php
} catch (PDOException $e) {
    error_log("Error loading damage details: " . $e->getMessage());
    echo '<div class="alert alert-danger">Error loading damage details. Please try again.</div>';
}
?>