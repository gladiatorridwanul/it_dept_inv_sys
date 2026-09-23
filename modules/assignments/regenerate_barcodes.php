<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../config/database.php';
require_once '../../includes/auth.php';

// Admin only access
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("Access denied. Admin only.");
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$message_type = '';

/**
 * Generate a proper Code 128 style barcode
 */
function generateBarcodeImage($text, $width = 400, $height = 80) {
    // Create image
    $image = imagecreate($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $white);
    
    // Code 128 character patterns (simplified)
    $patterns = array(
        '0' => '11011001100', '1' => '11001101100', '2' => '11001100110',
        '3' => '10010011000', '4' => '10010001100', '5' => '10001001100',
        '6' => '10011001000', '7' => '10011000100', '8' => '10001100100',
        '9' => '11001001000', 'A' => '11001000100', 'B' => '11000100100',
        'C' => '10110011100', 'D' => '10011011100', 'E' => '10011001110',
        'F' => '10111001100', 'G' => '10011101100', 'H' => '10011100110',
        'I' => '11001110010', 'J' => '11001011100', 'K' => '11001001110',
        'L' => '11011100100', 'M' => '11001110100', 'N' => '11101101110',
        'O' => '11101001100', 'P' => '11100101100', 'Q' => '11100100110',
        'R' => '11101100100', 'S' => '11100110100', 'T' => '11100110010',
        'U' => '11011011000', 'V' => '11011000110', 'W' => '11000110110',
        'X' => '10100011000', 'Y' => '10001011000', 'Z' => '10001000110'
    );
    
    // Convert text to uppercase
    $text = strtoupper($text);
    $barcode_string = '';
    
    // Build barcode pattern
    for($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        if(isset($patterns[$char])) {
            $barcode_string .= $patterns[$char];
        } else {
            $barcode_string .= '11001100110';
        }
    }
    
    // Add start and stop patterns
    $barcode_string = '11010000100' . $barcode_string . '1100011101011';
    
    // Draw the barcode
    $x = 15;
    $bar_height = $height - 20;
    $bar_width = 2;
    
    for($i = 0; $i < strlen($barcode_string); $i++) {
        if($barcode_string[$i] == '1') {
            imagefilledrectangle($image, $x, 8, $x + $bar_width - 1, $bar_height, $black);
        }
        $x += $bar_width;
    }
    
    // Add text below barcode
    $font_size = 3;
    $text_width = imagefontwidth($font_size) * strlen($text);
    $text_x = ($width - $text_width) / 2;
    $text_y = $height - 5;
    imagestring($image, $font_size, $text_x, $text_y, $text, $black);
    
    return $image;
}

// Handle actions
if($action == 'regenerate_all') {
    // Get all public request assignments (even those with barcodes)
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.id, a.assignment_no, a.barcode_path 
        FROM assignments a
        INNER JOIN request_assignments ra ON ra.assignment_id = a.id
        WHERE a.source = 'request'
        ORDER BY a.id DESC
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll();
    
    $total = count($assignments);
    $updated = 0;
    $errors = 0;
    $error_list = [];
    
    $barcode_dir = '../../uploads/barcodes/';
    if(!file_exists($barcode_dir)) {
        mkdir($barcode_dir, 0777, true);
    }
    
    foreach($assignments as $assign) {
        // Delete old barcode file if exists
        if(!empty($assign['barcode_path']) && file_exists('../../' . $assign['barcode_path'])) {
            @unlink('../../' . $assign['barcode_path']);
        }
        
        // Generate new barcode
        $barcode_filename = 'barcode_' . $assign['assignment_no'] . '_' . time() . '_' . $assign['id'] . '.png';
        $barcode_fullpath = $barcode_dir . $barcode_filename;
        
        try {
            $image = generateBarcodeImage($assign['assignment_no']);
            imagepng($image, $barcode_fullpath);
            imagedestroy($image);
            
            // Update database
            $update_stmt = $pdo->prepare("UPDATE assignments SET barcode_path = ? WHERE id = ?");
            if($update_stmt->execute(['uploads/barcodes/' . $barcode_filename, $assign['id']])) {
                $updated++;
            } else {
                $errors++;
                $error_list[] = "DB Update failed for: " . $assign['assignment_no'];
            }
        } catch(Exception $e) {
            $errors++;
            $error_list[] = "Error for " . $assign['assignment_no'] . ": " . $e->getMessage();
        }
    }
    
    $message = "Regeneration complete!";
    $message_type = $errors > 0 ? 'warning' : 'success';
    $message .= "<br>Total: $total, Updated: $updated, Errors: $errors";
    if(!empty($error_list)) {
        $message .= "<br><br><strong>Error Details:</strong><br>" . implode("<br>", $error_list);
    }
}

if($action == 'generate_missing') {
    // Get only public request assignments WITHOUT barcode
    $stmt = $pdo->prepare("
        SELECT DISTINCT a.id, a.assignment_no 
        FROM assignments a
        INNER JOIN request_assignments ra ON ra.assignment_id = a.id
        WHERE a.source = 'request' 
        AND (a.barcode_path IS NULL OR a.barcode_path = '')
        ORDER BY a.id DESC
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll();
    
    $total = count($assignments);
    $generated = 0;
    $errors = 0;
    $error_list = [];
    
    if($total == 0) {
        $message = "No missing barcodes found! All public request assignments have barcodes.";
        $message_type = 'success';
    } else {
        $barcode_dir = '../../uploads/barcodes/';
        if(!file_exists($barcode_dir)) {
            mkdir($barcode_dir, 0777, true);
        }
        
        foreach($assignments as $assign) {
            $barcode_filename = 'barcode_' . $assign['assignment_no'] . '_' . time() . '_' . $assign['id'] . '.png';
            $barcode_fullpath = $barcode_dir . $barcode_filename;
            
            try {
                $image = generateBarcodeImage($assign['assignment_no']);
                imagepng($image, $barcode_fullpath);
                imagedestroy($image);
                
                $update_stmt = $pdo->prepare("UPDATE assignments SET barcode_path = ? WHERE id = ?");
                if($update_stmt->execute(['uploads/barcodes/' . $barcode_filename, $assign['id']])) {
                    $generated++;
                } else {
                    $errors++;
                    $error_list[] = "DB Update failed for: " . $assign['assignment_no'];
                }
            } catch(Exception $e) {
                $errors++;
                $error_list[] = "Error for " . $assign['assignment_no'] . ": " . $e->getMessage();
            }
        }
        
        $message = "Missing barcodes generated!";
        $message_type = $errors > 0 ? 'warning' : 'success';
        $message .= "<br>Total: $total, Generated: $generated, Errors: $errors";
        if(!empty($error_list)) {
            $message .= "<br><br><strong>Error Details:</strong><br>" . implode("<br>", $error_list);
        }
    }
}

// Get statistics
$stats = $pdo->query("
    SELECT 
        (SELECT COUNT(DISTINCT a.id) 
         FROM assignments a 
         INNER JOIN request_assignments ra ON ra.assignment_id = a.id 
         WHERE a.source = 'request') as total_request_assignments,
        (SELECT COUNT(DISTINCT a.id) 
         FROM assignments a 
         INNER JOIN request_assignments ra ON ra.assignment_id = a.id 
         WHERE a.source = 'request' 
         AND a.barcode_path IS NOT NULL 
         AND a.barcode_path != '') as has_barcode,
        (SELECT COUNT(DISTINCT a.id) 
         FROM assignments a 
         INNER JOIN request_assignments ra ON ra.assignment_id = a.id 
         WHERE a.source = 'request' 
         AND (a.barcode_path IS NULL OR a.barcode_path = '')) as no_barcode
")->fetch();

$total_request_assignments = $stats['total_request_assignments'] ?? 0;
$has_barcode = $stats['has_barcode'] ?? 0;
$no_barcode = $stats['no_barcode'] ?? 0;

// Get sample assignments without barcode
$sample_stmt = $pdo->prepare("
    SELECT DISTINCT a.id, a.assignment_no, a.barcode_path
    FROM assignments a
    INNER JOIN request_assignments ra ON ra.assignment_id = a.id
    WHERE a.source = 'request' 
    AND (a.barcode_path IS NULL OR a.barcode_path = '')
    LIMIT 10
");
$sample_stmt->execute();
$samples = $sample_stmt->fetchAll();

// Get sample assignments with barcode (for testing)
$test_stmt = $pdo->prepare("
    SELECT DISTINCT a.id, a.assignment_no, a.barcode_path
    FROM assignments a
    INNER JOIN request_assignments ra ON ra.assignment_id = a.id
    WHERE a.source = 'request' 
    AND a.barcode_path IS NOT NULL 
    AND a.barcode_path != ''
    LIMIT 1
");
$test_stmt->execute();
$test_sample = $test_stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerate Public Request Barcodes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; 
            background: #f1f5f9; 
            padding: 20px; 
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
        }
        h1 { 
            color: #0f172a; 
            font-size: 24px; 
            border-bottom: 2px solid #e2e8f0; 
            padding-bottom: 15px; 
            margin-bottom: 20px;
        }
        h1 i { color: #3b82f6; margin-right: 10px; }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 15px; 
            margin: 20px 0; 
        }
        .stat-box { 
            background: #f8fafc; 
            padding: 18px; 
            border-radius: 12px; 
            text-align: center; 
            border: 1px solid #e2e8f0; 
            transition: transform 0.2s;
        }
        .stat-box:hover { transform: translateY(-3px); }
        .stat-number { 
            font-size: 32px; 
            font-weight: 700; 
            color: #0f172a; 
            line-height: 1.2;
        }
        .stat-number.green { color: #22c55e; }
        .stat-number.red { color: #ef4444; }
        .stat-number.blue { color: #3b82f6; }
        .stat-label { 
            color: #64748b; 
            font-size: 13px; 
            margin-top: 5px; 
            font-weight: 500;
        }
        
        .alert { 
            padding: 16px 20px; 
            border-radius: 12px; 
            margin: 15px 0; 
            font-size: 14px;
            line-height: 1.6;
        }
        .alert-success { 
            background: #dcfce7; 
            color: #166534; 
            border: 1px solid #86efac; 
        }
        .alert-warning { 
            background: #fef3c7; 
            color: #92400e; 
            border: 1px solid #fcd34d; 
        }
        .alert-danger { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fca5a5; 
        }
        .alert-info { 
            background: #dbeafe; 
            color: #1e40af; 
            border: 1px solid #93c5fd; 
        }
        
        .btn { 
            display: inline-block; 
            padding: 10px 24px; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px;
            margin: 5px; 
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #22c55e; color: white; }
        .btn-success:hover { background: #16a34a; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #64748b; color: white; }
        .btn-secondary:hover { background: #475569; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }
        
        .actions { 
            margin: 25px 0; 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            align-items: center;
        }
        
        .warning-box { 
            background: #fef3c7; 
            border: 1px solid #f59e0b; 
            padding: 18px; 
            border-radius: 12px; 
            margin: 15px 0; 
        }
        .warning-box h3 { color: #92400e; margin-bottom: 10px; font-size: 16px; }
        .warning-box ul { padding-left: 20px; color: #78350f; font-size: 14px; }
        .warning-box ul li { margin-bottom: 5px; }
        
        .sample-list { 
            background: #f8fafc; 
            padding: 15px 20px; 
            border-radius: 12px; 
            margin: 15px 0; 
            border: 1px solid #e2e8f0; 
        }
        .sample-list h4 { color: #0f172a; margin-bottom: 10px; font-size: 15px; }
        .sample-item { 
            padding: 6px 0; 
            border-bottom: 1px solid #e2e8f0; 
            font-size: 13px; 
            font-family: 'Courier New', monospace; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sample-item:last-child { border-bottom: none; }
        .sample-item .badge { 
            font-size: 11px; 
            padding: 2px 10px; 
            border-radius: 20px; 
            font-weight: 600;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        }
        .badge-missing { background: #fee2e2; color: #991b1b; }
        .badge-exists { background: #dcfce7; color: #166534; }
        
        .divider { 
            margin: 25px 0; 
            padding-top: 25px; 
            border-top: 1px solid #e2e8f0; 
        }
        .divider h3 { color: #0f172a; font-size: 17px; margin-bottom: 10px; }
        .divider p { color: #64748b; font-size: 14px; }
        
        .progress-container {
            display: none;
            margin: 20px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .progress-container.active { display: block; }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar .progress-fill {
            height: 100%;
            background: #3b82f6;
            width: 0%;
            transition: width 0.5s;
            border-radius: 4px;
        }
        .progress-text { font-size: 14px; color: #0f172a; font-weight: 500; }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .actions { flex-direction: column; }
            .actions .btn { width: 100%; text-align: center; }
            .sample-item { flex-direction: column; align-items: flex-start; gap: 5px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-qrcode"></i> Public Request Barcode Manager</h1>
        
        <?php if(!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'warning' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-number blue"><?php echo $total_request_assignments; ?></div>
                <div class="stat-label">Total Public Request Assignments</div>
            </div>
            <div class="stat-box">
                <div class="stat-number green"><?php echo $has_barcode; ?></div>
                <div class="stat-label">Has Barcode <i class="fas fa-check-circle" style="color: #22c55e;"></i></div>
            </div>
            <div class="stat-box">
                <div class="stat-number red"><?php echo $no_barcode; ?></div>
                <div class="stat-label">Missing Barcode <i class="fas fa-exclamation-circle" style="color: #ef4444;"></i></div>
            </div>
        </div>
        
        <!-- Warning -->
        <div class="warning-box">
            <h3><i class="fas fa-exclamation-triangle"></i> Important Information</h3>
            <ul>
                <li><strong>Regenerate All:</strong> Recreates barcodes for ALL public request assignments (replaces existing ones)</li>
                <li><strong>Generate Missing:</strong> Only creates barcodes for assignments that don't have one yet</li>
                <li>Old barcode files will be <strong>deleted</strong> when regenerating all</li>
                <li>It is recommended to <strong>backup</strong> the database before running</li>
                <li>The process may take a few moments depending on the number of assignments</li>
            </ul>
        </div>
        
        <!-- Sample List -->
        <?php if($no_barcode > 0 && count($samples) > 0): ?>
        <div class="sample-list">
            <h4><i class="fas fa-list"></i> Sample of Missing Barcodes (showing up to 10)</h4>
            <?php foreach($samples as $sample): ?>
                <div class="sample-item">
                    <span>Assignment: <strong><?php echo $sample['assignment_no']; ?></strong> (ID: <?php echo $sample['id']; ?>)</span>
                    <span class="badge badge-missing">Missing Barcode</span>
                </div>
            <?php endforeach; ?>
            <?php if($no_barcode > 10): ?>
                <div class="sample-item" style="color: #64748b; font-family: inherit; border-bottom: none; padding-top: 10px;">
                    <i class="fas fa-ellipsis-h"></i> ... and <?php echo $no_barcode - 10; ?> more
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Test Sample -->
        <?php if($test_sample): ?>
        <div class="sample-list" style="border-color: #86efac; background: #f0fdf4;">
            <h4><i class="fas fa-check-circle" style="color: #22c55e;"></i> Test Assignment with Barcode</h4>
            <div class="sample-item">
                <span>Assignment: <strong><?php echo $test_sample['assignment_no']; ?></strong> (ID: <?php echo $test_sample['id']; ?>)</span>
                <span class="badge badge-exists">Has Barcode</span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="actions">
            <a href="?action=regenerate_all" class="btn btn-danger" onclick="return confirmRegenerateAll();">
                <i class="fas fa-sync-alt"></i> Regenerate All Barcodes
            </a>
            <a href="?action=generate_missing" class="btn btn-success" onclick="return confirmGenerateMissing();">
                <i class="fas fa-plus-circle"></i> Generate Missing Barcodes
            </a>
            <?php if($test_sample): ?>
                <a href="barcode_label.php?id=<?php echo $test_sample['id']; ?>&print=auto" target="_blank" class="btn btn-primary">
                    <i class="fas fa-print"></i> Test Barcode
                </a>
            <?php endif; ?>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
        
        <!-- Progress Container -->
        <div class="progress-container" id="progressContainer">
            <div class="progress-text" id="progressText">Processing...</div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="divider">
            <h3><i class="fas fa-info-circle"></i> Quick Information</h3>
            <p>
                <strong>Total Assignments:</strong> <?php echo $total_request_assignments; ?> | 
                <strong>With Barcode:</strong> <?php echo $has_barcode; ?> | 
                <strong>Missing:</strong> <?php echo $no_barcode; ?>
                <?php if($no_barcode > 0): ?>
                    <br><span style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> <?php echo $no_barcode; ?> assignments need barcode generation.</span>
                <?php else: ?>
                    <br><span style="color: #22c55e;"><i class="fas fa-check-circle"></i> All public request assignments have barcodes!</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <script>
        function confirmRegenerateAll() {
            return confirm(
                '⚠️ WARNING: This will REGENERATE ALL barcodes for public request assignments.\n\n' +
                'This will:\n' +
                '• Delete ALL existing barcode files for public request assignments\n' +
                '• Create new barcode files\n' +
                '• Update the database\n\n' +
                'Total assignments to process: <?php echo $total_request_assignments; ?>\n\n' +
                'Are you sure you want to continue?'
            );
        }
        
        function confirmGenerateMissing() {
            if(<?php echo $no_barcode; ?> === 0) {
                alert('No missing barcodes found! All public request assignments have barcodes.');
                return false;
            }
            return confirm(
                'This will generate barcodes for <?php echo $no_barcode; ?> assignments that are missing barcodes.\n\n' +
                'Are you sure you want to continue?'
            );
        }
        
        // Show progress on page load if action is being processed
        <?php if(isset($_GET['action']) && ($_GET['action'] == 'regenerate_all' || $_GET['action'] == 'generate_missing')): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('progressContainer');
            container.classList.add('active');
            document.getElementById('progressText').textContent = 'Processing barcodes... Please wait...';
            document.getElementById('progressFill').style.width = '100%';
        });
        <?php endif; ?>
    </script>
</body>
</html>