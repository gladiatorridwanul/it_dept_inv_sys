<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

// Fetch vendor details with statistics
$stmt = $pdo->prepare("SELECT v.*, 
                              (SELECT COUNT(*) FROM bills WHERE vendor_id = v.id) as bill_count,
                              (SELECT COALESCE(SUM(total_amount), 0) FROM bills WHERE vendor_id = v.id) as total_purchase,
                              (SELECT COALESCE(SUM(paid_amount), 0) FROM bills WHERE vendor_id = v.id) as total_paid,
                              (SELECT MAX(bill_date) FROM bills WHERE vendor_id = v.id) as last_bill_date
                       FROM vendors v WHERE v.id = ?");
$stmt->execute([$id]);
$vendor = $stmt->fetch();

if(!$vendor) {
    echo '<div class="alert alert-danger m-4">Vendor not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get recent bills from this vendor
$recentBills = $pdo->prepare("SELECT * FROM bills WHERE vendor_id = ? ORDER BY bill_date DESC LIMIT 5");
$recentBills->execute([$id]);
$recentBills = $recentBills->fetchAll();
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-600: #475569;
        --gray-700: #334155;
    }

    /* Page Header */
    .page-header {
        background: var(--primary-gradient);
        color: white;
        padding: 1.5rem 2rem;
        border-radius: 20px;
        margin-bottom: 1.5rem;
    }
    .page-header h4 {
        margin-bottom: 0.25rem;
        font-weight: 600;
    }
    .page-header p {
        margin-bottom: 0;
        opacity: 0.9;
        font-size: 0.8rem;
    }

    /* Info Cards */
    .info-card {
        background: white;
        border-radius: 18px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .info-card-header {
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: var(--gray-50);
    }
    .info-card-header i {
        margin-right: 8px;
        color: #667eea;
    }
    .info-card-body {
        padding: 1.25rem;
    }

    /* Stats Cards */
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 1rem;
        text-align: center;
        border: 1px solid var(--gray-200);
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    }
    .stat-icon {
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
    }
    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .stat-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
    }

    /* Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    .info-item {
        background: var(--gray-50);
        border-radius: 14px;
        padding: 0.875rem 1rem;
        border: 1px solid var(--gray-200);
    }
    .info-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        margin-bottom: 0.25rem;
    }
    .info-value {
        font-size: 0.85rem;
        font-weight: 500;
        color: #1e293b;
        word-break: break-word;
    }
    .info-value a {
        color: #3b82f6;
        text-decoration: none;
    }
    .info-value a:hover {
        text-decoration: underline;
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.25rem 0.875rem;
        border-radius: 30px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-active { background: #d1fae5; color: #065f46; }
    .status-inactive { background: #fee2e2; color: #dc2626; }

    /* Tables */
    .details-table {
        width: 100%;
        margin-bottom: 0;
    }
    .details-table th {
        background: var(--gray-50);
        font-size: 0.65rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        color: var(--gray-600);
        padding: 0.75rem;
        border-bottom: 1px solid var(--gray-200);
    }
    .details-table td {
        font-size: 0.75rem;
        padding: 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--gray-100);
    }

    /* Document Link */
    .document-link {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        .page-header {
            padding: 1rem;
        }
    }
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4><i class="fas fa-truck me-2"></i>Vendor Details</h4>
                <p>Complete vendor information and transaction history</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="list.php" class="btn btn-light btn-sm rounded-pill px-3 me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
                <a href="edit.php?id=<?php echo $vendor['id']; ?>" class="btn btn-warning btn-sm rounded-pill px-3 me-2">
                    <i class="fas fa-edit me-1"></i> Edit Vendor
                </a>
                <a href="../bills/receive_bill.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="fas fa-download me-1"></i> Receive Bill
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-primary"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-number"><?php echo $vendor['bill_count']; ?></div>
                <div class="stat-label">Total Bills</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-success"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-number">৳<?php echo number_format($vendor['total_purchase'], 0); ?></div>
                <div class="stat-label">Total Purchase</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-info"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="stat-number">৳<?php echo number_format($vendor['total_paid'], 0); ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-warning"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-number">৳<?php echo number_format($vendor['total_purchase'] - $vendor['total_paid'], 0); ?></div>
                <div class="stat-label">Balance Due</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Company Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-building"></i> Company Information
                </div>
                <div class="info-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-tag"></i> Vendor Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['vendor_name'] ?? $vendor['name']); ?></div>
                        </div>
                        <?php if(!empty($vendor['company_name'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-building"></i> Company Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['company_name']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($vendor['office_address']) || !empty($vendor['address'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Office Address</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($vendor['office_address'] ?? $vendor['address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($vendor['website'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-globe"></i> Website</div>
                            <div class="info-value">
                                <a href="<?php echo htmlspecialchars($vendor['website']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($vendor['website']); ?> <i class="fas fa-external-link-alt fa-xs"></i>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-address-card"></i> Contact Information
                </div>
                <div class="info-card-body">
                    <div class="info-grid">
                        <!-- Office Contact -->
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone-office"></i> Office Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['office_phone'] ?? $vendor['phone'] ?? '—'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> Office Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['office_email'] ?? $vendor['email'] ?? '—'); ?></div>
                        </div>
                        
                        <!-- Primary Contact -->
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user-tie"></i> Primary Contact</div>
                            <div class="info-value">
                                <strong><?php echo htmlspecialchars($vendor['contact_person'] ?? '—'); ?></strong>
                                <?php if(!empty($vendor['contact_designation'])): ?>
                                    <br><span class="text-muted"><?php echo htmlspecialchars($vendor['contact_designation']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-phone-alt"></i> Contact Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['contact_phone'] ?? $vendor['phone'] ?? '—'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-envelope"></i> Contact Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['contact_email'] ?? $vendor['email'] ?? '—'); ?></div>
                        </div>
                        <?php if(!empty($vendor['contact_address'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-home"></i> Contact Address</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($vendor['contact_address'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Secondary Contact -->
                        <?php if(!empty($vendor['secondary_contact_person'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user-friends"></i> Secondary Contact</div>
                            <div class="info-value">
                                <strong><?php echo htmlspecialchars($vendor['secondary_contact_person']); ?></strong>
                                <?php if(!empty($vendor['secondary_contact_designation'])): ?>
                                    <br><span class="text-muted"><?php echo htmlspecialchars($vendor['secondary_contact_designation']); ?></span>
                                <?php endif; ?>
                                <?php if(!empty($vendor['secondary_contact_phone'])): ?>
                                    <br><i class="fas fa-phone"></i> <?php echo htmlspecialchars($vendor['secondary_contact_phone']); ?>
                                <?php endif; ?>
                                <?php if(!empty($vendor['secondary_contact_email'])): ?>
                                    <br><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($vendor['secondary_contact_email']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tax & License Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-file-invoice-dollar"></i> Tax & License Information
                </div>
                <div class="info-card-body">
                    <div class="info-grid">
                        <?php if(!empty($vendor['tin_no'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-id-card"></i> TIN Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['tin_no']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($vendor['bin_no'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-barcode"></i> BIN Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['bin_no']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($vendor['trade_license_no'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-certificate"></i> Trade License No</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['trade_license_no']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($vendor['gst_no'])): ?>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-file-invoice"></i> GST Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($vendor['gst_no']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(empty($vendor['tin_no']) && empty($vendor['bin_no']) && empty($vendor['trade_license_no']) && empty($vendor['gst_no'])): ?>
                        <div class="info-item">
                            <div class="info-value text-muted">No tax or license information available.</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Attached Document -->
            <?php if(!empty($vendor['attached_document'])): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-paperclip"></i> Attached Document
                </div>
                <div class="info-card-body">
                    <div class="document-link">
                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                        <div>
                            <strong><?php echo basename($vendor['attached_document']); ?></strong>
                            <br>
                            <a href="/it-inventory/<?php echo $vendor['attached_document']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="fas fa-download"></i> Download Document
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes -->
            <?php if(!empty($vendor['notes'])): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-sticky-note"></i> Notes / Remarks
                </div>
                <div class="info-card-body">
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($vendor['notes'])); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Status Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-chart-line"></i> Status Overview
                </div>
                <div class="info-card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Current Status</span>
                        <span class="status-badge <?php echo $vendor['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <i class="fas fa-<?php echo $vendor['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo $vendor['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small">Vendor ID</span>
                        <strong class="small">#<?php echo $vendor['id']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small">Registered On</span>
                        <strong class="small"><?php echo date('d-M-Y', strtotime($vendor['created_at'])); ?></strong>
                    </div>
                    <?php if($vendor['last_bill_date']): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small">Last Bill Date</span>
                        <strong class="small"><?php echo date('d-M-Y', strtotime($vendor['last_bill_date'])); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <span class="small">Total Bills</span>
                        <strong class="small"><?php echo $vendor['bill_count']; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-bolt"></i> Quick Actions
                </div>
                <div class="info-card-body">
                    <div class="d-grid gap-2">
                        <a href="edit.php?id=<?php echo $vendor['id']; ?>" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit Vendor
                        </a>
                        <a href="../bills/receive_bill.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> Receive New Bill
                        </a>
                        <a href="../bills/list_bills.php?vendor=<?php echo $vendor['id']; ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-file-invoice"></i> View All Bills
                        </a>
                        <?php if($vendor['is_active']): ?>
                        <a href="list.php?toggle=1&id=<?php echo $vendor['id']; ?>" class="btn btn-outline-danger btn-sm" 
                           onclick="return confirm('Deactivate this vendor?')">
                            <i class="fas fa-ban"></i> Deactivate Vendor
                        </a>
                        <?php else: ?>
                        <a href="list.php?toggle=1&id=<?php echo $vendor['id']; ?>" class="btn btn-outline-success btn-sm"
                           onclick="return confirm('Activate this vendor?')">
                            <i class="fas fa-check"></i> Activate Vendor
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bills -->
    <?php if(count($recentBills) > 0): ?>
    <div class="info-card">
        <div class="info-card-header">
            <i class="fas fa-history"></i> Recent Bills (Last 5)
        </div>
        <div class="table-responsive">
            <table class="details-table">
                <thead>
                    <tr>
                        <th>Bill No</th>
                        <th>Bill Date</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Status</th>
                        <th width="80">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentBills as $bill): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong></td>
                        <td><?php echo date('d-M-Y', strtotime($bill['bill_date'])); ?></td>
                        <td>৳<?php echo number_format($bill['total_amount'], 2); ?></td>
                        <td>৳<?php echo number_format($bill['paid_amount'], 2); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $bill['status'] == 'paid' ? 'success' : ($bill['status'] == 'partial' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($bill['status']); ?>
                            </span>
                         </div>
                        <td>
                            <a href="../bills/view_bill.php?id=<?php echo $bill['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                         </div>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>