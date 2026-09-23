<?php
require_once '../includes/auth.php';
include '../includes/header.php';

// Create warranty tracking table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS warranties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    warranty_type ENUM('manufacturer', 'extended', 'service') DEFAULT 'manufacturer',
    warranty_start_date DATE,
    warranty_end_date DATE,
    warranty_provider VARCHAR(200),
    warranty_document VARCHAR(255),
    contact_phone VARCHAR(50),
    contact_email VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
)");

// Create licenses table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT,
    license_key VARCHAR(100),
    software_name VARCHAR(200),
    license_type ENUM('perpetual', 'subscription', 'trial') DEFAULT 'perpetual',
    purchase_date DATE,
    expiry_date DATE,
    seats INT DEFAULT 1,
    vendor VARCHAR(200),
    license_file VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
)");

// Add warranty
if(isset($_POST['add_warranty'])) {
    $item_id = $_POST['item_id'];
    $warranty_type = $_POST['warranty_type'];
    $warranty_start = $_POST['warranty_start'];
    $warranty_end = $_POST['warranty_end'];
    $provider = $_POST['provider'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("INSERT INTO warranties (item_id, warranty_type, warranty_start_date, warranty_end_date, warranty_provider, contact_phone, contact_email, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$item_id, $warranty_type, $warranty_start, $warranty_end, $provider, $phone, $email, $notes])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Warranty added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    } else {
        echo '<div class="alert alert-danger">Error adding warranty!</div>';
    }
}

// Add license
if(isset($_POST['add_license'])) {
    $item_id = $_POST['item_id'];
    $software_name = $_POST['software_name'];
    $license_key = $_POST['license_key'];
    $license_type = $_POST['license_type'];
    $purchase_date = $_POST['purchase_date'];
    $expiry_date = $_POST['expiry_date'];
    $seats = $_POST['seats'];
    $vendor = $_POST['vendor'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("INSERT INTO licenses (item_id, software_name, license_key, license_type, purchase_date, expiry_date, seats, vendor, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if($stmt->execute([$item_id, $software_name, $license_key, $license_type, $purchase_date, $expiry_date, $seats, $vendor, $notes])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> License added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    } else {
        echo '<div class="alert alert-danger">Error adding license!</div>';
    }
}

// Delete warranty
if(isset($_GET['delete_warranty']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM warranties WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">Warranty deleted!</div>';
}

// Delete license
if(isset($_GET['delete_license']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM licenses WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">License deleted!</div>';
}

// Get all data
$warranties = $pdo->query("SELECT w.*, i.name, i.item_code 
                           FROM warranties w 
                           JOIN items i ON w.item_id = i.id 
                           ORDER BY w.warranty_end_date ASC")->fetchAll();

$licenses = $pdo->query("SELECT l.*, i.name, i.item_code 
                         FROM licenses l 
                         JOIN items i ON l.item_id = i.id 
                         ORDER BY l.expiry_date ASC")->fetchAll();

$items = $pdo->query("SELECT id, item_code, name FROM items WHERE is_active=1 ORDER BY name")->fetchAll();

// Get expiring soon counts
$expiring_warranties_count = $pdo->query("SELECT COUNT(*) as count FROM warranties WHERE warranty_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch();
$expiring_licenses_count = $pdo->query("SELECT COUNT(*) as count FROM licenses WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetch();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-file-contract"></i> License & Warranty Tracking</h2>
            <hr>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Warranties</h6>
                            <h2 class="mb-0"><?php echo count($warranties); ?></h2>
                        </div>
                        <i class="fas fa-shield-alt fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Expiring Warranties</h6>
                            <h2 class="mb-0"><?php echo $expiring_warranties_count['count']; ?></h2>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Licenses</h6>
                            <h2 class="mb-0"><?php echo count($licenses); ?></h2>
                        </div>
                        <i class="fas fa-key fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Expiring Licenses</h6>
                            <h2 class="mb-0"><?php echo $expiring_licenses_count['count']; ?></h2>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Add Warranty Form -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add Warranty</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Select Item *</label>
                                <select name="item_id" class="form-select" required>
                                    <option value="">-- Select Item --</option>
                                    <?php foreach($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo $item['item_code'] . ' - ' . $item['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Warranty Type</label>
                                <select name="warranty_type" class="form-select">
                                    <option value="manufacturer">Manufacturer Warranty</option>
                                    <option value="extended">Extended Warranty</option>
                                    <option value="service">Service Contract</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Provider</label>
                                <input type="text" name="provider" class="form-control" placeholder="Warranty provider">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="warranty_start" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="warranty_end" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" name="phone" class="form-control" placeholder="Support phone">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" name="email" class="form-control" placeholder="Support email">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_warranty" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Add Warranty
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Add License Form -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add License</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Select Item *</label>
                                <select name="item_id" class="form-select" required>
                                    <option value="">-- Select Item --</option>
                                    <?php foreach($items as $item): ?>
                                    <option value="<?php echo $item['id']; ?>"><?php echo $item['item_code'] . ' - ' . $item['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Software Name *</label>
                                <input type="text" name="software_name" class="form-control" required placeholder="Software/Application name">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">License Key</label>
                                <input type="text" name="license_key" class="form-control" placeholder="License key/Product key">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">License Type</label>
                                <select name="license_type" class="form-select">
                                    <option value="perpetual">Perpetual</option>
                                    <option value="subscription">Subscription</option>
                                    <option value="trial">Trial</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Vendor</label>
                                <input type="text" name="vendor" class="form-control" placeholder="Software vendor">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Purchase Date</label>
                                <input type="date" name="purchase_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Number of Seats</label>
                                <input type="number" name="seats" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="Additional notes..."></textarea>
                            </div>
                        </div>
                        <button type="submit" name="add_license" class="btn btn-success w-100">
                            <i class="fas fa-save"></i> Add License
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Warranty List Table -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Warranty List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" id="warrantyTable">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Provider</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days Left</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($warranties as $w): 
                            $days_left = !empty($w['warranty_end_date']) ? ceil((strtotime($w['warranty_end_date']) - time()) / (60 * 60 * 24)) : null;
                            $status = '';
                            $status_class = '';
                            if(!$w['warranty_end_date']) {
                                $status = 'N/A';
                                $status_class = 'secondary';
                            } elseif($days_left < 0) {
                                $status = 'Expired';
                                $status_class = 'danger';
                            } elseif($days_left <= 30) {
                                $status = 'Expiring Soon';
                                $status_class = 'warning';
                            } else {
                                $status = 'Active';
                                $status_class = 'success';
                            }
                        ?>
                        <tr>
                            <td><?php echo $w['item_code']; ?></td>
                            <td><?php echo htmlspecialchars($w['name']); ?></td>
                            <td><?php echo ucfirst($w['warranty_type']); ?></td>
                            <td><?php echo $w['warranty_provider'] ?: '-'; ?></td>
                            <td><?php echo $w['warranty_start_date'] ? date('d-m-Y', strtotime($w['warranty_start_date'])) : '-'; ?></td>
                            <td><?php echo $w['warranty_end_date'] ? date('d-m-Y', strtotime($w['warranty_end_date'])) : '-'; ?></td>
                            <td>
                                <?php if($days_left && $days_left > 0): ?>
                                    <?php echo $days_left; ?> days
                                <?php elseif($days_left && $days_left <= 0): ?>
                                    Expired
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="?delete_warranty=1&id=<?php echo $w['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this warranty?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($warranties)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">No warranties found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- License List Table -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-key"></i> License List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover datatable" id="licenseTable">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Software Name</th>
                            <th>License Key</th>
                            <th>Type</th>
                            <th>Vendor</th>
                            <th>Purchase Date</th>
                            <th>Expiry Date</th>
                            <th>Seats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($licenses as $l): 
                            $days_left = !empty($l['expiry_date']) ? ceil((strtotime($l['expiry_date']) - time()) / (60 * 60 * 24)) : null;
                            $status = '';
                            $status_class = '';
                            if(!$l['expiry_date']) {
                                $status = 'N/A';
                                $status_class = 'secondary';
                            } elseif($days_left < 0) {
                                $status = 'Expired';
                                $status_class = 'danger';
                            } elseif($days_left <= 30) {
                                $status = 'Expiring Soon';
                                $status_class = 'warning';
                            } else {
                                $status = 'Active';
                                $status_class = 'success';
                            }
                        ?>
                        <tr>
                            <td><?php echo $l['item_code']; ?></td>
                            <td><?php echo htmlspecialchars($l['name']); ?></td>
                            <td><?php echo htmlspecialchars($l['software_name']); ?></td>
                            <td>
                                <?php if($l['license_key']): ?>
                                    <code><?php echo htmlspecialchars($l['license_key']); ?></code>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?php echo ucfirst($l['license_type']); ?></td>
                            <td><?php echo $l['vendor'] ?: '-'; ?></td>
                            <td><?php echo $l['purchase_date'] ? date('d-m-Y', strtotime($l['purchase_date'])) : '-'; ?></td>
                            <td><?php echo $l['expiry_date'] ? date('d-m-Y', strtotime($l['expiry_date'])) : 'N/A'; ?></td>
                            <td class="text-center"><?php echo $l['seats']; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status; ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="?delete_license=1&id=<?php echo $l['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this license?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($licenses)): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted">No licenses found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
}
.table td {
    vertical-align: middle;
}
.btn-group .btn {
    margin: 0 2px;
}
.badge {
    font-size: 11px;
    padding: 5px 10px;
}
code {
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
}
.datatable tbody tr:hover {
    background-color: #f8fafc;
}
.card-header {
    border-bottom: none;
}
</style>

<script>
$(document).ready(function() {
    // Initialize DataTables
    if($.fn.DataTable) {
        $('#warrantyTable').DataTable({
            pageLength: 10,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            },
            order: [[5, 'asc']]
        });
        
        $('#licenseTable').DataTable({
            pageLength: 10,
            language: {
                search: "Search:",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ entries"
            },
            order: [[7, 'asc']]
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>