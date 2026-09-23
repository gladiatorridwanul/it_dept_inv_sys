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
    <title>Device Transfer - <?php echo $transfer['transfer_no']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Cambria', 'Georgia', 'Times New Roman', serif;
            background: #f0f0f0;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            font-size: 10px;
        }
        
        .page-container {
            max-width: 7.5in;
            width: 7.5in;
            min-height: 10.5in;
            margin: 0 auto;
            background: white;
            border: 1px solid #000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            padding: 0;
            page-break-after: avoid;
            page-break-inside: avoid;
        }
        
        /* First Half - Product Information */
        .half-page-first {
            padding: 8px 12px 6px 12px;
            display: block;
            border-bottom: 2px dashed #000;
        }
        
        /* Second Half - Acknowledgment Form */
        .half-page-second {
            padding: 6px 12px 8px 12px;
            display: block;
        }
        
        .half-page-content {
            display: block;
        }
        
        /* Headers */
        .header, .ack-header {
            text-align: center;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 2px solid #000;
        }
        .header h1, .ack-header h2 {
            font-size: 16px;
            color: #000;
            letter-spacing: 2px;
            font-weight: 700;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .header .sub-title, .ack-header .ack-sub {
            font-size: 9px;
            color: #000;
            margin-top: 1px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .header .transfer-no, .ack-header .ack-transfer-no {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            margin-top: 1px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Grid Layouts - Using tables instead of flex/grid */
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .info-grid-row {
            display: table-row;
        }
        .info-grid-cell {
            display: table-cell;
            width: 50%;
            padding: 0;
            vertical-align: top;
        }
        .info-grid-cell:first-child {
            padding-right: 4px;
        }
        .info-grid-cell:last-child {
            padding-left: 4px;
        }
        
        .from-to-container {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }
        .from-to-row {
            display: table-row;
        }
        .from-to-cell {
            display: table-cell;
            width: 50%;
            padding: 0;
            vertical-align: top;
        }
        .from-to-cell:first-child {
            padding-right: 4px;
        }
        .from-to-cell:last-child {
            padding-left: 4px;
        }
        
        .info-box, .from-to-box {
            border: 1px solid #000;
            padding: 4px 6px;
            display: block;
        }
        
        .info-box-title, .from-to-box .box-title {
            font-weight: 700;
            font-size: 10px;
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
            display: block;
            margin-bottom: 1px;
            font-size: 10px;
            line-height: 1.2;
            font-family: 'Cambria', 'Georgia', serif;
            clear: both;
        }
        .info-label {
            display: inline-block;
            width: 65px;
            font-weight: 600;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .info-value {
            display: inline-block;
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
            padding: 4px 6px;
            margin-top: 4px;
            background: #fff;
            display: block;
        }
        .product-details-box .section-label {
            font-weight: 700;
            font-size: 10px;
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
            padding: 3px 6px;
            border: 1px solid #000;
            font-size: 9px;
            background: #fff;
            font-family: 'Cambria', 'Georgia', serif;
            display: block;
        }
        .employee-info-box strong {
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Signature Section */
        .signature-section {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 3px 0;
        }
        .signature-row {
            display: table-row;
        }
        .signature-cell {
            display: table-cell;
            width: 33.33%;
            padding: 0 6px;
            vertical-align: top;
            text-align: center;
        }
        .signature-cell:first-child {
            padding-left: 0;
        }
        .signature-cell:last-child {
            padding-right: 0;
        }
        .sign-box {
            text-align: center;
            padding: 2px;
            display: block;
        }
        .sign-box .sign-line {
            height: 22px;
            border-bottom: 1px solid #000;
            margin-bottom: 2px;
        }
        .sign-box .sign-label {
            font-size: 9px;
            font-weight: 600;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .sign-box .sign-sub {
            font-size: 7px;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Warning Note */
        .warning-note {
            margin-top: 4px;
            padding: 3px 6px;
            border: 1px solid #000;
            text-align: center;
            font-size: 9px;
            font-weight: 600;
            color: #000;
            background: #fff;
            font-family: 'Cambria', 'Georgia', serif;
            display: block;
        }
        .warning-note i {
            margin-right: 4px;
        }
        
        /* Footer */
        .footer-text {
            text-align: center;
            margin-top: 4px;
            padding-top: 3px;
            border-top: 1px solid #000;
            font-size: 8px;
            color: #000;
            font-family: 'Cambria', 'Georgia', serif;
            display: block;
        }
        
        /* Print button */
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
        .btn-print i {
            margin-right: 8px;
        }
        
        /* Custom Page Size - 7.5 × 10.5 inches with 0.15in Margin */
        @page {
            size: 7.5in 10.5in;
            margin: 0.15in;
        }
        
        @media print {
            .btn-print {
                display: none !important;
            }
            body {
                background: white;
                padding: 0;
                margin: 0;
                display: block;
                font-size: 10px;
            }
            .page-container {
                max-width: 100%;
                width: 100%;
                min-height: 100vh;
                border: none;
                margin: 0;
                box-shadow: none;
                border-radius: 0;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            
            .half-page-first {
                padding: 6px 10px 4px 10px;
                display: block;
                border-bottom: 2px dashed #000;
            }
            
            .half-page-second {
                padding: 4px 10px 6px 10px;
                display: block;
            }
            
            .header h1, .ack-header h2 { font-size: 15px; }
            .header .sub-title, .ack-header .ack-sub { font-size: 8px; }
            .header .transfer-no, .ack-header .ack-transfer-no { font-size: 10px; }
            .info-box-title, .from-to-box .box-title { font-size: 9px; }
            .info-row { font-size: 9px; }
            .info-label { width: 60px; }
            .product-details-box .section-label { font-size: 9px; }
            .employee-info-box { font-size: 8px; padding: 2px 5px; }
            
            .signature-section {
                width: 100%;
                margin: 3px 0 2px 0;
            }
            .signature-cell {
                padding: 0 4px;
            }
            .sign-box .sign-line { height: 20px; border-bottom: 1px solid #000; }
            .sign-box .sign-label { font-size: 8px; }
            .sign-box .sign-sub { font-size: 6px; }
            
            .warning-note { 
                font-size: 8px; 
                padding: 2px 5px;
                margin-top: 3px;
            }
            .footer-text { 
                font-size: 7px; 
                margin-top: 3px;
                padding-top: 2px;
            }
            .info-box, .from-to-box { padding: 3px 5px; }
            .product-details-box { padding: 3px 5px; }
            .info-grid { margin: 3px 0; }
            .from-to-container { margin: 3px 0; }
            .page-container { min-height: 10.5in; }
            .header, .ack-header { margin-bottom: 4px; padding-bottom: 3px; }
            .info-value { font-size: 9px; }
            .info-row { margin-bottom: 1px; line-height: 1.15; }
            .info-grid-cell:first-child { padding-right: 3px; }
            .info-grid-cell:last-child { padding-left: 3px; }
            .from-to-cell:first-child { padding-right: 3px; }
            .from-to-cell:last-child { padding-left: 3px; }
        }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print();">
        <i class="fas fa-print"></i> Print / Save PDF
    </button>
    
    <div class="page-container">
        <!-- ======================================== -->
        <!-- FIRST HALF: PRODUCT INFORMATION           -->
        <!-- ======================================== -->
        <div class="half-page-first">
            <div class="half-page-content">
                <div class="header">
                    <h1>DEVICE TRANSFER</h1>
                    <div class="sub-title">UniMed UniHealth Pharmaceutical Ltd. - IT Department</div>
                    <div class="transfer-no">Transfer No: <?php echo htmlspecialchars($transfer['transfer_no']); ?></div>
                </div>
                
                <!-- Transfer Info -->
                <div class="info-grid">
                    <div class="info-grid-row">
                        <div class="info-grid-cell">
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
                        </div>
                        <div class="info-grid-cell">
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
                    </div>
                </div>
                
                <!-- From / To -->
                <div class="from-to-container">
                    <div class="from-to-row">
                        <div class="from-to-cell">
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
                                    <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['from_address'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="from-to-cell">
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
                                    <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['to_address'])); ?></span>
                                </div>
                            </div>
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
                        <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['description'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['reason']): ?>
                    <div class="info-row">
                        <span class="info-label">Reason:</span>
                        <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['reason'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['notes']): ?>
                    <div class="info-row">
                        <span class="info-label">Notes:</span>
                        <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['notes'])); ?></span>
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
            </div>
            
            <!-- Warning Note -->
            <div class="warning-note">
                সাবধানতার সাথে বহন করুন, গুরুত্বপূর্ণ আইটি পণ্য (উলটাইনা যাবেনা) | Handle with care - Important IT equipment. Do not turn over.
            </div>
        </div>
        
        <!-- ======================================== -->
        <!-- SECOND HALF: ACKNOWLEDGMENT FORM          -->
        <!-- ======================================== -->
        <div class="half-page-second">
            <div class="half-page-content">
                <div class="ack-header">
                    <h2>DELIVERY ACKNOWLEDGMENT</h2>
                    <div class="ack-sub">Please sign below to confirm receipt of the device(s)</div>
                </div>
                
                <!-- Transfer Info -->
                <div class="info-grid">
                    <div class="info-grid-row">
                        <div class="info-grid-cell">
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
                        </div>
                        <div class="info-grid-cell">
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
                    </div>
                </div>
                
                <!-- From / To -->
                <div class="from-to-container">
                    <div class="from-to-row">
                        <div class="from-to-cell">
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
                                    <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['from_address'])); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="from-to-cell">
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
                                    <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['to_address'])); ?></span>
                                </div>
                            </div>
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
                        <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['description'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if($transfer['reason']): ?>
                    <div class="info-row">
                        <span class="info-label">Reason:</span>
                        <span class="info-value" style="font-size: 9px;"><?php echo nl2br(htmlspecialchars($transfer['reason'])); ?></span>
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
            </div>
            
            <!-- Signature Section -->
            <div>
                <div class="signature-section">
                    <div class="signature-row">
                        <div class="signature-cell">
                            <div class="sign-box">
                                <div class="sign-line"></div>
                                <div class="sign-label">Sender's Signature</div>
                                <div class="sign-sub">(IT Department)</div>
                            </div>
                        </div>
                        <div class="signature-cell">
                            <div class="sign-box">
                                <div class="sign-line"></div>
                                <div class="sign-label">Receiver's Signature</div>
                                <div class="sign-sub">(Recipient)</div>
                            </div>
                        </div>
                        <div class="signature-cell">
                            <div class="sign-box">
                                <div class="sign-line"></div>
                                <div class="sign-label">Witness Signature</div>
                                <div class="sign-sub">(Optional)</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="footer-text">
                    Generated: <?php echo date('d-m-Y H:i:s'); ?> | Status: <?php echo ucfirst($transfer['status']); ?> | UniMed UniHealth Pharmaceutical Ltd. - IT Department
                </div>
            </div>
        </div>
    </div>
</body>
</html>