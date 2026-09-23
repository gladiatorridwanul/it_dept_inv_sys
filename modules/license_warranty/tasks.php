<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get employees for dropdown - simpler query without user_id
$employees = [];
try {
    $stmt = $pdo->query("SELECT id, pf_no, full_name, designation FROM employees WHERE is_active = 1 ORDER BY full_name");
    $employees = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error loading employees: " . $e->getMessage());
}

// Get licenses for dropdown
$licenses = [];
try {
    $stmt = $pdo->query("SELECT id, software_name FROM licenses WHERE is_active = 1 ORDER BY software_name");
    $licenses = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error loading licenses: " . $e->getMessage());
}

// Get warranties for dropdown
$warranties = [];
try {
    // Check if item_name column exists
    $check_col = $pdo->query("SHOW COLUMNS FROM warranties LIKE 'item_name'");
    if($check_col->rowCount() > 0) {
        $stmt = $pdo->query("SELECT id, item_name FROM warranties WHERE is_active = 1 ORDER BY item_name");
        $warranties = $stmt->fetchAll();
    } else {
        // Fallback: use id as name
        $stmt = $pdo->query("SELECT id, CONCAT('Warranty #', id) as item_name FROM warranties WHERE is_active = 1");
        $warranties = $stmt->fetchAll();
    }
} catch(PDOException $e) {
    error_log("Error loading warranties: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_task': addTask($pdo); break;
            case 'edit_task': editTask($pdo); break;
            case 'update_status': updateTaskStatus($pdo); break;
            case 'add_comment': addComment($pdo); break;
            case 'delete_task': deleteTask($pdo); break;
        }
    }
}

function addTask($pdo) {
    $task_title = trim($_POST['task_title']);
    $task_description = trim($_POST['task_description']);
    $task_type = $_POST['task_type'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $reminder_date = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null;
    $assigned_to_employee_id = !empty($_POST['assigned_to_employee_id']) ? intval($_POST['assigned_to_employee_id']) : null;
    $related_license_id = !empty($_POST['related_license_id']) ? intval($_POST['related_license_id']) : null;
    $related_warranty_id = !empty($_POST['related_warranty_id']) ? intval($_POST['related_warranty_id']) : null;
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO user_tasks (task_title, task_description, task_type, priority, due_date, reminder_date, assigned_to, assigned_by, assigned_to_employee_id, related_license_id, related_warranty_id, status, progress_percentage, created_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, NOW(), 1)");
    $stmt->execute([$task_title, $task_description, $task_type, $priority, $due_date, $reminder_date, $user_id, $user_id, $assigned_to_employee_id, $related_license_id, $related_warranty_id]);
    $_SESSION['success_message'] = "Task created successfully!";
    header("Location: tasks.php");
    exit();
}

function editTask($pdo) {
    $id = intval($_POST['task_id']);
    $task_title = trim($_POST['task_title']);
    $task_description = trim($_POST['task_description']);
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $reminder_date = !empty($_POST['reminder_date']) ? $_POST['reminder_date'] : null;
    $assigned_to_employee_id = !empty($_POST['assigned_to_employee_id']) ? intval($_POST['assigned_to_employee_id']) : null;
    
    $stmt = $pdo->prepare("UPDATE user_tasks SET task_title=?, task_description=?, priority=?, due_date=?, reminder_date=?, assigned_to_employee_id=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$task_title, $task_description, $priority, $due_date, $reminder_date, $assigned_to_employee_id, $id]);
    $_SESSION['success_message'] = "Task updated successfully!";
    header("Location: tasks.php");
    exit();
}

function updateTaskStatus($pdo) {
    $task_id = intval($_POST['task_id']);
    $status = $_POST['status'];
    $progress = intval($_POST['progress_percentage']);
    $completion_notes = trim($_POST['completion_notes']);
    
    if($status == 'completed') {
        $stmt = $pdo->prepare("UPDATE user_tasks SET status=?, progress_percentage=?, completion_notes=?, completed_at=NOW(), updated_at=NOW() WHERE id=?");
        $stmt->execute([$status, $progress, $completion_notes, $task_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE user_tasks SET status=?, progress_percentage=?, completion_notes=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$status, $progress, $completion_notes, $task_id]);
    }
    $_SESSION['success_message'] = "Task status updated!";
    header("Location: tasks.php");
    exit();
}

function addComment($pdo) {
    $task_id = intval($_POST['task_id']);
    $comment = trim($_POST['comment']);
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO task_comments (task_id, comment, created_by, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$task_id, $comment, $user_id]);
    $_SESSION['success_message'] = "Comment added!";
    header("Location: tasks.php");
    exit();
}

function deleteTask($pdo) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("UPDATE user_tasks SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = "Task deleted!";
    header("Location: tasks.php");
    exit();
}

// Get tasks assigned to current user
$tasks = [];
try {
    // Simplified query - just use assigned_to which is user_id
    $tasks_query = "
        SELECT t.*, 
               e.full_name as assigned_to_name, 
               l.software_name as license_name,
               w.item_name as warranty_name
        FROM user_tasks t
        LEFT JOIN employees e ON t.assigned_to_employee_id = e.id
        LEFT JOIN licenses l ON t.related_license_id = l.id
        LEFT JOIN warranties w ON t.related_warranty_id = w.id
        WHERE t.assigned_to = ? AND t.is_active = 1
        ORDER BY 
            CASE WHEN t.status = 'pending' THEN 0 
                 WHEN t.status = 'in_progress' THEN 1 
                 ELSE 2 END,
            t.due_date ASC
    ";
    $stmt = $pdo->prepare($tasks_query);
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error loading tasks: " . $e->getMessage());
    // Fallback query without joins
    $tasks_query = "
        SELECT * FROM user_tasks 
        WHERE assigned_to = ? AND is_active = 1
        ORDER BY due_date ASC
    ";
    $stmt = $pdo->prepare($tasks_query);
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        *{font-family:'Inter',sans-serif}
        .task-card{background:white;border-radius:16px;padding:20px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border-left:4px solid}
        .task-priority-high{border-left-color:#ef4444}
        .task-priority-urgent{border-left-color:#dc2626}
        .task-priority-medium{border-left-color:#f59e0b}
        .task-priority-low{border-left-color:#10b981}
        .status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600}
        .status-pending{background:#fef3c7;color:#d97706}
        .status-in_progress{background:#dbeafe;color:#2563eb}
        .status-completed{background:#d1fae5;color:#059669}
        .progress-bar-custom{height:6px;border-radius:3px;background:#e2e8f0;overflow:hidden}
        .progress-fill{height:100%;border-radius:3px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);width:0%}
        .btn-gradient-warning{background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:white;border:none}
        .btn-gradient-primary{background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);color:white;border:none}
        .stats-card{background:white;border-radius:20px;padding:20px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,0.05)}
        .stats-number{font-size:28px;font-weight:800}
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-tasks text-warning me-2"></i> Task Management</h2>
                    <p class="text-muted">Manage your tasks, track progress, and collaborate with team members</p>
                </div>
                <div>
                    <button class="btn btn-gradient-warning" onclick="showAddTaskModal()">
                        <i class="fas fa-plus me-1"></i> Create Task
                    </button>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                <div class="stats-number"><?php echo count($tasks); ?></div>
                <div class="stats-label">Total Tasks</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-clock fa-2x text-info mb-2"></i>
                <div class="stats-number"><?php echo count(array_filter($tasks, fn($t)=>$t['status']=='pending')); ?></div>
                <div class="stats-label">Pending</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-spinner fa-2x text-primary mb-2"></i>
                <div class="stats-number"><?php echo count(array_filter($tasks, fn($t)=>$t['status']=='in_progress')); ?></div>
                <div class="stats-label">In Progress</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <div class="stats-number"><?php echo count(array_filter($tasks, fn($t)=>$t['status']=='completed')); ?></div>
                <div class="stats-label">Completed</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <?php foreach($tasks as $task): ?>
            <div class="task-card task-priority-<?php echo $task['priority']; ?>">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                        <small class="text-muted">
                            Type: <?php echo ucfirst(str_replace('_',' ',$task['task_type'])); ?> | 
                            Assigned to: <?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Self'); ?> | 
                            Due: <?php echo date('d-m-Y', strtotime($task['due_date'])); ?>
                        </small>
                    </div>
                    <div>
                        <span class="status-badge status-<?php echo $task['status']; ?>">
                            <?php echo ucfirst(str_replace('_',' ',$task['status'])); ?>
                        </span>
                        <button class="btn btn-sm btn-outline-primary ms-2" onclick="editTask(<?php echo htmlspecialchars(json_encode($task)); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteTask(<?php echo $task['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <p class="mb-2"><?php echo nl2br(htmlspecialchars($task['task_description'])); ?></p>
                <div class="progress-bar-custom mb-2">
                    <div class="progress-fill" style="width: <?php echo $task['progress_percentage']; ?>%"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small><?php echo $task['progress_percentage']; ?>% Complete</small>
                    <button class="btn btn-sm btn-gradient-primary" onclick="updateTaskStatus(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>', <?php echo $task['progress_percentage']; ?>)">
                        Update Progress
                    </button>
                </div>
                <?php if(!empty($task['license_name'])): ?>
                    <small class="text-muted mt-2 d-block"><i class="fas fa-key"></i> Related License: <?php echo htmlspecialchars($task['license_name']); ?></small>
                <?php endif; ?>
                <?php if(!empty($task['warranty_name'])): ?>
                    <small class="text-muted mt-1 d-block"><i class="fas fa-shield-alt"></i> Related Warranty: <?php echo htmlspecialchars($task['warranty_name']); ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if(empty($tasks)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i> No tasks assigned to you. Click "Create Task" to get started.
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Task Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Status Distribution</h6>
                        <div class="d-flex justify-content-between"><span>Pending</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['status']=='pending')); ?></strong></div>
                        <div class="d-flex justify-content-between"><span>In Progress</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['status']=='in_progress')); ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Completed</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['status']=='completed')); ?></strong></div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <h6>Priority Distribution</h6>
                        <div class="d-flex justify-content-between"><span>Urgent</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['priority']=='urgent')); ?></strong></div>
                        <div class="d-flex justify-content-between"><span>High</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['priority']=='high')); ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Medium</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['priority']=='medium')); ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Low</span><strong><?php echo count(array_filter($tasks, fn($t)=>$t['priority']=='low')); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><span id="modalTitle">Create New Task</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="taskForm">
                    <input type="hidden" name="action" id="formAction" value="add_task">
                    <input type="hidden" name="task_id" id="taskId" value="">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required-field">Task Title</label>
                            <input type="text" name="task_title" id="task_title" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="task_description" id="task_description" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Task Type</label>
                            <select name="task_type" id="task_type" class="form-select">
                                <option value="license_renewal">License Renewal</option>
                                <option value="warranty_renewal">Warranty Renewal</option>
                                <option value="software_update">Software Update</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Due Date</label>
                            <input type="date" name="due_date" id="due_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reminder Date</label>
                            <input type="date" name="reminder_date" id="reminder_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To (Employee)</label>
                            <select name="assigned_to_employee_id" id="assigned_to_employee_id" class="form-select select2-employee">
                                <option value="">Assign to Self</option>
                                <?php foreach($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>"><?php echo htmlspecialchars($emp['pf_no'] . ' - ' . $emp['full_name'] . ' (' . $emp['designation'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Related License</label>
                            <select name="related_license_id" id="related_license_id" class="form-select select2-license">
                                <option value="">None</option>
                                <?php foreach($licenses as $lic): ?>
                                    <option value="<?php echo $lic['id']; ?>"><?php echo htmlspecialchars($lic['software_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Related Warranty</label>
                            <select name="related_warranty_id" id="related_warranty_id" class="form-select select2-warranty">
                                <option value="">None</option>
                                <?php foreach($warranties as $war): ?>
                                    <option value="<?php echo $war['id']; ?>"><?php echo htmlspecialchars($war['item_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning ms-2">Save Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Update Task Progress</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="task_id" id="status_task_id">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="status_val" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Progress (%)</label>
                        <input type="range" name="progress_percentage" id="progress_val" class="form-range" min="0" max="100" step="10">
                        <div class="text-center mt-1"><span id="progress_display">0</span>%</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Completion Notes</label>
                        <textarea name="completion_notes" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary ms-2">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function(){ 
    $('.select2-employee, .select2-license, .select2-warranty').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#taskModal')
    }); 
});

function showAddTaskModal(){ 
    $('#modalTitle').text('Create New Task'); 
    $('#formAction').val('add_task'); 
    $('#taskId').val(''); 
    $('#taskForm')[0].reset(); 
    $('.select2-employee, .select2-license, .select2-warranty').val(null).trigger('change'); 
    $('#taskModal').modal('show'); 
}

function editTask(t){ 
    $('#modalTitle').text('Edit Task'); 
    $('#formAction').val('edit_task'); 
    $('#taskId').val(t.id); 
    $('#task_title').val(t.task_title); 
    $('#task_description').val(t.task_description); 
    $('#task_type').val(t.task_type); 
    $('#priority').val(t.priority); 
    $('#due_date').val(t.due_date); 
    $('#reminder_date').val(t.reminder_date); 
    $('#assigned_to_employee_id').val(t.assigned_to_employee_id).trigger('change'); 
    $('#related_license_id').val(t.related_license_id).trigger('change'); 
    $('#related_warranty_id').val(t.related_warranty_id).trigger('change'); 
    $('#taskModal').modal('show'); 
}

function updateTaskStatus(id, status, progress){ 
    $('#status_task_id').val(id); 
    $('#status_val').val(status); 
    $('#progress_val').val(progress); 
    $('#progress_display').text(progress); 
    $('#statusModal').modal('show'); 
    $('#progress_val').on('input', function(){ 
        $('#progress_display').text($(this).val()); 
    }); 
}

function deleteTask(id){ 
    Swal.fire({
        title: 'Delete Task?', 
        text: "This action cannot be undone.", 
        icon: 'warning', 
        showCancelButton: true, 
        confirmButtonColor: '#d33', 
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => { 
        if(result.isConfirmed){ 
            $('<form>', { method: 'POST' })
                .append($('<input>', { type: 'hidden', name: 'action', value: 'delete_task' }))
                .append($('<input>', { type: 'hidden', name: 'id', value: id }))
                .appendTo('body')
                .submit(); 
        } 
    }); 
}
</script>

<?php include '../../includes/footer.php'; ?>