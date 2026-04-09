<?php
require_once '../config.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Log to file
    $log_entry = date('Y-m-d H:i:s') . " | WA Click | Product: $product_id - $product_name | User: $user_id | IP: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/wa_clicks.log', $log_entry, FILE_APPEND);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>