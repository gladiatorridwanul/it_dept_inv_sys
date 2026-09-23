<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Get filter parameters
$category_id = $_GET['category_id'] ?? '';
$brand_id = $_GET['brand_id'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if($category_id) {
    $where_conditions[] = "i.category_id = ?";
    $params[] = $category_id;
}

if($brand_id) {
    $where_conditions[] = "i.brand_id = ?";
    $params[] = $brand_id;
}

if($stock_status == 'low') {
    $where_conditions[] = "i.current_qty <= i.min_qty AND i.current_qty > 0";
} elseif($stock_status == 'out') {
    $where_conditions[] = "i.current_qty = 0";
} elseif($stock_status == 'in') {
    $where_conditions[] = "i.current_qty > i.min_qty";
}

if($search) {
    $where_conditions[] = "(i.name LIKE ? OR i.item_code LIKE ? OR i.serial_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get stock items with assigned quantities
$stmt = $pdo->prepare("SELECT i.*, c.name as category_name, b.name as brand_name,
                              (SELECT COALESCE(SUM(quantity), 0) FROM assignments WHERE item_id = i.id AND status = 'assigned') as assigned_qty
                       FROM items i 
                       LEFT JOIN categories c ON i.category_id = c.id 
                       LEFT JOIN brands b ON i.brand_id = b.id 
                       $where_sql 
                       ORDER BY i.name");
$stmt->execute($params);
$items = $stmt->fetchAll();

// Calculate totals
$total_items = count($items);
$total_stock_value = 0;
$total_stock_quantity = 0;
$total_assigned_quantity = 0;
$total_available_quantity = 0;
$low_stock_count = 0;
$out_of_stock_count = 0;

foreach($items as $item) {
    $assigned_qty = $item['assigned_qty'];
    $available_qty = $item['current_qty'] - $assigned_qty;
    
    $total_stock_value += $item['price'] * $item['current_qty'];
    $total_stock_quantity += $item['current_qty'];
    $total_assigned_quantity += $assigned_qty;
    $total_available_quantity += $available_qty;
    
    if($item['current_qty'] == 0) {
        $out_of_stock_count++;
    } elseif($item['current_qty'] <= $item['min_qty']) {
        $low_stock_count++;
    }
}

// Get categories for filter
$categories = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();

// Get brands for filter
$brands = $pdo->query("SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name")->fetchAll();
?>

<style>
    .stats-card {
        transition: transform 0.3s;
        border-radius: 12px;
        cursor: pointer;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .stats-number {
        font-size: 28px;
        font-weight: 700;
    }
    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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
    .stock-badge {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .stock-in { background: #d1fae5; color: #065f46; }
    .stock-low { background: #fed7aa; color: #92400e; }
    .stock-out { background: #fee2e2; color: #991b1b; }
    .assigned-badge {
        background: #e0e7ff;
        color: #3730a3;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .available-badge {
        background: #d1fae5;
        color: #065f46;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        .stats-card {
            break-inside: avoid;
        }
        .filter-card {
            display: none;
        }
        .action-buttons {
            display: none;
        }
        body {
            padding: 0;
            margin: 0;
        }
        .main-content {
            margin: 0;
            padding: 10px;
        }
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
                    <h2><i class="fas fa-chart-line text-primary"></i> Stock Report</h2>
                    <p class="text-muted">View and analyze current inventory stock levels with assignment tracking</p>
                </div>
                <div class="mt-2 mt-md-0 action-buttons">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                    <button onclick="exportToExcel()" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button onclick="downloadPDF()" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </button>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-white bg-primary stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Items</h6>
                            <h2 class="stats-number mb-0"><?php echo number_format($total_items); ?></h2>
                        </div>
                        <div><i class="fas fa-boxes fa-2x opacity-50"></i></div>
                    </div>
                    <small>Unique product types</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-white bg-success stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Stock Value</h6>
                            <h4 class="mb-0">৳<?php echo number_format($total_stock_value, 2); ?></h4>
                        </div>
                        <div><i class="fas fa-money-bill-wave fa-2x opacity-50"></i></div>
                    </div>
                    <small>Inventory value</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-white bg-info stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Assigned Items</h6>
                            <h2 class="stats-number mb-0"><?php echo number_format($total_assigned_quantity); ?></h2>
                        </div>
                        <div><i class="fas fa-laptop fa-2x opacity-50"></i></div>
                    </div>
                    <small>Currently in use</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-2">
            <div class="card text-white bg-dark stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Available Items</h6>
                            <h2 class="stats-number mb-0"><?php echo number_format($total_available_quantity); ?></h2>
                        </div>
                        <div><i class="fas fa-check-circle fa-2x opacity-50"></i></div>
                    </div>
                    <small>Ready for assignment</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Cards Row 2 -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-warning stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Low Stock Items</h6>
                            <h2 class="stats-number mb-0"><?php echo $low_stock_count; ?></h2>
                        </div>
                        <div><i class="fas fa-exclamation-triangle fa-2x opacity-50"></i></div>
                    </div>
                    <small>Below minimum threshold</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-danger stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Out of Stock</h6>
                            <h2 class="stats-number mb-0"><?php echo $out_of_stock_count; ?></h2>
                        </div>
                        <div><i class="fas fa-ban fa-2x opacity-50"></i></div>
                    </div>
                    <small>Need restocking</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-white bg-secondary stats-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Stock Qty</h6>
                            <h2 class="stats-number mb-0"><?php echo number_format($total_stock_quantity); ?></h2>
                        </div>
                        <div><i class="fas fa-cubes fa-2x opacity-50"></i></div>
                    </div>
                    <small>Physical inventory count</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card no-print">
        <div class="row">
            <div class="col-md-12">
                <h6 class="mb-3"><i class="fas fa-filter"></i> Filter Reports</h6>
            </div>
        </div>
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Brand</label>
                <select name="brand_id" class="form-select">
                    <option value="">All Brands</option>
                    <?php foreach($brands as $brand): ?>
                    <option value="<?php echo $brand['id']; ?>" <?php echo $brand_id == $brand['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brand['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock Status</label>
                <select name="stock_status" class="form-select">
                    <option value="">All Stock</option>
                    <option value="in" <?php echo $stock_status == 'in' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="low" <?php echo $stock_status == 'low' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="out" <?php echo $stock_status == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Item name, code, serial..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-12 text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="stock_report.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Stock Report Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Stock Report Details</h5>
            <small>Generated on: <?php echo date('d-m-Y h:i A'); ?></small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0" id="stockTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Stock Qty</th>
                            <th>Assigned Qty</th>
                            <th>Available Qty</th>
                            <th>Min Qty</th>
                            <th>Price (BDT)</th>
                            <th>Total Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($items) > 0): ?>
                            <?php $sn = 1; foreach($items as $item): 
                                $assigned_qty = $item['assigned_qty'];
                                $available_qty = $item['current_qty'] - $assigned_qty;
                                $stock_class = '';
                                $stock_text = '';
                                if($item['current_qty'] == 0) {
                                    $stock_class = 'stock-out';
                                    $stock_text = 'Out of Stock';
                                } elseif($item['current_qty'] <= $item['min_qty']) {
                                    $stock_class = 'stock-low';
                                    $stock_text = 'Low Stock';
                                } else {
                                    $stock_class = 'stock-in';
                                    $stock_text = 'In Stock';
                                }
                            ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td><strong><?php echo $item['item_code']; ?></strong></td>
                                <td><?php echo htmlspecialchars($item['name']); ?>
                                    <?php if($item['model_number']): ?>
                                        <br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small>
                                    <?php endif; ?>
                                </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td><?php echo htmlspecialchars($item['brand_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-center">
                                    <span class="fw-bold"><?php echo number_format($item['current_qty']); ?></span>
                                 </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-center">
                                    <span class="assigned-badge"><?php echo number_format($assigned_qty); ?></span>
                                 </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-center">
                                    <span class="available-badge"><?php echo number_format($available_qty); ?></span>
                                 </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-center"><?php echo $item['min_qty']; ?> </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-end">৳<?php echo number_format($item['price'], 2); ?> </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-end text-success fw-bold">
                                    ৳<?php echo number_format($item['price'] * $item['current_qty'], 2); ?>
                                 </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                                <td class="text-center">
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $stock_text; ?>
                                    </span>
                                 </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="mb-0">No stock items found matching your criteria.</p>
                                 </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr class="fw-bold">
                            <td colspan="5" class="text-end">Totals: </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            <td class="text-center"><?php echo number_format($total_stock_quantity); ?> </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            <td class="text-center"><?php echo number_format($total_assigned_quantity); ?> </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            <td class="text-center"><?php echo number_format($total_available_quantity); ?> </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            <td colspan="2" class="text-end">Total Value: </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            <td class="text-end fw-bold">৳<?php echo number_format($total_stock_value, 2); ?> </br><small class="text-muted">Model: <?php echo htmlspecialchars($item['model_number']); ?></small></td>
                            <td></td>
                         </tr>
                    </tfoot>
                 </table>
            </div>
        </div>
    </div>
    
    <!-- Summary Section -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="alert alert-info">
                <div class="row">
                    <div class="col-md-4">
                        <strong><i class="fas fa-chart-pie"></i> Stock Summary</strong>
                    </div>
                    <div class="col-md-8">
                        <div class="progress mb-2" style="height: 25px;">
                            <?php 
                            $total = $total_stock_quantity > 0 ? $total_stock_quantity : 1;
                            $assigned_percent = ($total_assigned_quantity / $total) * 100;
                            $available_percent = ($total_available_quantity / $total) * 100;
                            ?>
                            <div class="progress-bar bg-info" style="width: <?php echo $assigned_percent; ?>%">
                                Assigned: <?php echo $total_assigned_quantity; ?>
                            </div>
                            <div class="progress-bar bg-success" style="width: <?php echo $available_percent; ?>%">
                                Available: <?php echo $total_available_quantity; ?>
                            </div>
                        </div>
                        <small>Assigned items are currently in use by employees | Available items are ready for new assignments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function exportToExcel() {
    var table = document.getElementById('stockTable');
    var html = table.outerHTML;
    var url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    var link = document.createElement('a');
    link.href = url;
    link.download = 'stock_report_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.xls';
    link.click();
}

function downloadPDF() {
    var element = document.querySelector('.main-content');
    var opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: 'stock_report_' + new Date().toISOString().slice(0,19).replace(/:/g, '-') + '.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, letterRendering: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    html2pdf().set(opt).from(element).save();
}

// Print function with better formatting
window.onbeforeprint = function() {
    document.querySelectorAll('.no-print').forEach(function(el) {
        el.style.display = 'none';
    });
};

window.onafterprint = function() {
    document.querySelectorAll('.no-print').forEach(function(el) {
        el.style.display = '';
    });
};
</script>

<?php include '../../includes/footer.php'; ?>