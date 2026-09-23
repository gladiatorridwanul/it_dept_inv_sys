<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Check if payment_status column exists in stock_in table
$stmt = $pdo->prepare("SHOW COLUMNS FROM stock_in");
$stmt->execute();
$stock_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$has_payment_status = in_array('payment_status', $stock_columns);

// Handle payment status update via AJAX or POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_payment_status') {
    $stock_id = intval($_POST['stock_id']);
    $payment_status = $_POST['payment_status'];
    
    if ($has_payment_status) {
        $stmt = $pdo->prepare("UPDATE stock_in SET payment_status = ? WHERE id = ?");
        if ($stmt->execute([$payment_status, $stock_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update']);
        }
    } else {
        // If column doesn't exist, use bill_status instead
        $stmt = $pdo->prepare("UPDATE stock_in SET bill_status = ? WHERE id = ?");
        if ($stmt->execute([$payment_status, $stock_id])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update']);
        }
    }
    exit();
}

// Get filter parameters
$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;
$payment_status = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query with proper column handling
$query = "
    SELECT s.*, v.vendor_name, 
           (SELECT COUNT(*) FROM stock_in_items WHERE stock_in_id = s.id) as item_count,
           (SELECT COALESCE(SUM(quantity), 0) FROM stock_in_items WHERE stock_in_id = s.id) as total_items,
           b.bill_no, b.status as bill_status, b.paid_amount, b.balance_amount
    FROM stock_in s
    LEFT JOIN vendors v ON s.vendor_id = v.id
    LEFT JOIN bills b ON s.bill_id = b.id
    WHERE 1=1
";

$params = [];

if ($vendor_id > 0) {
    $query .= " AND s.vendor_id = ?";
    $params[] = $vendor_id;
}

if (!empty($payment_status)) {
    if ($has_payment_status) {
        $query .= " AND s.payment_status = ?";
    } else {
        $query .= " AND s.bill_status = ?";
    }
    $params[] = $payment_status;
}

if (!empty($search)) {
    $query .= " AND (s.invoice_no LIKE ? OR v.vendor_name LIKE ? OR s.notes LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$stock_entries = $stmt->fetchAll();

// Get vendors for filter dropdown
$vendors = $pdo->query("SELECT id, vendor_name FROM vendors WHERE is_active = 1 ORDER BY vendor_name")->fetchAll();

// ============================================
// FIX: Get summary stats based on bill status (balance_amount)
// ============================================

// Total stock value
$total_stock_value = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM stock_in")->fetch()['total'];
$total_entries = $pdo->query("SELECT COUNT(*) as count FROM stock_in")->fetch()['count'];

// Get stock entries with their bill information
$all_stock = $pdo->query("
    SELECT s.id, s.total_amount, s.payment_status, s.bill_status,
           b.id as bill_id, b.balance_amount, b.status as bill_status_full
    FROM stock_in s
    LEFT JOIN bills b ON s.bill_id = b.id
")->fetchAll();

$pending_count = 0;
$pending_amount = 0;
$partial_count = 0;
$partial_amount = 0;
$paid_count = 0;
$paid_amount = 0;

foreach($all_stock as $stock) {
    // Determine payment status based on bill balance
    if($stock['bill_id'] && $stock['balance_amount'] !== null && $stock['balance_amount'] > 0) {
        // Has bill with balance > 0 = Partial
        $partial_count++;
        $partial_amount += $stock['total_amount'];
    } elseif($stock['bill_id'] && $stock['balance_amount'] !== null && $stock['balance_amount'] == 0) {
        // Has bill with balance = 0 = Paid
        $paid_count++;
        $paid_amount += $stock['total_amount'];
    } elseif($stock['bill_id'] && $stock['balance_amount'] === null) {
        // Has bill but no balance info - check bill_status
        if($stock['bill_status_full'] == 'paid') {
            $paid_count++;
            $paid_amount += $stock['total_amount'];
        } else {
            $pending_count++;
            $pending_amount += $stock['total_amount'];
        }
    } else {
        // No bill = Pending
        $pending_count++;
        $pending_amount += $stock['total_amount'];
    }
}

// Also check payment_status directly if column exists
if ($has_payment_status) {
    $direct_pending = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount FROM stock_in WHERE payment_status = 'pending' OR payment_status IS NULL")->fetch();
    $direct_partial = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount FROM stock_in WHERE payment_status = 'partial'")->fetch();
    $direct_paid = $pdo->query("SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount FROM stock_in WHERE payment_status = 'paid'")->fetch();
    
    // If direct payment_status is set, use those values instead
    if($direct_pending['count'] > 0 || $direct_partial['count'] > 0 || $direct_paid['count'] > 0) {
        $pending_count = $direct_pending['count'];
        $pending_amount = $direct_pending['amount'];
        $partial_count = $direct_partial['count'];
        $partial_amount = $direct_partial['amount'];
        $paid_count = $direct_paid['count'];
        $paid_amount = $direct_paid['amount'];
    }
}

$unbilled_entries = $pdo->query("SELECT COUNT(*) as count FROM stock_in WHERE bill_id IS NULL OR bill_id = 0")->fetch()['count'];
?>

<style>
    .status-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-block;
    }
    .status-paid {
        background: #d1fae5;
        color: #065f46;
    }
    .status-pending {
        background: #fef3c7;
        color: #92400e;
    }
    .status-partial {
        background: #cffafe;
        color: #0891b2;
    }
    .filter-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
        cursor: pointer;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .stat-amount {
        font-size: 0.85rem;
        font-weight: 600;
        color: #475569;
        margin-top: 2px;
    }
    .stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }
    .stat-card .stat-icon {
        font-size: 1.5rem;
        margin-bottom: 5px;
        display: block;
    }
    .stat-card.total .stat-number { color: #0f172a; }
    .stat-card.total .stat-icon { color: #3b82f6; }
    .stat-card.pending .stat-number { color: #d97706; }
    .stat-card.pending .stat-icon { color: #f59e0b; }
    .stat-card.partial .stat-number { color: #0891b2; }
    .stat-card.partial .stat-icon { color: #0ea5e9; }
    .stat-card.paid .stat-number { color: #059669; }
    .stat-card.paid .stat-icon { color: #10b981; }
    
    .table-container {
        overflow-x: auto;
    }
    .payment-status-select {
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        font-size: 0.75rem;
        cursor: pointer;
    }
    .payment-status-select.paid { background: #d1fae5; border-color: #10b981; color: #065f46; }
    .payment-status-select.pending { background: #fef3c7; border-color: #f59e0b; color: #92400e; }
    .payment-status-select.partial { background: #cffafe; border-color: #0ea5e9; color: #0891b2; }
    
    .badge-item {
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 12px;
    }
    
    /* Modal Styles */
    .modal-content {
        border-radius: 16px;
        border: none;
    }
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 16px 16px 0 0;
    }
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    .detail-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .detail-section h6 {
        font-weight: 700;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e2e8f0;
    }
    .detail-row {
        display: flex;
        margin-bottom: 6px;
        font-size: 13px;
    }
    .detail-label {
        width: 140px;
        font-weight: 600;
        color: #475569;
        flex-shrink: 0;
    }
    .detail-value {
        flex: 1;
        color: #1e293b;
    }
    .items-table {
        font-size: 12px;
    }
    .items-table th {
        background: #f1f5f9;
        font-weight: 600;
    }
    .status-text {
        font-weight: 600;
    }
    .status-text.paid { color: #065f46; }
    .status-text.pending { color: #92400e; }
    .status-text.partial { color: #0891b2; }
    
    /* Link styling for stat cards */
    .stat-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .stat-card-link:hover .stat-card {
        transform: translateY(-3px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="fas fa-boxes text-primary"></i> Stock List</h2>
                    <p class="text-muted">Manage and track all stock entries</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="stock_in.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Stock Entry
                    </a>
                    <a href="stock_report.php" class="btn btn-outline-secondary">
                        <i class="fas fa-chart-bar"></i> Stock Report
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Statistics Cards - Shows accurate data from database -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="?" class="stat-card-link">
                <div class="stat-card total">
                    <span class="stat-icon"><i class="fas fa-boxes"></i></span>
                    <div class="stat-number"><?php echo number_format($total_entries); ?></div>
                    <div class="stat-amount">৳ <?php echo number_format($total_stock_value, 2); ?></div>
                    <div class="stat-label">Total Stock Entries</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="?payment_status=pending" class="stat-card-link">
                <div class="stat-card pending">
                    <span class="stat-icon"><i class="fas fa-clock"></i></span>
                    <div class="stat-number"><?php echo number_format($pending_count); ?></div>
                    <div class="stat-amount">৳ <?php echo number_format($pending_amount, 2); ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="?payment_status=partial" class="stat-card-link">
                <div class="stat-card partial">
                    <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                    <div class="stat-number"><?php echo number_format($partial_count); ?></div>
                    <div class="stat-amount">৳ <?php echo number_format($partial_amount, 2); ?></div>
                    <div class="stat-label">Partial Payments</div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="?payment_status=paid" class="stat-card-link">
                <div class="stat-card paid">
                    <span class="stat-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="stat-number"><?php echo number_format($paid_count); ?></div>
                    <div class="stat-amount">৳ <?php echo number_format($paid_amount, 2); ?></div>
                    <div class="stat-label">Completed Payments</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="row align-items-end">
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="form-label small text-muted">Vendor</label>
                <select id="filterVendor" class="form-select form-select-sm">
                    <option value="0">All Vendors</option>
                    <?php foreach($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" <?php echo $vendor_id == $vendor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vendor['vendor_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="form-label small text-muted">Payment Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $payment_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="partial" <?php echo $payment_status == 'partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="paid" <?php echo $payment_status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                </select>
            </div>
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="form-label small text-muted">Search</label>
                <input type="text" id="searchInput" class="form-control form-control-sm" 
                       placeholder="Invoice #, Vendor, Notes..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button id="applyFilter" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-filter"></i> Apply Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list"></i> Stock Entries</h5>
                <span class="badge bg-secondary">Total: <?php echo count($stock_entries); ?> entries</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-container">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Invoice No.</th>
                            <th>Purchase Date</th>
                            <th>Vendor</th>
                            <th>Items</th>
                            <th>Total Qty</th>
                            <th>Total Amount</th>
                            <th>Payment Status</th>
                            <th>Bill Status</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($stock_entries) > 0): ?>
                            <?php $serial = 1; ?>
                            <?php foreach($stock_entries as $entry): 
                                // Get payment status value safely
                                if ($has_payment_status) {
                                    $current_status = $entry['payment_status'] ?? 'pending';
                                } else {
                                    $current_status = $entry['bill_status'] ?? 'pending';
                                }
                                if (empty($current_status)) $current_status = 'pending';
                                
                                $status_class = '';
                                if($current_status == 'paid') $status_class = 'status-paid';
                                elseif($current_status == 'partial') $status_class = 'status-partial';
                                else $status_class = 'status-pending';
                                
                                // Check if bill is fully paid (balance_amount = 0)
                                $is_fully_paid = ($entry['bill_id'] && ($entry['balance_amount'] ?? 0) <= 0);
                            ?>
                            <tr>
                                <td><?php echo $serial++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($entry['invoice_no']); ?></strong>
                                    <?php if($entry['bill_no']): ?>
                                        <br><small class="text-muted">Bill: <?php echo htmlspecialchars($entry['bill_no']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($entry['purchase_date'])); ?></td>
                                <td><?php echo htmlspecialchars($entry['vendor_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-info badge-item"><?php echo $entry['item_count'] ?? 0; ?> Items</span>
                                </td>
                                <td><span class="badge bg-secondary badge-item"><?php echo $entry['total_items'] ?? 0; ?> Units</span></td>
                                <td><strong class="text-primary">৳<?php echo number_format($entry['total_amount'], 2); ?></strong></td>
                                <td>
                                    <?php if($is_fully_paid): ?>
                                        <!-- Show as text when fully paid -->
                                        <span class="status-text paid"><i class="fas fa-check-circle"></i> Paid</span>
                                    <?php else: ?>
                                        <!-- Show dropdown when not fully paid -->
                                        <select class="payment-status-select <?php echo $current_status; ?>" 
                                                data-id="<?php echo $entry['id']; ?>" 
                                                data-has-status="<?php echo $has_payment_status ? '1' : '0'; ?>"
                                                onchange="updatePaymentStatus(this)">
                                            <option value="pending" <?php echo $current_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="partial" <?php echo $current_status == 'partial' ? 'selected' : ''; ?>>Partial</option>
                                            <option value="paid" <?php echo $current_status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                        </select>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($entry['bill_id']): ?>
                                        <span class="badge bg-success">Billed</span>
                                        <?php if(($entry['balance_amount'] ?? 0) > 0): ?>
                                            <br><small class="text-danger">Due: ৳<?php echo number_format($entry['balance_amount'] ?? 0, 2); ?></small>
                                        <?php else: ?>
                                            <br><small class="text-success"><i class="fas fa-check"></i> Fully Paid</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Unbilled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewStock(<?php echo $entry['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if(!$entry['bill_id']): ?>
                                            <a href="../bills/receive_bill.php?stock_id=<?php echo $entry['id']; ?>&vendor_id=<?php echo $entry['vendor_id']; ?>" 
                                               class="btn btn-outline-success" title="Receive Bill">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($entry['bill_id'] && ($entry['balance_amount'] ?? 0) > 0): ?>
                                            <a href="../bills/make_payment.php?id=<?php echo $entry['bill_id']; ?>" 
                                               class="btn btn-outline-warning" title="Make Payment">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No stock entries found</p>
                                    <a href="stock_in.php" class="btn btn-sm btn-primary mt-3">Add Stock Entry</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Stock Modal -->
<div class="modal fade" id="viewStockModal" tabindex="-1" aria-labelledby="viewStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStockModalLabel"><i class="fas fa-box me-2"></i> Stock Entry Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stockDetails">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading stock details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printStockDetails()"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function updatePaymentStatus(selectElement) {
    const stockId = selectElement.dataset.id;
    const newStatus = selectElement.value;
    const originalValue = selectElement.querySelector('option:checked')?.value || 'pending';
    
    // Store original value
    if (!selectElement.hasAttribute('data-original')) {
        selectElement.setAttribute('data-original', originalValue);
    }
    
    // Show loading state
    selectElement.disabled = true;
    selectElement.style.opacity = '0.6';
    
    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: {
            action: 'update_payment_status',
            stock_id: stockId,
            payment_status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update class for styling
                selectElement.className = 'payment-status-select ' + newStatus;
                selectElement.removeAttribute('data-original');
                
                // Show success feedback
                selectElement.style.backgroundColor = '#d1fae5';
                setTimeout(function() {
                    selectElement.style.backgroundColor = '';
                }, 500);
                
                showNotification('Payment status updated successfully', 'success');
                
                // Reload after 1 second to update stats
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                // Revert on error
                selectElement.value = selectElement.getAttribute('data-original') || 'pending';
                showNotification('Failed to update status: ' + (response.error || 'Unknown error'), 'error');
            }
        },
        error: function(xhr, status, error) {
            // Revert on error
            selectElement.value = selectElement.getAttribute('data-original') || 'pending';
            showNotification('An error occurred: ' + error, 'error');
        },
        complete: function() {
            selectElement.disabled = false;
            selectElement.style.opacity = '1';
        }
    });
}

function viewStock(id) {
    $('#stockDetails').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading stock details...</p></div>');
    $('#viewStockModal').modal('show');
    
    $.ajax({
        url: 'get_stock_details.php',
        type: 'POST',
        data: {id: id},
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var data = response.data;
                var items = response.items;
                var html = '';
                
                // Stock Information
                html += '<div class="detail-section">';
                html += '<h6><i class="fas fa-info-circle me-2 text-primary"></i> Stock Information</h6>';
                html += '<div class="detail-row"><span class="detail-label">Invoice No:</span><span class="detail-value"><strong>' + escapeHtml(data.invoice_no) + '</strong></span></div>';
                html += '<div class="detail-row"><span class="detail-label">Purchase Date:</span><span class="detail-value">' + formatDate(data.purchase_date) + '</span></div>';
                html += '<div class="detail-row"><span class="detail-label">Vendor:</span><span class="detail-value">' + escapeHtml(data.vendor_name || 'N/A') + '</span></div>';
                html += '<div class="detail-row"><span class="detail-label">Total Amount:</span><span class="detail-value"><strong>৳' + formatNumber(data.total_amount) + '</strong></span></div>';
                html += '<div class="detail-row"><span class="detail-label">Payment Status:</span><span class="detail-value"><span class="status-badge ' + getStatusClass(data.payment_status || data.bill_status || 'pending') + '">' + (data.payment_status || data.bill_status || 'Pending').toUpperCase() + '</span></span></div>';
                
                // Check if bill exists
                if (data.bill_no) {
                    html += '<div class="detail-row"><span class="detail-label">Bill No:</span><span class="detail-value">' + escapeHtml(data.bill_no) + '</span></div>';
                    html += '<div class="detail-row"><span class="detail-label">Bill Status:</span><span class="detail-value"><span class="status-badge status-' + (data.bill_status || 'pending') + '">' + (data.bill_status || 'Pending').toUpperCase() + '</span></span></div>';
                    html += '<div class="detail-row"><span class="detail-label">Paid Amount:</span><span class="detail-value">৳' + formatNumber(data.paid_amount || 0) + '</span></div>';
                    html += '<div class="detail-row"><span class="detail-label">Balance Due:</span><span class="detail-value"><strong class="text-danger">৳' + formatNumber(data.balance_amount || 0) + '</strong></span></div>';
                } else {
                    html += '<div class="detail-row"><span class="detail-label">Bill Status:</span><span class="detail-value"><span class="badge bg-warning">Unbilled</span></span></div>';
                }
                
                if (data.notes) {
                    html += '<div class="detail-row"><span class="detail-label">Notes:</span><span class="detail-value">' + escapeHtml(data.notes) + '</span></div>';
                }
                html += '</div>';
                
                // Items Section
                html += '<div class="detail-section">';
                html += '<h6><i class="fas fa-boxes me-2 text-primary"></i> Items Purchased</h6>';
                
                if (items && items.length > 0) {
                    html += '<div class="table-responsive">';
                    html += '<table class="table table-bordered items-table mb-0">';
                    html += '<thead><tr><th>Item Code</th><th>Item Name</th><th>Quantity</th><th>Unit Price (৳)</th><th>Total (৳)</th></tr></thead>';
                    html += '<tbody>';
                    
                    var itemTotal = 0;
                    for (var i = 0; i < items.length; i++) {
                        var item = items[i];
                        var totalPrice = parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0);
                        itemTotal += totalPrice;
                        
                        html += '<tr>';
                        html += '<td>' + escapeHtml(item.item_code || 'N/A') + '</td>';
                        html += '<td>' + escapeHtml(item.name || 'Unknown Item') + '</td>';
                        html += '<td class="text-center">' + (item.quantity || 0) + '</td>';
                        html += '<td class="text-end">' + formatNumber(item.unit_price || 0) + '</td>';
                        html += '<td class="text-end">' + formatNumber(totalPrice) + '</td>';
                        html += '</tr>';
                    }
                    
                    html += '<tr class="table-secondary fw-bold">';
                    html += '<td colspan="4" class="text-end">Subtotal:</td>';
                    html += '<td class="text-end">' + formatNumber(itemTotal) + '</td>';
                    html += '</tr>';
                    
                    html += '<tr class="table-secondary fw-bold">';
                    html += '<td colspan="4" class="text-end">Total Amount:</td>';
                    html += '<td class="text-end text-success">' + formatNumber(data.total_amount) + '</td>';
                    html += '</tr>';
                    
                    html += '</tbody></table></div>';
                } else {
                    html += '<div class="text-center py-3 text-muted"><i class="fas fa-info-circle"></i> No items found for this stock entry.</div>';
                }
                html += '</div>';
                
                $('#stockDetails').html(html);
            } else {
                $('#stockDetails').html('<div class="alert alert-danger">Error: ' + escapeHtml(response.message) + '</div>');
            }
        },
        error: function() {
            $('#stockDetails').html('<div class="alert alert-danger">Error loading stock details. Please try again.</div>');
        }
    });
}

function printStockDetails() {
    var printContents = document.getElementById('stockDetails').innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Stock Details</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<style>body { padding: 20px; } .btn, .modal-footer { display: none; } @media print { .btn, .modal-footer { display: none; } } .detail-section { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px; } .detail-row { display: flex; margin-bottom: 6px; font-size: 13px; } .detail-label { width: 140px; font-weight: 600; color: #475569; flex-shrink: 0; } .detail-value { flex: 1; color: #1e293b; }</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="container">');
    printWindow.document.write('<div class="text-center mb-4"><h2>Stock Entry Details</h2><p>Generated on: ' + new Date().toLocaleString() + '</p><hr></div>');
    printWindow.document.write(printContents);
    printWindow.document.write('</div></body></html>');
    printWindow.document.close();
    printWindow.print();
}

function getStatusClass(status) {
    if (status == 'paid') return 'status-paid';
    if (status == 'partial') return 'status-partial';
    return 'status-pending';
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    var date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function showNotification(message, type) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type === 'success' ? 'success' : 'error',
            title: type === 'success' ? 'Success!' : 'Error!',
            text: message,
            timer: 2000,
            showConfirmButton: false
        });
    } else {
        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (type === 'success' ? 'success' : 'danger') + ' alert-dismissible fade show position-fixed';
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        document.body.appendChild(alertDiv);
        setTimeout(function() { if (alertDiv && alertDiv.remove) alertDiv.remove(); }, 3000);
    }
}

// Filter functionality
$(document).ready(function() {
    $('#applyFilter').click(function() {
        var vendor = $('#filterVendor').val();
        var status = $('#filterStatus').val();
        var search = $('#searchInput').val();
        
        var url = window.location.pathname + '?';
        if (vendor && vendor != 0) url += 'vendor_id=' + vendor + '&';
        if (status) url += 'payment_status=' + encodeURIComponent(status) + '&';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        
        window.location.href = url;
    });
    
    $('#searchInput').keypress(function(e) {
        if (e.which == 13) {
            $('#applyFilter').click();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>