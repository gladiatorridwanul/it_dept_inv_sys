<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-t');

$stmt = $pdo->prepare("SELECT a.*, e.full_name, e.pf_no, e.designation, e.department, i.name as item_name, i.item_code 
                       FROM assignments a 
                       JOIN employees e ON a.employee_id=e.id 
                       JOIN items i ON a.item_id=i.id 
                       WHERE a.assigned_date BETWEEN ? AND ?
                       ORDER BY a.assigned_date DESC");
$stmt->execute([$from_date, $to_date]);
$assignments = $stmt->fetchAll();
?>

<div class="container-fluid">
    <h2><i class="fas fa-chart-pie"></i> Assignment Report</h2>
    <hr>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-4">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                </div>
                <div class="col-md-4">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                </div>
                <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary form-control">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>Assignment Report (<?php echo date('d-m-Y', strtotime($from_date)); ?> to <?php echo date('d-m-Y', strtotime($to_date)); ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Assignment No</th>
                            <th>Employee</th>
                            <th>PF No</th>
                            <th>Department</th>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Assigned Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($assignments as $assign): ?>
                         </tr>
                            <td><?php echo $assign['assignment_no']; ?></td>
                            <td><?php echo htmlspecialchars($assign['full_name']); ?></td>
                            <td><?php echo $assign['pf_no']; ?></td>
                            <td><?php echo $assign['department']; ?></td>
                            <td><?php echo htmlspecialchars($assign['item_name']); ?><br><small><?php echo $assign['item_code']; ?></small></td>
                            <td><?php echo $assign['quantity']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($assign['assigned_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $assign['status'] == 'assigned' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($assign['status']); ?>
                                </span>
                            </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>