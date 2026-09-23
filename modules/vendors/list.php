<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle Excel Upload for Bulk Import
if(isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] == 0) {
    $excel_loaded = false;
    if(file_exists('../../vendor/autoload.php')) {
        require_once '../../vendor/autoload.php';
        $excel_loaded = true;
    }
    
    if($excel_loaded) {
        try {
            $file = $_FILES['excel_file']['tmp_name'];
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $count = 0;
            $errors = [];
            
            foreach($rows as $index => $row) {
                if($index == 0) continue;
                
                if(!empty($row[0])) {
                    $vendor_name = trim($row[0]);
                    $company_name = trim($row[1] ?? '');
                    $contact_person = trim($row[2] ?? '');
                    $contact_designation = trim($row[3] ?? '');
                    $contact_phone = trim($row[4] ?? '');
                    $contact_email = trim($row[5] ?? '');
                    $office_phone = trim($row[6] ?? '');
                    $office_email = trim($row[7] ?? '');
                    $office_address = trim($row[8] ?? '');
                    $tin_no = trim($row[9] ?? '');
                    $bin_no = trim($row[10] ?? '');
                    $trade_license_no = trim($row[11] ?? '');
                    $gst_no = trim($row[12] ?? '');
                    $website = trim($row[13] ?? '');
                    
                    $stmt = $pdo->prepare("INSERT INTO vendors (
                        vendor_name, company_name, contact_person, contact_designation, 
                        contact_phone, contact_email, office_phone, office_email, office_address,
                        tin_no, bin_no, trade_license_no, gst_no, website
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    
                    $stmt->execute([
                        $vendor_name, $company_name, $contact_person, $contact_designation,
                        $contact_phone, $contact_email, $office_phone, $office_email, $office_address,
                        $tin_no, $bin_no, $trade_license_no, $gst_no, $website
                    ]);
                    $count++;
                }
            }
            echo '<div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> ' . $count . ' vendors imported successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        } catch(Exception $e) {
            echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    } else {
        echo '<div class="alert alert-warning">PhpSpreadsheet not installed. Please run: composer require phpoffice/phpspreadsheet</div>';
    }
}

// Handle Active/De-Active
if(isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE vendors SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> Vendor status updated!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Delete vendor
if(isset($_GET['delete']) && isset($_GET['id']) && isAdmin()) {
    $id = $_GET['id'];
    
    // Check if vendor has any bills
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM bills WHERE vendor_id = ?");
    $checkStmt->execute([$id]);
    $billCount = $checkStmt->fetch()['count'];
    
    if($billCount > 0) {
        echo '<div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> Cannot delete vendor with ' . $billCount . ' associated bill(s)!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM vendors WHERE id = ?");
        $stmt->execute([$id]);
        echo '<div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Vendor deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if($search) {
    $where = "WHERE vendor_name LIKE ? OR company_name LIKE ? OR contact_person LIKE ? OR contact_phone LIKE ? OR contact_email LIKE ? OR office_phone LIKE ? OR tin_no LIKE ? OR bin_no LIKE ? OR gst_no LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare("SELECT v.*, 
                              (SELECT COUNT(*) FROM bills WHERE vendor_id = v.id) as bill_count,
                              (SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE vendor_id = v.id) as total_purchase
                       FROM vendors v $where ORDER BY v.vendor_name");
$stmt->execute($params);
$vendors = $stmt->fetchAll();

// Get statistics
$totalVendors = $pdo->query("SELECT COUNT(*) as count FROM vendors")->fetch()['count'];
$activeVendors = $pdo->query("SELECT COUNT(*) as count FROM vendors WHERE is_active = 1")->fetch()['count'];
$totalPurchases = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM bills")->fetch()['total'];
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
    .vendor-card {
        transition: all 0.3s;
    }
    .vendor-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 12px;
    }
    .table td {
        font-size: 13px;
        vertical-align: middle;
    }
    .badge-sm {
        font-size: 10px;
        padding: 3px 8px;
    }
    .contact-info {
        font-size: 11px;
        color: #6c757d;
    }
    @media (max-width: 768px) {
        .stats-number {
            font-size: 20px;
        }
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-3">
        <div class="col-md-8">
            <h2><i class="fas fa-truck text-primary"></i> Vendors Management</h2>
            <p class="text-muted">Manage vendor information, track purchases, and monitor performance</p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#excelModal">
                <i class="fas fa-file-excel"></i> Import
            </button>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Vendor
            </a>
            <a href="export.php" class="btn btn-info">
                <i class="fas fa-download"></i> Export
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Total Vendors</p>
                            <h2 class="stats-number"><?php echo $totalVendors; ?></h2>
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
                            <p class="stats-label">Active Vendors</p>
                            <h2 class="stats-number"><?php echo $activeVendors; ?></h2>
                        </div>
                        <div><i class="fas fa-check-circle fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Total Purchases</p>
                            <h2 class="stats-number">৳<?php echo number_format($totalPurchases, 0); ?></h2>
                        </div>
                        <div><i class="fas fa-money-bill-wave fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="stats-label">Avg. Purchase</p>
                            <h2 class="stats-number">৳<?php echo $totalVendors > 0 ? number_format($totalPurchases / $totalVendors, 0) : '0'; ?></h2>
                        </div>
                        <div><i class="fas fa-chart-line fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="row mb-3">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by Name, Company, Contact Person, Phone, Email, TIN, BIN, GST..." 
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
            <span class="text-muted">Showing <?php echo count($vendors); ?> vendors</span>
        </div>
    </div>

    <!-- Excel Import Modal -->
    <div class="modal fade" id="excelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-file-excel"></i> Bulk Import Vendors</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Instructions:</strong><br>
                            Excel columns (in order):
                            <ol class="mb-0 mt-2 small">
                                <li>Vendor Name *</li>
                                <li>Company Name</li>
                                <li>Contact Person</li>
                                <li>Contact Designation</li>
                                <li>Contact Phone</li>
                                <li>Contact Email</li>
                                <li>Office Phone</li>
                                <li>Office Email</li>
                                <li>Office Address</li>
                                <li>TIN Number</li>
                                <li>BIN Number</li>
                                <li>Trade License No</li>
                                <li>GST Number</li>
                                <li>Website</li>
                            </ol>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Excel File</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-secondary" onclick="downloadTemplate()">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Import Vendors</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Vendor List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0 datatable" id="vendorsTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Vendor Information</th>
                            <th>Contact Details</th>
                            <th>Tax / License</th>
                            <th>Office Contact</th>
                            <th>Purchases</th>
                            <th>Status</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($vendors) > 0): ?>
                            <?php $sn = 1; foreach($vendors as $vendor): ?>
                            <tr>
                                <td><?php echo $sn++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($vendor['vendor_name'] ?? $vendor['name']); ?></strong>
                                    <?php if(!empty($vendor['company_name'])): ?>
                                        <br><small class="text-muted"><i class="fas fa-building"></i> <?php echo htmlspecialchars($vendor['company_name']); ?></small>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['office_address'])): ?>
                                        <br><small class="contact-info"><i class="fas fa-map-marker-alt"></i> <?php echo substr(htmlspecialchars($vendor['office_address']), 0, 60); ?></small>
                                    <?php elseif(!empty($vendor['address'])): ?>
                                        <br><small class="contact-info"><i class="fas fa-map-marker-alt"></i> <?php echo substr(htmlspecialchars($vendor['address']), 0, 60); ?></small>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['website'])): ?>
                                        <br><small class="contact-info"><i class="fas fa-globe"></i> <?php echo htmlspecialchars($vendor['website']); ?></small>
                                    <?php endif; ?>
                                 </br><small class="contact-info"><i class="fas fa-globe"></i> <?php echo htmlspecialchars($vendor['website']); ?></small></td>
                                <td>
                                    <?php if(!empty($vendor['contact_person'])): ?>
                                        <div><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($vendor['contact_person']); ?></strong>
                                        <?php if(!empty($vendor['contact_designation'])): ?>
                                            <br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small>
                                        <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['contact_phone'])): ?>
                                        <div class="mt-1"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($vendor['contact_phone']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['contact_email'])): ?>
                                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($vendor['contact_email']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['secondary_contact_person'])): ?>
                                        <div class="mt-1 text-muted small">
                                            <i class="fas fa-user-friends"></i> Emergency: <?php echo htmlspecialchars($vendor['secondary_contact_person']); ?>
                                        </div>
                                    <?php endif; ?>
                                 </br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small></td>
                                <td>
                                    <?php if(!empty($vendor['tin_no'])): ?>
                                        <div><i class="fas fa-id-card"></i> TIN: <?php echo htmlspecialchars($vendor['tin_no']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['bin_no'])): ?>
                                        <div><i class="fas fa-barcode"></i> BIN: <?php echo htmlspecialchars($vendor['bin_no']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['trade_license_no'])): ?>
                                        <div><i class="fas fa-certificate"></i> Trade: <?php echo htmlspecialchars($vendor['trade_license_no']); ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['gst_no'])): ?>
                                        <div><i class="fas fa-file-invoice"></i> GST: <?php echo htmlspecialchars($vendor['gst_no']); ?></div>
                                    <?php endif; ?>
                                    <?php if(empty($vendor['tin_no']) && empty($vendor['bin_no']) && empty($vendor['gst_no'])): ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                 </br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small></td>
                                <td>
                                    <?php if(!empty($vendor['office_phone'])): ?>
                                        <div><i class="fas fa-phone-office"></i> <?php echo htmlspecialchars($vendor['office_phone']); ?></div>
                                    <?php elseif(!empty($vendor['phone'])): ?>
                                        <div><i class="fas fa-phone-office"></i> <?php echo htmlspecialchars($vendor['phone']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                    <?php if(!empty($vendor['office_email'])): ?>
                                        <div><i class="fas fa-envelope-open-text"></i> <?php echo htmlspecialchars($vendor['office_email']); ?></div>
                                    <?php elseif(!empty($vendor['email'])): ?>
                                        <div><i class="fas fa-envelope-open-text"></i> <?php echo htmlspecialchars($vendor['email']); ?></div>
                                    <?php endif; ?>
                                 </br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $vendor['bill_count']; ?> bills</span>
                                    <br><small class="text-success">৳<?php echo number_format($vendor['total_purchase'], 0); ?></small>
                                 </br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small></td>
                                <td>
                                    <span class="badge bg-<?php echo $vendor['is_active'] ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $vendor['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo $vendor['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                 </br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="edit.php?id=<?php echo $vendor['id']; ?>" class="btn btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view.php?id=<?php echo $vendor['id']; ?>" class="btn btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?toggle=1&id=<?php echo $vendor['id']; ?>" class="btn btn-warning" 
                                           title="<?php echo $vendor['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                           onclick="return confirm('Are you sure you want to <?php echo $vendor['is_active'] ? 'deactivate' : 'activate'; ?> this vendor?')">
                                            <i class="fas fa-<?php echo $vendor['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <?php if(isAdmin() && $vendor['bill_count'] == 0): ?>
                                        <a href="?delete=1&id=<?php echo $vendor['id']; ?>" class="btn btn-danger delete-confirm" 
                                           title="Delete" onclick="return confirm('Are you sure you want to delete this vendor?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                 </br><small class="text-muted">(<?php echo htmlspecialchars($vendor['contact_designation']); ?>)</small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="mb-0">No vendors found.</p>
                                    <a href="add.php" class="btn btn-sm btn-primary mt-2">Add your first vendor</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function downloadTemplate() {
    const headers = [
        'Vendor Name', 'Company Name', 'Contact Person', 'Contact Designation', 
        'Contact Phone', 'Contact Email', 'Office Phone', 'Office Email', 
        'Office Address', 'TIN Number', 'BIN Number', 'Trade License No', 
        'GST Number', 'Website'
    ];
    const sampleData = [[
        'Tech Solutions Inc.',
        'Tech Solutions International Ltd.',
        'John Doe',
        'Sales Director',
        '01712345678',
        'john.doe@techsolutions.com',
        '02-9881234',
        'info@techsolutions.com',
        'House 45, Road 12, Block C, Banani, Dhaka-1213',
        '123456789012',
        'BIN-987654321',
        'TRADE-2024-00123',
        'GST-123456789012',
        'https://www.techsolutions.com'
    ]];
    
    let csvContent = headers.map(h => `"${h}"`).join(',') + '\n';
    sampleData.forEach(row => {
        csvContent += row.map(cell => `"${cell}"`).join(',') + '\n';
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'vendor_template.csv';
    link.click();
    URL.revokeObjectURL(blob);
}
</script>

<?php include '../../includes/footer.php'; ?>