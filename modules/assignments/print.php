<?php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;
$auto_print = isset($_GET['print']) ? true : false;

$stmt = $pdo->prepare("SELECT a.*, e.full_name, e.designation, e.pf_no, e.job_location, e.department, e.email, e.phone,
                       i.name as item_name, i.specification, i.serial_number, i.model_number, i.brand, i.item_code,
                       u.full_name as assigned_by_name
                       FROM assignments a 
                       JOIN employees e ON a.employee_id = e.id 
                       JOIN items i ON a.item_id = i.id 
                       JOIN users u ON a.assigned_by = u.id
                       WHERE a.id = ?");
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if(!$assignment) {
    die("<div style='text-align:center; padding:50px; font-family:Cambria;'><h2>Assignment not found!</h2><a href='list.php'>Back to List</a></div>");
}

// Get barcode path
$barcodePath = $assignment['barcode_path'] ?? '';
$barcodeFullPath = !empty($barcodePath) ? '../../' . $barcodePath : '';
$hasBarcode = !empty($barcodePath) && file_exists($barcodeFullPath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Acknowledgement - <?php echo $assignment['assignment_no']; ?></title>
    <style>
        /* Reset and Base Styles - A4 Paper Size */
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
        
        /* A4 Size Container - 210mm width equivalent */
        .acknowledgement {
            max-width: 210mm;
            width: 100%;
            margin: 0 auto;
            background: white;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        /* Print Styles - Black & White, Single Page */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .acknowledgement {
                box-shadow: none;
                margin: 0;
                max-width: 100%;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
            .signature-line {
                border-bottom: 1px solid #000 !important;
            }
            .barcode-section {
                border: 1px dashed #aaa !important;
            }
            .info-card, .terms-box {
                border: 1px solid #ccc !important;
            }
            /* Ensure black and white */
            .info-card h6, .details-table th {
                background: #eee !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Main Container */
        .acknowledgement {
            background: white;
        }
        
        /* Header Section */
        .ack-header {
            text-align: center;
            padding: 15px 20px;
            border-bottom: 2px solid #333;
        }
        
        .company-name {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .company-tagline {
            font-size: 9pt;
            color: #555;
            margin-top: 3px;
        }
        
        .doc-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            letter-spacing: 1px;
        }
        
        /* Content Area */
        .ack-content {
            padding: 15px 20px;
        }
        
        /* Barcode Section */
        .barcode-section {
            background: #f8f9fa;
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px dashed #999;
        }
        
        .barcode-label {
            font-size: 9pt;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .barcode-section img {
            max-width: 200px;
            height: auto;
        }
        
        .barcode-number {
            font-family: monospace;
            font-size: 11pt;
            letter-spacing: 2px;
            margin-top: 5px;
        }
        
        /* Two Column Grid */
        .info-grid {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .info-card {
            flex: 1;
            min-width: 200px;
            border: 1px solid #ddd;
            padding: 10px 12px;
        }
        
        .info-card h6 {
            font-size: 10pt;
            font-weight: bold;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 9pt;
            line-height: 1.3;
        }
        
        .info-row .label {
            width: 90px;
            font-weight: bold;
            color: #333;
            flex-shrink: 0;
        }
        
        .info-row .value {
            flex: 1;
            color: #222;
        }
        
        /* Details Table */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        
        .details-table th {
            background: #f0f0f0;
            padding: 8px 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        
        .details-table td {
            padding: 6px 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .details-table td:first-child {
            width: 35%;
            font-weight: bold;
            background: #fafafa;
        }
        
        /* Terms Box */
        .terms-box {
            background: #fafafa;
            border: 1px solid #ddd;
            padding: 10px 12px;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        
        .terms-box strong {
            font-size: 9pt;
        }
        
        .terms-box ul {
            margin-top: 5px;
            padding-left: 20px;
        }
        
        .terms-box li {
            margin-bottom: 3px;
        }
        
        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
        }
        
        .signature-box {
            text-align: center;
            width: 45%;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 35px;
            margin-bottom: 5px;
            width: 100%;
        }
        
        .signature-label {
            font-size: 8pt;
            color: #555;
        }
        
        /* Date Stamp */
        .date-stamp {
            text-align: center;
            margin-top: 10px;
            font-size: 8pt;
            color: #666;
        }
        
        /* Footer */
        .ack-footer {
            background: #f8f8f8;
            padding: 8px 20px;
            text-align: center;
            font-size: 7pt;
            color: #666;
            border-top: 1px solid #ddd;
        }
        
        /* Buttons - Screen Only */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #eee;
        }
        
        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-size: 10pt;
            font-weight: normal;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: Cambria, Georgia, serif;
        }
        
        .btn-print { background: #2d6a4f; color: white; }
        .btn-barcode { background: #2563eb; color: white; }
        .btn-back { background: #6c757d; color: white; }
        
        /* Print Optimizations */
        @media print {
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .acknowledgement {
                width: 100%;
                max-width: 100%;
            }
            .info-grid {
                page-break-inside: avoid;
            }
            .details-table {
                page-break-inside: avoid;
            }
            .terms-box {
                page-break-inside: avoid;
            }
            .signature-section {
                page-break-inside: avoid;
            }
            /* Ensure colors print as black/white */
            .info-card h6, .details-table th {
                background: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .barcode-section {
                background: #fafafa !important;
            }
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .info-grid {
                flex-direction: column;
                gap: 10px;
            }
            .action-buttons {
                flex-direction: column;
            }
            .btn {
                justify-content: center;
            }
            .info-row {
                flex-direction: column;
            }
            .info-row .label {
                width: 100%;
                margin-bottom: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="acknowledgement">
        <!-- Header Section -->
        <div class="ack-header">
            <div class="company-name">IT INVENTORY MANAGEMENT SYSTEM</div>
            <div class="company-tagline">Department of Information Technology</div>
            <div class="doc-title">DEVICE ASSIGNMENT ACKNOWLEDGEMENT</div>
        </div>
        
        <!-- Content Section -->
        <div class="ack-content">
            <!-- Barcode Section -->
            <div class="barcode-section">
                <div class="barcode-label">ASSIGNMENT IDENTIFIER</div>
                <?php if($hasBarcode): ?>
                    <img src="../../<?php echo $barcodePath; ?>" alt="Barcode">
                <?php else: ?>
                    <div style="font-family: monospace; font-size: 16pt; letter-spacing: 3px; padding: 5px;">
                        <?php echo $assignment['assignment_no']; ?>
                    </div>
                <?php endif; ?>
                <div class="barcode-number"><?php echo $assignment['assignment_no']; ?></div>
            </div>
            
            <!-- Two Column Information Grid -->
            <div class="info-grid">
                <!-- Employee Information Card -->
                <div class="info-card">
                    <h6>EMPLOYEE INFORMATION</h6>
                    <div class="info-row">
                        <span class="label">Full Name:</span>
                        <span class="value"><?php echo htmlspecialchars($assignment['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">PF Number:</span>
                        <span class="value"><?php echo $assignment['pf_no']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Designation:</span>
                        <span class="value"><?php echo $assignment['designation'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Department:</span>
                        <span class="value"><?php echo $assignment['department'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Location:</span>
                        <span class="value"><?php echo $assignment['job_location'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Contact:</span>
                        <span class="value"><?php echo $assignment['phone'] ?: 'N/A'; ?></span>
                    </div>
                </div>
                
                <!-- Device Information Card -->
                <div class="info-card">
                    <h6>DEVICE INFORMATION</h6>
                    <div class="info-row">
                        <span class="label">Device Name:</span>
                        <span class="value"><?php echo htmlspecialchars($assignment['item_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Item Code:</span>
                        <span class="value"><?php echo $assignment['item_code']; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Brand:</span>
                        <span class="value"><?php echo $assignment['brand'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Model Number:</span>
                        <span class="value"><?php echo $assignment['model_number'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Serial Number:</span>
                        <span class="value"><?php echo $assignment['serial_number'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Quantity:</span>
                        <span class="value"><?php echo $assignment['quantity']; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Assignment Details Table -->
            <table class="details-table">
                <thead>
                    <tr><th colspan="2">ASSIGNMENT DETAILS</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td width="35%">Assignment Number:</td>
                        <td><strong><?php echo $assignment['assignment_no']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Assigned Date:</td>
                        <td><?php echo date('d-m-Y', strtotime($assignment['assigned_date'])); ?></td>
                    </tr>
                    <tr>
                        <td>Expected Return Date:</td>
                        <td><?php echo $assignment['expected_return_date'] ? date('d-m-Y', strtotime($assignment['expected_return_date'])) : 'Not Specified'; ?></td>
                    </tr>
                    <tr>
                        <td>Assigned By:</td>
                        <td><?php echo $assignment['assigned_by_name']; ?></td>
                    </tr>
                    <?php if(!empty($assignment['notes'])): ?>
                    <tr>
                        <td>Notes:</td>
                        <td><?php echo nl2br(htmlspecialchars($assignment['notes'])); ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Device Specifications (if available) -->
            <?php if(!empty($assignment['specification'])): ?>
            <div class="info-card" style="margin-bottom: 15px;">
                <h6>DEVICE SPECIFICATIONS</h6>
                <p style="font-size: 9pt; line-height: 1.4;"><?php echo nl2br(htmlspecialchars($assignment['specification'])); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Terms and Conditions -->
            <div class="terms-box">
                <strong>TERMS AND CONDITIONS</strong>
                <ul>
                    <li>The device must be returned in good working condition upon request or employment separation.</li>
                    <li>Any damage, loss, or malfunction must be reported immediately to the IT Department.</li>
                    <li>Do not remove, damage, or tamper with asset tags, barcodes, or identification labels.</li>
                    <li>Software installation or hardware modifications require prior written approval from IT Department.</li>
                    <li>The employee is responsible for the safety and security of the assigned device.</li>
                    <li>Loss of device must be reported immediately and may result in recovery cost.</li>
                </ul>
            </div>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">EMPLOYEE SIGNATURE</div>
                    <div class="signature-label">(Receiver)</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">IT DEPARTMENT SIGNATURE</div>
                    <div class="signature-label">(Issuing Authority)</div>
                </div>
            </div>
            
            <!-- Date Stamp -->
            <div class="date-stamp">
                Generated on: <?php echo date('d-m-Y h:i A'); ?>
            </div>
        </div>
        
        <!-- Footer Section -->
        <div class="ack-footer">
            This is a system-generated document. Scan the barcode for quick lookup.<br>
            For any queries, please contact IT Department.
        </div>
        
        <!-- Action Buttons (Screen Only) -->
        <div class="action-buttons no-print">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Print / PDF
            </button>
            <a href="barcode_label.php?id=<?php echo $assignment['id']; ?>" target="_blank" class="btn btn-barcode">
                <i class="fas fa-qrcode"></i> Print Barcode Label
            </a>
            <a href="list.php" class="btn btn-back">
                <i class="fas fa-list"></i> Assignment List
            </a>
        </div>
    </div>
    
    <?php if($auto_print): ?>
    <script>
        setTimeout(function() { window.print(); }, 500);
    </script>
    <?php endif; ?>
</body>
</html>