<?php
require_once '../../includes/auth.php';

$bill_id = $_GET['bill_id'] ?? 0;
$payment_id = $_GET['payment_id'] ?? 0;

$data = null;
$ack_type = null;

// Get payment acknowledgement with items
if($payment_id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, b.bill_no, b.total_amount, b.paid_amount, b.balance_amount,
               b.bill_date, b.due_date,
               v.vendor_name, v.gst_no, v.office_email as email, v.office_phone as phone, v.office_address as address,
               u.full_name as received_by_name
        FROM bill_payments p
        JOIN bills b ON p.bill_id = b.id
        JOIN vendors v ON b.vendor_id = v.id
        LEFT JOIN users u ON p.created_by = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $data = $stmt->fetch();
    $ack_type = 'payment';
} 
// Get full bill acknowledgement with items
elseif($bill_id > 0) {
    $stmt = $pdo->prepare("
        SELECT b.*, v.vendor_name, v.gst_no, v.office_email as email, v.office_phone as phone, v.office_address as address,
               (SELECT COUNT(*) FROM bill_payments WHERE bill_id = b.id) as payment_count,
               (SELECT COALESCE(SUM(amount), 0) FROM bill_payments WHERE bill_id = b.id) as total_paid_sum,
               u.full_name as created_by_name
        FROM bills b 
        JOIN vendors v ON b.vendor_id = v.id 
        LEFT JOIN users u ON b.created_by = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bill_id]);
    $data = $stmt->fetch();
    $ack_type = 'full';
}

if(!$data) {
    die('<div style="text-align:center;padding:50px;font-family:Cambria;"><h2>Acknowledgement Not Found</h2><a href="list_bills.php" style="color:#2d6a4f;">Go back to Bills</a></div>');
}

// Get bill items - FIX: Properly fetch items
$stmt = $pdo->prepare("
    SELECT bi.*, i.item_code, i.name, i.specification
    FROM bill_items bi
    JOIN items i ON bi.item_id = i.id
    WHERE bi.bill_id = ?
    ORDER BY bi.id
");
$stmt->execute([$data['bill_id'] ?? $bill_id]);
$items = $stmt->fetchAll();

// If no items in bill_items, try to get from stock_in_items
if(empty($items)) {
    $stmt = $pdo->prepare("
        SELECT sii.*, i.name, i.item_code, i.specification
        FROM stock_in_items sii
        LEFT JOIN items i ON sii.item_id = i.id
        WHERE sii.stock_in_id = (
            SELECT stock_in_id FROM bills WHERE id = ?
        )
    ");
    $stmt->execute([$data['bill_id'] ?? $bill_id]);
    $items = $stmt->fetchAll();
}

// If still no items, try to get from stock_in directly
if(empty($items)) {
    $stmt = $pdo->prepare("
        SELECT si.*, i.name, i.item_code, i.specification
        FROM stock_in si
        LEFT JOIN items i ON si.item_id = i.id
        WHERE si.bill_id = ?
    ");
    $stmt->execute([$data['bill_id'] ?? $bill_id]);
    $stockItems = $stmt->fetchAll();
    
    foreach($stockItems as $stock) {
        $items[] = [
            'id' => $stock['id'],
            'bill_id' => $data['bill_id'] ?? $bill_id,
            'item_id' => $stock['item_id'],
            'quantity' => $stock['quantity'] ?? 1,
            'unit_price' => $stock['unit_price'] ?? 0,
            'total_price' => $stock['total_amount'] ?? 0,
            'name' => $stock['name'] ?? 'Unknown Item',
            'item_code' => $stock['item_code'] ?? 'N/A',
            'specification' => $stock['specification'] ?? ''
        ];
    }
}

// Check if bill is fully paid for full acknowledgement
if($ack_type == 'full') {
    $balance_amount = $data['balance_amount'] ?? ($data['total_amount'] - $data['paid_amount']);
    if($balance_amount > 0) {
        die('<div style="text-align:center;padding:50px;font-family:Cambria;max-width:500px;margin:0 auto;">
            <div style="background:#fff3cd;border:1px solid #ffecb5;padding:30px;">
                <h2 style="color:#856404;">Cannot Generate Acknowledgement</h2>
                <p style="color:#856404;">This bill is not fully paid yet.</p>
                <p><strong>Balance Due: BDT ' . number_format($balance_amount, 2) . '</strong></p>
                <a href="make_payment.php?id=' . $bill_id . '" style="margin-top:15px;padding:10px 20px;background:#ffc107;color:#333;text-decoration:none;display:inline-block;">Make Payment</a>
                <a href="list_bills.php" style="margin-top:15px;margin-left:10px;padding:10px 20px;background:#6c757d;color:white;text-decoration:none;display:inline-block;">Back to Bills</a>
            </div>
        </div>');
        exit();
    }
}

// Format data
if($ack_type == 'payment') {
    $ack_no = 'ACK-PAY-' . str_pad($payment_id, 6, '0', STR_PAD_LEFT);
    $ack_title = 'PAYMENT ACKNOWLEDGEMENT';
    $amount = $data['amount'];
    $date = $data['payment_date'];
    $mode = $data['payment_mode'];
    $bill_no = $data['bill_no'];
    $total_amount = $data['total_amount'];
    $paid_amount = $data['paid_amount'];
    $balance = $data['balance_amount'];
    $reference = $data['transaction_id'] ?? $data['cheque_no'] ?? 'N/A';
    $notes = $data['notes'] ?? '';
    $received_by = $data['received_by_name'] ?? 'System';
    $prev_paid = $paid_amount - $amount;
} else {
    $ack_no = 'ACK-FULL-' . str_pad($bill_id, 6, '0', STR_PAD_LEFT);
    $ack_title = 'FULL AND FINAL SETTLEMENT';
    $amount = $data['total_amount'];
    $date = date('Y-m-d');
    $mode = 'MULTIPLE PAYMENTS';
    $bill_no = $data['bill_no'];
    $total_amount = $data['total_amount'];
    $paid_amount = $data['total_amount'];
    $balance = 0;
    $reference = 'Full Settlement';
    $notes = $data['notes'] ?? '';
    $received_by = $data['created_by_name'] ?? 'System';
    $prev_paid = 0;
}

// Format numbers and dates
$amount_formatted = number_format($amount, 2);
$total_formatted = number_format($total_amount, 2);
$paid_formatted = number_format($paid_amount, 2);
$balance_formatted = number_format($balance, 2);
$ack_date = date('d-m-Y', strtotime($date));
$bill_date = date('d-m-Y', strtotime($data['bill_date']));
$due_date = !empty($data['due_date']) ? date('d-m-Y', strtotime($data['due_date'])) : 'N/A';
$mode_text = ucfirst(str_replace('_', ' ', $mode));

// Calculate item total from items array
$items_total = 0;
foreach($items as $item) {
    $items_total += $item['total_price'];
}

// Convert amount to words
function amountToWords($number) {
    $number = floor((float)$number);
    if($number == 0) return 'Zero';
    
    $words = array(
        1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
        15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
        19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty',
        50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );
    
    if($number < 20) return $words[$number];
    if($number < 100) {
        $tens = floor($number / 10) * 10;
        $ones = $number % 10;
        return $words[$tens] . ($ones ? ' ' . $words[$ones] : '');
    }
    if($number < 1000) {
        $hundreds = floor($number / 100);
        $remainder = $number % 100;
        return $words[$hundreds] . ' Hundred' . ($remainder ? ' ' . amountToWords($remainder) : '');
    }
    if($number < 100000) {
        $thousands = floor($number / 1000);
        $remainder = $number % 1000;
        return amountToWords($thousands) . ' Thousand' . ($remainder ? ' ' . amountToWords($remainder) : '');
    }
    if($number < 10000000) {
        $lakhs = floor($number / 100000);
        $remainder = $number % 100000;
        return amountToWords($lakhs) . ' Lakh' . ($remainder ? ' ' . amountToWords($remainder) : '');
    }
    $crores = floor($number / 10000000);
    $remainder = $number % 10000000;
    return amountToWords($crores) . ' Crore' . ($remainder ? ' ' . amountToWords($remainder) : '');
}
$amount_in_words = amountToWords($amount) . ' Taka Only';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Acknowledgement - <?php echo $ack_no; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Cambria, Georgia, serif;
            font-size: 10pt;
            background: #e0e0e0;
            padding: 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }
        .ack-container {
            max-width: 1000px;
            width: 100%;
            background: white;
            margin: 0 auto;
            position: relative;
        }
        @media print {
            body { background: white; padding: 0; }
            .ack-container { margin: 0; }
            .no-print { display: none !important; }
            .signature-line { border-bottom: 1px solid #000 !important; }
            .watermark { opacity: 0.1; }
            .item-table { page-break-inside: avoid; }
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 60pt;
            font-weight: bold;
            color: rgba(40, 167, 69, 0.08);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
        }
        .ack-content {
            padding: 20px 25px;
            position: relative;
            z-index: 1;
        }
        /* Header */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
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
        .ack-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 8px;
            letter-spacing: 2px;
        }
        /* Two Column Layout */
        .two-column {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .col {
            flex: 1;
        }
        /* Sections */
        .section {
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .section-title {
            background: #f0f0f0;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 10pt;
            border-bottom: 1px solid #ddd;
        }
        .section-body {
            padding: 10px 12px;
        }
        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 9pt;
        }
        .info-row .label {
            width: 100px;
            font-weight: bold;
            color: #444;
        }
        .info-row .value {
            flex: 1;
            color: #222;
        }
        /* Items Table */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8pt;
        }
        .item-table th, .item-table td {
            border: 1px solid #ddd;
            padding: 6px 8px;
        }
        .item-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }
        .item-table td:last-child, .item-table th:last-child {
            text-align: right;
        }
        .item-table .text-center {
            text-align: center;
        }
        .item-table .text-right {
            text-align: right;
        }
        /* Amount Box */
        .amount-box {
            background: #fafafa;
            border: 1px solid #ccc;
            text-align: center;
            padding: 12px;
            margin-bottom: 15px;
        }
        .amount-label {
            font-size: 9pt;
            letter-spacing: 1px;
            color: #555;
        }
        .amount-value {
            font-size: 22pt;
            font-weight: bold;
            margin: 5px 0;
        }
        .amount-words {
            font-size: 8pt;
            color: #555;
            border-top: 1px dashed #ccc;
            padding-top: 8px;
            margin-top: 5px;
        }
        /* Payment Table */
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .payment-table th, .payment-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            font-size: 9pt;
        }
        .payment-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }
        .payment-table td:last-child, .payment-table th:last-child {
            text-align: right;
        }
        .total-row {
            background: #fafafa;
            font-weight: bold;
        }
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        /* Declaration */
        .declaration {
            background: #f5f5f5;
            padding: 10px 12px;
            text-align: center;
            font-size: 8pt;
            color: #555;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .signature-box {
            text-align: center;
            width: 45%;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 30px;
            margin-bottom: 5px;
        }
        .signature-label {
            font-size: 8pt;
            color: #666;
        }
        /* Footer */
        .footer {
            background: #f5f5f5;
            padding: 8px 15px;
            text-align: center;
            font-size: 7pt;
            color: #666;
            border-top: 1px solid #ddd;
            margin-top: 15px;
        }
        /* Buttons */
        .action-buttons {
            text-align: center;
            padding: 15px;
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
        @media (max-width: 700px) {
            .two-column { flex-direction: column; gap: 0; }
            .action-buttons { display: flex; flex-direction: column; gap: 10px; }
            .btn { margin: 0; }
            .item-table { font-size: 7pt; }
            .item-table th, .item-table td { padding: 4px; }
        }
    </style>
</head>
<body>
<div class="ack-container">
    <?php if($ack_type == 'full'): ?>
    <div class="watermark">PAID IN FULL</div>
    <?php endif; ?>
    
    <div class="ack-content">
        <!-- Header -->
        <div class="header">
            <div class="company-name">IT INVENTORY MANAGEMENT SYSTEM</div>
            <div class="company-tagline">Department of Information Technology</div>
            <div class="ack-title"><?php echo $ack_title; ?></div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="two-column">
            <!-- Left Column -->
            <div class="col">
                <div class="section">
                    <div class="section-title">ACKNOWLEDGEMENT DETAILS</div>
                    <div class="section-body">
                        <div class="info-row"><div class="label">Acknowledge No:</div><div class="value"><?php echo $ack_no; ?></div></div>
                        <div class="info-row"><div class="label">Date:</div><div class="value"><?php echo $ack_date; ?></div></div>
                        <div class="info-row"><div class="label">Bill No:</div><div class="value"><?php echo htmlspecialchars($bill_no); ?></div></div>
                        <div class="info-row"><div class="label">Bill Date:</div><div class="value"><?php echo $bill_date; ?></div></div>
                        <div class="info-row"><div class="label">Due Date:</div><div class="value"><?php echo $due_date; ?></div></div>
                        <?php if($ack_type == 'payment'): ?>
                        <div class="info-row"><div class="label">Payment Mode:</div><div class="value"><?php echo $mode_text; ?></div></div>
                        <div class="info-row"><div class="label">Reference No:</div><div class="value"><?php echo htmlspecialchars($reference); ?></div></div>
                        <?php endif; ?>
                        <div class="info-row"><div class="label">Received By:</div><div class="value"><?php echo htmlspecialchars($received_by); ?></div></div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">VENDOR INFORMATION</div>
                    <div class="section-body">
                        <div class="info-row"><div class="label">Vendor Name:</div><div class="value"><?php echo htmlspecialchars($data['vendor_name']); ?></div></div>
                        <?php if(!empty($data['gst_no'])): ?>
                        <div class="info-row"><div class="label">GST Number:</div><div class="value"><?php echo htmlspecialchars($data['gst_no']); ?></div></div>
                        <?php endif; ?>
                        <?php if(!empty($data['phone'])): ?>
                        <div class="info-row"><div class="label">Phone:</div><div class="value"><?php echo htmlspecialchars($data['phone']); ?></div></div>
                        <?php endif; ?>
                        <?php if(!empty($data['email'])): ?>
                        <div class="info-row"><div class="label">Email:</div><div class="value"><?php echo htmlspecialchars($data['email']); ?></div></div>
                        <?php endif; ?>
                        <?php if(!empty($data['address'])): ?>
                        <div class="info-row"><div class="label">Address:</div><div class="value"><?php echo nl2br(htmlspecialchars($data['address'])); ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col">
                <div class="amount-box">
                    <div class="amount-label">AMOUNT <?php echo $ack_type == 'payment' ? 'PAID' : 'SETTLED'; ?></div>
                    <div class="amount-value">BDT <?php echo $amount_formatted; ?></div>
                    <div class="amount-words"><?php echo $amount_in_words; ?></div>
                </div>
                
                <table class="payment-table">
                    <thead><tr><th>DESCRIPTION</th><th>AMOUNT (BDT)</th></tr></thead>
                    <tbody>
                        <tr><td>Total Bill Amount</td><td><?php echo $total_formatted; ?></td></tr>
                        <?php if($prev_paid > 0): ?>
                        <tr><td>Previously Paid</td><td><?php echo number_format($prev_paid, 2); ?></td></tr>
                        <?php endif; ?>
                        <tr class="total-row"><td>This <?php echo $ack_type == 'payment' ? 'Payment' : 'Settlement'; ?></td><td><?php echo $amount_formatted; ?></td></tr>
                        <tr>
                            <td>Remaining Balance</td>
                            <td class="<?php echo $balance > 0 ? 'text-danger' : 'text-success'; ?>"><?php echo $balance_formatted; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ITEMS LIST SECTION -->
        <div class="section">
            <div class="section-title">ITEMS PURCHASED</div>
            <div class="section-body p-0">
                <?php if(count($items) > 0): ?>
                <div class="table-responsive">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th width="15%">Item Code</th>
                                <th width="35%">Item Name / Specification</th>
                                <th width="12%">Quantity</th>
                                <th width="18%">Unit Price (BDT)</th>
                                <th width="20%">Total (BDT)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($item['name']); ?>
                                    <?php if(!empty($item['specification'])): ?>
                                    <br><small style="color:#666;"><?php echo htmlspecialchars(substr($item['specification'], 0, 50)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right"><?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background:#f0f0f0; font-weight:bold;">
                                <td colspan="4" style="text-align:right;">GRAND TOTAL:</td>
                                <td class="text-right"><?php echo $total_formatted; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-3 text-muted">
                    <i class="fas fa-info-circle"></i> No items found for this bill.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(!empty($notes)): ?>
        <div class="section">
            <div class="section-title">NOTES</div>
            <div class="section-body">
                <div class="info-row"><div class="value"><?php echo nl2br(htmlspecialchars($notes)); ?></div></div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Declaration -->
        <div class="declaration">
            This acknowledges receipt of the above-mentioned amount from the vendor against the specified bill.
            This is a computer-generated document and requires no physical signature.
        </div>
        
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box"><div class="signature-line"></div><div class="signature-label">For IT Department</div><div class="signature-label">Authorized Signatory</div></div>
            <div class="signature-box"><div class="signature-line"></div><div class="signature-label">Vendor Representative</div><div class="signature-label">(Received by)</div></div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            This is a computer generated acknowledgement. No physical signature required.<br>
            For queries, please contact IT Department.
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn">🖨️ Print</button>
        <a href="list_bills.php" class="btn btn-secondary">← Back to Bills</a>
    </div>
</div>
</body>
</html>