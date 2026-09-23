<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle status update
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    $new_status = '';
    
    switch($action) {
        case 'approve': $new_status = 'approved'; break;
        case 'dispatch': $new_status = 'dispatched'; break;
        case 'deliver': $new_status = 'delivered'; break;
        case 'cancel': $new_status = 'cancelled'; break;
    }
    
    if($new_status) {
        try {
            // Get current status before update
            $check_stmt = $pdo->prepare("SELECT status FROM device_transfers WHERE id = ?");
            $check_stmt->execute([$id]);
            $current_status = $check_stmt->fetchColumn();
            
            // Update status
            $stmt = $pdo->prepare("UPDATE device_transfers SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            // Add to delivery log
            $log_stmt = $pdo->prepare("INSERT INTO delivery_logs (transfer_id, action, status_from, status_to, performed_by) VALUES (?, ?, ?, ?, ?)");
            $log_stmt->execute([$id, $action, $current_status, $new_status, $_SESSION['user_id']]);
            
            $_SESSION['success'] = "Transfer status updated to " . ucfirst($new_status);
            header("Location: list.php");
            exit();
        } catch(PDOException $e) {
            echo '<div class="alert alert-danger m-4">Error updating status: ' . $e->getMessage() . '</div>';
        }
    }
}

// Handle delete
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM device_transfers WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Transfer deleted successfully";
        header("Location: list.php");
        exit();
    } catch(PDOException $e) {
        echo '<div class="alert alert-danger m-4">Error deleting: ' . $e->getMessage() . '</div>';
    }
}

// ============================================
// SEARCH & FILTER PARAMETERS
// ============================================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Pagination variables
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// ============================================
// BUILD QUERY WITH FILTERS
// ============================================
$where_conditions = [];
$params = [];

if(!empty($search)) {
    $where_conditions[] = "(transfer_no LIKE ? OR product_name LIKE ? OR from_location LIKE ? OR to_location LIKE ? OR tracking_no LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if(!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if(!empty($date_from)) {
    $where_conditions[] = "DATE(transfer_date) >= ?";
    $params[] = $date_from;
}

if(!empty($date_to)) {
    $where_conditions[] = "DATE(transfer_date) <= ?";
    $params[] = $date_to;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

try {
    // ============================================
    // GET TOTAL COUNT FOR PAGINATION
    // ============================================
    $count_sql = "SELECT COUNT(*) as total FROM device_transfers $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_records / $limit);

    // ============================================
    // GET TRANSFERS WITH PAGINATION - FIXED SQL
    // ============================================
    // Use intval to ensure numeric values for LIMIT and OFFSET
    $limit_val = intval($limit);
    $offset_val = intval($offset);
    
    $sql = "SELECT * FROM device_transfers $where_sql ORDER BY id DESC LIMIT $limit_val OFFSET $offset_val";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transfers = $stmt->fetchAll();
    
} catch(PDOException $e) {
    echo '<div class="alert alert-danger m-4">Error loading transfers: ' . $e->getMessage() . '</div>';
    $transfers = [];
    $total_records = 0;
    $total_pages = 1;
}

// Build URL parameters for pagination links
$url_params = [];
if($search) $url_params['search'] = $search;
if($status_filter) $url_params['status_filter'] = $status_filter;
if($date_from) $url_params['date_from'] = $date_from;
if($date_to) $url_params['date_to'] = $date_to;
$query_string = http_build_query($url_params);
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 30px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-draft { background: #f1f5f9; color: #475569; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-approved { background: #dbeafe; color: #1e40af; }
    .status-dispatched { background: #cffafe; color: #0891b2; }
    .status-delivered { background: #d1fae5; color: #065f46; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
    .btn-sm { padding: 4px 8px; font-size: 11px; }
    .btn-group-print { display: inline-flex; gap: 2px; }
    .btn-group-print .btn { border-radius: 4px; }
    
    .filter-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }
    .filter-card .form-label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
    }
    .filter-card .form-control, .filter-card .form-select {
        font-size: 13px;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 6px 12px;
    }
    .filter-card .form-control:focus, .filter-card .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .filter-card .btn-filter {
        padding: 6px 20px;
        font-size: 13px;
        border-radius: 8px;
    }
    
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 20px;
        background: #f8f9fa;
        border-top: 1px solid #e2e8f0;
        flex-wrap: wrap;
        gap: 10px;
    }
    .pagination-info {
        font-size: 13px;
        color: #475569;
    }
    .pagination {
        display: inline-flex;
        gap: 4px;
        margin: 0;
        padding: 0;
    }
    .pagination .page-item {
        list-style: none;
        display: inline-block;
    }
    .pagination .page-link {
        display: block;
        padding: 6px 14px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        color: #1e293b;
        text-decoration: none;
        font-size: 13px;
        transition: all 0.2s;
        background: white;
    }
    .pagination .page-link:hover {
        background-color: #f1f5f9;
        border-color: #94a3b8;
    }
    .pagination .active .page-link {
        background-color: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    .pagination .disabled .page-link {
        color: #94a3b8;
        pointer-events: none;
        background-color: #f1f5f9;
    }
    
    @media (max-width: 768px) {
        .filter-card .row > div {
            margin-bottom: 10px;
        }
        .pagination-container {
            flex-direction: column;
            text-align: center;
        }
        .pagination {
            justify-content: center;
        }
    }
</style>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fas fa-exchange-alt text-primary me-2"></i>Device Transfers</h4>
            <p class="text-muted small mb-0">Manage device transfers between departments and locations</p>
        </div>
        <a href="create.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> New Transfer
        </a>
    </div>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close p-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- ============================================ -->
    <!-- SEARCH & FILTER SECTION -->
    <!-- ============================================ -->
    <div class="filter-card">
        <form method="GET" action="list.php" id="filterForm">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label"><i class="fas fa-search me-1"></i> Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Transfer No, Product, Location..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="fas fa-tag me-1"></i> Status</label>
                    <select name="status_filter" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="dispatched" <?php echo $status_filter == 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                        <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="fas fa-calendar me-1"></i> Date From</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label"><i class="fas fa-calendar me-1"></i> Date To</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary btn-filter">
                            <i class="fas fa-filter me-1"></i> Apply Filter
                        </button>
                        <a href="list.php" class="btn btn-secondary btn-filter">
                            <i class="fas fa-undo me-1"></i> Reset
                        </a>
                        <?php if($search || $status_filter || $date_from || $date_to): ?>
                            <span class="badge bg-info d-flex align-items-center" style="font-size:13px; padding:6px 14px;">
                                Filtered: <?php echo $total_records; ?> results
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- ============================================ -->
    <!-- TRANSFERS TABLE -->
    <!-- ============================================ -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="fas fa-list me-2"></i>Transfer List</h6>
            <span class="badge bg-secondary">Total: <?php echo $total_records; ?> entries</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="50">ID</th>
                            <th>Transfer No</th>
                            <th>Date</th>
                            <th>To</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th width="240">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($transfers) > 0): ?>
                            <?php foreach($transfers as $transfer): ?>
                            <tr>
                                <td><?php echo $transfer['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($transfer['transfer_no']); ?></strong></td>
                                <td><?php echo date('d-m-Y', strtotime($transfer['transfer_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transfer['to_location']); ?></td>
                                <td><?php echo htmlspecialchars($transfer['product_name']); ?></td>
                                <td><?php echo $transfer['quantity']; ?></td>
                                <td><span class="status-badge status-<?php echo $transfer['status']; ?>"><?php echo ucfirst($transfer['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <!-- Two Print Buttons -->
                                        <div class="btn-group-print">
                                            <a href="print_transfer.php?id=<?php echo $transfer['id']; ?>" target="_blank" class="btn btn-sm btn-primary" title="Print Device Transfer">
                                                <i class="fas fa-print"></i> Transfer
                                            </a>
                                            <a href="print_acknowledgment.php?id=<?php echo $transfer['id']; ?>" target="_blank" class="btn btn-sm btn-success" title="Print Delivery Acknowledgment">
                                                <i class="fas fa-file-signature"></i> Acknowledge
                                            </a>
                                        </div>
                                        <?php if($transfer['status'] == 'draft'): ?>
                                        <a href="edit.php?id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this transfer?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if($transfer['status'] == 'pending'): ?>
                                        <a href="?action=approve&id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this transfer?')">
                                            <i class="fas fa-check"></i> Approve
                                        </a>
                                        <?php endif; ?>
                                        <?php if($transfer['status'] == 'approved'): ?>
                                        <a href="?action=dispatch&id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-info" onclick="return confirm('Mark as dispatched?')">
                                            <i class="fas fa-truck"></i> Dispatch
                                        </a>
                                        <?php endif; ?>
                                        <?php if($transfer['status'] == 'dispatched'): ?>
                                        <a href="?action=deliver&id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as delivered?')">
                                            <i class="fas fa-check-circle"></i> Deliver
                                        </a>
                                        <?php endif; ?>
                                        <?php if(!in_array($transfer['status'], ['delivered', 'cancelled'])): ?>
                                        <a href="?action=cancel&id=<?php echo $transfer['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this transfer?')">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2 d-block"></i>
                                    <?php if($search || $status_filter || $date_from || $date_to): ?>
                                        No transfers found matching your filters.
                                        <br><a href="list.php" class="btn btn-sm btn-outline-primary mt-2">Clear Filters</a>
                                    <?php else: ?>
                                        No transfers found. Click "New Transfer" to create one.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- ============================================ -->
        <!-- PAGINATION SECTION -->
        <!-- ============================================ -->
        <?php if($total_pages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
            </div>
            <ul class="pagination">
                <!-- Previous Page -->
                <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link"><i class="fas fa-chevron-left"></i> Previous</span>
                    </li>
                <?php endif; ?>
                
                <!-- Page Numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=1">1</a>
                    </li>
                    <?php if($start_page > 2): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if($i == $page): ?>
                        <li class="page-item active">
                            <span class="page-link"><?php echo $i; ?></span>
                        </li>
                    <?php else: ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($end_page < $total_pages): ?>
                    <?php if($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                    </li>
                <?php endif; ?>
                
                <!-- Next Page -->
                <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo $query_string; ?><?php echo $query_string ? '&' : ''; ?>page=<?php echo $page + 1; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Next <i class="fas fa-chevron-right"></i></span>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>