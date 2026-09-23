<?php
// Force remove restrictive CSP and set permissive one - MUST BE FIRST
if(headers_sent() === false) {
    header_remove('Content-Security-Policy');
    header_remove('X-Content-Security-Policy');
    header_remove('X-WebKit-CSP');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; img-src 'self' data: https:; media-src 'self' data: blob:;");
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/auth.php';
include '../../includes/header.php';

try {
    // Pagination variables - Cast to integers
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
    $offset = ($page - 1) * $limit;
    
    // Get filter parameters
    $search = trim($_GET['search'] ?? '');
    $category_filter = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : '';
    $brand_filter = isset($_GET['brand']) && $_GET['brand'] !== '' ? (int)$_GET['brand'] : '';
    $type_filter = isset($_GET['type']) && $_GET['type'] !== '' ? (int)$_GET['type'] : '';
    
    // Build WHERE clause
    $where_conditions = ["i.is_active = 1"];
    $params = [];
    
    if(!empty($search)) {
        $where_conditions[] = "(i.item_code LIKE ? OR i.name LIKE ? OR i.serial_number LIKE ? OR i.model_number LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if(!empty($category_filter)) {
        $where_conditions[] = "i.category_id = ?";
        $params[] = $category_filter;
    }
    
    if(!empty($brand_filter)) {
        $where_conditions[] = "i.brand_id = ?";
        $params[] = $brand_filter;
    }
    
    if(!empty($type_filter)) {
        $where_conditions[] = "i.type_id = ?";
        $params[] = $type_filter;
    }
    
    $where_sql = implode(" AND ", $where_conditions);
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) as total FROM items i WHERE $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_items = (int)$count_stmt->fetch()['total'];
    $total_pages = ($total_items > 0) ? ceil($total_items / $limit) : 1;
    
    // Ensure page is within bounds
    if($page > $total_pages && $total_pages > 0) {
        $page = $total_pages;
        $offset = ($page - 1) * $limit;
    }
    
    // Get items with pagination - Calculate stock from serials directly
    $sql = "
        SELECT 
            i.*, 
            b.name as brand_name,
            c.name as category_name,
            it.name as type_name,
            (SELECT COUNT(*) FROM item_serial_numbers WHERE item_id = i.id) as total_serials,
            (SELECT COALESCE(SUM(quantity), 0) FROM assignments WHERE item_id = i.id AND status = 'assigned' AND return_status = 'active') as assigned_qty,
            (SELECT COUNT(*) FROM item_serial_numbers WHERE item_id = i.id AND (is_assigned = 0 OR is_assigned IS NULL)) as available_serials,
            (SELECT COUNT(*) FROM item_serial_numbers WHERE item_id = i.id AND is_assigned = 1) as assigned_serials
        FROM items i 
        LEFT JOIN brands b ON i.brand_id = b.id 
        LEFT JOIN categories c ON i.category_id = c.id
        LEFT JOIN item_types it ON i.type_id = it.id
        WHERE $where_sql
        ORDER BY i.id DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get filter options for dropdowns
    $categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
    $brands = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
    $item_types = $pdo->query("SELECT id, name FROM item_types WHERE is_active = 1 ORDER BY name")->fetchAll();
    
    // Calculate statistics for ALL items
    $all_items_count = $pdo->query("SELECT COUNT(*) FROM items WHERE is_active = 1")->fetchColumn();
    $total_value_query = $pdo->query("SELECT COALESCE(SUM(price * current_qty), 0) as total FROM items WHERE is_active = 1")->fetch();
    $total_value_all = $total_value_query['total'];
    $low_stock_all = $pdo->query("SELECT COUNT(*) FROM items WHERE is_active = 1 AND current_qty > 0 AND current_qty <= min_qty")->fetchColumn();
    $out_stock_all = $pdo->query("SELECT COUNT(*) FROM items WHERE is_active = 1 AND current_qty = 0")->fetchColumn();
    
    // Calculate totals for current page items
    $totalAssigned = 0;
    $totalAvailable = 0;
    $totalCurrentQty = 0;
    $totalValue = 0;
    
    foreach($items as &$item) {
        // Use serial counts for stock information (more accurate)
        $totalSerials = (int)($item['total_serials'] ?? 0);
        $assignedSerials = (int)($item['assigned_serials'] ?? 0);
        $availableSerials = (int)($item['available_serials'] ?? max(0, $totalSerials - $assignedSerials));
        
        // If no serials exist, fall back to current_qty
        if($totalSerials == 0) {
            $totalSerials = (int)$item['current_qty'];
            $availableSerials = max(0, $totalSerials - $assignedSerials);
        }
        
        $item['calculated_current_qty'] = $totalSerials;
        $item['assigned_qty'] = $assignedSerials;
        $item['real_available'] = $availableSerials;
        $item['price'] = (float)($item['price'] ?? 0);
        
        $totalAssigned += $assignedSerials;
        $totalAvailable += $availableSerials;
        $totalCurrentQty += $totalSerials;
        $totalValue += $item['price'] * $totalSerials;
    }
    unset($item);
    
} catch(PDOException $e) {
    error_log("Database error in list.php: " . $e->getMessage());
    $items = [];
    $total_items = 0;
    $total_pages = 1;
    $all_items_count = 0;
    $total_value_all = 0;
    $low_stock_all = 0;
    $out_stock_all = 0;
    $totalAssigned = 0;
    $totalAvailable = 0;
    $totalCurrentQty = 0;
    $totalValue = 0;
    $categories = [];
    $brands = [];
    $item_types = [];
    
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-database me-2"></i>
            <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Build query string for pagination
$query_params = [];
if(!empty($search)) $query_params['search'] = $search;
if(!empty($category_filter)) $query_params['category'] = $category_filter;
if(!empty($brand_filter)) $query_params['brand'] = $brand_filter;
if(!empty($type_filter)) $query_params['type'] = $type_filter;
$query_params['limit'] = $limit;
$query_string = !empty($query_params) ? '&' . http_build_query($query_params) : '';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-boxes me-2"></i> Items / Devices</h2>
                    <p class="text-muted mb-0">Manage all inventory items and devices</p>
                </div>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Item
                </a>
            </div>
            <hr>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-2 col-6">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo number_format($all_items_count); ?></h3>
                            <p class="mb-0 small">Total Items</p>
                        </div>
                        <i class="fas fa-boxes fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">৳<?php echo number_format($total_value_all, 0); ?></h3>
                            <p class="mb-0 small">Total Value</p>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card bg-warning text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $low_stock_all; ?></h3>
                            <p class="mb-0 small">Low Stock</p>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card bg-danger text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $out_stock_all; ?></h3>
                            <p class="mb-0 small">Out of Stock</p>
                        </div>
                        <i class="fas fa-times-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $totalAssigned; ?></h3>
                            <p class="mb-0 small">Assigned (Page)</p>
                        </div>
                        <i class="fas fa-user-check fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card bg-secondary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $totalAvailable; ?></h3>
                            <p class="mb-0 small">Available (Page)</p>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Item Code, Name, Serial, Model..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Brand</label>
                    <select name="brand" class="form-select">
                        <option value="">All Brands</option>
                        <?php foreach($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo $brand_filter == $brand['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Item Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach($item_types as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $type_filter == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Per Page</label>
                    <select name="limit" class="form-select" id="perPageSelect">
                        <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10 items</option>
                        <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25 items</option>
                        <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50 items</option>
                        <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100 items</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
                    <a href="list.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Clear All</a>
                    <?php if(!empty($search) || !empty($category_filter) || !empty($brand_filter) || !empty($type_filter)): ?>
                        <span class="text-muted ms-3"><i class="fas fa-info-circle"></i> Showing filtered results (<?php echo $total_items; ?> items found)</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Item List 
                <span class="badge bg-light text-dark ms-2"><?php echo $total_items; ?> items</span>
                <span class="badge bg-info ms-1">Page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">ID</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th style="width: 80px;">Stock</th>
                            <th style="width: 80px;">Assigned</th>
                            <th style="width: 80px;">Available</th>
                            <th>Unit Price</th>
                            <th>Total Value</th>
                            <th>Status</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($items) > 0): ?>
                            <?php foreach($items as $item): ?>
                                <?php
                                $stockStatus = '';
                                $stockBadge = '';
                                if($item['calculated_current_qty'] == 0) {
                                    $stockStatus = 'Out of Stock';
                                    $stockBadge = 'danger';
                                } elseif($item['real_available'] == 0 && $item['calculated_current_qty'] > 0) {
                                    $stockStatus = 'All Assigned';
                                    $stockBadge = 'warning';
                                } elseif($item['calculated_current_qty'] <= $item['min_qty']) {
                                    $stockStatus = 'Low Stock';
                                    $stockBadge = 'warning';
                                } else {
                                    $stockStatus = 'In Stock';
                                    $stockBadge = 'success';
                                }
                                ?>
                                <tr class="<?php 
                                    echo $item['calculated_current_qty'] == 0 ? 'table-danger' : 
                                        ($item['real_available'] == 0 && $item['calculated_current_qty'] > 0 ? 'table-warning' : ''); 
                                ?>">
                                    <td class="text-center"><?php echo $item['id']; ?></td>
                                    <td class="text-center"><strong><?php echo htmlspecialchars($item['item_code']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                        <?php if(!empty($item['serial_number'])): ?>
                                            <br><small class="text-muted">SN: <?php echo htmlspecialchars($item['serial_number']); ?></small>
                                        <?php endif; ?>
                                        <?php if(!empty($item['model_number'])): ?>
                                            <br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if(!empty($item['type_name'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($item['type_name']); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['brand_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($item['category_name'] ?? '-'); ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?php echo $item['calculated_current_qty']; ?></span></td>
                                    <td class="text-center"><span class="badge bg-info"><?php echo $item['assigned_qty']; ?></span></td>
                                    <td class="text-center">
                                        <?php if($item['real_available'] > 0): ?>
                                            <span class="badge bg-success"><?php echo $item['real_available']; ?></span>
                                        <?php elseif($item['real_available'] == 0 && $item['calculated_current_qty'] > 0): ?>
                                            <span class="badge bg-warning">0</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">৳<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-end">৳<?php echo number_format($item['price'] * $item['calculated_current_qty'], 2); ?></td>
                                    <td class="text-center"><span class="badge bg-<?php echo $stockBadge; ?>"><?php echo $stockStatus; ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($item['real_available'] > 0): ?>
                                                <a href="../assignments/assign.php?item_id=<?php echo $item['id']; ?>" class="btn btn-outline-success" title="Assign">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center py-5">
                                    <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
                                    <p class="text-muted">No items found matching your criteria.</p>
                                    <a href="add.php" class="btn btn-primary btn-sm">Add New Item</a>
                                    <a href="list.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if(count($items) > 0): ?>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="6" class="text-end">Page Totals:</td>
                            <td class="text-center"><?php echo $totalCurrentQty; ?></td>
                            <td class="text-center"><?php echo $totalAssigned; ?></td>
                            <td class="text-center"><?php echo $totalAvailable; ?></td>
                            <td colspan="2" class="text-end">Page Value: ৳<?php echo number_format($totalValue, 2); ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small">
                    Showing <strong><?php echo count($items); ?></strong> of <strong><?php echo $total_items; ?></strong> items
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=1&limit=<?php echo $limit; ?><?php echo $query_string; ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&limit=<?php echo $limit; ?><?php echo $query_string; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        if($start_page > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        for($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&limit=<?php echo $limit; ?><?php echo $query_string; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if($end_page < $total_pages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&limit=<?php echo $limit; ?><?php echo $query_string; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&limit=<?php echo $limit; ?><?php echo $query_string; ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Stock Status Legend -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body py-2">
                    <small class="text-muted">
                        <i class="fas fa-square text-success"></i> In Stock - Available for Assignment &nbsp;&nbsp;
                        <i class="fas fa-square text-warning"></i> All Assigned / Low Stock &nbsp;&nbsp;
                        <i class="fas fa-square text-danger"></i> Out of Stock &nbsp;&nbsp;
                        <i class="fas fa-info-circle text-info"></i> Click on View to see assignment details
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Per page change handler
(function() {
    var perPageSelect = document.getElementById('perPageSelect');
    if(perPageSelect) {
        perPageSelect.addEventListener('change', function() {
            var limit = this.value;
            var url = new URL(window.location.href);
            url.searchParams.set('limit', limit);
            url.searchParams.set('page', 1);
            window.location.href = url.toString();
        });
    }
})();
</script>

<?php include '../../includes/footer.php'; ?>