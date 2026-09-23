<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$stmt = $pdo->prepare("SELECT r.*, i.name as item_name 
                       FROM requests r 
                       LEFT JOIN items i ON r.item_id=i.id 
                       WHERE r.employee_id IN (SELECT id FROM employees WHERE is_active=1)
                       ORDER BY r.id DESC");
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-history"></i> My Requests</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Request No</th>
                            <th>Type</th>
                            <th>Item</th>
                            <th>Requested Date</th>
                            <th>Status</th>
                            <th>Accepted Date</th>
                         </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                         </tr>
                            <td><?php echo $req['request_no']; ?></td>
                            <td><?php echo ucfirst($req['request_type']); ?></td>
                            <td><?php echo htmlspecialchars($req['item_name'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($req['requested_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $req['status'] == 'approved' ? 'success' : 
                                        ($req['status'] == 'rejected' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($req['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $req['accepted_date'] ? date('d-m-Y H:i', strtotime($req['accepted_date'])) : '-'; ?></td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>