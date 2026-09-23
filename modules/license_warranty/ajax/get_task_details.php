<?php
require_once '../../../config/database.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Session expired</div>';
    exit();
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if(!$id) {
    echo '<div class="alert alert-danger">Invalid request</div>';
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM user_tasks WHERE id = ? AND assigned_to = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$task = $stmt->fetch();

if(!$task) {
    echo '<div class="alert alert-danger">Task not found</div>';
    exit();
}
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <tr><th width="30%">Task Title</th><td><strong><?php echo htmlspecialchars($task['task_title']); ?></strong></td></tr>
        <tr><th>Description</th><td><?php echo nl2br(htmlspecialchars($task['task_description'])); ?></td></tr>
        <tr><th>Task Type</th><td><?php echo ucfirst(str_replace('_', ' ', $task['task_type'])); ?></td></tr>
        <tr><th>Priority</th><td><span class="badge bg-<?php echo $task['priority'] == 'urgent' ? 'danger' : ($task['priority'] == 'high' ? 'warning' : ($task['priority'] == 'medium' ? 'info' : 'secondary')); ?>"><?php echo ucfirst($task['priority']); ?></span></td></tr>
        <tr><th>Due Date</th><td><?php echo date('d-m-Y', strtotime($task['due_date'])); ?></td></tr>
        <tr><th>Reminder Date</th><td><?php echo $task['reminder_date'] ? date('d-m-Y', strtotime($task['reminder_date'])) : 'Not set'; ?></td></tr>
        <tr><th>Status</th><td><span class="badge bg-<?php echo $task['status'] == 'completed' ? 'success' : ($task['status'] == 'in_progress' ? 'info' : 'warning'); ?>"><?php echo ucfirst($task['status']); ?></span></td></tr>
        <?php if($task['completion_notes']): ?>
        <tr><th>Completion Notes</th><td><?php echo nl2br(htmlspecialchars($task['completion_notes'])); ?></td></tr>
        <?php endif; ?>
        <?php if($task['completed_at']): ?>
        <tr><th>Completed At</th><td><?php echo date('d-m-Y h:i A', strtotime($task['completed_at'])); ?></td></tr>
        <?php endif; ?>
    </table>
</div>