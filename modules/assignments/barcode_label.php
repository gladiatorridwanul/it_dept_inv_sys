<?php
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;
$copies = isset($_GET['copies']) ? intval($_GET['copies']) : 1;

// Limit copies to prevent abuse
if($copies > 50) $copies = 50;
if($copies < 1) $copies = 1;

$stmt = $pdo->prepare("SELECT a.*, e.full_name, e.pf_no, i.name as item_name, i.item_code, i.serial_number
                       FROM assignments a 
                       JOIN employees e ON a.employee_id = e.id 
                       JOIN items i ON a.item_id = i.id 
                       WHERE a.id = ?");
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if(!$assignment) {
    die("Assignment not found!");
}

// Check and set source if not already set
if(empty($assignment['source'])) {
    try {
        $check_request = $pdo->prepare("SELECT COUNT(*) FROM request_assignments WHERE assignment_id = ?");
        $check_request->execute([$id]);
        $count = $check_request->fetchColumn();
        
        if($count > 0) {
            $update_source = $pdo->prepare("UPDATE assignments SET source = 'request' WHERE id = ?");
            $update_source->execute([$id]);
            $assignment['source'] = 'request';
        } else {
            try {
                $update_source = $pdo->prepare("UPDATE assignments SET source = 'admin' WHERE id = ?");
                $update_source->execute([$id]);
                $assignment['source'] = 'admin';
            } catch(PDOException $e) {
                $assignment['source'] = 'admin';
            }
        }
    } catch(PDOException $e) {
        $assignment['source'] = 'admin';
    }
}

/**
 * Generate a barcode image
 * Size: 1.5 inch width x 0.5 inch height (at 300 DPI = 450px x 150px)
 */
function generateBarcodeImage($text, $width = 450, $height = 150) {
    // Create image with white background
    $image = imagecreate($width, $height);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    // Fill with white background
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
    
    // Build barcode pattern from text
    for($i = 0; $i < strlen($text); $i++) {
        $char = $text[$i];
        if(isset($patterns[$char])) {
            $barcode_string .= $patterns[$char];
        } else {
            $barcode_string .= '11001100110'; // Default pattern for unknown chars
        }
    }
    
    // Add start and stop patterns for Code 128
    $barcode_string = '11010000100' . $barcode_string . '1100011101011';
    
    // Calculate bar width to fit in the image
    $total_bars = strlen($barcode_string);
    $padding = 20;
    $available_width = $width - ($padding * 2);
    $bar_width = max(1, floor($available_width / $total_bars));
    
    // Adjust bar width to use full width
    if($bar_width < 1) $bar_width = 1;
    
    // Calculate actual width used
    $total_width = $total_bars * $bar_width;
    $start_x = ($width - $total_width) / 2;
    
    // Draw the barcode
    $x = $start_x;
    $bar_height = $height - 10; // Leave small margin at top and bottom
    
    for($i = 0; $i < strlen($barcode_string); $i++) {
        if($barcode_string[$i] == '1') {
            imagefilledrectangle($image, $x, 5, $x + $bar_width - 1, $bar_height, $black);
        }
        $x += $bar_width;
    }
    
    return $image;
}

// Generate barcode if not exists or if file is missing
if(empty($assignment['barcode_path']) || !file_exists('../../' . $assignment['barcode_path'])) {
    $barcode_dir = '../../uploads/barcodes/';
    if(!file_exists($barcode_dir)) {
        mkdir($barcode_dir, 0777, true);
    }
    
    $barcode_filename = 'barcode_' . $assignment['assignment_no'] . '_' . time() . '.png';
    $barcode_fullpath = $barcode_dir . $barcode_filename;
    
    // Generate barcode image (1.5in x 0.5in at ~300 DPI)
    $image = generateBarcodeImage($assignment['assignment_no'], 450, 150);
    imagepng($image, $barcode_fullpath);
    imagedestroy($image);
    
    $update_stmt = $pdo->prepare("UPDATE assignments SET barcode_path = ? WHERE id = ?");
    $update_stmt->execute(['uploads/barcodes/' . $barcode_filename, $id]);
    
    $barcodePath = 'uploads/barcodes/' . $barcode_filename;
} else {
    $barcodePath = $assignment['barcode_path'];
}

$hasBarcode = !empty($barcodePath) && file_exists('../../' . $barcodePath);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Label - <?php echo $assignment['assignment_no']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Label size: 1.5in width x 0.5in height */
        @page {
            size: 1.5in 0.5in;
            margin: 0;
        }
        
        body {
            background: white;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        
        .label-container {
            width: 1.5in;
            height: 0.5in;
            display: flex;
            align-items: center;
            justify-content: center;
            page-break-after: always;
            page-break-inside: avoid;
            break-inside: avoid;
            background: white;
            padding: 1mm;
        }
        
        .label-content {
            text-align: center;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .label-content img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        @media print {
            body {
                background: white;
            }
            .label-container {
                page-break-after: always;
                page-break-inside: avoid;
            }
            .no-print {
                display: none !important;
            }
        }
        
        @media screen {
            body {
                background: #e0e0e0;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .label-container {
                margin: 10px auto;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                border: 1px solid #ddd;
                background: white;
                width: 1.5in;
                height: 0.5in;
            }
        }
        
        .no-print {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
        }
        .no-print button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-print {
            background: #4CAF50;
            color: white;
        }
        .btn-print:hover {
            background: #45a049;
        }
        .btn-close {
            background: #666;
            color: white;
            margin-left: 10px;
        }
        .btn-close:hover {
            background: #555;
        }
        .btn-copies {
            background: #2196F3;
            color: white;
            margin-left: 10px;
        }
        .btn-copies:hover {
            background: #1976D2;
        }
        .size-info {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php for($i = 1; $i <= $copies; $i++): ?>
    <div class="label-container">
        <div class="label-content">
            <?php if($hasBarcode): ?>
                <img src="../../<?php echo $barcodePath; ?>" alt="Barcode">
            <?php else: ?>
                <div style="font-family: 'Courier New', monospace; font-size: 14px; font-weight: bold; letter-spacing: 2px; color: #000;">
                    <?php echo $assignment['assignment_no']; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endfor; ?>
    
    <div class="no-print">
        <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Label</button>
        <button class="btn-close" onclick="window.close()"><i class="fas fa-times"></i> Close</button>
        <button class="btn-copies" onclick="changeCopies()"><i class="fas fa-copy"></i> Change Copies</button>
        <p class="size-info">
            Label size: 1.5in × 0.5in | Copies: <?php echo $copies; ?>
            <?php if(isset($assignment['source']) && $assignment['source'] == 'request'): ?>
                | Source: Public Request
            <?php endif; ?>
        </p>
    </div>
    
    <script>
        function changeCopies() {
            var copies = prompt('Enter number of copies (1-50):', '<?php echo $copies; ?>');
            if(copies !== null && copies > 0 && copies <= 50) {
                window.location.href = 'barcode_label.php?id=<?php echo $id; ?>&copies=' + copies;
            } else if(copies !== null) {
                alert('Please enter a number between 1 and 50.');
            }
        }
        
        <?php if(isset($_GET['print']) && $_GET['print'] == 'auto'): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>
    </script>
</body>
</html>