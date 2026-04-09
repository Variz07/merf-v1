<?php
// TURN ON ERROR REPORTING
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

// CEK KONEKSI DATABASE
if (!isset($pdo)) {
    die("Database connection failed!");
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    header('Location: ../index.php');
    exit;
}

try {
    // GET PRODUCT DETAILS - VERSION SEDERHANA DULU
    $sql = "SELECT p.*, 
            u.user_id as seller_id, 
            u.full_name, 
            u.profile_pic, 
            u.phone as seller_phone,
            u.rating as seller_rating
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.product_id = ? AND p.is_available = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: ../index.php?error=product_not_found');
        exit;
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = htmlspecialchars($product['name']);

// UPDATE VIEW COUNT
try {
    $stmt = $pdo->prepare("UPDATE products SET total_views = total_views + 1 WHERE product_id = ?");
    $stmt->execute([$product_id]);
} catch (PDOException $e) {
    // SILENT FAIL
}

// GET IMAGES
$product_images = [];
if (!empty($product['image'])) {
    $decoded = json_decode($product['image'], true);
    if (is_array($decoded)) {
        $product_images = $decoded;
    } else {
        $product_images = [$product['image']];
    }
}

if (empty($product_images)) {
    $product_images = ['no-image.png'];
}

// GET RATING COUNT
$rating_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $rating_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // SILENT FAIL
}

// GET FAVORITE COUNT
$favorite_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $favorite_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // SILENT FAIL
}

// SIMPLE IMAGE FUNCTION
function getImageUrl($image_name) {
    global $pdo;
    
    if (empty($image_name)) {
        return SITE_URL . 'assets/images/no-image.png';
    }
    
    $base_url = SITE_URL . 'assets/images/uploads/';
    $base_path = __DIR__ . '/../assets/images/uploads/';
    
    if (file_exists($base_path . $image_name)) {
        return $base_url . $image_name;
    }
    
    return SITE_URL . 'assets/images/no-image.png';
}

include '../header.php';
?>

<div class="container">
    <div style="padding: 20px; background: white; border-radius: 8px; margin: 20px 0;">
        <h1 style="color: #4C3C27;"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- IMAGE -->
            <div>
                <img src="<?php echo getImageUrl($product_images[0]); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="width: 100%; height: 300px; object-fit: contain; border: 1px solid #ddd; border-radius: 8px;">
            </div>
            
            <!-- INFO -->
            <div>
                <div style="font-size: 28px; font-weight: 700; color: #4C3C27; margin-bottom: 20px;">
                    <?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?>
                </div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 10px 0; color: #666;">Kategori</td>
                        <td style="padding: 10px 0;"><?php echo ucfirst($product['category']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #666;">Lokasi</td>
                        <td style="padding: 10px 0;"><?php echo htmlspecialchars($product['location']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #666;">Penjual</td>
                        <td style="padding: 10px 0;"><?php echo htmlspecialchars($product['full_name']); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #666;">Deskripsi</td>
                        <td style="padding: 10px 0;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></td>
                    </tr>
                </table>
                
                <!-- WHATSAPP BUTTON -->
                <?php if (!empty($product['seller_phone'])): 
                    $phone = preg_replace('/[^0-9]/', '', $product['seller_phone']);
                    if (substr($phone, 0, 1) == '0') {
                        $phone = '62' . substr($phone, 1);
                    }
                    $message = "Halo%20" . urlencode($product['full_name']) . "%2C%0A%0A";
                    $message .= "Saya%20tertarik%20dengan%20produk%20*" . urlencode($product['name']) . "*%0A";
                    $message .= "Harga%3A%20" . urlencode(formatCurrency($product['discounted_price'] ?? $product['price']));
                    $wa_link = "https://wa.me/$phone?text=$message";
                ?>
                    <a href="<?php echo $wa_link; ?>" 
                       target="_blank"
                       style="display: inline-block; background: #25D366; color: white; padding: 12px 30px; border-radius: 25px; text-decoration: none; margin-top: 20px;">
                        <i class="fab fa-whatsapp"></i> Pesan via WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>