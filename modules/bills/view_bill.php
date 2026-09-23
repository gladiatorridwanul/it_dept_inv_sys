<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

// Fetch bill details with vendor information
$stmt = $pdo->prepare("SELECT b.*, v.name as vendor_name, v.contact_person, v.phone as vendor_phone, v.email as vendor_email, v.address as vendor_address,
                              u.full_name as created_by_name
                       FROM bills b 
                       LEFT JOIN vendors v ON b.vendor_id = v.id
                       LEFT JOIN users u ON b.created_by = u.id
                       WHERE b.id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if(!$bill) {
    echo '<div class="alert alert-danger m-4">Bill not found!</div>';
    include '../../includes/footer.php';
    exit();
}

// Get bill items (stock_in items associated with this bill)
$itemsStmt = $pdo->prepare("SELECT si.*, i.name as item_name, i.item_code, i.serial_number, i.model_number, i.brand
                            FROM stock_in si
                            JOIN items i ON si.item_id = i.id
                            WHERE si.bill_id = ?");
$itemsStmt->execute([$id]);
$billItems = $itemsStmt->fetchAll();

// Get payment history
$paymentsStmt = $pdo->prepare("SELECT * FROM bill_payments WHERE bill_id = ? ORDER BY payment_date DESC");
$paymentsStmt->execute([$id]);
$payments = $paymentsStmt->fetchAll();

// Get payment slips
$slipsStmt = $pdo->prepare("SELECT * FROM payment_slips WHERE bill_id = ? ORDER BY created_at DESC");
$slipsStmt->execute([$id]);
$paymentSlips = $slipsStmt->fetchAll();

// Calculate totals
$totalItems = array_sum(array_column($billItems, 'quantity'));
$totalPaid = $bill['paid_amount'] ?? 0;
$balanceDue = $bill['total_amount'] - $totalPaid;
$paymentCount = count($payments);
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

    /* Cards */
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

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.25rem 0.875rem;
        border-radius: 30px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-paid { background: #d1fae5; color: #065f46; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-partial { background: #cffafe; color: #0891b2; }

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

    /* Payment Item */
    .payment-item {
        background: var(--gray-50);
        border-radius: 12px;
        padding: 0.875rem;
        margin-bottom: 0.75rem;
        border: 1px solid var(--gray-200);
    }

    /* Attachment Link */
    .attachment-link {
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
        .stat-number {
            font-size: 1.2rem;
        }
    }
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4><i class="fas fa-file-invoice-dollar me-2"></i>Bill Details</h4>
                <p>Complete bill information, items, and payment history</p>
            </div>
            <div class="mt-2 mt-md-0">
                <a href="list_bills.php" class="btn btn-light btn-sm rounded-pill px-3 me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to List
                </a>
                <?php if($balanceDue > 0): ?>
                <a href="make_payment.php?id=<?php echo $bill['id']; ?>" class="btn btn-success btn-sm rounded-pill px-3 me-2">
                    <i class="fas fa-money-bill-wave me-1"></i> Make Payment
                </a>
                <?php endif; ?>
                <a href="generate_payment_slip.php?id=<?php echo $bill['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="fas fa-print me-1"></i> Print
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-primary"><i class="fas fa-boxes"></i></div>
                <div class="stat-number"><?php echo $totalItems; ?></div>
                <div class="stat-label">Total Items</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-success"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-number">৳<?php echo number_format($bill['total_amount'], 2); ?></div>
                <div class="stat-label">Total Amount</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-info"><i class="fas fa-hand-holding-usd"></i></div>
                <div class="stat-number">৳<?php echo number_format($totalPaid, 2); ?></div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="stat-card">
                <div class="stat-icon text-warning"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-number">৳<?php echo number_format($balanceDue, 2); ?></div>
                <div class="stat-label">Balance Due</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Bill Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-info-circle"></i> Bill Information
                </div>
                <div class="info-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-hashtag"></i> Bill Number</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($bill['bill_no']); ?></strong></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-alt"></i> Bill Date</div>
                            <div class="info-value"><?php echo date('d-M-Y', strtotime($bill['bill_date'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-hourglass-end"></i> Due Date</div>
                            <div class="info-value"><?php echo $bill['due_date'] ? date('d-M-Y', strtotime($bill['due_date'])) : '—'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-calendar-check"></i> Bill Received Date</div>
                            <div class="info-value"><?php echo $bill['bill_received_date'] ? date('d-M-Y', strtotime($bill['bill_received_date'])) : '—'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-tag"></i> Status</div>
                            <div class="info-value">
                                <span class="status-badge status-<?php echo $bill['status']; ?>">
                                    <i class="fas fa-<?php echo $bill['status'] == 'paid' ? 'check-circle' : ($bill['status'] == 'partial' ? 'clock' : 'exclamation-circle'); ?>"></i>
                                    <?php echo ucfirst($bill['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-user"></i> Created By</div>
                            <div class="info-value"><?php echo htmlspecialchars($bill['created_by_name'] ?? 'System'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vendor Information -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-truck"></i> Vendor Information
                </div>
                <div class="info-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Vendor Name</div>
                            <div class="info-value">
                                <strong><?php echo htmlspecialchars($bill['vendor_name'] ?? '—'); ?></strong>
                            </div>
                        </div>
                        <?php if(!empty($bill['contact_person'])): ?>
                        <div class="info-item">
                            <div class="info-label">Contact Person</div>
                            <div class="info-value"><?php echo htmlspecialchars($bill['contact_person']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($bill['vendor_phone'])): ?>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo htmlspecialchars($bill['vendor_phone']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($bill['vendor_email'])): ?>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($bill['vendor_email']); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(!empty($bill['vendor_address'])): ?>
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($bill['vendor_address'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bill Items -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-boxes"></i> Bill Items (<?php echo count($billItems); ?> items)
                </div>
                <div class="table-responsive">
                    <table class="details-table">
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Item Code</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Serial No</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($billItems) > 0): ?>
                                <?php foreach($billItems as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                    <td><?php echo $item['item_code']; ?></td>
                                    <td><?php echo htmlspecialchars($item['brand'] ?? '—'); ?></td>
                                    <td><?php echo $item['model_number'] ?? '—'; ?></td>
                                    <td><?php echo $item['serial_number'] ?? '—'; ?></td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td>৳<?php echo number_format($item['unit_price'], 2); ?></td>
                                    <td><strong>৳<?php echo number_format($item['total_amount'], 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3 text-muted">No items found for this bill.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(count($billItems) > 0): ?>
                        <tfoot>
                            <tr>
                                <td colspan="7" class="text-end"><strong>Grand Total:</strong></td>
                                <td><strong>৳<?php echo number_format($bill['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Payment History -->
            <?php if(count($payments) > 0 || count($paymentSlips) > 0): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-history"></i> Payment History (<?php echo $paymentCount; ?> payments)
                </div>
                <div class="info-card-body">
                    <?php foreach($payments as $payment): ?>
                    <div class="payment-item">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <strong><i class="fas fa-money-bill-wave text-success"></i> <?php echo ucfirst($payment['payment_mode']); ?></strong>
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('d-M-Y', strtotime($payment['payment_date'])); ?>
                                    <?php if($payment['cheque_no']): ?>
                                        <span class="mx-2">|</span> <i class="fas fa-money-check"></i> Cheque: <?php echo $payment['cheque_no']; ?>
                                    <?php endif; ?>
                                    <?php if($payment['transaction_id']): ?>
                                        <span class="mx-2">|</span> <i class="fas fa-exchange-alt"></i> TXN: <?php echo $payment['transaction_id']; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-success">৳<?php echo number_format($payment['amount'], 2); ?></span>
                            </div>
                        </div>
                        <?php if($payment['notes']): ?>
                        <div class="small text-muted mt-2"><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($payment['notes']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php foreach($paymentSlips as $slip): ?>
                    <div class="payment-item">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div>
                                <strong><i class="fas fa-file-invoice text-info"></i> Payment Slip: <?php echo $slip['slip_no']; ?></strong>
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-calendar-alt"></i> <?php echo date('d-M-Y', strtotime($slip['payment_date'])); ?>
                                    <span class="mx-2">|</span> <i class="fas fa-money-bill-wave"></i> <?php echo ucfirst($slip['payment_mode']); ?>
                                </div>
                            </div>
                            <div>
                                <span class="badge bg-info">৳<?php echo number_format($slip['payment_amount'], 2); ?></span>
                            </div>
                        </div>
                        <?php if($slip['slip_document']): ?>
                        <div class="mt-2">
                            <a href="/it-inventory/<?php echo $slip['slip_document']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-download"></i> View Slip
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Payment Summary Card -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-chart-pie"></i> Payment Summary
                </div>
                <div class="info-card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small">Bill Amount</span>
                        <strong class="small">৳<?php echo number_format($bill['total_amount'], 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="small">Total Paid</span>
                        <strong class="small text-success">৳<?php echo number_format($totalPaid, 2); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="small">Balance Due</span>
                        <strong class="small text-<?php echo $balanceDue > 0 ? 'danger' : 'success'; ?>">
                            ৳<?php echo number_format($balanceDue, 2); ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- Bill Attachment -->
            <?php if(!empty($bill['bill_attachment'])): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-paperclip"></i> Bill Attachment
                </div>
                <div class="info-card-body">
                    <div class="attachment-link">
                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                        <div>
                            <strong><?php echo basename($bill['bill_attachment']); ?></strong>
                            <br>
                            <a href="/it-inventory/<?php echo $bill['bill_attachment']; ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-bolt"></i> Quick Actions
                </div>
                <div class="info-card-body">
                    <div class="d-grid gap-2">
                        <?php if($balanceDue > 0): ?>
                        <a href="make_payment.php?id=<?php echo $bill['id']; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-money-bill-wave"></i> Make Payment
                        </a>
                        <?php endif; ?>
                        <a href="generate_payment_slip.php?id=<?php echo $bill['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-print"></i> Generate Payment Slip
                        </a>
                        <a href="generate_acknowledgement.php?id=<?php echo $bill['id']; ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-file-signature"></i> Generate Acknowledgement
                        </a>
                        <?php if($bill['vendor_id']): ?>
                        <a href="../vendors/view.php?id=<?php echo $bill['vendor_id']; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-truck"></i> View Vendor Details
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <?php if(!empty($bill['notes'])): ?>
            <div class="info-card">
                <div class="info-card-header">
                    <i class="fas fa-sticky-note"></i> Notes
                </div>
                <div class="info-card-body">
                    <div class="info-value small"><?php echo nl2br(htmlspecialchars($bill['notes'])); ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>