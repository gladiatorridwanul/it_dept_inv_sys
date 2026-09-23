<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list.php');
    exit();
}

$damage_id = (int)$_GET['id'];

// Ensure PDO connection is available
if (!isset($pdo) || !$pdo) {
    die("Database connection error. Please check your configuration.");
}

// Fetch damage details with proper joins
$damage = null;
try {
    // First, let's check what columns actually exist in the damages table
    $check_columns = $pdo->query("DESCRIBE damages");
    $existing_columns = $check_columns->fetchAll(PDO::FETCH_COLUMN);
    
    // Build query dynamically based on existing columns
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
            i.description as item_description,
            e.full_name as employee_name,
            e.pf_no,
            e.department,
            e.designation,
            e.email as employee_email,
            e.phone as employee_phone,
            u.username as reported_by_name,
            au.username as approved_by_name
        FROM damages d
        LEFT JOIN items i ON d.item_id = i.id
        LEFT JOIN employees e ON d.employee_id = e.id
        LEFT JOIN users u ON d.reported_by = u.id
        LEFT JOIN users au ON d.approved_by = au.id
        WHERE d.id = ?
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$damage_id]);
    $damage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$damage) {
        // Try to get at least basic damage info without joins
        $basic_query = "SELECT * FROM damages WHERE id = ?";
        $basic_stmt = $pdo->prepare($basic_query);
        $basic_stmt->execute([$damage_id]);
        $damage = $basic_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$damage) {
            header('Location: list.php');
            exit();
        }
    }
    
} catch(PDOException $e) {
    error_log("Error loading damage details: " . $e->getMessage());
    // Try fallback query without complex joins
    try {
        $fallback_query = "SELECT * FROM damages WHERE id = ?";
        $fallback_stmt = $pdo->prepare($fallback_query);
        $fallback_stmt->execute([$damage_id]);
        $damage = $fallback_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$damage) {
            die("Error: Damage record not found. Please go back and try again.");
        }
    } catch(PDOException $e2) {
        error_log("Fallback query also failed: " . $e2->getMessage());
        die("Error loading damage details. Please check the database connection and try again.");
    }
}

// Try to get additional details if missing from main query
if ((empty($damage['item_name']) || $damage['item_name'] == 'N/A') && !empty($damage['item_id'])) {
    try {
        $item_query = "SELECT name, item_code, price, brand, serial_number, model_number, version FROM items WHERE id = ?";
        $item_stmt = $pdo->prepare($item_query);
        $item_stmt->execute([$damage['item_id']]);
        $item_details = $item_stmt->fetch(PDO::FETCH_ASSOC);
        if ($item_details) {
            $damage = array_merge($damage, $item_details);
        }
    } catch(PDOException $e) {
        // Silently fail
    }
}

if ((empty($damage['employee_name']) || $damage['employee_name'] == 'N/A') && !empty($damage['employee_id'])) {
    try {
        $emp_query = "SELECT full_name, pf_no, department, designation, email, phone FROM employees WHERE id = ?";
        $emp_stmt = $pdo->prepare($emp_query);
        $emp_stmt->execute([$damage['employee_id']]);
        $emp_details = $emp_stmt->fetch(PDO::FETCH_ASSOC);
        if ($emp_details) {
            $damage = array_merge($damage, $emp_details);
        }
    } catch(PDOException $e) {
        // Silently fail
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damage Report - <?php echo htmlspecialchars($damage['damage_no'] ?? 'N/A'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Cambria, Georgia, 'Times New Roman', serif;
            font-size: 10pt;
            background: #e0e0e0;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }
        .print-container {
            max-width: 1000px;
            width: 100%;
            background: white;
            margin: 0 auto;
            padding: 20px 25px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .print-container {
                padding: 15px 20px;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .signature-line {
                border-bottom: 1px solid #000 !important;
            }
            @page {
                size: A4;
                margin: 10mm;
            }
        }
        
        /* Header */
        .print-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
        }
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
            color: #000;
        }
        .company-tagline {
            font-size: 9pt;
            color: #555;
            margin-top: 3px;
        }
        .report-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 8px;
            letter-spacing: 2px;
            color: #000;
        }
        .report-meta {
            font-size: 9pt;
            color: #666;
            margin-top: 5px;
        }
        
        /* Info Bar */
        .info-bar {
            background: #f5f5f5;
            padding: 8px 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            border: 1px solid #000;
        }
        .info-item {
            text-align: center;
        }
        .info-label {
            font-size: 7pt;
            color: #666;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 10pt;
            font-weight: bold;
            margin-top: 2px;
            color: #000;
        }
        
        /* Status Badge - Black & White */
        .status-badge {
            display: inline-block;
            padding: 2px 12px;
            border: 1px solid #000;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            background: #fff;
        }
        
        /* Sections */
        .section {
            border: 1px solid #000;
            margin-bottom: 12px;
        }
        .section-title {
            background: #f0f0f0;
            padding: 5px 12px;
            font-weight: bold;
            font-size: 9pt;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
        }
        .section-body {
            padding: 10px 12px;
        }
        
        /* Detail Table */
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        .detail-table td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .detail-table .label {
            width: 120px;
            font-weight: 600;
            color: #444;
        }
        .detail-table .value {
            color: #000;
        }
        .detail-table .separator {
            width: 30px;
        }
        
        /* Two Column Layout */
        .two-column {
            display: flex;
            gap: 15px;
            margin-bottom: 12px;
        }
        .col {
            flex: 1;
        }
        
        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #000;
        }
        .signature-box {
            text-align: center;
            width: 30%;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin-top: 25px;
            margin-bottom: 4px;
            height: 1px;
        }
        .signature-label {
            font-size: 7pt;
            color: #666;
        }
        .signature-name {
            font-size: 8pt;
            font-weight: 600;
            color: #000;
            margin-top: 2px;
        }
        
        /* Footer */
        .footer {
            background: #f5f5f5;
            padding: 6px 12px;
            text-align: center;
            font-size: 7pt;
            color: #666;
            border-top: 1px solid #000;
            margin-top: 12px;
        }
        
        /* Buttons */
        .action-buttons {
            text-align: center;
            padding: 12px;
            background: white;
        }
        .btn {
            display: inline-block;
            padding: 8px 20px;
            background: #2d6a4f;
            color: white;
            text-decoration: none;
            font-size: 10pt;
            margin: 0 5px;
            border: none;
            cursor: pointer;
            font-family: Cambria, Georgia, serif;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-danger {
            background: #dc3545;
        }
        
        @media (max-width: 700px) {
            .two-column {
                flex-direction: column;
                gap: 0;
            }
            .info-bar {
                flex-direction: column;
                gap: 5px;
            }
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            .btn {
                margin: 0;
                text-align: center;
            }
            .signature-section {
                flex-direction: column;
                gap: 15px;
            }
            .signature-box {
                width: 100%;
            }
            .detail-table .label {
                width: 80px;
            }
        }
    </style>
</head>
<body>

<div class="print-container">
    <!-- Print Button (hidden when printing) -->
    <div class="no-print" style="text-align:center; margin-bottom:15px;">
        <button onclick="window.print()" class="btn btn-danger">
            <i class="fas fa-print"></i> Print Report
        </button>
        <a href="list.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Print Header -->
    <div class="print-header">
        <div class="company-name">IT INVENTORY MANAGEMENT SYSTEM</div>
        <div class="company-tagline">Department of Information Technology</div>
        <div class="report-title">DAMAGE REPORT</div>
        <div class="report-meta">
            Report No: <?php echo htmlspecialchars($damage['damage_no'] ?? 'N/A'); ?> &nbsp;|&nbsp; 
            Generated: <?php echo date('d-m-Y h:i A'); ?>
        </div>
    </div>

    <!-- Info Bar -->
    <div class="info-bar">
        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value"><span class="status-badge"><?php echo ucfirst($damage['status'] ?? 'Pending'); ?></span></div>
        </div>
        <div class="info-item">
            <div class="info-label">Severity</div>
            <div class="info-value"><span class="status-badge"><?php echo ucfirst($damage['damage_severity'] ?? 'Minor'); ?></span></div>
        </div>
        <div class="info-item">
            <div class="info-label">Damage Type</div>
            <div class="info-value"><?php echo ucfirst(str_replace('_', ' ', $damage['damage_type'] ?? 'N/A')); ?></div>
        </div>
        <div class="info-item">
            <div class="info-label">Damage Date</div>
            <div class="info-value"><?php echo !empty($damage['damage_date']) ? date('d-m-Y', strtotime($damage['damage_date'])) : 'N/A'; ?></div>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="two-column">
        <!-- Left Column -->
        <div class="col">
            <!-- Item Details -->
            <div class="section">
                <div class="section-title"><i class="fas fa-laptop"></i> Item Details</div>
                <div class="section-body">
                    <table class="detail-table">
                        <tr>
                            <td class="label">Item Name:</td>
                            <td class="value"><strong><?php echo htmlspecialchars($damage['item_name'] ?? $damage['name'] ?? 'N/A'); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label">Item Code:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['item_code'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Brand:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['item_brand'] ?? $damage['brand'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php if (!empty($damage['item_serial']) || !empty($damage['serial_number'])): ?>
                        <tr>
                            <td class="label">Serial Number:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['item_serial'] ?? $damage['serial_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($damage['item_model']) || !empty($damage['model_number'])): ?>
                        <tr>
                            <td class="label">Model Number:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['item_model'] ?? $damage['model_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($damage['item_version']) || !empty($damage['version'])): ?>
                        <tr>
                            <td class="label">Version:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['item_version'] ?? $damage['version'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="label">Item Price:</td>
                            <td class="value"><strong>BDT <?php echo number_format($damage['item_price'] ?? $damage['price'] ?? 0, 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Damage Details -->
            <div class="section">
                <div class="section-title"><i class="fas fa-exclamation-triangle"></i> Damage Information</div>
                <div class="section-body">
                    <table class="detail-table">
                        <tr>
                            <td class="label">Estimated Cost:</td>
                            <td class="value">BDT <?php echo number_format($damage['estimated_cost'] ?? 0, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Actual Cost:</td>
                            <td class="value"><strong>BDT <?php echo number_format($damage['actual_cost'] ?? 0, 2); ?></strong></td>
                        </tr>
                        <?php if (!empty($damage['damage_description'])): ?>
                        <tr>
                            <td class="label" style="vertical-align:top;">Description:</td>
                            <td class="value"><?php echo nl2br(htmlspecialchars($damage['damage_description'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($damage['damage_cause'])): ?>
                        <tr>
                            <td class="label" style="vertical-align:top;">Cause:</td>
                            <td class="value"><?php echo nl2br(htmlspecialchars($damage['damage_cause'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col">
            <!-- Employee Details -->
            <div class="section">
                <div class="section-title"><i class="fas fa-user"></i> Responsible Person</div>
                <div class="section-body">
                    <table class="detail-table">
                        <tr>
                            <td class="label">Employee Name:</td>
                            <td class="value"><strong><?php echo htmlspecialchars($damage['employee_name'] ?? $damage['full_name'] ?? 'N/A'); ?></strong></td>
                        </tr>
                        <tr>
                            <td class="label">PF No:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['pf_no'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Department:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['department'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Designation:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['designation'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php if (!empty($damage['employee_email']) || !empty($damage['email'])): ?>
                        <tr>
                            <td class="label">Email:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['employee_email'] ?? $damage['email'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($damage['employee_phone']) || !empty($damage['phone'])): ?>
                        <tr>
                            <td class="label">Phone:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['employee_phone'] ?? $damage['phone'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Approval Details -->
            <div class="section">
                <div class="section-title"><i class="fas fa-check-circle"></i> Approval Information</div>
                <div class="section-body">
                    <table class="detail-table">
                        <tr>
                            <td class="label">Reported By:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['reported_by_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Reported Date:</td>
                            <td class="value"><?php echo !empty($damage['created_at']) ? date('d-m-Y h:i A', strtotime($damage['created_at'])) : 'N/A'; ?></td>
                        </tr>
                        <?php if (!empty($damage['approved_by_name'])): ?>
                        <tr>
                            <td class="label">Approved By:</td>
                            <td class="value"><?php echo htmlspecialchars($damage['approved_by_name']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($damage['approved_date'])): ?>
                        <tr>
                            <td class="label">Approved Date:</td>
                            <td class="value"><?php echo date('d-m-Y', strtotime($damage['approved_date'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($damage['approval_notes'])): ?>
                        <tr>
                            <td class="label" style="vertical-align:top;">Notes:</td>
                            <td class="value"><?php echo nl2br(htmlspecialchars($damage['approval_notes'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Repair/Resolution Details (if applicable) -->
    <?php if (($damage['status'] == 'repaired' || $damage['status'] == 'replaced') && (!empty($damage['repair_notes']) || !empty($damage['repaired_by']))): ?>
    <div class="section">
        <div class="section-title"><i class="fas fa-wrench"></i> Repair / Resolution Details</div>
        <div class="section-body">
            <table class="detail-table">
                <?php if (!empty($damage['repair_notes'])): ?>
                <tr>
                    <td class="label" style="vertical-align:top;">Notes:</td>
                    <td class="value"><?php echo nl2br(htmlspecialchars($damage['repair_notes'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($damage['repaired_by'])): ?>
                <tr>
                    <td class="label">Handled By:</td>
                    <td class="value"><?php echo htmlspecialchars($damage['repaired_by']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (!empty($damage['repaired_date'])): ?>
                <tr>
                    <td class="label">Resolution Date:</td>
                    <td class="value"><?php echo date('d-m-Y', strtotime($damage['repaired_date'])); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Reported By</div>
            <div class="signature-name"><?php echo htmlspecialchars($damage['reported_by_name'] ?? '_________________'); ?></div>
            <div style="font-size:7pt; color:#999;">Date: <?php echo date('d-m-Y'); ?></div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Approved By</div>
            <div class="signature-name"><?php echo htmlspecialchars($damage['approved_by_name'] ?? '_________________'); ?></div>
            <div style="font-size:7pt; color:#999;">Date: <?php echo !empty($damage['approved_date']) ? date('d-m-Y', strtotime($damage['approved_date'])) : '________'; ?></div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Received By</div>
            <div class="signature-name">_________________</div>
            <div style="font-size:7pt; color:#999;">Date: ________</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        This is a computer-generated document. No physical signature required.<br>
        For queries, please contact IT Inventory Department. &copy; <?php echo date('Y'); ?>
    </div>
</div>

<script>
    // Auto-print if ?print parameter is present
    <?php if(isset($_GET['print']) && $_GET['print'] == 'auto'): ?>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
    <?php endif; ?>
</script>

</body>
</html>