<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$month = $_GET['month'] ?? date('Y-m');
$stmt = $pdo->prepare("SELECT * FROM cash_register WHERE DATE_FORMAT(month_year, '%Y-%m') = ?");
$stmt->execute([$month]);
$register = $stmt->fetch();

if(!$register) {
    echo '<div class="alert alert-warning">No data found for selected month!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get purchase details
$stmt = $pdo->prepare("SELECT mpd.*, v.name as vendor_name, b.bill_no 
                      FROM monthly_purchase_details mpd
                      LEFT JOIN vendors v ON mpd.vendor_id = v.id
                      LEFT JOIN bills b ON mpd.bill_id = b.id
                      WHERE mpd.cash_register_id = ?
                      ORDER BY mpd.created_at DESC");
$stmt->execute([$register['id']]);
$purchases = $stmt->fetchAll();

// Get all bills for the month
$stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name 
                      FROM bills b 
                      JOIN vendors v ON b.vendor_id=v.id 
                      WHERE MONTH(b.bill_date) = MONTH(?) AND YEAR(b.bill_date) = YEAR(?)
                      ORDER BY b.bill_date");
$stmt->execute([$month . '-01', $month . '-01']);
$allBills = $stmt->fetchAll();

$totalPaid = array_sum(array_column($purchases, 'paid_amount'));
$totalPending = array_sum(array_column(array_filter($allBills, function($b) { return $b['status'] != 'paid'; }), 'balance_amount'));
$totalBills = array_sum(array_column($allBills, 'total_amount'));
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-chart-line"></i> Monthly Purchase Report - <?php echo date('F Y', strtotime($month . '-01')); ?></h2>
        </div>
        <div class="col-md-4 text-end">
            <button onclick="window.print()" class="btn btn-info">
                <i class="fas fa-print"></i> Print Report
            </button>
            <a href="?month=<?php echo date('Y-m', strtotime($month . '-01 last month')); ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <a href="?month=<?php echo date('Y-m', strtotime($month . '-01 +1 month')); ?>" class="btn btn-secondary">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h6>Monthly Budget</h6>
                    <h3>₹<?php echo number_format($register['monthly_budget'], 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h6>Total Paid</h6>
                    <h3>₹<?php echo number_format($totalPaid, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h6>Pending Payment</h6>
                    <h3>₹<?php echo number_format($totalPending, 2); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h6>Closing Balance</h6>
                    <h3>₹<?php echo number_format($register['closing_balance'], 2); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Paid Purchases List -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Paid Purchases (This Month)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vendor</th>
                            <th>Bill No</th>
                            <th>Amount Paid</th>
                            <th>Payment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $paidPurchases = array_filter($purchases, function($p) { return $p['payment_status'] == 'paid'; });
                        foreach($paidPurchases as $purchase): 
                        ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($purchase['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($purchase['vendor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $purchase['bill_no'] ?? 'N/A'; ?></td>
                            <td>₹<?php echo number_format($purchase['paid_amount'], 2); ?></td>
                            <td><?php echo $purchase['payment_date'] ? date('d-m-Y', strtotime($purchase['payment_date'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($paidPurchases)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No paid purchases this month</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total:</th>
                            <th>₹<?php echo number_format($totalPaid, 2); ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pending Payments List -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-clock"></i> Pending Payments</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Vendor</th>
                            <th>Bill Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Pending Amount</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $pendingBills = array_filter($allBills, function($b) { return $b['status'] != 'paid'; });
                        foreach($pendingBills as $bill): 
                        ?>
                        <tr>
                            <td><?php echo $bill['bill_no']; ?></td>
                            <td><?php echo htmlspecialchars($bill['vendor_name']); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td>
                            <td>₹<?php echo number_format($bill['total_amount'], 2); ?></td>
                            <td>₹<?php echo number_format($bill['paid_amount'], 2); ?></td>
                            <td><strong class="text-danger">₹<?php echo number_format($bill['balance_amount'], 2); ?></strong></td>
                            <td><?php echo $bill['due_date'] ? date('d-m-Y', strtotime($bill['due_date'])) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($pendingBills)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No pending payments</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Total Pending:</th>
                            <th colspan="2">₹<?php echo number_format($totalPending, 2); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Budget Utilization -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Budget Utilization</h5>
        </div>
        <div class="card-body">
            <?php 
            $utilization = $register['monthly_budget'] > 0 ? ($totalPaid / $register['monthly_budget']) * 100 : 0;
            $utilizationColor = $utilization > 100 ? 'danger' : ($utilization > 80 ? 'warning' : 'success');
            ?>
            <div class="text-center mb-3">
                <h4>Budget Usage: <?php echo round($utilization, 1); ?>%</h4>
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-<?php echo $utilizationColor; ?>" style="width: <?php echo min($utilization, 100); ?>%">
                        ₹<?php echo number_format($totalPaid, 2); ?> / ₹<?php echo number_format($register['monthly_budget'], 2); ?>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="alert alert-success">
                        <strong>Cash Added:</strong> ₹<?php echo number_format($register['total_cash_in'], 2); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-warning">
                        <strong>Total Spent:</strong> ₹<?php echo number_format($register['total_cash_out'], 2); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>