<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$report_type = $_GET['type'] ?? 'monthly';
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$selected_month = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';

// Get all available years for filter
$years = $pdo->query("SELECT DISTINCT YEAR(created_at) as year FROM cash_register_transactions UNION SELECT DISTINCT YEAR(created_at) as year FROM bill_payments ORDER BY year DESC")->fetchAll();

// Get available months for selected year
$months = [];
for($i = 1; $i <= 12; $i++) {
    $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
}

// MONTHLY REPORT DATA
if($report_type == 'monthly') {
    // Get cash register data for selected month
    $stmt = $pdo->prepare("SELECT * FROM cash_register WHERE month_year = ?");
    $stmt->execute([$selected_month]);
    $register = $stmt->fetch();
    
    // Get all cash in transactions for selected month
    $stmt = $pdo->prepare("SELECT * FROM cash_register_transactions 
                          WHERE transaction_type = 'cash_in' 
                          AND MONTH(created_at) = ? AND YEAR(created_at) = ?
                          ORDER BY created_at DESC");
    $stmt->execute([$month, $year]);
    $cashInTransactions = $stmt->fetchAll();
    
    // Get all bill payments (cash out) for selected month
    $stmt = $pdo->prepare("SELECT bp.*, b.bill_no, v.name as vendor_name, u.full_name as created_by_name
                          FROM bill_payments bp
                          JOIN bills b ON bp.bill_id = b.id
                          JOIN vendors v ON b.vendor_id = v.id
                          LEFT JOIN users u ON bp.created_by = u.id
                          WHERE MONTH(bp.payment_date) = ? AND YEAR(bp.payment_date) = ?
                          ORDER BY bp.created_at DESC");
    $stmt->execute([$month, $year]);
    $billPayments = $stmt->fetchAll();
    
    // Get all bills for the month
    $stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name
                          FROM bills b
                          JOIN vendors v ON b.vendor_id = v.id
                          WHERE MONTH(b.bill_date) = ? AND YEAR(b.bill_date) = ?
                          ORDER BY b.bill_date DESC");
    $stmt->execute([$month, $year]);
    $bills = $stmt->fetchAll();
    
    // Calculate totals
    $totalCashIn = array_sum(array_column($cashInTransactions, 'amount'));
    $totalCashOut = array_sum(array_column($billPayments, 'amount'));
    $netCashFlow = $totalCashIn - $totalCashOut;
    $totalBillsAmount = array_sum(array_column($bills, 'total_amount'));
    $totalPaidAmount = array_sum(array_column($bills, 'paid_amount'));
    $totalBalanceDue = array_sum(array_column($bills, 'balance_amount'));
    
    // Get opening balance (from previous month's closing balance)
    $prev_month = date('Y-m-01', strtotime($selected_month . ' -1 month'));
    $stmt = $pdo->prepare("SELECT closing_balance FROM cash_register WHERE month_year = ?");
    $stmt->execute([$prev_month]);
    $prevRegister = $stmt->fetch();
    $openingBalance = $prevRegister['closing_balance'] ?? 0;
    
    // Closing balance calculation
    $closingBalance = $openingBalance + $totalCashIn - $totalCashOut;
}

// YEARLY REPORT DATA
else {
    $stmt = $pdo->prepare("SELECT * FROM cash_register WHERE YEAR(month_year) = ?");
    $stmt->execute([$year]);
    $registers = $stmt->fetchAll();
    
    // Get all cash in transactions for the year
    $stmt = $pdo->prepare("SELECT * FROM cash_register_transactions 
                          WHERE transaction_type = 'cash_in' AND YEAR(created_at) = ?
                          ORDER BY created_at DESC");
    $stmt->execute([$year]);
    $cashInTransactions = $stmt->fetchAll();
    
    // Get all bill payments for the year
    $stmt = $pdo->prepare("SELECT bp.*, b.bill_no, v.name as vendor_name
                          FROM bill_payments bp
                          JOIN bills b ON bp.bill_id = b.id
                          JOIN vendors v ON b.vendor_id = v.id
                          WHERE YEAR(bp.payment_date) = ?
                          ORDER BY bp.created_at DESC");
    $stmt->execute([$year]);
    $billPayments = $stmt->fetchAll();
    
    // Get monthly summary
    $monthlySummary = [];
    $totalCashInYear = 0;
    $totalCashOutYear = 0;
    
    for($m = 1; $m <= 12; $m++) {
        $monthCashIn = array_sum(array_column(array_filter($cashInTransactions, function($t) use ($m) {
            return date('n', strtotime($t['created_at'])) == $m;
        }), 'amount'));
        
        $monthCashOut = array_sum(array_column(array_filter($billPayments, function($t) use ($m) {
            return date('n', strtotime($t['payment_date'])) == $m;
        }), 'amount'));
        
        $monthlySummary[$m] = [
            'month' => $months[$m],
            'cash_in' => $monthCashIn,
            'cash_out' => $monthCashOut,
            'net' => $monthCashIn - $monthCashOut
        ];
        
        $totalCashInYear += $monthCashIn;
        $totalCashOutYear += $monthCashOut;
    }
    
    $netCashFlowYear = $totalCashInYear - $totalCashOutYear;
    
    // Get opening balance (from previous year's closing)
    $prev_year = $year - 1;
    $stmt = $pdo->prepare("SELECT closing_balance FROM cash_register WHERE YEAR(month_year) = ? ORDER BY month_year DESC LIMIT 1");
    $stmt->execute([$prev_year]);
    $prevYearRegister = $stmt->fetch();
    $openingBalanceYear = $prevYearRegister['closing_balance'] ?? 0;
    
    // Get all bills for the year
    $stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name
                          FROM bills b
                          JOIN vendors v ON b.vendor_id = v.id
                          WHERE YEAR(b.bill_date) = ?
                          ORDER BY b.bill_date DESC");
    $stmt->execute([$year]);
    $billsYear = $stmt->fetchAll();
    
    $totalBillsAmountYear = array_sum(array_column($billsYear, 'total_amount'));
    $totalPaidAmountYear = array_sum(array_column($billsYear, 'paid_amount'));
    $totalBalanceDueYear = array_sum(array_column($billsYear, 'balance_amount'));
}
?>

<style>
    .stats-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border-radius: 15px;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .stats-number {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 0;
    }
    .summary-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
    }
    .income-card {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }
    .expense-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .net-card {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 12px;
    }
    .table td {
        font-size: 12px;
        vertical-align: middle;
    }
    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .btn-export {
        background: #28a745;
        color: white;
    }
    .btn-export:hover {
        background: #218838;
        color: white;
    }
    @media (max-width: 768px) {
        .stats-number {
            font-size: 20px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="fas fa-chart-line text-primary"></i> Financial Report</h2>
                    <p class="text-muted">Complete financial overview with cash flow analysis</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <button onclick="window.print()" class="btn btn-secondary me-2">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button onclick="exportToExcel()" class="btn btn-export">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <div class="row">
            <div class="col-md-12">
                <h6 class="mb-3"><i class="fas fa-filter"></i> Filter Reports</h6>
            </div>
        </div>
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="type" class="form-select" id="reportType">
                    <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly Report</option>
                    <option value="yearly" <?php echo $report_type == 'yearly' ? 'selected' : ''; ?>>Yearly Report</option>
                </select>
            </div>
            <div class="col-md-3" id="yearDiv">
                <label class="form-label">Year</label>
                <select name="year" class="form-select">
                    <?php foreach($years as $y): ?>
                    <option value="<?php echo $y['year']; ?>" <?php echo $year == $y['year'] ? 'selected' : ''; ?>>
                        <?php echo $y['year']; ?>
                    </option>
                    <?php endforeach; ?>
                    <?php if(empty($years)): ?>
                    <option value="<?php echo date('Y'); ?>"><?php echo date('Y'); ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3" id="monthDiv">
                <label class="form-label">Month</label>
                <select name="month" class="form-select">
                    <?php foreach($months as $m_num => $m_name): ?>
                    <option value="<?php echo $m_num; ?>" <?php echo $month == $m_num ? 'selected' : ''; ?>>
                        <?php echo $m_name; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Generate Report
                </button>
            </div>
        </form>
    </div>

    <?php if($report_type == 'monthly'): ?>
    <!-- ==================== MONTHLY REPORT ==================== -->
    
    <!-- Month Title -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h3 class="text-center text-primary">
                <i class="fas fa-calendar-alt"></i> Financial Report for <?php echo date('F Y', strtotime($selected_month)); ?>
            </h3>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card income-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Total Cash In</p>
                            <h3 class="stats-number">৳<?php echo number_format($totalCashIn, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-arrow-down fa-2x opacity-50"></i></div>
                    </div>
                    <small>Budget added</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card expense-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Total Cash Out</p>
                            <h3 class="stats-number">৳<?php echo number_format($totalCashOut, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-arrow-up fa-2x opacity-50"></i></div>
                    </div>
                    <small>Bill payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card net-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Net Cash Flow</p>
                            <h3 class="stats-number <?php echo $netCashFlow >= 0 ? 'text-white' : 'text-white'; ?>">
                                ৳<?php echo number_format($netCashFlow, 2); ?>
                            </h3>
                        </div>
                        <div><i class="fas fa-chart-line fa-2x opacity-50"></i></div>
                    </div>
                    <small>Inflow - Outflow</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Balance Due</p>
                            <h3 class="stats-number">৳<?php echo number_format($totalBalanceDue, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-hourglass-half fa-2x opacity-50"></i></div>
                    </div>
                    <small>Pending bills amount</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Balance Summary Card -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">Opening Balance</h6>
                    <h4>৳<?php echo number_format($openingBalance, 2); ?></h4>
                    <small>Start of month</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">Total Income</h6>
                    <h4>৳<?php echo number_format($totalCashIn, 2); ?></h4>
                    <small>Budget added this month</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">Closing Balance</h6>
                    <h4>৳<?php echo number_format($closingBalance, 2); ?></h4>
                    <small>End of month</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Cash In Transactions -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-arrow-down"></i> Cash In Transactions (Budget Added)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($cashInTransactions) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Added By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($cashInTransactions as $trans): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y H:i', strtotime($trans['created_at'])); ?></td>
                                    <td><strong class="text-success">৳<?php echo number_format($trans['amount'], 2); ?></strong></td>
                                    <td><?php echo htmlspecialchars($trans['description'] ?? '-'); ?></td>
                                    <td><?php echo $trans['created_by'] ? 'Admin' : 'System'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr class="fw-bold">
                                    <td class="text-end">Total Cash In:</td>
                                    <td colspan="3">৳<?php echo number_format($totalCashIn, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">No cash in transactions this month.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bill Payments (Cash Out) -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-arrow-up"></i> Bill Payments (Cash Out)</h5>
                </div>
                <div class="card-body">
                    <?php if(count($billPayments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Bill No</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Payment Mode</th>
                                    <th>Processed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($billPayments as $payment): ?>
                                <tr>
                                    <td><?php echo date('d-m-Y', strtotime($payment['payment_date'])); ?></td>
                                    <td><strong><?php echo $payment['bill_no']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($payment['vendor_name']); ?></td>
                                    <td><strong class="text-danger">৳<?php echo number_format($payment['amount'], 2); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst($payment['payment_mode']); ?></span></td>
                                    <td><?php echo $payment['created_by_name'] ?? 'System'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Total Cash Out:</td>
                                    <td colspan="3">৳<?php echo number_format($totalCashOut, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">No bill payments this month.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bills Summary -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Bills Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="alert alert-info text-center">
                                <strong>Total Bills</strong>
                                <h4>৳<?php echo number_format($totalBillsAmount, 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success text-center">
                                <strong>Total Paid</strong>
                                <h4>৳<?php echo number_format($totalPaidAmount, 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning text-center">
                                <strong>Balance Due</strong>
                                <h4>৳<?php echo number_format($totalBalanceDue, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(count($bills) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Bill No</th>
                                    <th>Vendor</th>
                                    <th>Bill Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Balance Due</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($bills as $bill): ?>
                                <tr>
                                    <td><strong><?php echo $bill['bill_no']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($bill['vendor_name']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td>
                                    <td>৳<?php echo number_format($bill['total_amount'], 2); ?></td>
                                    <td>৳<?php echo number_format($bill['paid_amount'], 2); ?></td>
                                    <td><strong class="text-danger">৳<?php echo number_format($bill['balance_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($bill['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Totals:</td>
                                    <td>৳<?php echo number_format($totalBillsAmount, 2); ?></td>
                                    <td>৳<?php echo number_format($totalPaidAmount, 2); ?></td>
                                    <td>৳<?php echo number_format($totalBalanceDue, 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">No bills this month.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- ==================== YEARLY REPORT ==================== -->
    
    <!-- Year Title -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h3 class="text-center text-primary">
                <i class="fas fa-calendar-alt"></i> Financial Report for Year <?php echo $year; ?>
            </h3>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card income-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Total Cash In (Year)</p>
                            <h3 class="stats-number">৳<?php echo number_format($totalCashInYear, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-arrow-down fa-2x opacity-50"></i></div>
                    </div>
                    <small>Total budget added</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card expense-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Total Cash Out (Year)</p>
                            <h3 class="stats-number">৳<?php echo number_format($totalCashOutYear, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-arrow-up fa-2x opacity-50"></i></div>
                    </div>
                    <small>Total bill payments</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card net-card text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Net Cash Flow</p>
                            <h3 class="stats-number">৳<?php echo number_format($netCashFlowYear, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-chart-line fa-2x opacity-50"></i></div>
                    </div>
                    <small>Inflow - Outflow</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card stats-card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="mb-1 small">Balance Due</p>
                            <h3 class="stats-number">৳<?php echo number_format($totalBalanceDueYear, 2); ?></h3>
                        </div>
                        <div><i class="fas fa-hourglass-half fa-2x opacity-50"></i></div>
                    </div>
                    <small>Pending bills amount</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Opening & Closing Balance -->
    <div class="row mb-4">
        <div class="col-md-6 mb-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">Opening Balance (Jan 1)</h6>
                    <h4>৳<?php echo number_format($openingBalanceYear, 2); ?></h4>
                    <small>Start of year</small>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h6 class="mb-1">Yearly Performance</h6>
                    <h4 class="<?php echo $netCashFlowYear >= 0 ? 'text-white' : 'text-white'; ?>">
                        <?php echo $netCashFlowYear >= 0 ? '+' : ''; ?>৳<?php echo number_format($netCashFlowYear, 2); ?>
                    </h4>
                    <small>Net change for the year</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Summary Table -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Monthly Summary for <?php echo $year; ?></h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Cash In (Budget Added)</th>
                                    <th>Cash Out (Bill Payments)</th>
                                    <th>Net Cash Flow</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $runningBalance = $openingBalanceYear;
                                foreach($monthlySummary as $summary): 
                                    $runningBalance += $summary['net'];
                                    $statusClass = $summary['net'] >= 0 ? 'text-success' : 'text-danger';
                                    $statusIcon = $summary['net'] >= 0 ? 'arrow-up' : 'arrow-down';
                                ?>
                                <tr>
                                    <td><strong><?php echo $summary['month']; ?></strong></td>
                                    <td>৳<?php echo number_format($summary['cash_in'], 2); ?></td>
                                    <td>৳<?php echo number_format($summary['cash_out'], 2); ?></td>
                                    <td><strong class="<?php echo $statusClass; ?>"><?php echo $summary['net'] >= 0 ? '+' : ''; ?>৳<?php echo number_format($summary['net'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $summary['net'] >= 0 ? 'success' : 'danger'; ?>">
                                            <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                            <?php echo $summary['net'] >= 0 ? 'Profit' : 'Loss'; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr class="fw-bold">
                                    <td class="text-end">Total:</td>
                                    <td>৳<?php echo number_format($totalCashInYear, 2); ?></td>
                                    <td>৳<?php echo number_format($totalCashOutYear, 2); ?></td>
                                    <td>৳<?php echo number_format($netCashFlowYear, 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Running Balance Chart Style Summary -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Running Balance Trend</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Opening Balance</th>
                                    <th>Cash In</th>
                                    <th>Cash Out</th>
                                    <th>Net Change</th>
                                    <th>Closing Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $balance = $openingBalanceYear;
                                foreach($monthlySummary as $summary): 
                                    $openingBal = $balance;
                                    $balance += $summary['net'];
                                ?>
                                <tr>
                                    <td><?php echo $summary['month']; ?></td>
                                    <td>৳<?php echo number_format($openingBal, 2); ?></td>
                                    <td>৳<?php echo number_format($summary['cash_in'], 2); ?></td>
                                    <td>৳<?php echo number_format($summary['cash_out'], 2); ?></td>
                                    <td><?php echo $summary['net'] >= 0 ? '+' : ''; ?>৳<?php echo number_format($summary['net'], 2); ?></td>
                                    <td><strong>৳<?php echo number_format($balance, 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr class="fw-bold">
                                    <td class="text-end">Year End Balance:</td>
                                    <td colspan="5">৳<?php echo number_format($balance, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Yearly Bills Summary -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-file-invoice"></i> Yearly Bills Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="alert alert-info text-center">
                                <strong>Total Bills</strong>
                                <h4>৳<?php echo number_format($totalBillsAmountYear, 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-success text-center">
                                <strong>Total Paid</strong>
                                <h4>৳<?php echo number_format($totalPaidAmountYear, 2); ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning text-center">
                                <strong>Balance Due</strong>
                                <h4>৳<?php echo number_format($totalBalanceDueYear, 2); ?></h4>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(count($billsYear) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Bill No</th>
                                    <th>Vendor</th>
                                    <th>Bill Date</th>
                                    <th>Total Amount</th>
                                    <th>Paid Amount</th>
                                    <th>Balance Due</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($billsYear as $bill): ?>
                                <tr>
                                    <td><strong><?php echo $bill['bill_no']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($bill['vendor_name']); ?></td>
                                    <td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td>
                                    <td>৳<?php echo number_format($bill['total_amount'], 2); ?></td>
                                    <td>৳<?php echo number_format($bill['paid_amount'], 2); ?></td>
                                    <td><strong class="text-danger">৳<?php echo number_format($bill['balance_amount'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php echo $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($bill['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-secondary">
                                <tr class="fw-bold">
                                    <td colspan="3" class="text-end">Totals:</td>
                                    <td>৳<?php echo number_format($totalBillsAmountYear, 2); ?></td>
                                    <td>৳<?php echo number_format($totalPaidAmountYear, 2); ?></td>
                                    <td>৳<?php echo number_format($totalBalanceDueYear, 2); ?></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">No bills for this year.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Show/hide month dropdown based on report type
$(document).ready(function() {
    function toggleMonthDiv() {
        if($('#reportType').val() == 'monthly') {
            $('#monthDiv').show();
        } else {
            $('#monthDiv').hide();
        }
    }
    
    $('#reportType').change(function() {
        toggleMonthDiv();
        $('#filterForm').submit();
    });
    
    toggleMonthDiv();
});

function exportToExcel() {
    var tables = document.querySelectorAll('.table');
    var html = '<html><head><title>Financial Report</title></head><body>';
    html += '<h1>Financial Report - <?php echo $report_type == 'monthly' ? date('F Y', strtotime($selected_month)) : 'Year ' . $year; ?></h1>';
    tables.forEach(function(table) {
        html += table.outerHTML;
        html += '<br><br>';
    });
    html += '</body></html>';
    
    var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'financial_report_<?php echo $report_type . '_' . ($report_type == 'monthly' ? $year . '_' . $month : $year); ?>.xls';
    link.click();
}
</script>

<?php include '../../includes/footer.php'; ?>