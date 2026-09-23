<?php
require_once '../../config/database.php';
require_once '../../includes/request_functions.php';

$id = $_GET['id'] ?? 0;

// Get main request data
$stmt = $pdo->prepare("
    SELECT r.*, e.full_name, e.pf_no, e.designation, e.department, e.job_location, e.phone, e.email,
           u.full_name as processed_by_name, ru.full_name as resolved_by_name
    FROM requests r 
    JOIN employees e ON r.employee_id = e.id 
    LEFT JOIN users u ON r.processed_by = u.id
    LEFT JOIN users ru ON r.resolved_by = ru.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$request = $stmt->fetch();

if(!$request) {
    die("<div style='text-align:center; padding:50px;'><h2>Request not found!</h2><a href='all_requests.php'>Back to Requests</a></div>");
}

// Get assignments (allocated items)
$assignments = [];
try {
    $stmt = $pdo->prepare("
        SELECT ra.*, i.name as item_name, i.item_code, i.brand,
               isn.serial_number, isn.model_number, isn.version,
               a.assignment_no, a.assigned_date, a.expected_return_date, a.status as assignment_status
        FROM request_assignments ra
        LEFT JOIN items i ON ra.item_id = i.id
        LEFT JOIN assignments a ON ra.assignment_id = a.id
        LEFT JOIN item_serial_numbers isn ON a.id = isn.assignment_id
        WHERE ra.request_id = ? AND ra.status IN ('allocated', 'delivered')
        ORDER BY ra.created_at DESC
    ");
    $stmt->execute([$id]);
    $assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    $assignments = [];
}

// Get specific request details based on type
$multiple_devices = [];
$specific_data = null;

if($request['request_type'] == 'device_assign') {
    if(!empty($request['request_data'])) {
        $request_data = json_decode($request['request_data'], true);
        if($request_data && isset($request_data['devices'])) {
            $multiple_devices = $request_data['devices'];
        }
    }
    if(empty($multiple_devices)) {
        $stmt = $pdo->prepare("
            SELECT adr.*, i.name as item_name, i.item_code, i.specification, i.serial_number, i.model_number
            FROM assign_device_requests adr
            LEFT JOIN items i ON adr.device_id = i.id
            WHERE adr.request_id = ?
        ");
        $stmt->execute([$id]);
        $multiple_devices = $stmt->fetchAll();
    }
} elseif($request['request_type'] == 'technical_support') {
    $stmt = $pdo->prepare("SELECT * FROM technical_support_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
    if(!$specific_data && $request['request_data']) {
        $specific_data = json_decode($request['request_data'], true);
    }
} elseif($request['request_type'] == 'software_access') {
    $stmt = $pdo->prepare("SELECT * FROM software_access_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
    if(!$specific_data && $request['request_data']) {
        $specific_data = json_decode($request['request_data'], true);
    }
} elseif($request['request_type'] == 'accessories') {
    $stmt = $pdo->prepare("SELECT * FROM accessories_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
    if(!$specific_data && $request['request_data']) {
        $specific_data = json_decode($request['request_data'], true);
    }
} elseif($request['request_type'] == 'return_device') {
    $stmt = $pdo->prepare("SELECT * FROM return_device_requests WHERE request_id = ?");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
} elseif($request['request_type'] == 'upgrade') {
    $stmt = $pdo->prepare("
        SELECT ur.*, i.name as current_item_name, i.item_code as current_item_code,
               a.assignment_no
        FROM upgrade_requests ur
        LEFT JOIN items i ON ur.assignment_id = i.id
        LEFT JOIN assignments a ON ur.assignment_id = a.id
        WHERE ur.request_id = ?
    ");
    $stmt->execute([$id]);
    $specific_data = $stmt->fetch();
}

// Parse description for technical support
$description_lines = explode("\n", $request['description'] ?? '');
$parsed_details = [];
foreach($description_lines as $line) {
    if(trim($line) && strpos($line, ':') !== false) {
        list($key, $value) = explode(':', $line, 2);
        $parsed_details[trim($key)] = trim($value);
    }
}

// Helper function to get status text
function getStatusText($status) {
    $statusMap = [
        'pending' => 'PENDING',
        'under_observation' => 'UNDER OBSERVATION',
        'processing' => 'PROCESSING',
        'completed' => 'COMPLETED',
        'rejected' => 'REJECTED'
    ];
    return $statusMap[$status] ?? strtoupper(str_replace('_', ' ', $status));
}

function getPriorityText($priority) {
    $priorityMap = [
        'low' => 'LOW',
        'medium' => 'MEDIUM',
        'high' => 'HIGH',
        'critical' => 'CRITICAL'
    ];
    return $priorityMap[$priority] ?? 'MEDIUM';
}

$created = new DateTime($request['requested_date']);
$now = new DateTime();
$diff = $created->diff($now);
$daysOpen = $diff->days;

$requestTypeMap = [
    'technical_support' => 'Technical Support / Report an Issue',
    'software_access' => 'Software Access Request',
    'accessories' => 'Accessories Request',
    'return_device' => 'Return Device Request',
    'upgrade' => 'Upgrade Request',
    'device_assign' => 'Multiple Device Assignment',
];
$requestTypeDisplay = $requestTypeMap[$request['request_type']] ?? ucfirst(str_replace('_', ' ', $request['request_type']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Acknowledgement - <?php echo htmlspecialchars($request['request_no']); ?></title>
    <style>
        /* A4 Print Styles - Black & White, Cambria Font */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @page {
            size: A4;
            margin: 0.7in 0.5in;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .acknowledgement-container {
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            .page-break {
                page-break-before: avoid;
                page-break-inside: avoid;
            }
            table, .info-section, .details-section {
                page-break-inside: avoid;
            }
        }
        
        body {
            font-family: 'Cambria', 'Times New Roman', Georgia, serif;
            font-size: 10pt;
            line-height: 1.35;
            background: white;
            color: black;
        }
        
        .acknowledgement-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
        }
        
        /* Header - Black & White */
        .ack-header {
            border-bottom: 2px solid black;
            padding: 8px 0 12px 0;
            text-align: center;
            margin-bottom: 15px;
        }
        .company-name {
            font-size: 14pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 5px;
            text-decoration: underline;
        }
        .doc-subtitle {
            font-size: 8pt;
            margin-top: 3px;
        }
        
        /* Info Sections - Black & White */
        .info-section {
            border: 1px solid black;
            margin-bottom: 10px;
            background: white;
        }
        .section-header {
            background: #ddd;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 9pt;
            text-transform: uppercase;
            border-bottom: 1px solid black;
        }
        .section-content {
            padding: 6px 8px;
        }
        .info-row {
            display: flex;
            margin-bottom: 3px;
            font-size: 9pt;
            padding-bottom: 2px;
        }
        .info-label {
            width: 110px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .info-value {
            flex: 1;
        }
        
        /* Two Column Layout */
        .two-column-layout {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }
        .left-column {
            flex: 1;
            min-width: 45%;
        }
        .right-column {
            flex: 1;
            min-width: 45%;
        }
        
        /* Details Section */
        .details-section {
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 6px;
            padding-bottom: 2px;
            border-bottom: 1.5px solid black;
            text-transform: uppercase;
        }
        
        /* Tables - Black & White */
        .details-table, .device-table, .assignment-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
            border: 1px solid black;
        }
        .details-table td, .device-table td, .assignment-table td,
        .details-table th, .device-table th, .assignment-table th {
            padding: 4px 6px;
            border: 1px solid black;
            vertical-align: top;
        }
        .details-table td:first-child {
            width: 28%;
            font-weight: bold;
            background: #f5f5f5;
        }
        .device-table th, .assignment-table th {
            background: #eee;
            font-weight: bold;
            text-align: left;
        }
        
        /* Resolution Box */
        .resolution-box {
            border: 1px solid black;
            padding: 6px 8px;
            background: #f9f9f9;
            font-size: 8.5pt;
        }
        
        /* Signature Section */
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid black;
        }
        .signature-box {
            text-align: center;
            width: 45%;
        }
        .signature-line {
            border-bottom: 1px solid black;
            margin-top: 25px;
            margin-bottom: 4px;
            width: 100%;
        }
        .signature-label {
            font-size: 8pt;
            font-weight: bold;
        }
        
        /* Footer */
        .ack-footer {
            border-top: 1px solid black;
            padding: 6px 0;
            text-align: center;
            font-size: 7pt;
            margin-top: 8px;
            background: #f5f5f5;
        }
        
        /* Action Buttons - Screen only */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 15px 20px;
            background: white;
            border-top: 1px solid #ccc;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 20px;
            border: 1px solid #666;
            border-radius: 4px;
            font-size: 10pt;
            cursor: pointer;
            text-decoration: none;
            background: #f0f0f0;
            color: black;
            font-family: 'Cambria', 'Times New Roman', serif;
        }
        .btn:hover {
            background: #e0e0e0;
        }
        
        /* Responsive */
        @media (max-width: 700px) {
            .two-column-layout {
                flex-direction: column;
                gap: 10px;
            }
        }
        
        /* Status text */
        .status-text, .priority-text {
            font-weight: bold;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
<div class="acknowledgement-container">
    <!-- Header -->
    <div class="ack-header">
        <div class="company-name">IT INVENTORY MANAGEMENT SYSTEM</div>
        <div class="doc-title">REQUEST ACKNOWLEDGEMENT</div>
        <div class="doc-subtitle">Official Record of Support Request</div>
    </div>
    
    <!-- Two Column Layout -->
    <div class="two-column-layout">
        <!-- Left Column -->
        <div class="left-column">
            <!-- Request Information Section -->
            <div class="info-section">
                <div class="section-header">REQUEST INFORMATION</div>
                <div class="section-content">
                    <div class="info-row">
                        <div class="info-label">Request No:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['request_no']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Request Type:</div>
                        <div class="info-value"><?php echo htmlspecialchars($requestTypeDisplay); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value"><span class="status-text"><?php echo getStatusText($request['status']); ?></span></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Priority:</div>
                        <div class="info-value"><span class="priority-text"><?php echo getPriorityText($request['priority'] ?? 'medium'); ?></span></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Request Date:</div>
                        <div class="info-value"><?php echo date('d-m-Y h:i A', strtotime($request['requested_date'])); ?></div>
                    </div>
                    <?php if($request['estimated_completion_date']): ?>
                    <div class="info-row">
                        <div class="info-label">Est. Completion:</div>
                        <div class="info-value"><?php echo date('d-m-Y', strtotime($request['estimated_completion_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($request['assigned_to_team']): ?>
                    <div class="info-row">
                        <div class="info-label">Assigned Team:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['assigned_to_team']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Employee Information Section -->
            <div class="info-section">
                <div class="section-header">EMPLOYEE INFORMATION</div>
                <div class="section-content">
                    <div class="info-row">
                        <div class="info-label">Full Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['full_name']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">PF No:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['pf_no']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Designation:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['designation'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Department:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['department'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Job Location:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['job_location'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Phone:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['phone'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['email'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="right-column">
            <!-- Tracking Information Section -->
            <div class="info-section">
                <div class="section-header">TRACKING INFORMATION</div>
                <div class="section-content">
                    <div class="info-row">
                        <div class="info-label">Created On:</div>
                        <div class="info-value"><?php echo date('d-m-Y H:i:s', strtotime($request['created_at'])); ?></div>
                    </div>
                    <?php if($request['accepted_date']): ?>
                    <div class="info-row">
                        <div class="info-label">First Reviewed:</div>
                        <div class="info-value"><?php echo date('d-m-Y H:i:s', strtotime($request['accepted_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if($request['resolved_date']): ?>
                    <div class="info-row">
                        <div class="info-label">Resolved Date:</div>
                        <div class="info-value"><?php echo date('d-m-Y H:i:s', strtotime($request['resolved_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="info-row">
                        <div class="info-label">Days Open:</div>
                        <div class="info-value"><?php echo $daysOpen; ?> days</div>
                    </div>
                    <?php if($request['processed_by_name']): ?>
                    <div class="info-row">
                        <div class="info-label">Processed By:</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['processed_by_name']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Additional Details for Specific Request Types -->
            <?php if($specific_data && $request['request_type'] != 'device_assign'): 
                $exclude_fields = ['id', 'request_id', 'created_at', 'updated_at'];
            ?>
            <div class="info-section">
                <div class="section-header">ADDITIONAL DETAILS</div>
                <div class="section-content">
                    <?php foreach($specific_data as $key => $value): 
                        if(in_array($key, $exclude_fields)) continue;
                        if(empty($value) && $value !== '0') continue;
                        $label = ucfirst(str_replace('_', ' ', $key));
                        if($key == 'urgent_requirement') $value = $value ? 'Yes' : 'No';
                        if($key == 'supervisor_approval') $value = $value ? 'Approved' : 'Pending';
                        if($key == 'budget_approval') $value = $value ? 'Approved' : 'Not Approved';
                        if($key == 'under_warranty') $value = $value ? 'Yes' : 'No';
                        if($key == 'data_backup_confirmed') $value = $value ? 'Yes' : 'No';
                        if($key == 'cleaning_done') $value = $value ? 'Yes' : 'No';
                    ?>
                    <div class="info-row">
                        <div class="info-label"><?php echo $label; ?>:</div>
                        <div class="info-value"><?php echo nl2br(htmlspecialchars((string)$value)); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Requested Details Section -->
    <div class="details-section">
        <div class="section-title">REQUESTED ITEMS / DETAILS</div>
        
        <?php if($request['request_type'] == 'upgrade' && $specific_data): ?>
            <table class="details-table">
                <?php if($specific_data['assignment_no']): ?>
                <tr><td>Current Assignment No</td><td><?php echo htmlspecialchars($specific_data['assignment_no']); ?></td></tr>
                <?php endif; ?>
                <tr><td>Required Upgrade</td><td><?php echo nl2br(htmlspecialchars($specific_data['required_upgrade'] ?? 'Not specified')); ?></td></tr>
                <tr><td>Reason for Upgrade</td><td><?php echo nl2br(htmlspecialchars($specific_data['reason'] ?? 'Not specified')); ?></td></tr>
                <?php if($specific_data['device_condition']): ?>
                <tr><td>Device Condition</td><td><?php echo ucfirst(str_replace('_', ' ', $specific_data['device_condition'])); ?></td></tr>
                <?php endif; ?>
            </table>
            
        <?php elseif($request['request_type'] == 'device_assign' && count($multiple_devices) > 0): ?>
            <table class="device-table">
                <thead><tr><th width="5%">#</th><th width="30%">Device Name</th><th width="15%">Item Code</th><th width="35%">Specifications</th><th width="15%">Qty</th></tr></thead>
                <tbody>
                    <?php $counter = 1; foreach($multiple_devices as $device): 
                        $device_name = $device['item_name'] ?? $device['device_name'] ?? 'N/A';
                        $item_code = $device['item_code'] ?? 'N/A';
                        $specs = $device['specification'] ?? $device['required_specifications'] ?? 'N/A';
                        $qty = $device['quantity'] ?? 1;
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><strong><?php echo htmlspecialchars($device_name); ?></strong></td>
                        <td><?php echo htmlspecialchars($item_code); ?></td>
                        <td><?php echo nl2br(htmlspecialchars(substr($specs, 0, 100))); ?></td>
                        <td class="text-center"><?php echo $qty; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php 
            $assign_info = json_decode($request['request_data'], true);
            if($assign_info && (isset($assign_info['assigned_by_date']) || isset($assign_info['overall_reason']))):
            ?>
            <table class="details-table">
                <?php if(isset($assign_info['assigned_by_date']) && $assign_info['assigned_by_date']): ?>
                <tr><td>Assignment Date</td><td><?php echo date('d-m-Y', strtotime($assign_info['assigned_by_date'])); ?></td></tr>
                <?php endif; ?>
                <?php if(isset($assign_info['overall_reason']) && $assign_info['overall_reason']): ?>
                <tr><td>Overall Reason</td><td><?php echo nl2br(htmlspecialchars(substr($assign_info['overall_reason'], 0, 200))); ?></td></tr>
                <?php endif; ?>
            </table>
            <?php endif; ?>
            
        <?php elseif(count($parsed_details) > 0): ?>
            <table class="details-table">
                <?php foreach($parsed_details as $key => $value): ?>
                <tr><td><?php echo htmlspecialchars($key); ?></td><td><?php echo nl2br(htmlspecialchars(substr($value, 0, 200))); ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <table class="details-table">
                <tr><td>Description</td><td><?php echo nl2br(htmlspecialchars(substr($request['description'] ?? 'No description provided.', 0, 300))); ?></td></tr>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- ALLOCATED ITEMS SECTION -->
    <?php if(count($assignments) > 0): ?>
    <div class="details-section">
        <div class="section-title">ALLOCATED ITEMS / DEVICES</div>
        <table class="assignment-table">
            <thead><tr><th>Assignment No</th><th>Item</th><th>Serial Number</th><th>Model</th><th>Allocation Date</th></tr></thead>
            <tbody>
                <?php foreach($assignments as $assign): ?>
                <tr>
                    <td><?php echo htmlspecialchars($assign['assignment_no'] ?? 'N/A'); ?></td>
                    <td><strong><?php echo htmlspecialchars($assign['item_name'] ?? 'N/A'); ?></strong><br><small><?php echo htmlspecialchars($assign['item_code'] ?? ''); ?></small></td>
                    <td><?php echo htmlspecialchars($assign['serial_number'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($assign['model_number'] ?? '-'); ?></td>
                    <td><?php echo !empty($assign['assigned_date']) ? date('d-m-Y', strtotime($assign['assigned_date'])) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Resolution Notes -->
    <?php if($request['resolution_notes']): ?>
    <div class="details-section">
        <div class="section-title">RESOLUTION NOTES</div>
        <div class="resolution-box"><?php echo nl2br(htmlspecialchars(substr($request['resolution_notes'], 0, 300))); ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">EMPLOYEE SIGNATURE</div>
            <div class="signature-label" style="font-size:7pt; margin-top:3px;">Name: <?php echo htmlspecialchars($request['full_name']); ?></div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">IT DEPARTMENT SIGNATURE</div>
            <div class="signature-label" style="font-size:7pt; margin-top:3px;">Authorized Signatory</div>
        </div>
    </div>
    
    <!-- Footer -->
    <div class="ack-footer">
        This is an official system-generated acknowledgement. 
        <br>Generated on: <?php echo date('d-m-Y H:i:s'); ?>
    </div>
    
    <!-- Action Buttons (Screen only, hidden when printing) -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="btn">🖨️ Print / Save as PDF</button>
        <a href="view_request.php?id=<?php echo $id; ?>" class="btn">← View Full Request</a>
        <a href="all_requests.php" class="btn">← Back to All Requests</a>
    </div>
</div>

<script>
    // Auto-trigger print dialog when page loads (optional - uncomment if needed)
    // window.addEventListener('load', function() {
    //     window.print();
    // });
</script>
</body>
</html>