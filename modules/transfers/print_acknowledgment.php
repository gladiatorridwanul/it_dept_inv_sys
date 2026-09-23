<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$stmt = $pdo->prepare("SELECT * FROM device_transfers WHERE id = ?");
$stmt->execute([$id]);
$transfer = $stmt->fetch();

if(!$transfer) {
    die("Transfer not found");
}

// Get employee details if available
$employee_info = null;
if($transfer['employee_id']) {
    $emp_stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $emp_stmt->execute([$transfer['employee_id']]);
    $employee_info = $emp_stmt->fetch();
}

// Get item details if available
$item_info = null;
if($transfer['item_id']) {
    $item_stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
    $item_stmt->execute([$transfer['item_id']]);
    $item_info = $item_stmt->fetch();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delivery Acknowledgment - <?php echo $transfer['transfer_no']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Cambria', 'Georgia', 'Times New Roman', serif;
            background: #f0f0f0;
            padding: 20px;
            font-size: 11px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }
        
        .page-container {
            max-width: 148mm;
            width: 148mm;
            margin: 0 auto;
            background: white;
            border: 1px solid #000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 8px 12px;
        }
        
        /* Headers */
        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #000;
        }
        .header h1 {
            font-size: 18px;
            color: #000;
            letter-spacing: 2px;
            font-weight: 700;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .header .sub-title {
            font-size: 11px;
            color: #000;
            margin-top: 2px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .header .transfer-no {
            font-size: 13px;
            font-weight: bold;
            color: #000;
            margin-top: 3px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .header .ack-sub {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Grid Layouts */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 4px 0;
        }
        
        .from-to-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin: 4px 0;
        }
        
        .info-box, .from-to-box {
            border: 1px solid #000;
            padding: 4px 8px;
        }
        
        .info-box-title, .from-to-box .box-title {
            font-weight: 700;
            font-size: 11px;
            color: #000;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            text-align: center;
            font-family: 'Cambria', 'Georgia', serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 1px;
            font-size: 11px;
            line-height: 1.3;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .info-label {
            width: 85px;
            font-weight: 600;
            color: #000;
            flex-shrink: 0;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .info-value {
            flex: 1;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        .status-text {
            font-weight: 600;
            text-transform: uppercase;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        .product-details-box {
            border: 1px solid #000;
            padding: 4px 8px;
            margin-top: 4px;
            background: #fff;
        }
        .product-details-box .section-label {
            font-weight: 700;
            font-size: 11px;
            margin-bottom: 3px;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .employee-info-box {
            margin-top: 4px;
            padding: 3px 8px;
            border: 1px solid #000;
            font-size: 10px;
            background: #fff;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .employee-info-box strong {
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Signature Section */
        .signature-section {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin: 8px 0 4px 0;
        }
        .sign-box {
            text-align: center;
            padding: 3px;
        }
        .sign-box .sign-line {
            height: 35px;
            border-bottom: 1px solid #000;
            margin-bottom: 3px;
        }
        .sign-box .sign-label {
            font-size: 10px;
            font-weight: 600;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .sign-box .sign-sub {
            font-size: 8px;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #000;
            font-size: 9px;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 28px;
            background: #333;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            z-index: 1000;
            font-family: 'Segoe UI', Arial, sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            transition: all 0.2s;
        }
        .btn-print:hover { 
            background: #000; 
            transform: scale(1.02);
        }
        .btn-print i { margin-right: 8px; }
        
        @page { 
            size: A5; 
            margin: 0.15in;
        }
        
        @media print {
            .btn-print { display: none !important; }
            body { 
                background: white; 
                padding: 0; 
                margin: 0; 
                font-size: 11px;
                display: block;
            }
            .page-container { 
                max-width: 100%; 
                width: 100%;
                border: none; 
                margin: 0; 
                padding: 0.1in 0.15in;
                box-shadow: none;
                height: auto;
                min-height: auto;
            }
            .header h1 { font-size: 17px; }
            .header .sub-title { font-size: 10px; }
            .header .transfer-no { font-size: 12px; }
            .header .ack-sub { font-size: 9px; }
            .info-box-title, .from-to-box .box-title { font-size: 10px; }
            .info-row { font-size: 10px; }
            .info-label { width: 80px; }
            .product-details-box .section-label { font-size: 10px; }
            .employee-info-box { font-size: 9px; padding: 3px 6px; }
            .sign-box .sign-line { height: 30px; }
            .sign-box .sign-label { font-size: 9px; }
            .sign-box .sign-sub { font-size: 7px; }
            .footer-text { font-size: 8px; }
            .info-box, .from-to-box { padding: 3px 6px; }
            .signature-section { gap: 15px; margin: 6px 0 3px 0; }
            .product-details-box { padding: 3px 6px; }
            .header { margin-bottom: 6px; padding-bottom: 4px; }
            .info-grid { gap: 6px; margin: 3px 0; }
            .from-to-container { gap: 6px; margin: 3px 0; }
            .info-value { font-size: 10px; }
        }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print();">
        <i class="fas fa-print"></i> Print / Save PDF
    </button>
    
    <div class="page-container">
        <div class="header">
            <h1>DELIVERY ACKNOWLEDGMENT</h1>
            <div class="sub-title">UniMed UniHealth Pharmaceutical Ltd. - IT Department</div>
            <div class="transfer-no">Transfer No: <?php echo htmlspecialchars($transfer['transfer_no']); ?></div>
            <div class="ack-sub">Please sign below to confirm receipt of the device(s)</div>
        </div>
        
        <!-- Transfer Info -->
        <div class="info-grid">
            <div class="info-box">
                <div class="info-box-title">Transfer Details</div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span class="info-value"><?php echo date('d-m-Y', strtotime($transfer['transfer_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><span class="status-text"><?php echo ucfirst($transfer['status']); ?></span></span>
                </div>
                <?php if($transfer['tracking_no']): ?>
                <div class="info-row">
                    <span class="info-label">Tracking No:</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars($transfer['tracking_no']); ?></strong></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Handled By:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['handled_by']) ?: '—'; ?></span>
                </div>
            </div>
            
            <div class="info-box">
                <div class="info-box-title">Product Details</div>
                <div class="info-row">
                    <span class="info-label">Product:</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars($transfer['product_name']); ?></strong></span>
                </div>
                <?php if($transfer['product_type']): ?>
                <div class="info-row">
                    <span class="info-label">Type:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['product_type']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Quantity:</span>
                    <span class="info-value"><?php echo $transfer['quantity']; ?> unit(s)</span>
                </div>
                <?php if($transfer['model_number']): ?>
                <div class="info-row">
                    <span class="info-label">Model No:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['model_number']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- From / To -->
        <div class="from-to-container">
            <div class="from-to-box">
                <div class="box-title">FROM (Sender)</div>
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['from_location']); ?></span>
                </div>
                <?php if($transfer['from_department']): ?>
                <div class="info-row">
                    <span class="info-label">Dept:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['from_department']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value" style="font-size: 10px;"><?php echo nl2br(htmlspecialchars($transfer['from_address'])); ?></span>
                </div>
            </div>
            
            <div class="from-to-box">
                <div class="box-title">TO (Recipient)</div>
                <div class="info-row">
                    <span class="info-label">Location:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['to_location']); ?></span>
                </div>
                <?php if($transfer['to_department']): ?>
                <div class="info-row">
                    <span class="info-label">Dept:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['to_department']); ?></span>
                </div>
                <?php endif; ?>
                <?php if($transfer['to_attn']): ?>
                <div class="info-row">
                    <span class="info-label">Attn:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transfer['to_attn']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value" style="font-size: 10px;"><?php echo nl2br(htmlspecialchars($transfer['to_address'])); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Additional Details -->
        <?php if($transfer['serial_numbers'] || $transfer['description'] || $transfer['reason']): ?>
        <div class="product-details-box">
            <div class="section-label">Additional Information</div>
            <?php if($transfer['serial_numbers']): ?>
            <div class="info-row">
                <span class="info-label">Serial No(s):</span>
                <span class="info-value"><?php echo nl2br(htmlspecialchars($transfer['serial_numbers'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if($transfer['description']): ?>
            <div class="info-row">
                <span class="info-label">Description:</span>
                <span class="info-value" style="font-size: 10px;"><?php echo nl2br(htmlspecialchars($transfer['description'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if($transfer['reason']): ?>
            <div class="info-row">
                <span class="info-label">Reason:</span>
                <span class="info-value" style="font-size: 10px;"><?php echo nl2br(htmlspecialchars($transfer['reason'])); ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Employee Info -->
        <?php if($transfer['employee_name']): ?>
        <div class="employee-info-box">
            <strong>Employee:</strong> 
            <?php echo htmlspecialchars($transfer['employee_name']); ?>
            <?php if($transfer['employee_pf_no']): ?> | PF: <?php echo htmlspecialchars($transfer['employee_pf_no']); ?><?php endif; ?>
            <?php if($transfer['employee_designation']): ?> | <?php echo htmlspecialchars($transfer['employee_designation']); ?><?php endif; ?>
            <?php if($transfer['employee_department']): ?> | Dept: <?php echo htmlspecialchars($transfer['employee_department']); ?><?php endif; ?>
            <?php if($transfer['employee_phone']): ?> | Phone: <?php echo htmlspecialchars($transfer['employee_phone']); ?><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-label">Sender's Signature</div>
                <div class="sign-sub">(IT Department)</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-label">Receiver's Signature</div>
                <div class="sign-sub">(Recipient)</div>
            </div>
            <div class="sign-box">
                <div class="sign-line"></div>
                <div class="sign-label">Witness Signature</div>
                <div class="sign-sub">(Optional)</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            Generated: <?php echo date('d-m-Y H:i:s'); ?> | Status: <?php echo ucfirst($transfer['status']); ?> | UniMed UniHealth Pharmaceutical Ltd. - IT Department
        </div>
    </div>
</body>
</html>