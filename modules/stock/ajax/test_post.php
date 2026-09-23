<?php
header('Content-Type: application/json');

// Log the request
file_put_contents(dirname(__FILE__) . '/test_log.txt', date('Y-m-d H:i:s') . " - POST received\n", FILE_APPEND);
file_put_contents(dirname(__FILE__) . '/test_log.txt', print_r($_POST, true) . "\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => 'Test endpoint is working',
    'received_data' => $_POST
]);
?>