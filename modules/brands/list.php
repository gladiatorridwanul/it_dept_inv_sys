<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle Active/De-Active
if(isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("UPDATE brands SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> Brand status updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Delete Brand
if(isset($_GET['delete']) && isset($_GET['id']) && isAdmin()) {
    $id = (int)$_GET['id'];
    // Check if brand is used in items
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE brand_id = ?");
    $stmt->execute([$id]);
    $used = $stmt->fetch();
    
    if($used['count'] > 0) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Cannot delete brand! It is used in ' . $used['count'] . ' item(s).
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Brand deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if($search) {
    $where = "WHERE name LIKE ? OR description LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT b.*, 
                              (SELECT COUNT(*) FROM items WHERE brand_id = b.id) as item_count
                       FROM brands b $where ORDER BY b.name");
$stmt->execute($params);
$brands = $stmt->fetchAll();

// Get statistics
$totalBrands = $pdo->query("SELECT COUNT(*) as count FROM brands")->fetch()['count'];
$activeBrands = $pdo->query("SELECT COUNT(*) as count FROM brands WHERE is_active = 1")->fetch()['count'];
$inactiveBrands = $totalBrands - $activeBrands;
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
        margin-bottom: 0;
    }
    .stats-label {
        font-size: 12px;
        opacity: 0.8;
        margin-bottom: 0;
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .btn-group-sm .btn {
        margin: 0 2px;
    }
    .brand-row:hover {
        background-color: #f8f9fa;
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
                    <h2><i class="fas fa-trademark text-primary"></i> Brands Management</h2>
                    <p class="text-muted">Manage product brands and manufacturers</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Brand
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Total Brands</p>
                            <h2 class="stats-number"><?php echo $totalBrands; ?></h2>
                        </div>
                        <div><i class="fas fa-building fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Active Brands</p>
                            <h2 class="stats-number"><?php echo $activeBrands; ?></h2>
                        </div>
                        <div><i class="fas fa-check-circle fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Inactive Brands</p>
                            <h2 class="stats-number"><?php echo $inactiveBrands; ?></h2>
                        </div>
                        <div><i class="fas fa-ban fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Total Items</p>
                            <h2 class="stats-number"><?php 
                                $itemStmt = $pdo->query("SELECT COUNT(DISTINCT brand_id) as count FROM items WHERE brand_id IS NOT NULL");
                                echo $itemStmt->fetch()['count'];
                            ?></h2>
                        </div>
                        <div><i class="fas fa-boxes fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search Bar -->
    <div class="row mb-3">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by brand name or description..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if($search): ?>
                        <a href="list.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="col-md-6 text-end">
            <span class="text-muted">Showing <?php echo count($brands); ?> brand(s)</span>
        </div>
    </div>
    
    <!-- Brands Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Brand Directory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable mb-0" id="brandsTable">
                    <thead>
                        <tr>
                            <th width="50">ID</th>
                            <th>Brand Name</th>
                            <th>Description</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($brands) > 0): ?>
                            <?php foreach($brands as $brand): ?>
                            <tr class="brand-row">
                                <td><?php echo $brand['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($brand['name']); ?></strong>
                                    <?php if($brand['item_count'] > 0): ?>
                                        <br><small class="text-muted">Popular brand</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $desc = htmlspecialchars($brand['description'] ?? '');
                                    echo !empty($desc) ? (strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc) : '<span class="text-muted">No description</span>';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $brand['item_count']; ?> item(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $brand['is_active'] ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $brand['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo $brand['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d-M-Y', strtotime($brand['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="edit.php?id=<?php echo $brand['id']; ?>" class="btn btn-info" title="Edit Brand">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle=1&id=<?php echo $brand['id']; ?>" class="btn btn-warning" 
                                           title="<?php echo $brand['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                           onclick="return confirm('Are you sure you want to <?php echo $brand['is_active'] ? 'deactivate' : 'activate'; ?> this brand?')">
                                            <i class="fas fa-<?php echo $brand['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <?php if(isAdmin()): ?>
                                            <a href="?delete=1&id=<?php echo $brand['id']; ?>" class="btn btn-danger delete-confirm" 
                                               title="Delete Brand" 
                                               onclick="return confirm('Are you sure you want to delete this brand?\n\nThis action cannot be undone!')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="mb-0">No brands found.</p>
                                    <?php if($search): ?>
                                        <p class="text-muted">Try a different search term.</p>
                                    <?php else: ?>
                                        <a href="add.php" class="btn btn-sm btn-primary mt-2">Add your first brand</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>