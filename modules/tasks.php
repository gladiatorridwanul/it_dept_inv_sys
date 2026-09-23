<?php
require_once '../includes/auth.php';
include '../includes/header.php';

// Add new task
if(isset($_POST['add_task'])) {
    $task_title = $_POST['task_title'];
    $task_description = $_POST['task_description'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $assigned_to = $_POST['assigned_to'] ?? $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("INSERT INTO tasks (task_title, task_description, priority, due_date, assigned_to, assigned_by, status) 
                          VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    if($stmt->execute([$task_title, $task_description, $priority, $due_date, $assigned_to, $_SESSION['user_id']])) {
        echo '<div class="alert alert-success">Task added successfully!</div>';
    } else {
        echo '<div class="alert alert-danger">Error adding task!</div>';
    }
}

// Update task status
if(isset($_GET['update_status']) && isset($_GET['id'])) {
    $status = $_GET['update_status'];
    $id = $_GET['id'];
    $completed_date = ($status == 'completed') ? date('Y-m-d H:i:s') : null;
    
    $stmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_date = ? WHERE id = ?");
    $stmt->execute([$status, $completed_date, $id]);
    echo '<div class="alert alert-success">Task status updated!</div>';
}

// Delete task
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$id]);
    echo '<div class="alert alert-success">Task deleted!</div>';
}

// Get tasks with filters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';

$where = [];
$params = [];

if($status_filter != 'all') {
    $where[] = "status = ?";
    $params[] = $status_filter;
}
if($priority_filter != 'all') {
    $where[] = "priority = ?";
    $params[] = $priority_filter;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("SELECT t.*, u.full_name as assigned_to_name, u2.full_name as assigned_by_name 
                       FROM tasks t
                       LEFT JOIN users u ON t.assigned_to = u.id
                       LEFT JOIN users u2 ON t.assigned_by = u2.id
                       $where_sql
                       ORDER BY FIELD(status, 'pending', 'in_progress', 'completed'), due_date ASC");
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Get users for assignment
$users = $pdo->query("SELECT id, full_name FROM users WHERE is_active=1 ORDER BY full_name")->fetchAll();

// Task statistics
$total_tasks = $pdo->query("SELECT COUNT(*) as count FROM tasks")->fetch();
$pending_tasks = $pdo->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'pending'")->fetch();
$in_progress = $pdo->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'in_progress'")->fetch();
$completed_tasks = $pdo->query("SELECT COUNT(*) as count FROM tasks WHERE status = 'completed'")->fetch();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><i class="fas fa-tasks"></i> Task Management</h2>
            <hr>
        </div>
    </div>
    
    <!-- Task Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body text-center">
                    <h6>Total Tasks</h6>
                    <h2><?php echo $total_tasks['count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body text-center">
                    <h6>Pending</h6>
                    <h2><?php echo $pending_tasks['count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body text-center">
                    <h6>In Progress</h6>
                    <h2><?php echo $in_progress['count']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body text-center">
                    <h6>Completed</h6>
                    <h2><?php echo $completed_tasks['count']; ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4">
            <!-- Add Task Form -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Add New Task</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label>Task Title *</label>
                            <input type="text" name="task_title" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="task_description" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Priority</label>
                            <select name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Due Date</label>
                            <input type="date" name="due_date" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Assign To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">-- Assign to Someone --</option>
                                <?php foreach($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="add_task" class="btn btn-success w-100">Add Task</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <!-- Task List -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Task List</h5>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" class="row mb-3">
                        <div class="col-md-5">
                            <select name="status" class="form-select">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select name="priority" class="form-select">
                                <option value="all" <?php echo $priority_filter == 'all' ? 'selected' : ''; ?>>All Priority</option>
                                <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $priority_filter == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tasks as $task): ?>
                                <tr class="<?php echo $task['status'] == 'completed' ? 'text-muted' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($task['task_title']); ?></strong><br>
                                        <small><?php echo nl2br(htmlspecialchars(substr($task['task_description'], 0, 100))); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $task['priority'] == 'urgent' ? 'danger' : 
                                                ($task['priority'] == 'high' ? 'warning' : 
                                                ($task['priority'] == 'medium' ? 'info' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($task['priority']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $task['assigned_to_name'] ?? 'Unassigned'; ?></td>
                                    <td><?php echo $task['due_date'] ? date('d-m-Y', strtotime($task['due_date'])) : '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $task['status'] == 'completed' ? 'success' : 
                                                ($task['status'] == 'in_progress' ? 'info' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if($task['status'] != 'completed'): ?>
                                            <a href="?update_status=completed&id=<?php echo $task['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as completed?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                            <a href="?update_status=in_progress&id=<?php echo $task['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-play"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="?delete=1&id=<?php echo $task['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this task?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(empty($tasks)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No tasks found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>