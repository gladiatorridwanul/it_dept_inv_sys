<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if($status_filter != 'all') {
    $where_conditions[] = "b.status = ?";
    $params[] = $status_filter;
}

if(!empty($search)) {
    $where_conditions[] = "(b.bill_no LIKE ? OR v.vendor_name LIKE ? OR s.invoice_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get bills with payment info
$stmt = $pdo->prepare("
    SELECT b.*, v.vendor_name, v.phone as vendor_phone, v.office_address, v.office_email, v.tin_no, v.bin_no,
           s.invoice_no as stock_invoice,
           (SELECT COUNT(*) FROM bill_payments WHERE bill_id = b.id) as payment_count,
           (SELECT MAX(created_at) FROM bill_payments WHERE bill_id = b.id) as last_payment_date
    FROM bills b 
    JOIN vendors v ON b.vendor_id = v.id 
    LEFT JOIN stock_in s ON b.stock_in_id = s.id
    $where_sql 
    ORDER BY 
        CASE b.status 
            WHEN 'pending' THEN 1 
            WHEN 'partial' THEN 2 
            WHEN 'paid' THEN 3 
        END, 
        b.bill_date DESC
");
$stmt->execute($params);
$bills = $stmt->fetchAll();

// Get counts for all statuses
$counts = [
    'all' => $pdo->query("SELECT COUNT(*) as count FROM bills")->fetch()['count'],
    'pending' => $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'pending'")->fetch()['count'],
    'partial' => $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'partial'")->fetch()['count'],
    'paid' => $pdo->query("SELECT COUNT(*) as count FROM bills WHERE status = 'paid'")->fetch()['count']
];

// Calculate totals
$totals = [
    'total_amount' => $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills")->fetch()['total'],
    'total_paid' => $pdo->query("SELECT COALESCE(SUM(paid_amount), 0) as total FROM bills")->fetch()['total'],
    'total_balance' => $pdo->query("SELECT COALESCE(SUM(balance_amount), 0) as total FROM bills")->fetch()['total']
];
?>

<style>
    .stats-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .stats-card .card-body {
        padding: 15px 20px;
    }
    .stats-number {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 0;
    }
    .stats-label {
        font-size: 12px;
        opacity: 0.8;
        margin-bottom: 0;
    }
    .bill-row-pending {
        border-left: 4px solid #dc3545;
    }
    .bill-row-partial {
        border-left: 4px solid #ffc107;
    }
    .bill-row-paid {
        border-left: 4px solid #28a745;
        opacity: 0.85;
    }
    .btn-group-sm .btn {
        padding: 4px 10px;
        font-size: 12px;
        margin: 0 2px;
    }
    .payment-progress {
        width: 100px;
        height: 4px;
        background: #e9ecef;
        border-radius: 2px;
        overflow: hidden;
    }
    .payment-progress-bar {
        height: 100%;
        border-radius: 2px;
    }
    .filter-btn {
        border-radius: 30px;
        padding: 6px 18px;
        margin: 0 3px;
        font-size: 13px;
    }
    .filter-btn.active {
        background: #2d6a4f;
        color: white;
        border-color: #2d6a4f;
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
    }
    .table td {
        font-size: 13px;
        vertical-align: middle;
    }
    .bill-details-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .bill-details-header {
        background: #f8f9fa;
        padding: 10px 15px;
        border-bottom: 1px solid #dee2e6;
        font-weight: 600;
    }
    .bill-details-body {
        padding: 15px;
    }
    .items-table {
        font-size: 12px;
    }
    .items-table th {
        background: #e9ecef;
        font-size: 11px;
    }
    .print-only { display: none; }
    @media print {
        .print-only { display: block; }
        .no-print { display: none !important; }
    }
    @media (max-width: 768px) {
        .stats-number {
            font-size: 20px;
        }
        .filter-btn {
            padding: 4px 12px;
            font-size: 11px;
        }
        .btn-group-sm .btn {
            padding: 3px 6px;
            font-size: 10px;
        }
        .mobile-stack {
            flex-direction: column;
            align-items: stretch !important;
        }
        .mobile-stack .col-md-6 {
            width: 100%;
            margin-bottom: 10px;
        }
        .mobile-stack .btn-group {
            flex-wrap: wrap;
            justify-content: center;
        }
        .mobile-stack .btn-group .filter-btn {
            margin: 2px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-file-invoice-dollar text-primary"></i> Bills Management</h2>
            <p class="text-muted">Manage vendor bills, track payments, and generate documents</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="receive_bill.php" class="btn btn-primary">
                <i class="fas fa-download"></i> Receive Bill
            </a>
            <a href="create_bill.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Create Bill
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-2">
            <a href="?status=all" class="text-decoration-none">
                <div class="card stats-card text-white bg-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-label">Total Bills</p>
                                <h2 class="stats-number"><?php echo $counts['all']; ?></h2>
                            </div>
                            <div><i class="fas fa-file-invoice fa-2x opacity-50"></i></div>
                        </div>
                        <small>Value: ৳<?php echo number_format($totals['total_amount'], 0); ?></small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <a href="?status=pending" class="text-decoration-none">
                <div class="card stats-card text-white bg-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-label">Pending</p>
                                <h2 class="stats-number"><?php echo $counts['pending']; ?></h2>
                            </div>
                            <div><i class="fas fa-clock fa-2x opacity-50"></i></div>
                        </div>
                        <small>Due: ৳<?php 
                            $pendingDue = $pdo->query("SELECT COALESCE(SUM(balance_amount), 0) as total FROM bills WHERE status = 'pending'")->fetch()['total'];
                            echo number_format($pendingDue, 0);
                        ?></small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <a href="?status=partial" class="text-decoration-none">
                <div class="card stats-card text-white bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-label">Partial</p>
                                <h2 class="stats-number"><?php echo $counts['partial']; ?></h2>
                            </div>
                            <div><i class="fas fa-chart-line fa-2x opacity-50"></i></div>
                        </div>
                        <small>Due: ৳<?php 
                            $partialDue = $pdo->query("SELECT COALESCE(SUM(balance_amount), 0) as total FROM bills WHERE status = 'partial'")->fetch()['total'];
                            echo number_format($partialDue, 0);
                        ?></small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <a href="?status=paid" class="text-decoration-none">
                <div class="card stats-card text-white bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="stats-label">Paid</p>
                                <h2 class="stats-number"><?php echo $counts['paid']; ?></h2>
                            </div>
                            <div><i class="fas fa-check-circle fa-2x opacity-50"></i></div>
                        </div>
                        <small>Paid: ৳<?php 
                            $paidTotal = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills WHERE status = 'paid'")->fetch()['total'];
                            echo number_format($paidTotal, 0);
                        ?></small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="row mb-3 mobile-stack">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by Bill No, Vendor, or Stock Invoice..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if($search): ?>
                    <a href="?status=<?php echo $status_filter; ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group" role="group">
                <a href="?status=all" class="btn btn-outline-secondary filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                <a href="?status=pending" class="btn btn-outline-danger filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=partial" class="btn btn-outline-warning filter-btn <?php echo $status_filter == 'partial' ? 'active' : ''; ?>">Partial</a>
                <a href="?status=paid" class="btn btn-outline-success filter-btn <?php echo $status_filter == 'paid' ? 'active' : ''; ?>">Paid</a>
            </div>
        </div>
    </div>

    <!-- Bills Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Bill List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Bill No</th>
                            <th>Stock Invoice</th>
                            <th>Vendor</th>
                            <th>Bill Date</th>
                            <th>Total (৳)</th>
                            <th>Paid (৳)</th>
                            <th>Balance (৳)</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th width="280">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($bills) > 0): ?>
                            <?php foreach($bills as $bill): 
                                $paidPercent = ($bill['total_amount'] > 0) ? ($bill['paid_amount'] / $bill['total_amount']) * 100 : 0;
                                $rowClass = $bill['status'] == 'pending' ? 'bill-row-pending' : ($bill['status'] == 'partial' ? 'bill-row-partial' : 'bill-row-paid');
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong>
                                    <?php if($bill['payment_count'] > 0): ?>
                                        <br><small class="text-muted"><?php echo $bill['payment_count']; ?> payment(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($bill['stock_invoice'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($bill['vendor_name']); ?>
                                    <?php if($bill['vendor_phone']): ?>
                                        <br><small class="text-muted"><?php echo $bill['vendor_phone']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($bill['bill_date'])); ?></td>
                                <td class="text-end"><?php echo number_format($bill['total_amount'], 2); ?></td>
                                <td class="text-end text-success"><?php echo number_format($bill['paid_amount'], 2); ?></td>
                                <td class="text-end text-danger fw-bold"><?php echo number_format($bill['balance_amount'], 2); ?></td>
                                <td style="width: 100px;">
                                    <div class="payment-progress">
                                        <div class="payment-progress-bar bg-<?php echo $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'partial' ? 'warning' : 'danger'); ?>" 
                                             style="width: <?php echo $paidPercent; ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo round($paidPercent); ?>% paid</small>
                                </td>
                                <td>
                                    <?php if($bill['status'] == 'paid'): ?>
                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span>
                                    <?php elseif($bill['status'] == 'partial'): ?>
                                        <span class="badge bg-warning"><i class="fas fa-chart-line"></i> Partial</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger"><i class="fas fa-hourglass-half"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-info" onclick="viewBill(<?php echo $bill['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        
                                        <?php if($bill['balance_amount'] > 0): ?>
                                            <a href="make_payment.php?id=<?php echo $bill['id']; ?>" class="btn btn-success" title="Make Payment">
                                                <i class="fas fa-money-bill-wave"></i> Pay
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($bill['paid_amount'] > 0): ?>
                                            <a href="generate_payment_slip.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-secondary" title="Generate Payment Slip" target="_blank">
                                                <i class="fas fa-receipt"></i> Slip
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($bill['status'] == 'paid'): ?>
                                            <a href="generate_acknowledgement.php?bill_id=<?php echo $bill['id']; ?>" class="btn btn-warning" title="Generate Acknowledgement" target="_blank">
                                                <i class="fas fa-file-signature"></i> Ack
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if($bill['bill_attachment']): ?>
                                            <a href="/<?php echo $bill['bill_attachment']; ?>" target="_blank" class="btn btn-dark" title="View Bill Document">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-danger" onclick="deleteBill(<?php echo $bill['id']; ?>)" title="Delete Bill">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="mb-0">No bills found.</p>
                                    <a href="create_bill.php" class="btn btn-sm btn-primary mt-2">Create your first bill</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-secondary">
                            <th colspan="4" class="text-end">Totals:</th>
                            <th class="text-end">৳ <?php echo number_format($totals['total_amount'], 2); ?></th>
                            <th class="text-end">৳ <?php echo number_format($totals['total_paid'], 2); ?></th>
                            <th class="text-end">৳ <?php echo number_format($totals['total_balance'], 2); ?></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Bill Modal -->
<div class="modal fade" id="viewBillModal" tabindex="-1" aria-labelledby="viewBillModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewBillModalLabel"><i class="fas fa-file-invoice"></i> Bill Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="billDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading bill details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printBillBtn" onclick="printBill()"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBillModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash"></i> Delete Bill</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this bill?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. This will also remove all associated stock entries.</p>
                <input type="hidden" id="delete_bill_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Permanently</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
function viewBill(id) {
    $('#billDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading bill details...</p></div>');
    
    $.ajax({
        url: 'get_bill_details.php',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                var bill = response.bill;
                var items = response.items;
                var payments = response.payments;
                
                var html = '';
                
                // Bill Header
                html += '<div class="row mb-4">';
                html += '<div class="col-md-6">';
                html += '<div class="bill-details-card">';
                html += '<div class="bill-details-header"><i class="fas fa-building"></i> Vendor Information</div>';
                html += '<div class="bill-details-body">';
                html += '<p><strong>Vendor Name:</strong> ' + escapeHtml(bill.vendor_name) + '</p>';
                html += '<p><strong>Phone:</strong> ' + (bill.vendor_phone || 'N/A') + '</p>';
                html += '<p><strong>Address:</strong> ' + (bill.office_address || 'N/A') + '</p>';
                html += '<p><strong>TIN/BIN:</strong> ' + (bill.tin_no || 'N/A') + ' / ' + (bill.bin_no || 'N/A') + '</p>';
                html += '</div></div></div>';
                
                html += '<div class="col-md-6">';
                html += '<div class="bill-details-card">';
                html += '<div class="bill-details-header"><i class="fas fa-file-invoice"></i> Bill Information</div>';
                html += '<div class="bill-details-body">';
                html += '<p><strong>Bill No:</strong> ' + escapeHtml(bill.bill_no) + '</p>';
                html += '<p><strong>Bill Date:</strong> ' + formatDate(bill.bill_date) + '</p>';
                html += '<p><strong>Due Date:</strong> ' + (bill.due_date ? formatDate(bill.due_date) : 'N/A') + '</p>';
                html += '<p><strong>Status:</strong> <span class="badge bg-' + (bill.status == 'paid' ? 'success' : (bill.status == 'partial' ? 'warning' : 'danger')) + '">' + bill.status.toUpperCase() + '</span></p>';
                html += '</div></div></div></div>';
                
                // Items Table - FIX: Show items properly
                html += '<div class="bill-details-card">';
                html += '<div class="bill-details-header"><i class="fas fa-boxes"></i> Items Purchased</div>';
                html += '<div class="bill-details-body p-0">';
                
                if(items && items.length > 0) {
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-bordered items-table mb-0">';
                    html += '<thead><tr>';
                    html += '<th>Item Code</th><th>Item Name</th><th>Quantity</th><th>Unit Price (BDT)</th><th>Total (BDT)</th>';
                    html += '</tr></thead><tbody>';
                    
                    var itemTotal = 0;
                    for(var i = 0; i < items.length; i++) {
                        var item = items[i];
                        var itemName = item.name || item.item_name || 'Unknown Item';
                        var itemCode = item.item_code || 'N/A';
                        var qty = item.quantity || 1;
                        var unitPrice = parseFloat(item.unit_price || 0);
                        var totalPrice = parseFloat(item.total_price || (qty * unitPrice));
                        itemTotal += totalPrice;
                        
                        html += '<tr>';
                        html += '<td>' + escapeHtml(itemCode) + '</td>';
                        html += '<td>' + escapeHtml(itemName) + '</td>';
                        html += '<td class="text-center">' + qty + '</td>';
                        html += '<td class="text-end">' + formatNumber(unitPrice) + '</td>';
                        html += '<td class="text-end">' + formatNumber(totalPrice) + '</td>';
                        html += '</tr>';
                    }
                    
                    html += '<tr class="table-secondary fw-bold">';
                    html += '<td colspan="4" class="text-end">Subtotal:</td>';
                    html += '<td class="text-end">' + formatNumber(itemTotal) + '</td>';
                    html += '</tr>';
                    
                    html += '<tr class="table-secondary fw-bold">';
                    html += '<td colspan="4" class="text-end">Total Amount:</td>';
                    html += '<td class="text-end text-success fs-5">' + formatNumber(bill.total_amount) + '</td>';
                    html += '</tr>';
                    
                    html += '</tbody></table></div>';
                } else {
                    html += '<div class="text-center py-3 text-muted">';
                    html += '<i class="fas fa-info-circle"></i> No items found for this bill.';
                    html += '</div>';
                }
                
                html += '</div></div>';
                
                // Payment History
                if(payments && payments.length > 0) {
                    html += '<div class="bill-details-card">';
                    html += '<div class="bill-details-header"><i class="fas fa-credit-card"></i> Payment History</div>';
                    html += '<div class="bill-details-body p-0">';
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-bordered items-table mb-0">';
                    html += '<thead><tr>';
                    html += '<th>Slip No</th><th>Payment Date</th><th>Amount (BDT)</th><th>Mode</th><th>Reference</th>';
                    html += '</tr></thead><tbody>';
                    
                    var paidTotal = 0;
                    for(var i = 0; i < payments.length; i++) {
                        var p = payments[i];
                        html += '<tr>';
                        html += '<td>' + escapeHtml(p.slip_no) + '</td>';
                        html += '<td>' + formatDate(p.payment_date) + '</td>';
                        html += '<td class="text-end text-success">' + formatNumber(p.payment_amount) + '</td>';
                        html += '<td>' + (p.payment_mode ? p.payment_mode.toUpperCase() : 'N/A') + '</td>';
                        html += '<td>' + (p.cheque_no || p.transaction_id || 'N/A') + '</td>';
                        html += '</tr>';
                        paidTotal += parseFloat(p.payment_amount);
                    }
                    
                    html += '<tr class="table-secondary fw-bold">';
                    html += '<td colspan="2" class="text-end">Total Paid:</td>';
                    html += '<td class="text-end text-success">' + formatNumber(paidTotal) + '</td>';
                    html += '<td colspan="2"></td>';
                    html += '</tr>';
                    
                    if(parseFloat(bill.balance_amount) > 0) {
                        html += '<tr class="table-danger fw-bold">';
                        html += '<td colspan="2" class="text-end">Balance Due:</td>';
                        html += '<td class="text-end text-danger">' + formatNumber(bill.balance_amount) + '</td>';
                        html += '<td colspan="2"></td>';
                        html += '</tr>';
                    }
                    
                    html += '</tbody></table></div></div></div>';
                }
                
                // Notes/Remarks
                if(bill.remarks) {
                    html += '<div class="bill-details-card">';
                    html += '<div class="bill-details-header"><i class="fas fa-sticky-note"></i> Remarks</div>';
                    html += '<div class="bill-details-body">';
                    html += '<p class="mb-0">' + escapeHtml(bill.remarks) + '</p>';
                    html += '</div></div>';
                }
                
                $('#billDetails').html(html);
            } else {
                $('#billDetails').html('<div class="alert alert-danger">Error: ' + escapeHtml(response.message) + '</div>');
            }
        },
        error: function() {
            $('#billDetails').html('<div class="alert alert-danger">Error loading bill details. Please try again.</div>');
        }
    });
    
    $('#viewBillModal').modal('show');
}

function deleteBill(id) {
    $('#delete_bill_id').val(id);
    $('#deleteBillModal').modal('show');
}

function printBill() {
    var printContents = document.getElementById('billDetails').innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Bill Details</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">');
    printWindow.document.write('<style>body { padding: 20px; } .btn-close, .modal-footer { display: none; } @media print { .btn, .modal-footer { display: none; } }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="container">');
    printWindow.document.write('<div class="text-center mb-4">');
    printWindow.document.write('<h2>Bill Details</h2>');
    printWindow.document.write('<p>Generated on: ' + new Date().toLocaleString() + '</p>');
    printWindow.document.write('<hr>');
    printWindow.document.write('</div>');
    printWindow.document.write(printContents);
    printWindow.document.write('</div></body></html>');
    printWindow.document.close();
    printWindow.print();
}

function formatDate(dateString) {
    if(!dateString) return 'N/A';
    var date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function escapeHtml(text) {
    if(!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

// Confirm Delete
$('#confirmDeleteBtn').on('click', function() {
    var billId = $('#delete_bill_id').val();
    
    $.ajax({
        url: 'delete_bill.php',
        type: 'POST',
        data: {id: billId},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#deleteBillModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error deleting bill. Please try again.');
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>