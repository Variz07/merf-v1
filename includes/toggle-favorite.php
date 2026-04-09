<?php
require_once '../config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

$product_id = $_POST['product_id'] ?? 0;

if(!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
    exit();
}

// Check if product exists
$stmt = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ? AND is_available = 1");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit();
}

// Check if already favorited
$stmt = $pdo->prepare("SELECT fav_id FROM favorites WHERE product_id = ? AND user_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
$favorite = $stmt->fetch();

if($favorite) {
    // Remove from favorites
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE product_id = ? AND user_id = ?");
    $result = $stmt->execute([$product_id, $_SESSION['user_id']]);
    
    if($result) {
        // Update favorite count
        $stmt = $pdo->prepare("UPDATE products SET total_likes = total_likes - 1 WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        echo json_encode([
            'success' => true,
            'is_favorited' => false,
            'message' => 'Dihapus dari favorit'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus dari favorit']);
    }
} else {
    // Add to favorites
    $stmt = $pdo->prepare("INSERT INTO favorites (product_id, user_id) VALUES (?, ?)");
    $result = $stmt->execute([$product_id, $_SESSION['user_id']]);
    
    if($result) {
        // Update favorite count
        $stmt = $pdo->prepare("UPDATE products SET total_likes = total_likes + 1 WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // Send notification to seller
        $stmt = $pdo->prepare("
            SELECT seller_id FROM products WHERE product_id = ?
        ");
        $stmt->execute([$product_id]);
        $seller = $stmt->fetch();
        
        if($seller && $seller['seller_id'] != $_SESSION['user_id']) {
            $product_stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = ?");
            $product_stmt->execute([$product_id]);
            $product_name = $product_stmt->fetchColumn();
            
            sendNotification(
                $seller['seller_id'],
                'like',
                'Produk Disukai',
                'Produk "' . $product_name . '" disukai oleh ' . $_SESSION['user_name']
            );
        }
        
        echo json_encode([
            'success' => true,
            'is_favorited' => true,
            'message' => 'Ditambahkan ke favorit'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke favorit']);
    }
}
?>