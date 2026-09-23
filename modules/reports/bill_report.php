<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$month = $_GET['month'] ?? date('Y-m');
$stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name, c.month_year 
                       FROM bills b 
                       JOIN vendors v ON b.vendor_id=v.id 
                       LEFT JOIN cash_register c ON b.cash_register_id=c.id 
                       WHERE DATE_FORMAT(b.bill_date, '%Y-%m') = ?
                       ORDER BY b.bill_date DESC");
$stmt->execute([$month]);
$bills = $stmt->fetchAll();

// Get cash register data for the month
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE DATE_FORMAT(month_year, '%Y-%m') = ?");
$stmt->execute([$month]);
$register = $stmt->fetch();

$totalBills = array_sum(array_column($bills, 'total_amount'));
$totalPaid = array_sum(array_column($bills, 'paid_amount'));
$totalBalance = array_sum(array_column($bills, 'balance_amount'));
?>

<div class="container-fluid">
    <h2><i class="fas fa-file-invoice"></i> Bill Clearance Report</h2>
    <hr>
    
    <div class="row mb-3">
        <div class="col-md-4">
            <form method="GET" class="d-flex">
                <input type="month" name="month" class="form-control me-2" value="<?php echo $month; ?>">
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
        <div class="col-md-8 text-end">
            <button onclick="window.print()" class="btn btn-info">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5>Total Bills</h5>
                    <h3>₹<?php echo number_format($totalBills, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5>Total Paid</h5>
                    <h3>₹<?php echo number_format($totalPaid, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5>Balance Due</h5>
                    <h3>₹<?php echo number_format($totalBalance, 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <?php if($register): ?>
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5>Cash Register Summary - <?php echo date('F Y', strtotime($register['month_year'])); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Opening Balance:</strong><br>
                    ₹<?php echo number_format($register['opening_balance'], 2); ?>
                </div>
                <div class="col-md-3">
                    <strong>Total Cash In:</strong><br>
                    ₹<?php echo number_format($register['total_cash_in'], 2); ?>
                </div>
                <div class="col-md-3">
                    <strong>Total Cash Out:</strong><br>
                    ₹<?php echo number_format($register['total_cash_out'], 2); ?>
                </div>
                <div class="col-md-3">
                    <strong>Closing Balance:</strong><br>
                    ₹<?php echo number_format($register['closing_balance'], 2); ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5>Bill Details</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Vendor</th>
                            <th>Bill Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bills as $bill): ?>
                        <tr>
                            <td><?php echo $bill['bill_no']; ?></td>
                            <td><?php echo $bill['vendor_name']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td>
                            <td>₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($bill['paid_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($bill['balance_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $bill['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($bill['status']); ?>
                                </span>
                             </td>
                            <td><?php echo $bill['payment_date'] ? date('d-m-Y', strtotime($bill['payment_date'])) : '-'; ?></td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>