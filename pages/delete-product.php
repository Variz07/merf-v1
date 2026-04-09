<?php
require_once '../config.php';

// Cek login
if(!isLoggedIn()) {
    redirect('../auth/signin.php', 'Please login first', 'error');
}

// Cek apakah ada ID produk
if(!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('profile.php', 'Product ID not specified', 'error');
}

$product_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Cek apakah produk ada dan milik user atau user adalah admin
$stmt = $pdo->prepare("
    SELECT p.*, u.role 
    FROM products p
    JOIN users u ON u.user_id = ?
    WHERE p.product_id = ?
");
$stmt->execute([$user_id, $product_id]);
$product = $stmt->fetch();

if(!$product) {
    redirect('profile.php', 'Product not found', 'error');
}

// Cek kepemilikan (hanya pemilik atau admin yang bisa hapus)
$is_owner = ($product['seller_id'] == $user_id);
$is_admin = ($_SESSION['user_role'] == 'admin');

if(!$is_owner && !$is_admin) {
    redirect('profile.php', 'You do not have permission to delete this product', 'error');
}

// Hapus gambar dari folder uploads (jika bukan default)
if(!empty($product['image']) && $product['image'] != 'no-image.png') {
    $image_path = '../' . UPLOAD_PATH . $product['image'];
    if(file_exists($image_path)) {
        unlink($image_path);
    }
}

// Hapus product dari database (soft delete atau hard delete)
// Menggunakan soft delete (set is_available = 0) agar tidak hilang dari database
$stmt = $pdo->prepare("UPDATE products SET is_available = 0 WHERE product_id = ?");
$result = $stmt->execute([$product_id]);

if($result) {
    // Hapus juga dari favorites
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE product_id = ?");
    $stmt->execute([$product_id]);
    
    redirect('profile.php', 'Product deleted successfully', 'success');
} else {
    redirect('profile.php', 'Failed to delete product', 'error');
}
?>