<?php
require_once '../../includes/auth.php';
include '../../includes/header.php';

$id = $_GET['id'] ?? 0;

// Get request details
$stmt = $pdo->prepare("SELECT r.*, e.full_name, e.pf_no 
                       FROM requests r 
                       JOIN employees e ON r.employee_id = e.id 
                       WHERE r.id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();

if(!$request) {
    redirect('all_requests.php');
}

// Get comments history
$comments = $pdo->prepare("SELECT c.*, u.full_name as user_name 
                          FROM request_comments c 
                          JOIN users u ON c.commented_by = u.id 
                          WHERE c.request_id = ? 
                          ORDER BY c.created_at DESC");
$comments->execute([$id]);
$comments = $comments->fetchAll();

// Get attachments
$attachments = $pdo->prepare("SELECT * FROM request_attachments WHERE request_id = ?");
$attachments->execute([$id]);
$attachments = $attachments->fetchAll();

// Get status change history from request_data or comments
$status_history = [];
foreach($comments as $comment) {
    if(strpos($comment['comment'], 'Status changed from') !== false || 
       strpos($comment['comment'], 'Status changed to') !== false) {
        $status_history[] = $comment;
    }
}
?>

<style>
    .timeline {
        position: relative;
        padding-left: 40px;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 25px;
    }
    .timeline-icon {
        position: absolute;
        left: -40px;
        top: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    .timeline-content {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
    }
    .timeline-content strong {
        display: block;
        margin-bottom: 5px;
    }
    .attachment-item {
        display: inline-block;
        margin: 5px;
        padding: 5px 10px;
        background: #e9ecef;
        border-radius: 5px;
    }
</style>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="fas fa-history text-primary"></i> Request History</h2>
                    <p class="text-muted">Request No: <strong><?php echo $request['request_no']; ?></strong> | 
                    Employee: <?php echo htmlspecialchars($request['full_name']); ?> (<?php echo $request['pf_no']; ?>)</p>
                </div>
                <div>
                    <a href="view_request.php?id=<?php echo $id; ?>" class="btn btn-info">
                        <i class="fas fa-eye"></i> View Request
                    </a>
                    <a href="all_requests.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <hr>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Timeline -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-timeline"></i> Activity Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Request Created -->
                        <div class="timeline-item">
                            <div class="timeline-icon bg-success">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Request Created</strong>
                                <p class="mb-0">Request submitted by <?php echo htmlspecialchars($request['full_name']); ?></p>
                                <small class="text-muted"><?php echo date('d-m-Y h:i A', strtotime($request['requested_date'])); ?></small>
                            </div>
                        </div>
                        
                        <!-- Comments / Activity -->
                        <?php foreach($comments as $comment): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-info">
                                <i class="fas fa-comment"></i>
                            </div>
                            <div class="timeline-content">
                                <strong><?php echo htmlspecialchars($comment['user_name']); ?></strong>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                <small class="text-muted"><?php echo date('d-m-Y h:i A', strtotime($comment['created_at'])); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Status Changes -->
                        <?php if($request['accepted_date']): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-warning">
                                <i class="fas fa-eye"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>First Reviewed</strong>
                                <p class="mb-0">Request was reviewed and accepted</p>
                                <small class="text-muted"><?php echo date('d-m-Y h:i A', strtotime($request['accepted_date'])); ?></small>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Current Status -->
                        <div class="timeline-item">
                            <div class="timeline-icon bg-<?php 
                                echo $request['status'] == 'completed' ? 'success' : 
                                    ($request['status'] == 'rejected' ? 'danger' : 'secondary'); 
                            ?>">
                                <i class="fas fa-flag-checkered"></i>
                            </div>
                            <div class="timeline-content">
                                <strong>Current Status: <?php echo ucfirst($request['status']); ?></strong>
                                <?php if($request['resolution_notes']): ?>
                                <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($request['resolution_notes'])); ?></p>
                                <?php endif; ?>
                                <?php if($request['processed_by']): ?>
                                <small class="text-muted">Last updated by: <?php echo $request['processed_by_name'] ?? 'System'; ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Attachments -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-paperclip"></i> Attachments</h5>
                </div>
                <div class="card-body">
                    <?php if(count($attachments) > 0): ?>
                        <?php foreach($attachments as $att): ?>
                        <div class="attachment-item">
                            <a href="/it-inventory/<?php echo $att['file_path']; ?>" target="_blank">
                                <i class="fas fa-file"></i> <?php echo $att['file_name']; ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">No attachments</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Request Summary -->
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>Total Comments:</th><td><?php echo count($comments); ?></tr>
                        <tr><th>Attachments:</th><td><?php echo count($attachments); ?></tr>
                        <tr><th>Days Open:</th>
                            <td>
                                <?php 
                                $created = new DateTime($request['requested_date']);
                                $now = new DateTime();
                                $diff = $created->diff($now);
                                echo $diff->days . ' days';
                                ?>
                            </td>
                        </tr>
                        <?php if($request['estimated_completion_date']): ?>
                        <tr><th>Est. Completion:</th><td><?php echo date('d-m-Y', strtotime($request['estimated_completion_date'])); ?></tr>
                        <?php endif; ?>
                     </table
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>