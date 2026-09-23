<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

// Handle Excel Upload
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
                
                if(!empty($row[0]) && !empty($row[1])) {
                    $pf_no = trim($row[0]);
                    $full_name = trim($row[1]);
                    $designation = trim($row[2] ?? '');
                    $job_location = trim($row[3] ?? '');
                    $company = trim($row[4] ?? '');
                    $department = trim($row[5] ?? '');
                    $phone = trim($row[6] ?? '');
                    $email = trim($row[7] ?? '');
                    $joining_date = !empty($row[8]) ? date('Y-m-d', strtotime($row[8])) : date('Y-m-d');
                    
                    $stmt = $pdo->prepare("SELECT id FROM employees WHERE pf_no = ?");
                    $stmt->execute([$pf_no]);
                    $existing = $stmt->fetch();
                    
                    if($existing) {
                        $stmt = $pdo->prepare("UPDATE employees SET full_name=?, designation=?, job_location=?, company=?, department=?, phone=?, email=?, joining_date=? WHERE pf_no=?");
                        $stmt->execute([$full_name, $designation, $job_location, $company, $department, $phone, $email, $joining_date, $pf_no]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO employees (pf_no, full_name, designation, job_location, company, department, phone, email, joining_date) 
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$pf_no, $full_name, $designation, $job_location, $company, $department, $phone, $email, $joining_date]);
                    }
                    $count++;
                }
            }
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> ' . $count . ' employees imported/updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        } catch(Exception $e) {
            echo '<div class="alert alert-danger">Error reading Excel file: ' . $e->getMessage() . '</div>';
        }
    } else {
        echo '<div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> PhpSpreadsheet library not installed.
              </div>';
    }
}

// Handle Active/De-Active
if(isset($_GET['toggle']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE employees SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> Employee status updated!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>';
}

// Delete employee
if(isset($_GET['delete']) && isset($_GET['id']) && isAdmin()) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignments WHERE employee_id = ? AND status = 'assigned'");
    $stmt->execute([$id]);
    $assignments = $stmt->fetch();
    
    if($assignments['count'] > 0) {
        echo '<div class="alert alert-danger">Cannot delete employee with active assignments!</div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        echo '<div class="alert alert-success">Employee deleted successfully!</div>';
    }
}

// Get distinct departments, companies, and designations from database
$departments = $pdo->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department")->fetchAll(PDO::FETCH_COLUMN);
$companies = $pdo->query("SELECT DISTINCT company FROM employees WHERE company IS NOT NULL AND company != '' ORDER BY company")->fetchAll(PDO::FETCH_COLUMN);
$designations = $pdo->query("SELECT DISTINCT designation FROM employees WHERE designation IS NOT NULL AND designation != '' ORDER BY designation")->fetchAll(PDO::FETCH_COLUMN);

// Pagination and Filtering
$search = $_GET['search'] ?? '';
$department_filter = $_GET['department'] ?? '';
$company_filter = $_GET['company'] ?? '';
$designation_filter = $_GET['designation'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build WHERE clause for filtering
$where_clauses = [];
$params = [];

if($search) {
    $where_clauses[] = "(pf_no LIKE ? OR full_name LIKE ? OR phone LIKE ? OR email LIKE ? OR department LIKE ? OR job_location LIKE ? OR company LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($department_filter) {
    $where_clauses[] = "department = ?";
    $params[] = $department_filter;
}

if($company_filter) {
    $where_clauses[] = "company = ?";
    $params[] = $company_filter;
}

if($designation_filter) {
    $where_clauses[] = "designation = ?";
    $params[] = $designation_filter;
}

if($status_filter !== '') {
    $where_clauses[] = "is_active = ?";
    $params[] = ($status_filter == 'active') ? 1 : 0;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM employees $where_sql");
$countStmt->execute($params);
$total_records = $countStmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

// Get paginated employees
$stmt = $pdo->prepare("SELECT e.*, 
                              (SELECT COUNT(*) FROM assignments WHERE employee_id = e.id AND status = 'assigned') as active_assignments
                       FROM employees e $where_sql 
                       ORDER BY e.full_name 
                       LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Get stats for dashboard (based on all employees, not filtered)
$deptStmt = $pdo->query("SELECT department, COUNT(*) as count FROM employees WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY count DESC LIMIT 5");
$deptStats = $deptStmt->fetchAll();

$total_employees = $pdo->query("SELECT COUNT(*) as total FROM employees")->fetch()['total'];
$active_employees = $pdo->query("SELECT COUNT(*) as total FROM employees WHERE is_active = 1")->fetch()['total'];
$total_devices = $pdo->query("SELECT SUM(quantity) as total FROM assignments WHERE status = 'assigned'")->fetch()['total'] ?? 0;
$total_departments = $pdo->query("SELECT COUNT(DISTINCT department) as total FROM employees WHERE department IS NOT NULL AND department != ''")->fetch()['total'];

?>

<style>
    .stats-card {
        transition: transform 0.3s;
        cursor: pointer;
        border-radius: 12px;
    }
    .stats-card:hover {
        transform: translateY(-5px);
    }
    .table th {
        background: #f8f9fa;
        font-weight: 600;
    }
    .btn-group-sm .btn {
        margin: 0 2px;
    }
    .employee-row:hover {
        background-color: #f8f9fa;
    }
    .filter-badge {
        background: #e9ecef;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        margin-right: 8px;
        margin-bottom: 5px;
        display: inline-block;
    }
    .filter-badge .remove-filter {
        margin-left: 8px;
        cursor: pointer;
        color: #dc3545;
    }
    .filter-badge .remove-filter:hover {
        color: #a71d2a;
    }
    .pagination {
        margin-bottom: 0;
    }
    .page-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }
    .active-filters {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 20px;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h2><i class="fas fa-users text-primary"></i> Employees Management</h2>
                    <p class="text-muted">Manage employee information, track assignments, and bulk import via Excel</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#excelModal">
                        <i class="fas fa-file-excel"></i> Excel Upload
                    </button>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Employee
                    </a>
                    <a href="export.php" class="btn btn-info">
                        <i class="fas fa-download"></i> Export
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Employees</h6>
                            <h2 class="mb-0"><?php echo number_format($total_employees); ?></h2>
                        </div>
                        <div><i class="fas fa-users fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Active</h6>
                            <h2 class="mb-0"><?php echo number_format($active_employees); ?></h2>
                        </div>
                        <div><i class="fas fa-user-check fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Active Devices</h6>
                            <h2 class="mb-0"><?php echo number_format($total_devices); ?></h2>
                        </div>
                        <div><i class="fas fa-laptop fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Departments</h6>
                            <h2 class="mb-0"><?php echo number_format($total_departments); ?></h2>
                        </div>
                        <div><i class="fas fa-building fa-2x opacity-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Advanced Search & Filter Box -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="fas fa-filter me-2"></i> Advanced Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Keyword Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="PF No, Name, Phone, Email..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department_filter == $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Company</label>
                        <select name="company" class="form-select">
                            <option value="">All Companies</option>
                            <?php foreach($companies as $comp): ?>
                                <option value="<?php echo htmlspecialchars($comp); ?>" <?php echo $company_filter == $comp ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comp); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Designation</label>
                        <select name="designation" class="form-select">
                            <option value="">All Designations</option>
                            <?php foreach($designations as $desig): ?>
                                <option value="<?php echo htmlspecialchars($desig); ?>" <?php echo $designation_filter == $desig ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($desig); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-search me-1"></i> Apply Filters
                        </button>
                        <a href="list.php" class="btn btn-outline-secondary btn-sm px-4">
                            <i class="fas fa-times me-1"></i> Clear All
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Active Filters Display -->
    <?php if($search || $department_filter || $company_filter || $designation_filter || $status_filter): ?>
    <div class="active-filters mb-3">
        <span class="text-muted small me-2"><i class="fas fa-filter"></i> Active Filters:</span>
        <?php if($search): ?>
            <span class="filter-badge">Search: "<?php echo htmlspecialchars($search); ?>" 
                <a href="javascript:void(0)" onclick="removeFilter('search')" class="remove-filter"><i class="fas fa-times-circle"></i></a>
            </span>
        <?php endif; ?>
        <?php if($department_filter): ?>
            <span class="filter-badge">Department: <?php echo htmlspecialchars($department_filter); ?>
                <a href="javascript:void(0)" onclick="removeFilter('department')" class="remove-filter"><i class="fas fa-times-circle"></i></a>
            </span>
        <?php endif; ?>
        <?php if($company_filter): ?>
            <span class="filter-badge">Company: <?php echo htmlspecialchars($company_filter); ?>
                <a href="javascript:void(0)" onclick="removeFilter('company')" class="remove-filter"><i class="fas fa-times-circle"></i></a>
            </span>
        <?php endif; ?>
        <?php if($designation_filter): ?>
            <span class="filter-badge">Designation: <?php echo htmlspecialchars($designation_filter); ?>
                <a href="javascript:void(0)" onclick="removeFilter('designation')" class="remove-filter"><i class="fas fa-times-circle"></i></a>
            </span>
        <?php endif; ?>
        <?php if($status_filter): ?>
            <span class="filter-badge">Status: <?php echo ucfirst($status_filter); ?>
                <a href="javascript:void(0)" onclick="removeFilter('status')" class="remove-filter"><i class="fas fa-times-circle"></i></a>
            </span>
        <?php endif; ?>
        <span class="float-end text-muted small">Showing <?php echo count($employees); ?> of <?php echo $total_records; ?> employees</span>
    </div>
    <?php endif; ?>
    
    <!-- Excel Upload Modal -->
    <div class="modal fade" id="excelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-file-excel"></i> Bulk Employee Import</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong><i class="fas fa-info-circle"></i> Instructions:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Upload Excel file (.xlsx or .xls format)</li>
                                <li>File should have headers in first row</li>
                                <li>Supported columns: PF No, Full Name, Designation, Job Location, Company, Department, Phone, Email, Joining Date</li>
                                <li>Employees with existing PF No will be updated</li>
                            </ul>
                        </div>
                        <div class="mt-3">
                            <label class="form-label"><i class="fas fa-file-upload"></i> Select Excel/CSV File</label>
                            <input type="file" name="excel_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <div class="mt-3">
                            <a href="#" class="btn btn-sm btn-outline-secondary" onclick="downloadTemplate()">
                                <i class="fas fa-download"></i> Download Template
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Import Employees</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Employees Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Employee Directory</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>PF No</th>
                            <th>Full Name</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Job Location</th>
                            <th>Company</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Joining Date</th>
                            <th>Status</th>
                            <th width="160">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($employees) > 0): ?>
                            <?php foreach($employees as $emp): ?>
                            <tr class="employee-row">
                                <td>
                                    <strong><?php echo htmlspecialchars($emp['pf_no']); ?></strong>
                                    <?php if($emp['active_assignments'] > 0): ?>
                                        <br><small class="text-success"><?php echo $emp['active_assignments']; ?> device(s)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($emp['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['designation'] ?: '-'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($emp['department'] ?: '-'); ?></span></td>
                                <td><?php echo htmlspecialchars($emp['job_location'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['company'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['phone'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                <td><?php echo $emp['joining_date'] ? date('d-M-Y', strtotime($emp['joining_date'])) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $emp['is_active'] ? 'success' : 'danger'; ?>">
                                        <i class="fas fa-<?php echo $emp['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                        <?php echo $emp['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                 </td>
                                 <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="edit.php?id=<?php echo $emp['id']; ?>" class="btn btn-info" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="assignments.php?id=<?php echo $emp['id']; ?>" class="btn btn-primary" title="View Assets">
                                            <i class="fas fa-laptop"></i>
                                        </a>
                                        <a href="?toggle=1&id=<?php echo $emp['id']; ?>" class="btn btn-warning" 
                                           title="<?php echo $emp['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                           onclick="return confirm('Are you sure you want to <?php echo $emp['is_active'] ? 'deactivate' : 'activate'; ?> this employee?')">
                                            <i class="fas fa-<?php echo $emp['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        </a>
                                        <?php if(isAdmin() && $emp['active_assignments'] == 0): ?>
                                        <a href="?delete=1&id=<?php echo $emp['id']; ?>" class="btn btn-danger delete-confirm" 
                                           title="Delete" onclick="return confirm('Are you sure you want to delete this employee?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                             <tr>
                                <td colspan="11" class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x text-muted"></i>
                                    <p class="mt-2">No employees found matching your criteria.</p>
                                    <a href="add.php" class="btn btn-sm btn-primary">Add New Employee</a>
                                </td>
                             </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if($total_pages > 1): ?>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="text-muted small">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> employees
                </div>
                <nav aria-label="Employee pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $department_filter ? '&department='.urlencode($department_filter) : ''; ?><?php echo $company_filter ? '&company='.urlencode($company_filter) : ''; ?><?php echo $designation_filter ? '&designation='.urlencode($designation_filter) : ''; ?><?php echo $status_filter ? '&status='.urlencode($status_filter) : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        </li>
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        if($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $department_filter ? '&department='.urlencode($department_filter) : ''; ?><?php echo $company_filter ? '&company='.urlencode($company_filter) : ''; ?><?php echo $designation_filter ? '&designation='.urlencode($designation_filter) : ''; ?><?php echo $status_filter ? '&status='.urlencode($status_filter) : ''; ?>">1</a>
                            </li>
                            <?php if($start_page > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $department_filter ? '&department='.urlencode($department_filter) : ''; ?><?php echo $company_filter ? '&company='.urlencode($company_filter) : ''; ?><?php echo $designation_filter ? '&designation='.urlencode($designation_filter) : ''; ?><?php echo $status_filter ? '&status='.urlencode($status_filter) : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if($end_page < $total_pages): ?>
                            <?php if($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $department_filter ? '&department='.urlencode($department_filter) : ''; ?><?php echo $company_filter ? '&company='.urlencode($company_filter) : ''; ?><?php echo $designation_filter ? '&designation='.urlencode($designation_filter) : ''; ?><?php echo $status_filter ? '&status='.urlencode($status_filter) : ''; ?>"><?php echo $total_pages; ?></a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $department_filter ? '&department='.urlencode($department_filter) : ''; ?><?php echo $company_filter ? '&company='.urlencode($company_filter) : ''; ?><?php echo $designation_filter ? '&designation='.urlencode($designation_filter) : ''; ?><?php echo $status_filter ? '&status='.urlencode($status_filter) : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function downloadTemplate() {
    const headers = ['PF No', 'Full Name', 'Designation', 'Job Location', 'Company', 'Department', 'Phone', 'Email', 'Joining Date'];
    const sampleData = [
        ['EMP001', 'John Doe', 'Software Developer', 'Mumbai', 'ABC Corp', 'IT', '9876543210', 'john.doe@company.com', '2024-01-01'],
        ['EMP002', 'Jane Smith', 'HR Manager', 'Delhi', 'ABC Corp', 'HR', '9876543211', 'jane.smith@company.com', '2024-01-15']
    ];
    
    let csvContent = headers.join(',') + '\n';
    sampleData.forEach(row => {
        csvContent += row.join(',') + '\n';
    });
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'employee_template.csv';
    link.click();
    URL.revokeObjectURL(blob);
}

function removeFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    if(filterName === 'search') url.searchParams.delete('search');
    if(filterName === 'department') url.searchParams.delete('department');
    if(filterName === 'company') url.searchParams.delete('company');
    if(filterName === 'designation') url.searchParams.delete('designation');
    if(filterName === 'status') url.searchParams.delete('status');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Auto-submit on filter change (optional)
document.querySelectorAll('select[name="department"], select[name="company"], select[name="designation"], select[name="status"]').forEach(select => {
    select.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>

<?php include '../../includes/footer.php'; ?>