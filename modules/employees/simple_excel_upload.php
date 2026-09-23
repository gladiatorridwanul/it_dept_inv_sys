<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Simple CSV/Excel upload without PhpSpreadsheet
if(isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == 0) {
    $file = $_FILES['csv_file']['tmp_name'];
    $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
    
    if($extension == 'csv') {
        $handle = fopen($file, "r");
        $header = fgetcsv($handle); // Read header row
        
        $count = 0;
        while(($data = fgetcsv($handle)) !== false) {
            if(!empty($data[0]) && !empty($data[1])) {
                $pf_no = trim($data[0]);
                $full_name = trim($data[1]);
                $designation = trim($data[2] ?? '');
                $job_location = trim($data[3] ?? '');
                $department = trim($data[4] ?? '');
                $phone = trim($data[5] ?? '');
                $email = trim($data[6] ?? '');
                $joining_date = !empty($data[7]) ? date('Y-m-d', strtotime($data[7])) : date('Y-m-d');
                
                // Check if exists
                $stmt = $pdo->prepare("SELECT id FROM employees WHERE pf_no = ?");
                $stmt->execute([$pf_no]);
                
                if($stmt->fetch()) {
                    // Update
                    $stmt = $pdo->prepare("UPDATE employees SET full_name=?, designation=?, job_location=?, department=?, phone=?, email=?, joining_date=? WHERE pf_no=?");
                    $stmt->execute([$full_name, $designation, $job_location, $department, $phone, $email, $joining_date, $pf_no]);
                } else {
                    // Insert
                    $stmt = $pdo->prepare("INSERT INTO employees (pf_no, full_name, designation, job_location, department, phone, email, joining_date) VALUES (?,?,?,?,?,?,?,?)");
                    $stmt->execute([$pf_no, $full_name, $designation, $job_location, $department, $phone, $email, $joining_date]);
                }
                $count++;
            }
        }
        fclose($handle);
        echo '<div class="alert alert-success">' . $count . ' employees imported successfully from CSV!</div>';
    } else {
        echo '<div class="alert alert-danger">Please upload CSV file format only. For Excel files, please convert to CSV first.</div>';
    }
}

// Download CSV Template
if(isset($_GET['download_template'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="employee_template.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['PF No', 'Full Name', 'Designation', 'Job Location', 'Department', 'Phone', 'Email', 'Joining Date']);
    fputcsv($output, ['EMP001', 'John Doe', 'Software Engineer', 'Mumbai', 'IT', '9876543210', 'john@example.com', '2024-01-01']);
    fputcsv($output, ['EMP002', 'Jane Smith', 'HR Manager', 'Delhi', 'HR', '9876543211', 'jane@example.com', '2024-01-15']);
    fclose($output);
    exit();
}
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><i class="fas fa-file-excel"></i> Simple Excel/CSV Upload (No Composer Required)</h2>
        </div>
    </div>
    
    <div class="alert alert-info">
        <strong><i class="fas fa-info-circle"></i> CSV Upload Instructions:</strong>
        <ul class="mb-0">
            <li>Download the CSV template using the button below</li>
            <li>Fill in employee details in the CSV file</li>
            <li>Save as CSV format (UTF-8)</li>
            <li>Upload using the form below</li>
            <li>For Excel files (.xlsx), first convert to CSV format using Excel: File → Save As → CSV UTF-8</li>
        </ul>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5><i class="fas fa-upload"></i> Upload Employee CSV</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select CSV File</label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    <small class="text-muted">Only CSV files are supported. Max size: 5MB</small>
                </div>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-upload"></i> Upload & Import
                </button>
                <a href="?download_template=1" class="btn btn-info">
                    <i class="fas fa-download"></i> Download CSV Template
                </a>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5><i class="fas fa-question-circle"></i> Alternative: Manual Entry</h5>
        </div>
        <div class="card-body">
            <p>If you're having trouble with CSV upload, you can:</p>
            <ol>
                <li>Use the <strong>"Add Employee"</strong> button on the main employees page to add individually</li>
                <li><strong>Install Composer</strong> and PhpSpreadsheet for Excel file support</li>
                <li><strong>Convert Excel to CSV</strong> first, then upload CSV file</li>
            </ol>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Employee Manually
            </a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>