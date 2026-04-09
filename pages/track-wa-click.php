<?php
require_once '../config.php';

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? 0;
    $product_name = $_POST['product_name'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Log ke file (opsional)
    $log_entry = date('Y-m-d H:i:s') . " | Product ID: $product_id | Product: $product_name | User ID: $user_id | IP: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/wa_clicks.log', $log_entry, FILE_APPEND);
    
    // Update product click count (optional)
    if($product_id) {
        $stmt = $pdo->prepare("UPDATE products SET total_views = total_views + 1 WHERE product_id = ?");
        $stmt->execute([$product_id]);
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>