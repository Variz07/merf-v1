<?php
/**
 * Helper functions for MERF Marketplace
 */

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get user avatar URL with caching prevention
function getUserAvatar($user_id) {
    global $pdo;
    
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        if (isset($_SESSION['profile_pic']) && !empty($_SESSION['profile_pic'])) {
            $pic = $_SESSION['profile_pic'];
        } else {
            $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $pic = $stmt->fetchColumn();
            $_SESSION['profile_pic'] = $pic;
        }
    } else {
        $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $pic = $stmt->fetchColumn();
    }

    if (empty($pic)) {
        $pic = 'default-profile.png';
    }

    $upload_path = __DIR__ . '/../assets/images/uploads/';
    $upload_url  = SITE_URL . 'assets/images/uploads/';

    if (file_exists($upload_path . $pic)) {
        return $upload_url . $pic . '?v=' . filemtime($upload_path . $pic);
    }

    $direct_path = __DIR__ . '/../assets/images/';
    if (file_exists($direct_path . $pic)) {
        return SITE_URL . 'assets/images/' . $pic . '?v=' . filemtime($direct_path . $pic);
    }

    return SITE_URL . 'assets/images/default-profile.png';
}

// Function to update user session after profile update
function refreshUserSession($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT full_name, email, profile_pic, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        return true;
    }
    return false;
}

// Time ago function
function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);

    if ($diff < 60) return 'baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';

    return date('d M Y', strtotime($datetime));
}

// Format currency
function formatCurrency($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Get user role
function getUserRole($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Check if user can edit product
function canEditProduct($product_id, $user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT seller_id FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $seller_id = $stmt->fetchColumn();

    if ($seller_id == $user_id) return true;
    return getUserRole($user_id) === 'admin';
}

// Get unread notification count
function getUnreadNotificationCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Redirect with message
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION[$type . '_message'] = $message;
    }

    if (!headers_sent()) {
        header("Location: $url");
        exit();
    }
}

// Calculate average rating
function calculateAverageRating($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT AVG(rating_value) FROM ratings WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $avg = $stmt->fetchColumn();
    return $avg ? round($avg, 1) : 0;
}

// Upload image
function uploadImage($file, $folder = 'products') {
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== 0) {
        return ['success' => false, 'message' => 'Error uploading file'];
    }

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large'];
    }

    $file_name = uniqid($folder . '_') . '.' . $file_ext;
    $upload_path = __DIR__ . '/../assets/images/uploads/' . $file_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $file_name];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

// Admin check
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Check if user is seller
function isSeller() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'seller';
}

// Get product image URL
function getProductImageUrl($image_name) {
    if(empty($image_name)) {
        return SITE_URL . 'assets/images/no-image.png';
    }
    
    $upload_path = __DIR__ . '/../assets/images/uploads/';
    $upload_url = SITE_URL . 'assets/images/uploads/';
    
    if(file_exists($upload_path . $image_name)) {
        return $upload_url . $image_name;
    }
    return SITE_URL . 'assets/images/no-image.png';
}

// Check if product is favorited
function isProductFavorited($product_id, $user_id) {
    if(!$user_id) return false;
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    return $stmt->fetchColumn() > 0;
}

// Get product rating count
function getProductRatingCount($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetchColumn();
}

// ==================== FOOD CATEGORY FUNCTIONS ====================

// Get food categories for upload form
function getFoodCategories() {
    return [
        'main_meals' => [
            'name' => 'Main Meals',
            'subcategories' => ['nasi', 'mie', 'ayam', 'ikan', 'soto', 'bakso', 'makanan berat'],
            'icon' => 'fa-bowl-food',
            'color' => '#FF6B6B'
        ],
        'snacks' => [
            'name' => 'Snacks',
            'subcategories' => ['snack', 'keripik', 'camilan', 'kue kering', 'cemilan'],
            'icon' => 'fa-cookie',
            'color' => '#4C6EF5'
        ],
        'beverages' => [
            'name' => 'Beverages',
            'subcategories' => ['minuman', 'kopi', 'teh', 'jus', 'soda'],
            'icon' => 'fa-mug-saucer',
            'color' => '#20C997'
        ],
        'desserts' => [
            'name' => 'Desserts',
            'subcategories' => ['dessert', 'puding', 'es krim', 'kue', 'cake', 'kue basah'],
            'icon' => 'fa-ice-cream',
            'color' => '#9775FA'
        ]
    ];
}

// Get subcategory based on category selection
function getSubcategoryForCategory($category_key, $product_name) {
    $categories = getFoodCategories();
    
    if(isset($categories[$category_key])) {
        // Return first subcategory as default
        return $categories[$category_key]['subcategories'][0];
    }
    
    // Default fallback
    return 'makanan';
}

// Get food categories with counts (MENGGUNAKAN getFoodCategories)
function getFoodCategoriesWithCounts() {
    global $pdo;
    $categories = getFoodCategories();
    $result = [];
    
    foreach($categories as $key => $cat) {
        $placeholders = implode(',', array_fill(0, count($cat['subcategories']), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = 'food' AND is_available = 1 AND subcategory IN ($placeholders)");
        $stmt->execute($cat['subcategories']);
        $result[$key] = [
            'name' => $cat['name'],
            'icon' => $cat['icon'],
            'color' => $cat['color'],
            'count' => $stmt->fetchColumn()
        ];
    }
    
    return $result;
}

// Get food products for homepage and food page
function getFoodProducts($limit = null, $category = null) {
    global $pdo;
    
    $sql = "SELECT p.*, u.full_name, u.profile_pic, u.phone as seller_phone,
            (SELECT AVG(rating_value) FROM ratings WHERE product_id = p.product_id) as avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE product_id = p.product_id) as rating_count,
            (SELECT COUNT(*) FROM orders WHERE product_id = p.product_id AND status = 'delivered') as sold
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.category = 'food' AND p.is_available = 1";
    
    $params = [];
    
    if($category && $category !== 'all') {
        $categories = getFoodCategories();
        $subcategories = $categories[$category]['subcategories'] ?? [];
        
        if(!empty($subcategories)) {
            $placeholders = implode(',', array_fill(0, count($subcategories), '?'));
            $sql .= " AND p.subcategory IN ($placeholders)";
            $params = array_merge($params, $subcategories);
        }
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ==================== PRELOVED CATEGORY FUNCTIONS ====================

// Get preloved categories for upload form
function getPrelovedCategories() {
    return [
        'clothes' => [
            'name' => 'Clothes / Fashion',
            'icon' => 'fa-tshirt',
            'color' => '#FF6B6B',
            'badge' => 'Thrifting',
            'description' => 'Pakaian, jaket, celana, dress, hijab'
        ],
        'skincare' => [
            'name' => 'Skincare & Beauty',
            'icon' => 'fa-spa',
            'color' => '#4C6EF5',
            'badge' => 'SKINCARE',
            'description' => 'Skincare, makeup, body care, hair care'
        ],
        'electronics' => [
            'name' => 'Electronics & Gadgets',
            'icon' => 'fa-laptop',
            'color' => '#20C997',
            'badge' => 'ELECTRONICS',
            'description' => 'Laptop, HP, tablet, aksesoris elektronik'
        ],
        'home' => [
            'name' => 'Home & Living',
            'icon' => 'fa-home',
            'color' => '#FD7E14',
            'badge' => 'HOME',
            'description' => 'Dekorasi, peralatan rumah, furnitur kecil'
        ],
        'sports' => [
            'name' => 'Sports & Hobbies',
            'icon' => 'fa-bicycle',
            'color' => '#9775FA',
            'badge' => 'SPORTS',
            'description' => 'Alat olahraga, mainan, koleksi hobi'
        ],
        'books' => [
            'name' => 'Books & Stationery',
            'icon' => 'fa-book',
            'color' => '#E64980',
            'badge' => 'BOOKS',
            'description' => 'Buku, novel, alat tulis, kertas'
        ],
        'other' => [
            'name' => 'Others',
            'icon' => 'fa-tag',
            'color' => '#6C757D',
            'badge' => 'LAINNYA',
            'description' => 'Barang lainnya yang tidak masuk kategori'
        ]
    ];
}

// Get preloved products by category
function getPrelovedProducts($category = null, $limit = 4) {
    global $pdo;
    
    $sql = "SELECT p.*, u.full_name, u.profile_pic, u.phone as seller_phone,
            (SELECT COUNT(*) FROM orders WHERE product_id = p.product_id AND status = 'delivered') as sold,
            (SELECT AVG(rating_value) FROM ratings WHERE product_id = p.product_id) as avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE product_id = p.product_id) as rating_count
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.category = 'preloved' AND p.is_available = 1";
    
    $params = [];
    
    if($category && $category !== 'all') {
        $sql .= " AND p.subcategory = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get preloved categories with counts
function getPrelovedCategoriesWithCounts() {
    $categories = getPrelovedCategories();
    global $pdo;
    $result = [];
    
    foreach($categories as $key => $cat) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = 'preloved' AND is_available = 1 AND subcategory = ?");
        $stmt->execute([$key]);
        $result[$key] = [
            'name' => $cat['name'],
            'icon' => $cat['icon'],
            'color' => $cat['color'],
            'badge' => $cat['badge'],
            'description' => $cat['description'],
            'count' => $stmt->fetchColumn()
        ];
    }
    
    return $result;
}

// ==================== SERVICE CATEGORY FUNCTIONS ====================

// Get service categories
function getServiceCategories() {
    return [
        'jastip' => [
            'name' => 'Jastip Food',
            'icon' => 'fa-shopping-bag',
            'color' => '#FF6B6B',
            'badge' => 'JASTIP',
            'description' => 'Titip beli makanan, minuman, atau barang'
        ],
        'courses' => [
            'name' => 'Courses & Tutoring',
            'icon' => 'fa-graduation-cap',
            'color' => '#4C6EF5',
            'badge' => 'COURSES',
            'description' => 'Kursus, bimbingan belajar, les privat'
        ],
        'repair' => [
            'name' => 'Repair & Maintenance',
            'icon' => 'fa-tools',
            'color' => '#20C997',
            'badge' => 'REPAIR',
            'description' => 'Service laptop, HP, elektronik'
        ],
        'beauty' => [
            'name' => 'Beauty & Nail Art',
            'icon' => 'fa-spa',
            'color' => '#FD7E14',
            'badge' => 'BEAUTY',
            'description' => 'Nail art, makeup, perawatan'
        ],
        'cleaning' => [
            'name' => 'Cleaning Service',
            'icon' => 'fa-broom',
            'color' => '#9775FA',
            'badge' => 'CLEANING',
            'description' => 'Jasa kebersihan kamar, kost'
        ],
        'design' => [
            'name' => 'Design & Creative',
            'icon' => 'fa-paint-brush',
            'color' => '#E64980',
            'badge' => 'DESIGN',
            'description' => 'Desain grafis, editing video'
        ],
        'other' => [
            'name' => 'Other Services',
            'icon' => 'fa-hands-helping',
            'color' => '#6C757D',
            'badge' => 'OTHER',
            'description' => 'Jasa lainnya'
        ]
    ];
}

// Get service products by category
function getServiceProducts($category = null, $limit = 4) {
    global $pdo;
    
    $sql = "SELECT p.*, u.full_name, u.profile_pic, u.phone as seller_phone,
            (SELECT COUNT(*) FROM orders WHERE product_id = p.product_id AND status = 'delivered') as sold,
            (SELECT AVG(rating_value) FROM ratings WHERE product_id = p.product_id) as avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE product_id = p.product_id) as rating_count
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.category = 'service' AND p.is_available = 1";
    
    $params = [];
    
    if($category && $category !== 'all') {
        $sql .= " AND p.subcategory = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    if($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Get service categories with counts
function getServiceCategoriesWithCounts() {
    $categories = getServiceCategories();
    global $pdo;
    $result = [];
    
    foreach($categories as $key => $cat) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = 'service' AND is_available = 1 AND subcategory = ?");
        $stmt->execute([$key]);
        $result[$key] = [
            'name' => $cat['name'],
            'icon' => $cat['icon'],
            'color' => $cat['color'],
            'badge' => $cat['badge'],
            'description' => $cat['description'],
            'count' => $stmt->fetchColumn()
        ];
    }
    
    return $result;
}

?>