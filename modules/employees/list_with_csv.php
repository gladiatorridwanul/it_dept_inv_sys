<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle CSV Upload (No Composer Required)
if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file']['tmp_name'];
    
    if(($handle = fopen($file, "r")) !== false) {
        // Skip header row
        $header = fgetcsv($handle);
        
        $count = 0;
        $errors = [];
        
        while(($data = fgetcsv($handle, 1000, ",")) !== false) {
            if(count($data) >= 2 && !empty($data[0]) && !empty($data[1])) {
                $pf_no = trim($data[0]);
                $full_name = trim($data[1]);
                $designation = trim($data[2] ?? '');
                $job_location = trim($data[3] ?? '');
                $department = trim($data[4] ?? '');
                $phone = trim($data[5] ?? '');
                $email = trim($data[6] ?? '');
                $joining_date = !empty($data[7]) ? date('Y-m-d', strtotime($data[7])) : date('Y-m-d');
                
                // Check if employee exists
                $check = $pdo->prepare("SELECT id FROM employees WHERE pf_no = ?");
                $check->execute([$pf_no]);
                
                if($check->fetch()) {
                    // Update existing
                    $stmt = $pdo->prepare("UPDATE employees SET full_name=?, designation=?, job_location=?, department=?, phone=?, email=?, joining_date=? WHERE pf_no=?");
                    $stmt->execute([$full_name, $designation, $job_location, $department, $phone, $email, $joining_date, $pf_no]);
                } else {
                    // Insert new
                    $stmt = $pdo->prepare("INSERT INTO employees (pf_no, full_name, designation, job_location, department, phone, email, joining_date) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$pf_no, $full_name, $designation, $job_location, $department, $phone, $email, $joining_date]);
                }
                $count++;
            } else {
                $errors[] = "Row " . ($count + 2) . " has missing required fields";
            }
        }
        fclose($handle);
        
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> ' . $count . ' employees imported/updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        
        if(!empty($errors)) {
            echo '<div class="alert alert-warning">
                    <strong>Warnings:</strong><br>' . implode('<br>', $errors) . '
                  </div>';
        }
    }
}

// Download CSV Template
if(isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employee_template.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['PF No', 'Full Name', 'Designation', 'Job Location', 'Department', 'Phone', 'Email', 'Joining Date']);
    fputcsv($output, ['EMP001', 'John Doe', 'Software Engineer', 'Mumbai', 'IT', '9876543210', 'john@example.com', date('Y-m-d')]);
    fputcsv($output, ['EMP002', 'Jane Smith', 'HR Manager', 'Delhi', 'HR', '9876543211', 'jane@example.com', date('Y-m-d')]);
    fclose($output);
    exit();
}

// Handle Active/De-Active
if(isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE employees SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">Employee status updated!</div>';
}

// Delete employee
if(isset($_GET['delete']) && isset($_GET['id']) && isAdmin()) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">Employee deleted successfully!</div>';
}

$search = $_GET['search'] ?? '';
$where = $search ? "WHERE pf_no LIKE '%$search%' OR full_name LIKE '%$search%' OR phone LIKE '%$search%'" : '';
$employees = $pdo->query("SELECT * FROM employees $where ORDER BY full_name")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-users"></i> Employees Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#csvModal">
                <i class="fas fa-file-csv"></i> CSV Upload
            </button>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Employee
            </a>
        </div>
    </div>
    
    <!-- CSV Upload Modal -->
    <div class="modal fade" id="csvModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-file-csv"></i> Upload Employee CSV</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Instructions:</strong>
                            <ol class="mb-0 mt-2">
                                <li>Download the CSV template using the button below</li>
                                <li>Open in Excel/Notepad and fill in employee data</li>
                                <li>Save as CSV (UTF-8) format</li>
                                <li>Upload the CSV file</li>
                            </ol>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">CSV File Format Required:</label>
                            <table class="table table-sm table-bordered">
                                <tr class="table-primary">
                                    <th>PF No*</th><th>Full Name*</th><th>Designation</th><th>Location</th>
                                    <th>Dept</th><th>Phone</th><th>Email</th><th>Joining Date</th>
                                </tr>
                                <tr>
                                    <td>EMP001</td><td>John Doe</td><td>Engineer</td><td>Mumbai</td>
                                    <td>IT</td><td>1234567890</td><td>john@email.com</td><td>2024-01-01</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Select CSV File</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                            <small class="text-muted">Max size: 5MB. UTF-8 encoding recommended.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="?download_template=1" class="btn btn-info">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Upload
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Search -->
    <div class="row mb-3">
        <div class="col-md-6">
            <form method="GET" class="d-flex">
                <input type="text" name="search" class="form-control me-2" 
                       placeholder="Search by PF No, Name, Phone..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if($search): ?>
                    <a href="list.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    
    <!-- Employees Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>PF No</th><th>Full Name</th><th>Designation</th><th>Department</th>
                            <th>Phone</th><th>Email</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td><?php echo $emp['pf_no']; ?></td>
                            <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                            <td><?php echo $emp['designation'] ?? '-'; ?></td>
                            <td><?php echo $emp['department'] ?? '-'; ?></td>
                            <td><?php echo $emp['phone'] ?? '-'; ?></td>
                            <td><?php echo $emp['email'] ?? '-'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $emp['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $emp['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="assignments.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-laptop"></i>
                                </a>
                                <a href="?toggle=1&id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-<?php echo $emp['is_active'] ? 'ban' : 'check'; ?>"></i>
                                </a>
                                <?php if(isAdmin()): ?>
                                <a href="?delete=1&id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger delete-confirm">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
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
$(document).ready(function() {
    $('.delete-confirm').click(function(e) {
        if(!confirm('Are you sure you want to delete this employee?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>