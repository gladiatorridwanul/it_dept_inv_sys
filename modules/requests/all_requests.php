<?php
require_once '../../includes/auth.php';
require_once '../../includes/request_functions.php';
include '../../includes/header.php';

$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Allowed request types - only these will be shown
$allowed_types = [
    'technical_support',
    'technical',
    'issue',
    'report',
    'software_access',
    'software',
    'upgrade',
    'accessories',
    'accessory',
    'device_assign'
];

// Build allowed types for SQL IN clause
$allowed_placeholders = implode(',', array_fill(0, count($allowed_types), '?'));
$where_conditions = [];
$params = $allowed_types;

if($status_filter != 'all') {
    $where_conditions[] = "r.status = ?";
    $params[] = $status_filter;
}

if($type_filter != 'all' && $type_filter != '') {
    $where_conditions[] = "r.request_type = ?";
    $params[] = $type_filter;
}

if(!empty($search)) {
    $where_conditions[] = "(r.request_no LIKE ? OR e.full_name LIKE ? OR e.pf_no LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Add allowed types condition
$where_sql = "WHERE r.request_type IN ($allowed_placeholders)";
if(!empty($where_conditions)) {
    $where_sql .= " AND " . implode(" AND ", $where_conditions);
}

// Main query
$sql = "SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
        u.full_name as processed_by_name,
        (SELECT COUNT(*) FROM request_comments WHERE request_id = r.id) as comment_count,
        (SELECT COUNT(*) FROM request_attachments WHERE request_id = r.id) as attachment_count
        FROM requests r 
        JOIN employees e ON r.employee_id = e.id 
        LEFT JOIN users u ON r.processed_by = u.id
        $where_sql 
        ORDER BY r.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get counts for each status (only for allowed types)
$status_counts = ['pending' => 0, 'under_observation' => 0, 'processing' => 0, 'completed' => 0, 'rejected' => 0];
$allowed_placeholders_counts = implode(',', array_fill(0, count($allowed_types), '?'));

foreach(array_keys($status_counts) as $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM requests WHERE request_type IN ($allowed_placeholders_counts) AND status = ?");
    $stmt->execute(array_merge($allowed_types, [$status]));
    $status_counts[$status] = $stmt->fetch()['count'];
}
$total_all = array_sum($status_counts);

// Get distinct request types from database (only allowed ones)
$type_counts_raw = $pdo->prepare("SELECT request_type, COUNT(*) as count 
                                  FROM requests 
                                  WHERE request_type IS NOT NULL AND request_type != ''
                                  AND request_type IN ($allowed_placeholders_counts)
                                  GROUP BY request_type 
                                  ORDER BY request_type");
$type_counts_raw->execute($allowed_types);
$type_counts_raw = $type_counts_raw->fetchAll();

// Map database request_type values to display names
$type_mapping = [
    'technical_support' => ['display' => '📌 Report an Issue', 'icon' => '📌'],
    'technical' => ['display' => '📌 Report an Issue', 'icon' => '📌'],
    'issue' => ['display' => '📌 Report an Issue', 'icon' => '📌'],
    'report' => ['display' => '📌 Report an Issue', 'icon' => '📌'],
    'software_access' => ['display' => '🔑 Software Access', 'icon' => '🔑'],
    'software' => ['display' => '🔑 Software Access', 'icon' => '🔑'],
    'accessories' => ['display' => '🖱️ IT Accessories Request', 'icon' => '🖱️'],
    'accessory' => ['display' => '🖱️ IT Accessories Request', 'icon' => '🖱️'],
    'upgrade' => ['display' => '⬆️ Request Upgrade', 'icon' => '⬆️'],
    'device_assign' => ['display' => '🏠 Multiple Device Assignment', 'icon' => '🏠']
];

// Build type options with counts (only for allowed types)
$type_options = [];
$display_types_used = [];

foreach($type_counts_raw as $tc) {
    $db_type = $tc['request_type'];
    $count = $tc['count'];
    
    if(isset($type_mapping[$db_type])) {
        $display = $type_mapping[$db_type]['display'];
        $display_key = $display;
        
        if(!isset($type_options[$display_key])) {
            $type_options[$display_key] = [
                'db_types' => [],
                'total_count' => 0,
                'display' => $display
            ];
        }
        $type_options[$display_key]['db_types'][] = $db_type;
        $type_options[$display_key]['total_count'] += $count;
        $display_types_used[$display_key] = true;
    }
}

// Add types with zero count for completeness (only the 5 specified types)
$allowed_display_types = [
    '📌 Report an Issue' => ['db_types' => ['technical_support', 'technical', 'issue', 'report'], 'total_count' => 0],
    '🔑 Software Access' => ['db_types' => ['software_access', 'software'], 'total_count' => 0],
    '⬆️ Request Upgrade' => ['db_types' => ['upgrade'], 'total_count' => 0],
    '🖱️ IT Accessories Request' => ['db_types' => ['accessories', 'accessory'], 'total_count' => 0],
    '🏠 Multiple Device Assignment' => ['db_types' => ['device_assign'], 'total_count' => 0]
];

foreach($allowed_display_types as $display_name => $info) {
    if(!isset($display_types_used[$display_name])) {
        $type_options[$display_name] = [
            'db_types' => $info['db_types'],
            'total_count' => 0,
            'display' => $display_name
        ];
    }
}

// Sort by display name
ksort($type_options);

// Function to get display name from database type
function getRequestTypeDisplayFromDB($db_type) {
    $mapping = [
        'technical_support' => '📌 Report an Issue',
        'technical' => '📌 Report an Issue',
        'issue' => '📌 Report an Issue',
        'report' => '📌 Report an Issue',
        'software_access' => '🔑 Software Access',
        'software' => '🔑 Software Access',
        'accessories' => '🖱️ IT Accessories Request',
        'accessory' => '🖱️ IT Accessories Request',
        'upgrade' => '⬆️ Request Upgrade',
        'device_assign' => '🏠 Multiple Device Assignment'
    ];
    return $mapping[$db_type] ?? '❓ ' . ucfirst(str_replace('_', ' ', $db_type));
}
?>

<style>
    :root {
        --primary: #3b82f6;
        --primary-dark: #2563eb;
        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-600: #475569;
        --gray-700: #334155;
    }

    /* Modern Stats Cards */
    .stats-card {
        transition: all 0.2s ease;
        border-radius: 16px;
        border: none;
        cursor: pointer;
        overflow: hidden;
        position: relative;
    }
    .stats-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 20px -8px rgba(0,0,0,0.15);
    }
    .stats-card .card-body {
        padding: 1rem 1rem;
    }
    .stats-number {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0;
    }
    .stats-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        opacity: 0.85;
        margin-bottom: 0.25rem;
    }

    /* Filter Card */
    .filter-card {
        background: white;
        border-radius: 20px;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03);
        border: 1px solid var(--gray-200);
    }
    .filter-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-600);
        margin-bottom: 0.5rem;
        display: block;
        letter-spacing: 0.3px;
    }
    .filter-select, .filter-input {
        border-radius: 12px;
        border: 1px solid var(--gray-200);
        padding: 0.6rem 0.875rem;
        font-size: 0.875rem;
        transition: all 0.2s;
        background-color: white;
    }
    .filter-select:focus, .filter-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        outline: none;
    }
    .btn-filter {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.6rem 1.25rem;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    .btn-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(59,130,246,0.25);
        color: white;
    }
    .btn-clear {
        background: var(--gray-100);
        color: var(--gray-600);
        border: none;
        border-radius: 12px;
        padding: 0.6rem 1.25rem;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    .btn-clear:hover {
        background: var(--gray-200);
        color: var(--gray-700);
    }

    /* Modern Table */
    .requests-table-container {
        background: white;
        border-radius: 20px;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .table-requests {
        margin-bottom: 0;
        width: 100%;
    }
    .table-requests thead th {
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--gray-600);
        padding: 1rem 0.875rem;
        vertical-align: middle;
    }
    .table-requests tbody td {
        font-size: 0.8125rem;
        vertical-align: middle;
        padding: 0.875rem;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
    }
    .table-requests tbody tr:hover {
        background-color: var(--gray-50);
    }

    /* Modern Badges */
    .type-badge, .priority-badge, .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.625rem;
        border-radius: 30px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .type-technical_support { background: #fee2e2; color: #991b1b; }
    .type-software_access { background: #e0e7ff; color: #3730a3; }
    .type-accessories { background: #fef3c7; color: #92400e; }
    .type-upgrade { background: #fef3c7; color: #b45309; }
    .type-device_assign { background: #cffafe; color: #155e75; }
    .type-unknown { background: var(--gray-100); color: var(--gray-600); }

    .priority-low { background: #f1f5f9; color: #475569; }
    .priority-medium { background: #cffafe; color: #0891b2; }
    .priority-high { background: #fed7aa; color: #9a3412; }
    .priority-critical { background: #fee2e2; color: #dc2626; }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-under_observation { background: #cffafe; color: #0891b2; }
    .status-processing { background: #e0e7ff; color: #3730a3; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #dc2626; }

    /* Action Buttons */
    .action-btn-group {
        display: flex;
        gap: 6px;
    }
    .action-btn {
        width: 30px;
        height: 30px;
        padding: 0;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        transition: all 0.2s;
    }
    .action-btn.btn-info { background: #e0f2fe; border-color: #e0f2fe; color: #0284c7; }
    .action-btn.btn-info:hover { background: #bae6fd; color: #0369a1; }
    .action-btn.btn-warning { background: #fef3c7; border-color: #fef3c7; color: #d97706; }
    .action-btn.btn-warning:hover { background: #fde68a; color: #b45309; }
    .action-btn.btn-primary { background: #e0e7ff; border-color: #e0e7ff; color: #4f46e5; }
    .action-btn.btn-primary:hover { background: #c7d2fe; color: #4338ca; }

    /* Employee info styling */
    .employee-name {
        font-weight: 600;
        color: #1e293b;
    }
    .employee-designation {
        font-size: 0.7rem;
        color: var(--gray-500);
        margin-top: 2px;
    }

    /* Attachment indicator */
    .attachment-indicator {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: var(--gray-100);
        padding: 2px 8px;
        border-radius: 30px;
        font-size: 0.7rem;
        font-weight: 500;
        color: var(--gray-600);
        cursor: pointer;
        transition: all 0.2s;
    }
    .attachment-indicator:hover {
        background: var(--primary);
        color: white;
    }
    .attachment-indicator i {
        font-size: 0.65rem;
    }

    /* Active filters bar */
    .active-filters-bar {
        background: var(--gray-50);
        border-radius: 12px;
        padding: 0.5rem 1rem;
        margin-bottom: 1.25rem;
        border: 1px solid var(--gray-200);
        font-size: 0.75rem;
    }
    .filter-badge {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 30px;
        padding: 0.25rem 0.625rem;
        font-size: 0.7rem;
        font-weight: 500;
        color: var(--gray-600);
    }
    
    /* Attachment Modal Styles */
    .attachments-modal .modal-dialog {
        max-width: 900px;
    }
    .attachments-modal .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    .attachments-modal .modal-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border: none;
        padding: 1rem 1.5rem;
    }
    .attachments-modal .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    .attachments-modal .modal-body {
        padding: 1.5rem;
        max-height: 70vh;
        overflow-y: auto;
    }
    .attachment-item {
        background: var(--gray-50);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        border: 1px solid var(--gray-200);
        transition: all 0.2s;
    }
    .attachment-item:hover {
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .attachment-icon {
        width: 45px;
        height: 45px;
        background: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: var(--primary);
    }
    .attachment-info {
        flex: 1;
    }
    .attachment-name {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
        word-break: break-all;
    }
    .attachment-meta {
        font-size: 0.7rem;
        color: var(--gray-500);
    }
    .attachment-preview {
        width: 70px;
        height: 70px;
        background: white;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: pointer;
        border: 1px solid var(--gray-200);
        transition: all 0.2s;
    }
    .attachment-preview:hover {
        border-color: var(--primary);
        transform: scale(1.02);
    }
    .attachment-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: cover;
    }
    .attachment-preview i {
        font-size: 2rem;
        color: var(--gray-400);
    }
    .file-icon {
        font-size: 1.5rem;
    }
    .file-icon.image { color: #10b981; }
    .file-icon.pdf { color: #dc2626; }
    .file-icon.word { color: #2b5797; }
    .file-icon.excel { color: #217346; }
    .file-icon.powerpoint { color: #d35230; }
    .file-icon.text { color: #6366f1; }
    .file-icon.other { color: #64748b; }

    /* Full Image Viewer Modal */
    .image-viewer-modal .modal-dialog {
        max-width: 95%;
        width: auto;
        margin: 1rem auto;
    }
    .image-viewer-modal .modal-content {
        background: rgba(0,0,0,0.9);
        border: none;
        border-radius: 12px;
    }
    .image-viewer-modal .modal-body {
        text-align: center;
        padding: 0;
    }
    .image-viewer-modal img {
        max-width: 100%;
        max-height: 85vh;
        border-radius: 8px;
    }
    .image-viewer-modal .btn-close-white {
        position: absolute;
        top: -40px;
        right: 0;
        filter: brightness(0) invert(1);
    }
    .image-controls {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.7);
        border-radius: 30px;
        padding: 8px 15px;
        display: flex;
        gap: 15px;
    }
    .image-controls button {
        background: transparent;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 5px 10px;
        border-radius: 50%;
        transition: all 0.2s;
    }
    .image-controls button:hover {
        background: rgba(255,255,255,0.2);
    }
    
    /* PDF Viewer Modal */
    .pdf-viewer-modal .modal-dialog {
        max-width: 90%;
        width: 90%;
        height: 90vh;
        margin: 5vh auto;
    }
    .pdf-viewer-modal .modal-content {
        height: 100%;
        border-radius: 12px;
        overflow: hidden;
    }
    .pdf-viewer-modal .modal-body {
        height: calc(100% - 60px);
        padding: 0;
    }
    .pdf-viewer-modal iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>

<!-- Attachment Modal -->
<div class="modal fade attachments-modal" id="attachmentsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paperclip me-2"></i>Request Attachments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="attachmentsModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading attachments...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div class="modal fade image-viewer-modal" id="imageViewerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-0">
                <img id="viewerImage" src="" alt="Attachment Preview" style="max-width: 100%; max-height: 85vh;">
                <div class="image-controls">
                    <button id="zoomInBtn" title="Zoom In"><i class="fas fa-search-plus"></i></button>
                    <button id="zoomOutBtn" title="Zoom Out"><i class="fas fa-search-minus"></i></button>
                    <button id="rotateBtn" title="Rotate"><i class="fas fa-undo-alt"></i></button>
                    <button id="downloadImageBtn" title="Download"><i class="fas fa-download"></i></button>
                </div>
            </div>
            <div class="text-center py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- PDF Viewer Modal -->
<div class="modal fade pdf-viewer-modal" id="pdfViewerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-file-pdf me-2"></i>PDF Document Viewer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe id="pdfViewer" src="" style="width:100%; height:100%; border:none;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="downloadPdfBtn"><i class="fas fa-download me-1"></i> Download PDF</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-semibold"><i class="fas fa-tasks me-2 text-primary"></i>Request Management</h4>
            <p class="text-muted small mb-0">Manage and track all IT support requests</p>
        </div>
        <a href="all_requests.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fas fa-sync-alt me-1"></i> Refresh
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-4">
            <a href="?status=all&type=<?php echo $type_filter; ?>&search=<?php echo urlencode($search); ?>" class="text-decoration-none">
                <div class="card bg-dark text-white stats-card">
                    <div class="card-body">
                        <div class="stats-label">Total Requests</div>
                        <div class="stats-number"><?php echo $total_all; ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="?status=pending&type=<?php echo $type_filter; ?>&search=<?php echo urlencode($search); ?>" class="text-decoration-none">
                <div class="card bg-warning text-white stats-card">
                    <div class="card-body">
                        <div class="stats-label">Pending</div>
                        <div class="stats-number"><?php echo $status_counts['pending']; ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="?status=under_observation&type=<?php echo $type_filter; ?>&search=<?php echo urlencode($search); ?>" class="text-decoration-none">
                <div class="card bg-info text-white stats-card">
                    <div class="card-body">
                        <div class="stats-label">Under Obs.</div>
                        <div class="stats-number"><?php echo $status_counts['under_observation']; ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="?status=processing&type=<?php echo $type_filter; ?>&search=<?php echo urlencode($search); ?>" class="text-decoration-none">
                <div class="card bg-primary text-white stats-card">
                    <div class="card-body">
                        <div class="stats-label">Processing</div>
                        <div class="stats-number"><?php echo $status_counts['processing']; ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="?status=completed&type=<?php echo $type_filter; ?>&search=<?php echo urlencode($search); ?>" class="text-decoration-none">
                <div class="card bg-success text-white stats-card">
                    <div class-card-body>
                        <div class="stats-label">Completed</div>
                        <div class="stats-number"><?php echo $status_counts['completed']; ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-4">
            <a href="?status=rejected&type=<?php echo $type_filter; ?>&search=<?php echo urlencode($search); ?>" class="text-decoration-none">
                <div class="card bg-danger text-white stats-card">
                    <div class="card-body">
                        <div class="stats-label">Rejected</div>
                        <div class="stats-number"><?php echo $status_counts['rejected']; ?></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="filter-card">
        <form method="GET" id="filterForm">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label"><i class="fas fa-filter me-1"></i> Status</label>
                    <select name="status" class="form-select filter-select" id="statusSelect">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending (<?php echo $status_counts['pending']; ?>)</option>
                        <option value="under_observation" <?php echo $status_filter == 'under_observation' ? 'selected' : ''; ?>>Under Observation (<?php echo $status_counts['under_observation']; ?>)</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing (<?php echo $status_counts['processing']; ?>)</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed (<?php echo $status_counts['completed']; ?>)</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected (<?php echo $status_counts['rejected']; ?>)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label"><i class="fas fa-tag me-1"></i> Request Type</label>
                    <select name="type" class="form-select filter-select" id="typeSelect">
                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <?php foreach($type_options as $display_name => $opt): ?>
                        <option value="<?php echo htmlspecialchars(implode(',', $opt['db_types'])); ?>" 
                                <?php 
                                    $selected = false;
                                    if($type_filter != 'all') {
                                        foreach($opt['db_types'] as $db_type) {
                                            if($type_filter == $db_type) {
                                                $selected = true;
                                                break;
                                            }
                                        }
                                    }
                                    echo $selected ? 'selected' : '';
                                ?>>
                            <?php echo $display_name; ?> (<?php echo $opt['total_count']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="filter-label"><i class="fas fa-search me-1"></i> Search</label>
                    <input type="text" name="search" class="form-control filter-input" 
                           placeholder="Request No, Employee Name, PF No..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-filter w-100">
                            <i class="fas fa-search me-1"></i> Apply
                        </button>
                        <a href="all_requests.php" class="btn btn-clear">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filters Bar -->
    <?php if($status_filter != 'all' || $type_filter != 'all' || !empty($search)): ?>
    <div class="active-filters-bar d-flex flex-wrap align-items-center gap-2">
        <span class="text-muted me-1"><i class="fas fa-filter"></i> Active filters:</span>
        <?php if($status_filter != 'all'): ?>
        <span class="filter-badge">Status: <?php echo ucfirst(str_replace('_', ' ', $status_filter)); ?></span>
        <?php endif; ?>
        <?php if($type_filter != 'all'): ?>
        <span class="filter-badge">Type: 
            <?php 
            foreach($type_options as $display_name => $opt) {
                foreach($opt['db_types'] as $db_type) {
                    if($type_filter == $db_type) {
                        echo $display_name;
                        break 2;
                    }
                }
            }
            ?>
        </span>
        <?php endif; ?>
        <?php if(!empty($search)): ?>
        <span class="filter-badge">Search: "<?php echo htmlspecialchars($search); ?>"</span>
        <?php endif; ?>
        <a href="all_requests.php" class="ms-auto text-decoration-none small">Clear all <i class="fas fa-times-circle ms-1"></i></a>
    </div>
    <?php endif; ?>

    <!-- Requests Table -->
    <div class="requests-table-container">
        <div class="table-responsive">
            <table class="table table-requests" id="requestsTable">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Request No</th>
                        <th>Request Type</th>
                        <th>Employee</th>
                        <th>Request Date</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th width="100">Attachments</th>
                        <th width="110">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($requests) > 0): ?>
                        <?php foreach($requests as $req): 
                            $attStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM request_attachments WHERE request_id = ?");
                            $attStmt->execute([$req['id']]);
                            $attCount = $attStmt->fetch()['cnt'];
                            
                            $display_type = getRequestTypeDisplayFromDB($req['request_type']);
                            $type_class = 'type-unknown';
                            if(strpos($display_type, 'Report an Issue') !== false) $type_class = 'type-technical_support';
                            elseif(strpos($display_type, 'Software Access') !== false) $type_class = 'type-software_access';
                            elseif(strpos($display_type, 'IT Accessories') !== false) $type_class = 'type-accessories';
                            elseif(strpos($display_type, 'Request Upgrade') !== false) $type_class = 'type-upgrade';
                            elseif(strpos($display_type, 'Multiple Device Assignment') !== false) $type_class = 'type-device_assign';
                            
                            $priority = $req['priority'] ?? 'medium';
                            $priority_class = 'priority-' . $priority;
                            $status = $req['status'];
                            $status_class = 'status-' . str_replace('_', '-', $status);
                        ?>
                        <tr>
                            <td class="fw-medium"><?php echo $req['id']; ?></td>
                            <td><span class="fw-semibold"><?php echo htmlspecialchars($req['request_no']); ?></span></td>
                            <td><span class="type-badge <?php echo $type_class; ?>"><?php echo $display_type; ?></span></td>
                            <td>
                                <div class="employee-name"><?php echo htmlspecialchars($req['full_name']); ?></div>
                                <div class="employee-designation"><?php echo htmlspecialchars($req['designation'] ?? ''); ?></div>
                             </div>
                            <td>
                                <?php echo date('d-m-Y', strtotime($req['requested_date'])); ?>
                                <div class="employee-designation"><?php echo date('H:i', strtotime($req['requested_date'])); ?></div>
                             </div>
                            <td><span class="priority-badge <?php echo $priority_class; ?>"><?php echo ucfirst($priority); ?></span></div>
                            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span></div>
                            <td>
                                <?php if($attCount > 0): ?>
                                <span class="attachment-indicator" onclick="showAttachments(<?php echo $req['id']; ?>, '<?php echo htmlspecialchars($req['request_no']); ?>')">
                                    <i class="fas fa-paperclip"></i> <?php echo $attCount; ?> file(s)
                                </span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                             </div>
                            <td>
                                <div class="action-btn-group">
                                    <a href="view_request.php?id=<?php echo $req['id']; ?>" class="btn action-btn btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_request.php?id=<?php echo $req['id']; ?>" class="btn action-btn btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="print_acknowledgement.php?id=<?php echo $req['id']; ?>" target="_blank" class="btn action-btn btn-primary" title="Print">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                             </div>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                                <p class="mb-0 text-muted">No requests found matching your criteria.</p>
                                <a href="all_requests.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 mt-3">
                                    <i class="fas fa-times me-1"></i> Clear All Filters
                                </a>
                             </div>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable if available
    if($.fn.DataTable && $('#requestsTable tbody tr').length > 1) {
        $('#requestsTable').DataTable({
            "pageLength": 25,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "Search within table:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "emptyTable": "No requests found"
            },
            "order": [[0, 'desc']],
            "columnDefs": [
                { "orderable": false, "targets": [7, 8] }
            ]
        });
    }
    
    // Handle type select change
    $('#typeSelect').on('change', function() {
        var selectedVal = $(this).val();
        if(selectedVal && selectedVal !== 'all') {
            var dbTypes = selectedVal.split(',');
            window.location.href = updateQueryStringParameter(window.location.href, 'type', dbTypes[0]);
        } else {
            window.location.href = updateQueryStringParameter(window.location.href, 'type', 'all');
        }
    });
    
    function updateQueryStringParameter(uri, key, value) {
        var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        var separator = uri.indexOf('?') !== -1 ? "&" : "?";
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }
    
    // Auto-submit on status change
    $('#statusSelect').on('change', function() {
        $('#filterForm').submit();
    });
    
    // Image viewer controls
    var currentZoom = 1;
    var currentRotation = 0;
    
    $('#zoomInBtn').click(function() {
        currentZoom = Math.min(currentZoom + 0.2, 3);
        $('#viewerImage').css('transform', 'scale(' + currentZoom + ') rotate(' + currentRotation + 'deg)');
    });
    
    $('#zoomOutBtn').click(function() {
        currentZoom = Math.max(currentZoom - 0.2, 0.5);
        $('#viewerImage').css('transform', 'scale(' + currentZoom + ') rotate(' + currentRotation + 'deg)');
    });
    
    $('#rotateBtn').click(function() {
        currentRotation = (currentRotation + 90) % 360;
        $('#viewerImage').css('transform', 'scale(' + currentZoom + ') rotate(' + currentRotation + 'deg)');
    });
    
    $('#downloadImageBtn').click(function() {
        var imgUrl = $('#viewerImage').attr('src');
        var link = document.createElement('a');
        link.href = imgUrl;
        link.download = 'attachment_' + Date.now() + '.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    $('#downloadPdfBtn').click(function() {
        var pdfUrl = $('#pdfViewer').attr('src');
        var link = document.createElement('a');
        link.href = pdfUrl;
        link.download = 'document_' + Date.now() + '.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Reset zoom and rotation when modal is closed
    $('#imageViewerModal').on('hidden.bs.modal', function() {
        currentZoom = 1;
        currentRotation = 0;
        $('#viewerImage').css('transform', 'scale(1) rotate(0deg)');
    });
});

// Function to show attachments in modal
function showAttachments(requestId, requestNo) {
    $('#attachmentsModal').modal('show');
    $('#attachmentsModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading attachments...</p></div>');
    
    $.ajax({
        url: 'get_attachments.php',
        type: 'GET',
        data: { request_id: requestId },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.attachments.length > 0) {
                var html = '<div class="mb-3 pb-2 border-bottom"><small class="text-muted"><i class="fas fa-folder-open me-1"></i> Request #' + requestNo + ' - ' + response.attachments.length + ' attachment(s)</small></div>';
                for(var i = 0; i < response.attachments.length; i++) {
                    var att = response.attachments[i];
                    var fileUrl = att.file_url;
                    var fileName = att.file_name;
                    var createdAt = att.created_at;
                    var isImage = att.is_image;
                    var fileExt = att.file_ext;
                    
                    // Determine file icon
                    var fileIcon = '';
                    if(isImage) {
                        fileIcon = '<i class="fas fa-file-image file-icon image"></i>';
                    } else if(fileExt === 'pdf') {
                        fileIcon = '<i class="fas fa-file-pdf file-icon pdf"></i>';
                    } else if(['doc', 'docx'].indexOf(fileExt) !== -1) {
                        fileIcon = '<i class="fas fa-file-word file-icon word"></i>';
                    } else if(['xls', 'xlsx'].indexOf(fileExt) !== -1) {
                        fileIcon = '<i class="fas fa-file-excel file-icon excel"></i>';
                    } else {
                        fileIcon = '<i class="fas fa-file-alt file-icon other"></i>';
                    }
                    
                    // Create preview HTML
                    var previewHtml = '';
                    if(isImage) {
                        previewHtml = '<div class="attachment-preview" onclick="viewFullImage(\'' + fileUrl + '\')">' +
                                      '<img src="' + fileUrl + '" alt="Preview" onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'70\' height=\'70\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23999\' stroke-width=\'1\'%3E%3Crect x=\'2\' y=\'2\' width=\'20\' height=\'20\' rx=\'2\'/%3E%3Cpath d=\'M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5\'/%3E%3C/svg%3E\'; this.onerror=null;">' +
                                      '</div>';
                    } else if(fileExt === 'pdf') {
                        previewHtml = '<div class="attachment-preview" onclick="viewPdf(\'' + fileUrl + '\')">' +
                                      '<i class="fas fa-file-pdf" style="font-size: 35px; color: #dc2626;"></i>' +
                                      '</div>';
                    } else {
                        previewHtml = '<div class="attachment-preview">' + fileIcon + '</div>';
                    }
                    
                    html += '<div class="attachment-item d-flex align-items-center gap-3">' +
                            '<div class="attachment-icon">' + fileIcon + '</div>' +
                            '<div class="attachment-info flex-grow-1">' +
                            '<div class="attachment-name">' + escapeHtml(fileName) + '</div>' +
                            '<div class="attachment-meta"><i class="far fa-clock me-1"></i> ' + createdAt + '</div>' +
                            '</div>' +
                            previewHtml +
                            '<div class="d-flex gap-2">' +
                            '<a href="' + fileUrl + '" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">' +
                            '<i class="fas fa-download me-1"></i> Download' +
                            '</a>' +
                            '</div>' +
                            '</div>';
                }
                $('#attachmentsModalBody').html(html);
            } else {
                $('#attachmentsModalBody').html('<div class="text-center py-4"><i class="fas fa-paperclip fa-3x text-muted mb-3 d-block"></i><p class="text-muted">No attachments found for this request.</p></div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#attachmentsModalBody').html('<div class="text-center py-4"><i class="fas fa-exclamation-triangle fa-3x text-danger mb-3 d-block"></i><p class="text-danger">Error loading attachments. Please try again.</p></div>');
        }
    });
}

function viewFullImage(imageUrl) {
    $('#viewerImage').attr('src', imageUrl);
    $('#imageViewerModal').modal('show');
}

function viewPdf(pdfUrl) {
    $('#pdfViewer').attr('src', pdfUrl);
    $('#pdfViewerModal').modal('show');
}

function escapeHtml(text) {
    if(!text) return '';
    return String(text).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}
</script>

<?php include '../../includes/footer.php'; ?>