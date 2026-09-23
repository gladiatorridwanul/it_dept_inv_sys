<?php
require_once '../../config/database.php';

$request_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, i.name as item_name, i.item_code 
                       FROM requests r 
                       JOIN employees e ON r.employee_id=e.id 
                       LEFT JOIN items i ON r.item_id=i.id 
                       WHERE r.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if(!$request) {
    die("Request not found!");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo ucfirst($request['request_type']); ?> Document</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; }
        .title { font-size: 20px; margin-top: 10px; font-weight: bold; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td, .info-table th { border: 1px solid #ddd; padding: 10px; vertical-align: top; }
        .info-table th { background-color: #f2f2f2; text-align: left; width: 30%; }
        .signature { margin-top: 50px; }
        .sign-line { margin-top: 50px; display: flex; justify-content: space-between; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #666; }
        button { padding: 10px 20px; margin: 20px; cursor: pointer; background: #2563eb; color: white; border: none; border-radius: 5px; }
        @media print { button { display: none; } }
    </style>
</head>
<body>
    <button onclick="window.print()">Print Document</button>
    <button onclick="window.close()">Close</button>
    
    <div class="header">
        <div class="company-name">IT Department Inventory System</div>
        <div class="title"><?php echo strtoupper($request['request_type']); ?> REQUEST ACKNOWLEDGEMENT</div>
        <div>Request No: <?php echo $request['request_no']; ?></div>
        <div>Date: <?php echo date('d-m-Y', strtotime($request['requested_date'])); ?></div>
    </div>
    
    <table class="info-table">
        <tr><th colspan="2">Request Information</th></tr>
        <tr><td>Request Type: </td><td><strong><?php echo ucfirst($request['request_type']); ?></strong></td></tr>
        <tr><td>Status: </td><td><span style="color: <?php echo $request['status'] == 'approved' ? 'green' : 'orange'; ?>;"><?php echo ucfirst($request['status']); ?></span></td></tr>
        
        <tr><th colspan="2">Employee Details</th></tr>
        <tr><td>Employee Name: </td><td><?php echo htmlspecialchars($request['full_name']); ?></td></tr>
        <tr><td>PF Number: </td><td><?php echo $request['pf_no']; ?></td></tr>
        <tr><td>Designation: </td><td><?php echo $request['designation']; ?></td></tr>
        <tr><td>Department: </td><td><?php echo $request['department']; ?></td></tr>
        
        <tr><th colspan="2">Device Details</th></tr>
        <tr><td>Device Name: </td><td><?php echo htmlspecialchars($request['item_name'] ?? 'N/A'); ?></td></tr>
        <tr><td>Item Code: </td><td><?php echo $request['item_code'] ?? 'N/A'; ?></td></tr>
        
        <tr><th colspan="2">Request Details</th></tr>
        <tr><td>Description: </td><td><?php echo nl2br(htmlspecialchars($request['description'])); ?></td></tr>
        <?php if($request['accepted_date']): ?>
        <tr><td>Accepted Date: </td><td><?php echo date('d-m-Y H:i', strtotime($request['accepted_date'])); ?></td></tr>
        <?php endif; ?>
    </table>
    
    <div class="signature">
        <div class="sign-line">
            <div>Employee Signature: ____________________</div>
            <div>IT Department Signature: ____________________</div>
        </div>
        <div style="margin-top: 20px;">
            <div>Date: ______________</div>
        </div>
    </div>
    
    <div class="footer">
        <p>This is a system generated document for <?php echo $request['request_type']; ?> request.</p>
        <p>Please keep for future reference.</p>
    </div>
</body>
</html>