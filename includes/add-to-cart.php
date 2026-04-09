<?php
require_once '../config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

$product_id = $_POST['product_id'] ?? 0;
$quantity = max(1, intval($_POST['quantity'] ?? 1));

if(!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
    exit();
}

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, u.user_id as seller_id 
    FROM products p 
    JOIN users u ON p.seller_id = u.user_id 
    WHERE p.product_id = ? AND p.is_available = 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if(!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit();
}

// Check if user is buying from themselves
if($product['seller_id'] == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Tidak dapat membeli produk sendiri']);
    exit();
}

// Check stock
if($product['quantity'] > 0 && $quantity > $product['quantity']) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
    exit();
}

// Check if already in cart (pending order)
$stmt = $pdo->prepare("
    SELECT order_id FROM orders 
    WHERE buyer_id = ? AND product_id = ? AND status = 'pending'
");
$stmt->execute([$_SESSION['user_id'], $product_id]);
$existing_order = $stmt->fetch();

if($existing_order) {
    echo json_encode(['success' => false, 'message' => 'Produk sudah ada di keranjang']);
    exit();
}

// Calculate price
$price = $product['discounted_price'] ?? $product['price'];
$total_price = $price * $quantity;

// Generate order code
$order_code = 'CART' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 4));

// Create order
$stmt = $pdo->prepare("
    INSERT INTO orders 
    (order_code, buyer_id, seller_id, product_id, quantity, total_price, status, payment_status) 
    VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')
");

try {
    $stmt->execute([
        $order_code,
        $_SESSION['user_id'],
        $product['seller_id'],
        $product_id,
        $quantity,
        $total_price
    ]);
    
    // Get cart count
    $cart_stmt = $pdo->prepare("
        SELECT COUNT(*) as cart_count FROM orders 
        WHERE buyer_id = ? AND status = 'pending'
    ");
    $cart_stmt->execute([$_SESSION['user_id']]);
    $cart_count = $cart_stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Ditambahkan ke keranjang',
        'cart_count' => $cart_count
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan ke keranjang: ' . $e->getMessage()]);
}
?>