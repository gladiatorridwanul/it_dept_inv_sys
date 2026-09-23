<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Accept request
if(isset($_GET['accept']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE requests SET status = 'approved', accepted_date = NOW(), accepted_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    echo '<div class="alert alert-success">Request approved successfully!</div>';
}

// Reject request
if(isset($_GET['reject']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE requests SET status = 'rejected', accepted_date = NOW(), accepted_by = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $id]);
    echo '<div class="alert alert-warning">Request rejected!</div>';
}

$stmt = $pdo->query("SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, i.name as item_name, i.item_code 
                    FROM requests r 
                    JOIN employees e ON r.employee_id=e.id 
                    LEFT JOIN items i ON r.item_id=i.id 
                    WHERE r.status = 'pending' 
                    ORDER BY r.requested_date ASC");
$requests = $stmt->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-clock"></i> Pending Requests</h2>
    <hr>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Request No</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Department</th>
                            <th>Request Type</th>
                            <th>Requested Date</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($requests as $req): ?>
                         </tr>
                            <td><?php echo $req['request_no']; ?></td>
                            <td><?php echo htmlspecialchars($req['full_name']); ?></td>
                            <td><?php echo $req['pf_no']; ?></td>
                            <td><?php echo $req['department']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $req['request_type'] == 'upgrade' ? 'warning' : 
                                        ($req['request_type'] == 'replace' ? 'danger' : 'info'); 
                                ?>">
                                    <?php echo ucfirst($req['request_type']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d-m-Y H:i', strtotime($req['requested_date'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars(substr($req['description'], 0, 100))); ?></td>
                            <td>
                                <a href="?accept=1&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Accept this request?')">
                                    <i class="fas fa-check"></i> Accept
                                </a>
                                <a href="?reject=1&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this request?')">
                                    <i class="fas fa-times"></i> Reject
                                </a>
                                <button class="btn btn-sm btn-info" onclick="viewDetails(<?php echo $req['id']; ?>)">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetails(id) {
    // You can implement a modal to show full details
    alert('View details for request ID: ' + id);
}
</script>

<?php include '../../includes/footer.php'; ?>