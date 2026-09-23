<?php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT pa.*, v.name as vendor_name, v.gst_no, v.email, v.phone, v.address,
                              u.full_name as generated_by_name, s.invoice_no, s.invoice_date, s.purchase_date
                       FROM payment_acknowledgements pa
                       JOIN vendors v ON pa.vendor_id = v.id
                       JOIN stock_in s ON pa.stock_in_id = s.id
                       JOIN users u ON pa.generated_by = u.id
                       WHERE pa.id = ?");
$stmt->execute([$id]);
$ack = $stmt->fetch();

if(!$ack) {
    die("Acknowledgement not found!");
}

// Get items for this invoice
$stmt = $pdo->prepare("SELECT si.*, i.name, i.item_code 
                       FROM stock_in_items si
                       JOIN items i ON si.item_id = i.id
                       WHERE si.stock_in_id = ?");
$stmt->execute([$ack['stock_in_id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Acknowledgement - <?php echo $ack['acknowledgement_no']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; padding: 40px; }
        .ack-container { max-width: 900px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); overflow: hidden; }
        .ack-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 30px; text-align: center; }
        .company-name { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
        .ack-title { font-size: 24px; margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.1); display: inline-block; border-radius: 10px; }
        .ack-number { font-size: 18px; margin-top: 10px; color: #ffd700; }
        .ack-body { padding: 30px; }
        .section-title { font-size: 18px; font-weight: bold; color: #1e3c72; border-left: 4px solid #2a5298; padding-left: 15px; margin-bottom: 15px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 10px; border-bottom: 1px solid #e0e0e0; }
        .info-table td:first-child { width: 35%; font-weight: bold; color: #555; }
        .amount-box { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
        .amount-box .amount { font-size: 36px; font-weight: bold; }
        .items-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .items-table th, .items-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .items-table th { background: #f8f9fa; }
        .signature-section { margin-top: 40px; display: flex; justify-content: space-between; border-top: 1px dashed #ccc; padding-top: 30px; }
        .print-btn { position: fixed; top: 20px; right: 20px; background: #2a5298; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-size: 16px; }
        .print-btn:hover { background: #1e3c72; }
        @media print { body { background: white; padding: 0; } .print-btn { display: none; } }
        .status-paid { background: #10b981; color: white; padding: 5px 15px; border-radius: 20px; display: inline-block; }
    </style>
</head>
<body>
    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print / Save PDF</button>
    
    <div class="ack-container">
        <div class="ack-header">
            <div class="company-name">IT Department Inventory System</div>
            <div class="ack-title">PAYMENT ACKNOWLEDGEMENT</div>
            <div class="ack-number">Acknowledgement No: <?php echo $ack['acknowledgement_no']; ?></div>
        </div>
        
        <div class="ack-body">
            <div class="section-title">Vendor Information</div>
            <table class="info-table">
                <tr><td>Vendor Name:</td><td><strong><?php echo htmlspecialchars($ack['vendor_name']); ?></strong></td></tr>
                <?php if($ack['gst_no']): ?><tr><td>GST Number:</td><td><?php echo $ack['gst_no']; ?></td></tr><?php endif; ?>
                <tr><td>Email:</td><td><?php echo $ack['email'] ?? 'N/A'; ?></td></tr>
                <tr><td>Phone:</td><td><?php echo $ack['phone'] ?? 'N/A'; ?></td></tr>
            </table>
            
            <div class="section-title">Invoice Information</div>
            <table class="info-table">
                <tr><td>Invoice/Bill No:</td><td><strong><?php echo $ack['invoice_no']; ?></strong></td></tr>
                <tr><td>Invoice Date:</td><td><?php echo date('d-m-Y', strtotime($ack['invoice_date'])); ?></td></tr>
                <tr><td>Purchase Date:</td><td><?php echo date('d-m-Y', strtotime($ack['purchase_date'])); ?></td></tr>
            </tr>
            
            <div class="section-title">Items Summary</div>
            <table class="items-table">
                <thead>
                    <tr><th>Item Code</th><th>Item Name</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>
                </thead>
                <tbody>
                    <?php foreach($items as $item): ?>
                    <tr>
                        <td><?php echo $item['item_code']; ?></td>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>৳<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td>৳<?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary"><th colspan="4" class="text-end">Total Amount:</th><th>৳<?php echo number_format($ack['total_amount'], 2); ?></th></tr>
                </tfoot>
            </table>
            
            <div class="section-title">Payment Details</div>
            <table class="info-table">
                <tr><td>Payment Amount:</td><td><strong>৳<?php echo number_format($ack['total_amount'], 2); ?></strong></td></tr>
                <tr><td>Payment Date:</td><td><?php echo date('d-m-Y', strtotime($ack['payment_date'])); ?></td></tr>
                <tr><td>Payment Mode:</td><td><?php echo ucfirst($ack['payment_mode']); ?></td></tr>
                <?php if($ack['cheque_no']): ?><tr><td>Cheque Number:</td><td><?php echo $ack['cheque_no']; ?></td></tr><?php endif; ?>
            </table>
            
            <div class="amount-box">
                <div class="amount">৳<?php echo number_format($ack['total_amount'], 2); ?></div>
                <div class="status-paid">PAID</div>
            </div>
            
            <div class="signature-section">
                <div><div>For IT Department</div><div style="margin-top: 40px;">Authorized Signature</div><div><?php echo $ack['generated_by_name']; ?></div></div>
                <div><div>For Vendor</div><div style="margin-top: 40px;">Receiver's Signature</div><div>Date: ___________</div></div>
            </div>
        </div>
        
        <div class="footer" style="background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px;">
            <p>This is a computer generated payment acknowledgement. No signature required.</p>
        </div>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</body>
</html>