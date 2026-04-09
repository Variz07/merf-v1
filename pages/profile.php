<?php
require_once '../config.php';

// Handle settings form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $action = $_POST['action'] ?? '';
    $uid = $_SESSION['user_id'];

    if ($action === 'update_account') {
        $phone = trim($_POST['phone'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, student_id = ? WHERE user_id = ?");
        $stmt->execute([$phone, $student_id, $uid]);
        header("Location: profile.php?id=$uid&success=account");
        exit;
    }

    if ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $dorm_address = trim($_POST['dorm_address'] ?? '');

        // Handle profile picture upload
        $profile_pic = null;
        if (!empty($_FILES['profile_pic']['name'])) {
            $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $uid . '.' . $ext;
            $upload_path = '../uploads/avatars/' . $filename;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path);
            $profile_pic = $filename;
        }

        if ($profile_pic) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, dorm_address = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $bio, $dorm_address, $profile_pic, $uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, bio = ?, dorm_address = ? WHERE user_id = ?");
            $stmt->execute([$full_name, $bio, $dorm_address, $uid]);
        }
        header("Location: profile.php?id=$uid&success=profile");
        exit;
    }

    if ($action === 'update_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$uid]);
        $user_row = $stmt->fetch();

        if (!password_verify($current, $user_row['password'])) {
            header("Location: profile.php?id=$uid&error=wrong_password");
            exit;
        }
        if ($new !== $confirm) {
            header("Location: profile.php?id=$uid&error=password_mismatch");
            exit;
        }
        if (strlen($new) < 8) {
            header("Location: profile.php?id=$uid&error=password_short");
            exit;
        }

        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed, $uid]);
        header("Location: profile.php?id=$uid&success=password");
        exit;
    }
}

// Get user ID from URL or session
$user_id = $_GET['id'] ?? (isLoggedIn() ? $_SESSION['user_id'] : null);

if(!$user_id) {
    redirect('../index.php', 'User not found', 'error');
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND status = 'active'");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user) {
    redirect('../index.php', 'User not found', 'error');
}

$page_title = htmlspecialchars($user['full_name']) . ' - Profile';

// Check if current user can edit this profile
$is_owner = isLoggedIn() && ($_SESSION['user_id'] == $user_id);
$is_admin = isLoggedIn() && isAdmin();
$can_edit = $is_owner || $is_admin;

// Get user stats
$stats = [];

// Total products (for sellers)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND is_available = 1");
$stmt->execute([$user_id]);
$stats['total_products'] = $stmt->fetchColumn();

// Total sales (for sellers)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders 
    WHERE seller_id = ? AND status = 'delivered'
");
$stmt->execute([$user_id]);
$stats['total_sales'] = $stmt->fetchColumn();

// Total revenue (for sellers)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_price + delivery_fee), 0) FROM orders 
    WHERE seller_id = ? AND status = 'delivered'
");
$stmt->execute([$user_id]);
$stats['total_revenue'] = $stmt->fetchColumn();

// Average rating (for sellers)
$stmt = $pdo->prepare("
    SELECT COALESCE(AVG(r.rating_value), 0) FROM ratings r
    JOIN products p ON r.product_id = p.product_id
    WHERE p.seller_id = ?
");
$stmt->execute([$user_id]);
$stats['avg_rating'] = round($stmt->fetchColumn(), 1);

// Total reviews
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM ratings r
    JOIN products p ON r.product_id = p.product_id
    WHERE p.seller_id = ?
");
$stmt->execute([$user_id]);
$stats['total_reviews'] = $stmt->fetchColumn();

// Total urgent needs (for all users)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM urgent_needs WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats['total_urgent'] = $stmt->fetchColumn();

// Total orders (as buyer)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
$stmt->execute([$user_id]);
$stats['total_orders'] = $stmt->fetchColumn();

// Total favorites received (for sellers)
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM favorites f
    JOIN products p ON f.product_id = p.product_id
    WHERE p.seller_id = ?
");
$stmt->execute([$user_id]);
$stats['total_favorites'] = $stmt->fetchColumn();

// Get user products (if seller)
$user_products = [];
if($user['role'] == 'seller' || $user['role'] == 'admin') {
    $stmt = $pdo->prepare("
        SELECT p.*, 
        (SELECT COUNT(*) FROM orders WHERE product_id = p.product_id AND status = 'delivered') as sold_count,
        (SELECT COUNT(*) FROM favorites WHERE product_id = p.product_id) as favorite_count
        FROM products p 
        WHERE p.seller_id = ? AND p.is_available = 1 
        ORDER BY p.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$user_id]);
    $user_products = $stmt->fetchAll();
}

// Get user urgent needs
$user_urgent = [];
$stmt = $pdo->prepare("
    SELECT * FROM urgent_needs 
    WHERE user_id = ? AND status = 'open'
    ORDER BY created_at DESC 
    LIMIT 3
");
$stmt->execute([$user_id]);
$user_urgent = $stmt->fetchAll();

// Get user activity stats
$activity_stats = [
    'applied' => rand(20, 40),  // Placeholder - implement actual logic
    'reviewed' => rand(60, 80),
    'contacted' => rand(10, 25)
];

include '../header.php';
?>

<div class="profile-page">
    <?php if(isset($_GET['success'])): ?>
<div class="alert-success" style="background:#d4edda;color:#155724;padding:14px 20px;border-radius:12px;margin-bottom:20px;max-width:1200px;margin:0 auto 20px;">
    ✅ <?php 
        $msgs = ['account'=>'Account details updated!','profile'=>'Profile updated!','password'=>'Password changed successfully!'];
        echo $msgs[$_GET['success']] ?? 'Saved!';
    ?>
</div>
<?php elseif(isset($_GET['error'])): ?>
<div class="alert-error" style="background:#f8d7da;color:#721c24;padding:14px 20px;border-radius:12px;margin-bottom:20px;max-width:1200px;margin:0 auto 20px;">
    ❌ <?php 
        $errs = ['wrong_password'=>'Current password is incorrect.','password_mismatch'=>'Passwords do not match.','password_short'=>'Password must be at least 8 characters.'];
        echo $errs[$_GET['error']] ?? 'Something went wrong.';
    ?>
</div>
<?php endif; ?>
    <div class="container">
        <!-- PROFILE HEADER CARD -->
        <div class="profile-header-card">
            <div class="profile-cover">
                <?php if($user['role'] == 'seller'): ?>
                <div class="cover-gradient seller-gradient"></div>
                <?php elseif($user['role'] == 'admin'): ?>
                <div class="cover-gradient admin-gradient"></div>
                <?php else: ?>
                <div class="cover-gradient customer-gradient"></div>
                <?php endif; ?>
            </div>
            
            <div class="profile-info-wrapper">
                <div class="profile-avatar-section">
                    <div class="profile-avatar">
                        <img src="<?php echo getUserAvatar($user_id); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                        <?php if($user['role'] == 'seller'): ?>
                        <span class="role-badge seller">
                            <i class="fas fa-store"></i>
                        </span>
                        <?php elseif($user['role'] == 'admin'): ?>
                        <span class="role-badge admin">
                            <i class="fas fa-crown"></i>
                        </span>
                        <?php else: ?>
                        <span class="role-badge customer">
                            <i class="fas fa-user"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($can_edit): ?>
                    <button class="btn-edit-profile" onclick="showEditForm()">
                        <i class="fas fa-pencil-alt"></i> Edit Profile
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="profile-details-section">
                    <div class="profile-name-section">
                        <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <span class="profile-username">@<?php echo strtolower(str_replace(' ', '', $user['full_name'])); ?></span>
                        
                        <?php if($user['role'] == 'seller'): ?>
                        <div class="seller-rating-badge">
                            <i class="fas fa-star"></i>
                            <span><?php echo $stats['avg_rating']; ?></span>
                            <span class="rating-count">(<?php echo $stats['total_reviews']; ?> reviews)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-bio">
                        <?php if($user['bio']): ?>
                        <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                        <?php else: ?>
                        <p class="no-bio">No bio yet.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="profile-meta">
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($user['dorm_address'] ?? 'No address'); ?></span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-envelope"></i>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if($user['phone']): ?>
                        <div class="meta-item">
                            <i class="fas fa-phone-alt"></i>
                            <span><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Joined <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATS GRID - DIFFERENT FOR EACH ROLE -->
        <div class="profile-stats-grid">
            <?php if($user['role'] == 'seller'): ?>
                <!-- SELLER STATS -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_products']; ?></h3>
                        <p>Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_sales']; ?></h3>
                        <p>Sales</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_favorites']; ?></h3>
                        <p>Favorites</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['avg_rating']; ?>/5</h3>
                        <p>Rating</p>
                    </div>
                </div>
                
            <?php elseif($user['role'] == 'admin'): ?>
                <!-- ADMIN STATS -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                        ?>
                        <h3><?php echo $total_users; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
                        ?>
                        <h3><?php echo $total_products; ?></h3>
                        <p>Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                        ?>
                        <h3><?php echo $total_orders; ?></h3>
                        <p>Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $total_reports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
                        ?>
                        <h3><?php echo $total_reports; ?></h3>
                        <p>Reports</p>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- CUSTOMER STATS -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Orders</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total_urgent']; ?></h3>
                        <p>Urgent Needs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $favorites = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
                        $favorites->execute([$user_id]);
                        ?>
                        <h3><?php echo $favorites->fetchColumn(); ?></h3>
                        <p>Favorites</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <?php
                        $reviews = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE user_id = ?");
                        $reviews->execute([$user_id]);
                        ?>
                        <h3><?php echo $reviews->fetchColumn(); ?></h3>
                        <p>Reviews</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ACTIVITY STATS (Applied, Reviewed, Contacted) - Like in the design -->
        <div class="activity-stats-card">
            <div class="activity-stats-header">
                <h3>Activity Overview</h3>
                <?php if($is_owner): ?>
                <a href="activity-log.php" class="view-all-link">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
                <?php endif; ?>
            </div>
            
            <div class="activity-stats-grid">
                <div class="activity-stat-item">
                    <span class="activity-label">Applied</span>
                    <span class="activity-number"><?php echo $activity_stats['applied']; ?></span>
                </div>
                <div class="activity-stat-item">
                    <span class="activity-label">Reviewed</span>
                    <span class="activity-number"><?php echo $activity_stats['reviewed']; ?></span>
                </div>
                <div class="activity-stat-item">
                    <span class="activity-label">Contacted</span>
                    <span class="activity-number"><?php echo $activity_stats['contacted']; ?></span>
                </div>
            </div>
        </div>

        <!-- TABS NAVIGATION -->
        <div class="profile-tabs">
            <button class="tab-btn active" onclick="showTab('products')">
                <i class="fas fa-box"></i> Products
            </button>
            
            <?php if($user['role'] == 'seller' || $user['role'] == 'admin'): ?>
            <button class="tab-btn" onclick="showTab('shop')">
                <i class="fas fa-store"></i> My Shop
            </button>
            <?php endif; ?>
            
            <button class="tab-btn" onclick="showTab('urgent')">
                <i class="fas fa-clock"></i> Urgent Needs
            </button>
            
            <button class="tab-btn" onclick="showTab('reviews')">
                <i class="fas fa-star"></i> Reviews
            </button>
            
            <?php if($is_owner): ?>
            <button class="tab-btn" onclick="showTab('settings')">
                <i class="fas fa-cog"></i> Settings
            </button>
            <?php endif; ?>
        </div>

        <!-- TAB CONTENT -->
        <div class="tab-content">
            
            <!-- TAB 1: PRODUCTS -->
            <div id="productsTab" class="tab-pane active">
                <?php if($user['role'] == 'seller' || $user['role'] == 'admin'): ?>
                    <?php if(empty($user_products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products yet</h3>
                        <p><?php echo $can_edit ? 'Start selling by uploading your first product!' : 'This seller has no products yet'; ?></p>
                        <?php if($can_edit): ?>
                        <a href="upload-product.php" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Upload Product
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="products-grid">
                        <?php foreach($user_products as $product): ?>
                        <div class="product-card-mini">
                            <div class="product-image">
                                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                                    <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </a>
                                <?php if($product['discounted_price']): ?>
                                <span class="discount-badge">-<?php echo round((($product['price'] - $product['discounted_price']) / $product['price']) * 100); ?>%</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <h4 class="product-title">
                                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h4>
                                <div class="product-price">
                                    <span class="current-price"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
                                </div>
                                <div class="product-meta">
                                    <span><i class="fas fa-shopping-cart"></i> <?php echo $product['sold_count'] ?? 0; ?> sold</span>
                                    <span><i class="fas fa-heart"></i> <?php echo $product['favorite_count'] ?? 0; ?></span>
                                </div>
                                <?php if($can_edit): ?>
                                <div class="product-actions">
                                    <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" class="btn-edit-small">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="btn-delete-small" onclick="deleteProduct(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if($stats['total_products'] > 6): ?>
                    <div class="view-more-container">
                        <a href="my-products.php?user=<?php echo $user_id; ?>" class="btn-view-more">
                            View All Products (<?php echo $stats['total_products']; ?>)
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- CUSTOMER - Show purchased/favorited products -->
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No products yet</h3>
                        <p>Start shopping to see your products here</p>
                        <a href="food.php" class="btn-primary">
                            <i class="fas fa-utensils"></i> Browse Food
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 2: MY SHOP (SELLER ONLY) -->
            <div id="shopTab" class="tab-pane">
                <?php if($user['role'] == 'seller' || $user['role'] == 'admin'): ?>
                <div class="shop-overview">
                    <div class="shop-header">
                        <h2>
                            <i class="fas fa-store"></i>
                            <?php echo htmlspecialchars($user['full_name']); ?>'s Shop
                        </h2>
                        <?php if($can_edit): ?>
                        <a href="edit-shop.php" class="btn-edit-shop">
                            <i class="fas fa-pencil-alt"></i> Edit Shop
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="shop-stats-mini">
                        <div class="shop-stat">
                            <span class="stat-label">Total Products</span>
                            <span class="stat-value"><?php echo $stats['total_products']; ?></span>
                        </div>
                        <div class="shop-stat">
                            <span class="stat-label">Total Sales</span>
                            <span class="stat-value"><?php echo $stats['total_sales']; ?></span>
                        </div>
                        <div class="shop-stat">
                            <span class="stat-label">Revenue</span>
                            <span class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></span>
                        </div>
                    </div>
                    
                    <div class="shop-categories">
                        <h3>Shop Categories</h3>
                        <div class="category-chips">
                            <a href="my-products.php?category=food" class="chip">
                                <i class="fas fa-utensils"></i> Food
                            </a>
                            <a href="my-products.php?category=preloved" class="chip">
                                <i class="fas fa-tshirt"></i> Preloved
                            </a>
                            <a href="my-products.php?category=service" class="chip">
                                <i class="fas fa-hands-helping"></i> Service
                            </a>
                            <a href="my-products.php?category=urgent" class="chip">
                                <i class="fas fa-clock"></i> Urgent
                            </a>
                        </div>
                    </div>
                    
                    <?php if($can_edit): ?>
                    <div class="quick-actions-shop">
                        <a href="upload-product.php" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            <span>Upload New Product</span>
                        </a>
                        <a href="my-products.php" class="quick-action-btn">
                            <i class="fas fa-box"></i>
                            <span>Manage Products</span>
                        </a>
                        <a href="my-orders.php?role=seller" class="quick-action-btn">
                            <i class="fas fa-truck"></i>
                            <span>Manage Orders</span>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Recent products preview -->
                    <div class="recent-products-preview">
                        <div class="preview-header">
                            <h4>Recent Products</h4>
                            <a href="my-products.php" class="view-link">View All</a>
                        </div>
                        <div class="preview-grid">
                            <?php 
                            $recent_products = array_slice($user_products, 0, 4);
                            foreach($recent_products as $product): 
                            ?>
                            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="preview-item">
                                <div class="preview-image">
                                    <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="preview-details">
                                    <span class="preview-title"><?php echo htmlspecialchars($product['name']); ?></span>
                                    <span class="preview-price"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 3: URGENT NEEDS -->
            <div id="urgentTab" class="tab-pane">
                <?php if(empty($user_urgent)): ?>
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h3>No urgent needs</h3>
                    <p><?php echo $can_edit ? 'Need something urgently? Post a request!' : 'This user has no urgent needs'; ?></p>
                    <?php if($can_edit): ?>
                    <a href="urgent.php" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Post Urgent Need
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="urgent-list-mini">
                    <?php foreach($user_urgent as $need): ?>
                    <div class="urgent-item">
                        <div class="urgent-content">
                            <h4><?php echo htmlspecialchars($need['title']); ?></h4>
                            <p><?php echo htmlspecialchars(substr($need['description'], 0, 100)); ?>...</p>
                            <div class="urgent-meta">
                                <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($need['location']); ?></span>
                                <span><i class="fas fa-clock"></i> <?php echo timeAgo($need['created_at']); ?></span>
                            </div>
                        </div>
                        <?php if($can_edit): ?>
                        <div class="urgent-actions">
                            <a href="edit-urgent.php?id=<?php echo $need['need_id']; ?>" class="btn-edit-small">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn-delete-small" onclick="deleteUrgent(<?php echo $need['need_id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <a href="urgent-detail.php?id=<?php echo $need['need_id']; ?>" class="btn-view">
                            View Details
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if($stats['total_urgent'] > 3): ?>
                <div class="view-more-container">
                    <a href="my-urgent.php" class="btn-view-more">
                        View All Requests (<?php echo $stats['total_urgent']; ?>)
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- TAB 4: REVIEWS -->
            <div id="reviewsTab" class="tab-pane">
                <?php
                $reviews_stmt = $pdo->prepare("
                    SELECT r.*, p.name as product_name, p.product_id, u.full_name as reviewer_name, u.profile_pic
                    FROM ratings r
                    JOIN products p ON r.product_id = p.product_id
                    JOIN users u ON r.user_id = u.user_id
                    WHERE p.seller_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 5
                ");
                $reviews_stmt->execute([$user_id]);
                $reviews = $reviews_stmt->fetchAll();
                ?>
                
                <?php if(empty($reviews)): ?>
                <div class="empty-state">
                    <i class="fas fa-star"></i>
                    <h3>No reviews yet</h3>
                    <p><?php echo $user['role'] == 'seller' ? 'Start selling to get reviews from customers!' : 'This user has no reviews'; ?></p>
                </div>
                <?php else: ?>
                <div class="reviews-list">
                    <?php foreach($reviews as $review): ?>
                    <div class="review-item">
                        <div class="reviewer-info">
                            <img src="<?php echo getUserAvatar($review['user_id']); ?>" alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>">
                            <div>
                                <h4><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating_value'] ? 'active' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="review-date"><?php echo timeAgo($review['created_at']); ?></span>
                        </div>
                        <div class="review-product">
                            <a href="product-detail.php?id=<?php echo $review['product_id']; ?>">
                                <?php echo htmlspecialchars($review['product_name']); ?>
                            </a>
                        </div>
                        <?php if($review['review']): ?>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="view-more-container">
                    <a href="user-reviews.php?id=<?php echo $user_id; ?>" class="btn-view-more">
                        View All Reviews
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 5: SETTINGS (OWNER ONLY) -->
            <?php if($is_owner): ?>
            <div id="settingsTab" class="tab-pane">
                <div class="settings-container">
                    <div class="settings-sidebar">
                        <div class="settings-menu">
                            <a href="#account" class="settings-menu-item active" onclick="showSettingsSection('account')">
                                <i class="fas fa-user"></i>
                                <span>Account Details</span>
                            </a>
                            <a href="#profile" class="settings-menu-item" onclick="showSettingsSection('profile')">
                                <i class="fas fa-id-card"></i>
                                <span>Profile Information</span>
                            </a>
                            <a href="#security" class="settings-menu-item" onclick="showSettingsSection('security')">
                                <i class="fas fa-shield-alt"></i>
                                <span>Security</span>
                            </a>
                            <a href="#notifications" class="settings-menu-item" onclick="showSettingsSection('notifications')">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </a>
                            <a href="#privacy" class="settings-menu-item" onclick="showSettingsSection('privacy')">
                                <i class="fas fa-lock"></i>
                                <span>Privacy</span>
                            </a>
                        </div>
                    </div>
                    
                    <div class="settings-content">
                        <!-- Account Details Section -->
                        <div id="accountSection" class="settings-section active">
    <h3>Account Details</h3>
    <form method="POST" action="profile.php?id=<?php echo $user_id; ?>" class="settings-form">
        <input type="hidden" name="action" value="update_account">
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="readonly-input">
            <small>Email cannot be changed</small>
        </div>
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Add your phone number">
        </div>
        <div class="form-group">
            <label>Student ID (NIM)</label>
            <input type="text" name="student_id" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>" placeholder="Optional">
        </div>
        <div class="form-group">
            <label>University</label>
            <input type="text" value="<?php echo htmlspecialchars($user['university'] ?? 'President University'); ?>" readonly class="readonly-input">
        </div>
        <button type="submit" class="btn-save-settings">Save Changes</button>
    </form>
</div>
                        
                        <!-- Profile Information Section -->
                        <div id="profileSection" class="settings-section">
                                <h3>Profile Information</h3>
                                <form method="POST" action="profile.php?id=<?php echo $user_id; ?>" enctype="multipart/form-data" class="settings-form">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Bio</label>
                                        <textarea name="bio" rows="4" placeholder="Tell others about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Location / Dorm Address</label>
                                        <input type="text" name="dorm_address" value="<?php echo htmlspecialchars($user['dorm_address'] ?? ''); ?>" placeholder="e.g.: SBH Tower A">
                                    </div>
                                    <div class="form-group">
                                        <label>Profile Picture</label>
                                        <div class="profile-pic-upload">
                                            <img src="<?php echo getUserAvatar($user_id); ?>" alt="Profile">
                                            <input type="file" name="profile_pic" accept="image/*" style="display:none" id="profilePicInput">
                                            <button type="button" class="btn-change-photo" onclick="document.getElementById('profilePicInput').click()">
                                                <i class="fas fa-camera"></i> Change Photo
                                            </button>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn-save-settings">Save Changes</button>
                                </form>
                            </div>
                        
                        <!-- Security Section -->
                        <div id="securitySection" class="settings-section">
                            <h3>Security</h3>
                            <form method="POST" action="profile.php?id=<?php echo $user_id; ?>" class="settings-form">
                                <input type="hidden" name="action" value="update_password">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" placeholder="Enter current password">
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" placeholder="Enter new password">
                                    <small>Minimum 8 characters</small>
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" placeholder="Confirm new password">
                                </div>
                                <button type="submit" class="btn-save-settings">Update Password</button>
                            </form>
                        </div>
                        
                        <!-- Notifications Section -->
                        <div id="notificationsSection" class="settings-section">
                            <h3>Notification Preferences</h3>
                            <div class="settings-form">
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" checked>
                                        <span>Email notifications for new messages</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" checked>
                                        <span>Push notifications for orders</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" checked>
                                        <span>Newsletter and promotions</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox">
                                        <span>Urgent needs alerts</span>
                                    </label>
                                </div>
                                <button class="btn-save-settings">Save Preferences</button>
                            </div>
                        </div>
                        
                        <!-- Privacy Section -->
                        <div id="privacySection" class="settings-section">
                            <h3>Privacy Settings</h3>
                            <div class="settings-form">
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" checked>
                                        <span>Show my email on profile</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" checked>
                                        <span>Show my phone number</span>
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox">
                                        <span>Allow others to see my order history</span>
                                    </label>
                                </div>
                                <button class="btn-save-settings">Save Privacy Settings</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- EDIT PROFILE FORM MODAL -->
<?php if($can_edit): ?>
<div id="editProfileModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Profile</h3>
            <button class="modal-close" onclick="hideEditForm()">&times;</button>
            <button onclick="showEditForm()">Edit Profile</button>
        </div> 
        <form method="POST" action="update-profile.php" enctype="multipart/form-data" class="modal-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Location / Dorm Address</label>
                <input type="text" name="dorm_address" value="<?php echo htmlspecialchars($user['dorm_address'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Profile Picture</label>
                <input type="file" name="profile_pic" accept="image/*">
                <small>Leave empty to keep current photo</small>
            </div>
            
            <div class="modal-actions">
                <button type="submit" class="btn-save">Save Changes</button>
                <button type="button" class="btn-cancel" onclick="hideEditForm()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
// Tab switching
function showTab(tabName) {
    // Hide all tab panes
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab pane
    document.getElementById(tabName + 'Tab').classList.add('active');
    
    // Add active class to clicked button
    event.currentTarget.classList.add('active');
}

// Settings section switching
function showSettingsSection(sectionId) {
    document.querySelectorAll('.settings-section').forEach(section => {
        section.classList.remove('active');
    });
    
    document.querySelectorAll('.settings-menu-item').forEach(item => {
        item.classList.remove('active');
    });
    
    document.getElementById(sectionId + 'Section').classList.add('active');
    event.currentTarget.classList.add('active');
}

// Edit profile modal
function showEditForm() {
    // Deactivate all tabs
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    // Activate Settings tab
    document.getElementById('settingsTab').classList.add('active');
    document.querySelector('[onclick="showTab(\'settings\')"]').classList.add('active');

    // Activate Account section inside settings
    document.querySelectorAll('.settings-section').forEach(s => s.classList.remove('active'));
    document.getElementById('accountSection').classList.add('active');

    // Scroll to the tab nav
    document.querySelector('.profile-tabs').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideEditForm() {
    document.getElementById('editProfileModal').style.display = 'none';
}

// Delete functions
function deleteProduct(productId) {
    if(confirm('Are you sure you want to delete this product?')) {
        window.location.href = 'delete-product.php?id=' + productId;
    }
}

function deleteUrgent(needId) {
    if(confirm('Are you sure you want to delete this urgent need?')) {
        window.location.href = 'delete-urgent.php?id=' + needId;
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('editProfileModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
});
</script>

<style>
/* ===== PROFILE PAGE - MINIMALIST DESIGN ===== */
.profile-page {
    background: #F9F7F4;
    min-height: 100vh;
    padding: 40px 0;
}

/* PROFILE HEADER CARD */
.profile-header-card {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid #E8E3D9;
    margin-bottom: 30px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.02);
}

.profile-cover {
    height: 100px;
    position: relative;
}

.cover-gradient {
    width: 100%;
    height: 100%;
}

.seller-gradient {
    background: linear-gradient(90deg, #4C3C27, #C9B59C);
}

.admin-gradient {
    background: linear-gradient(90deg, #300C0C, #DC3545);
}

.customer-gradient {
    background: linear-gradient(90deg, #6D6D6D, #C9B59C);
}

.profile-info-wrapper {
    display: flex;
    padding: 0 30px 30px;
    position: relative;
}

.profile-avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: -40px;
    margin-right: 30px;
}

.profile-avatar {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    margin-bottom: 15px;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.role-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: white;
    border: 2px solid white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.role-badge.seller {
    background: #FFD700;
    color: #2C2416;
}

.role-badge.admin {
    background: #DC3545;
    color: white;
}

.role-badge.customer {
    background: #4C3C27;
    color: white;
}

.btn-edit-profile {
    background: white;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    padding: 8px 20px;
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-edit-profile:hover {
    background: #4C3C27;
    color: white;
    border-color: #4C3C27;
}

.profile-details-section {
    flex: 1;
    padding-top: 20px;
}

.profile-name-section {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 12px;
}

.profile-name {
    font-size: 28px;
    font-weight: 700;
    color: #2C2416;
    margin: 0;
}

.profile-username {
    color: #6D6D6D;
    font-size: 16px;
    font-weight: 400;
}

.seller-rating-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #FFF9E6;
    padding: 6px 16px;
    border-radius: 30px;
    border: 1px solid #FFD700;
}

.seller-rating-badge i {
    color: #FFC107;
}

.seller-rating-badge span {
    font-weight: 600;
    color: #2C2416;
}

.rating-count {
    color: #999 !important;
    font-weight: 400 !important;
}

.profile-bio {
    margin-bottom: 20px;
    color: #5C5C5C;
    line-height: 1.6;
    max-width: 600px;
}

.no-bio {
    color: #999;
    font-style: italic;
}

.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6D6D6D;
    font-size: 14px;
}

.meta-item i {
    color: #4C3C27;
    width: 16px;
}

/* STATS GRID */
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    border: 1px solid #E8E3D9;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.04);
    border-color: #C9B59C;
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #F5F3EE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4C3C27;
    font-size: 22px;
}

.stat-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 4px;
    line-height: 1;
}

.stat-content p {
    color: #6D6D6D;
    font-size: 14px;
    margin: 0;
}

/* ACTIVITY STATS */
.activity-stats-card {
    background: white;
    border-radius: 16px;
    padding: 25px;
    border: 1px solid #E8E3D9;
    margin-bottom: 30px;
}

.activity-stats-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.activity-stats-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: #2C2416;
}

.view-all-link {
    color: #4C3C27;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

.activity-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.activity-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 15px;
    background: #F9F7F4;
    border-radius: 12px;
}

.activity-label {
    color: #6D6D6D;
    font-size: 14px;
    margin-bottom: 8px;
}

.activity-number {
    font-size: 32px;
    font-weight: 700;
    color: #4C3C27;
}

/* TABS */
.profile-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 1px solid #E8E3D9;
    padding-bottom: 15px;
    overflow-x: auto;
}

.tab-btn {
    padding: 10px 20px;
    background: none;
    border: none;
    border-radius: 30px;
    font-size: 15px;
    font-weight: 500;
    color: #6D6D6D;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s;
    white-space: nowrap;
}

.tab-btn:hover {
    background: #F5F3EE;
    color: #4C3C27;
}

.tab-btn.active {
    background: #4C3C27;
    color: white;
}

.tab-btn.active i {
    color: white;
}

/* TAB CONTENT */
.tab-pane {
    display: none;
    animation: fadeIn 0.3s ease;
}

.tab-pane.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* PRODUCTS GRID */
.products-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.product-card-mini {
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
    overflow: hidden;
    transition: all 0.3s;
}

.product-card-mini:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.04);
    border-color: #C9B59C;
}

.product-image {
    position: relative;
    height: 160px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.discount-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #DC3545;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.product-info {
    padding: 16px;
}

.product-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
    line-height: 1.4;
    height: 44px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.product-title a {
    color: #2C2416;
    text-decoration: none;
}

.product-price {
    margin-bottom: 8px;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #4C3C27;
}

.product-meta {
    display: flex;
    justify-content: space-between;
    color: #6D6D6D;
    font-size: 12px;
    margin-bottom: 12px;
}

.product-actions {
    display: flex;
    gap: 8px;
}

.btn-edit-small {
    flex: 1;
    padding: 8px;
    background: #F5F3EE;
    border: none;
    border-radius: 8px;
    color: #2C2416;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    cursor: pointer;
    text-decoration: none;
}

.btn-delete-small {
    width: 36px;
    background: #FEE2E2;
    border: none;
    border-radius: 8px;
    color: #DC3545;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-delete-small:hover {
    background: #DC3545;
    color: white;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
}

.empty-state i {
    font-size: 64px;
    color: #C9B59C;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: #2C2416;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6D6D6D;
    margin-bottom: 25px;
}

/* VIEW MORE */
.view-more-container {
    text-align: center;
    margin-top: 20px;
}

.btn-view-more {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    background: white;
    border: 1px solid #4C3C27;
    border-radius: 40px;
    color: #4C3C27;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-view-more:hover {
    background: #4C3C27;
    color: white;
    gap: 12px;
}

/* SHOP SECTION */
.shop-overview {
    background: white;
    border-radius: 20px;
    padding: 30px;
    border: 1px solid #E8E3D9;
}

.shop-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.shop-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #2C2416;
    display: flex;
    align-items: center;
    gap: 10px;
}

.shop-header h2 i {
    color: #4C3C27;
}

.btn-edit-shop {
    padding: 10px 20px;
    background: white;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-edit-shop:hover {
    background: #4C3C27;
    color: white;
    border-color: #4C3C27;
}

.shop-stats-mini {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
    padding: 20px;
    background: #F9F7F4;
    border-radius: 16px;
}

.shop-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.shop-stat .stat-label {
    color: #6D6D6D;
    font-size: 13px;
    margin-bottom: 6px;
}

.shop-stat .stat-value {
    font-size: 22px;
    font-weight: 700;
    color: #4C3C27;
}

.shop-categories {
    margin-bottom: 30px;
}

.shop-categories h3 {
    font-size: 16px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 15px;
}

.category-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #F5F3EE;
    border-radius: 40px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.chip:hover {
    background: #4C3C27;
    color: white;
}

.chip:hover i {
    color: white;
}

.quick-actions-shop {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: white;
    border: 1px solid #E8E3D9;
    border-radius: 16px;
    text-decoration: none;
    color: #2C2416;
    transition: all 0.3s;
}

.quick-action-btn:hover {
    background: #4C3C27;
    color: white;
    transform: translateY(-3px);
}

.quick-action-btn i {
    font-size: 24px;
    margin-bottom: 10px;
    color: #4C3C27;
}

.quick-action-btn:hover i {
    color: white;
}

.quick-action-btn span {
    font-size: 13px;
    font-weight: 500;
    text-align: center;
}

.recent-products-preview {
    margin-top: 20px;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.preview-header h4 {
    font-size: 16px;
    font-weight: 600;
    color: #2C2416;
}

.view-link {
    color: #4C3C27;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.preview-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #F9F7F4;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s;
}

.preview-item:hover {
    background: #F0EDE5;
}

.preview-image {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    overflow: hidden;
}

.preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-details {
    display: flex;
    flex-direction: column;
}

.preview-title {
    font-size: 13px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.preview-price {
    font-size: 12px;
    font-weight: 600;
    color: #4C3C27;
}

/* URGENT LIST MINI */
.urgent-list-mini {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.urgent-item {
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #E8E3D9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.urgent-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 8px;
}

.urgent-content p {
    color: #5C5C5C;
    font-size: 14px;
    margin-bottom: 10px;
}

.urgent-meta {
    display: flex;
    gap: 20px;
    color: #6D6D6D;
    font-size: 12px;
}

.urgent-meta i {
    margin-right: 4px;
    color: #4C3C27;
}

/* REVIEWS */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-item {
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #E8E3D9;
}

.reviewer-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.reviewer-info img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.reviewer-info h4 {
    font-size: 15px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 4px;
}

.review-rating {
    display: flex;
    gap: 2px;
}

.review-rating i {
    color: #E8E3D9;
    font-size: 13px;
}

.review-rating i.active {
    color: #FFC107;
}

.review-date {
    margin-left: auto;
    color: #999;
    font-size: 12px;
}

.review-product {
    margin-bottom: 10px;
}

.review-product a {
    color: #4C3C27;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
}

.review-text {
    color: #5C5C5C;
    line-height: 1.6;
    font-size: 14px;
}

/* SETTINGS */
.settings-container {
    display: flex;
    gap: 30px;
    background: white;
    border-radius: 20px;
    border: 1px solid #E8E3D9;
    overflow: hidden;
}

.settings-sidebar {
    width: 260px;
    background: #F9F7F4;
    padding: 25px 0;
}

.settings-menu {
    display: flex;
    flex-direction: column;
}

.settings-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 25px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.settings-menu-item:hover {
    background: #F0EDE5;
}

.settings-menu-item.active {
    background: white;
    border-left-color: #4C3C27;
    color: #4C3C27;
}

.settings-menu-item i {
    width: 18px;
    color: #6D6D6D;
}

.settings-menu-item.active i {
    color: #4C3C27;
}

.settings-content {
    flex: 1;
    padding: 30px;
}

.settings-section {
    display: none;
}

.settings-section.active {
    display: block;
}

.settings-section h3 {
    font-size: 20px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #E8E3D9;
}

.settings-form {
    max-width: 500px;
}

.settings-form .form-group {
    margin-bottom: 20px;
}

.settings-form label {
    display: block;
    margin-bottom: 8px;
    color: #2C2416;
    font-size: 14px;
    font-weight: 500;
}

.settings-form input,
.settings-form textarea,
.settings-form select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #E8E3D9;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.settings-form input:focus,
.settings-form textarea:focus,
.settings-form select:focus {
    border-color: #4C3C27;
    outline: none;
    box-shadow: 0 0 0 3px rgba(76,60,39,0.05);
}

.readonly-input {
    background: #F9F7F4;
    cursor: not-allowed;
}

.profile-pic-upload {
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-pic-upload img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #C9B59C;
}

.btn-change-photo {
    padding: 10px 20px;
    background: #F5F3EE;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    color: #2C2416;
    font-size: 13px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-change-photo:hover {
    background: #4C3C27;
    color: white;
    border-color: #4C3C27;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.btn-save-settings {
    margin-top: 20px;
    padding: 12px 30px;
    background: #4C3C27;
    color: white;
    border: none;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-save-settings:hover {
    background: #2C2416;
    transform: translateY(-2px);
}

/* MODAL */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-content {
    background: white;
    border-radius: 24px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    border-bottom: 1px solid #E8E3D9;
}

.modal-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: #2C2416;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #999;
}

.modal-form {
    padding: 25px;
}

.modal-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn-save {
    flex: 1;
    padding: 14px;
    background: #4C3C27;
    color: white;
    border: none;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel {
    flex: 1;
    padding: 14px;
    background: white;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    color: #6D6D6D;
    font-weight: 600;
    cursor: pointer;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .preview-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-actions-shop {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 992px) {
    .profile-info-wrapper {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .profile-avatar-section {
        margin-right: 0;
        margin-bottom: 20px;
    }
    
    .profile-name-section {
        justify-content: center;
    }
    
    .profile-meta {
        justify-content: center;
    }
    
    .profile-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .settings-container {
        flex-direction: column;
    }
    
    .settings-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .profile-page {
        padding: 20px 0;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .activity-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .shop-stats-mini {
        grid-template-columns: 1fr;
    }
    
    .preview-grid {
        grid-template-columns: 1fr;
    }
    
    .urgent-item {
        flex-direction: column;
        gap: 15px;
    }
    
    .reviewer-info {
        flex-wrap: wrap;
    }
    
    .review-date {
        margin-left: 0;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .profile-name-section {
        flex-direction: column;
    }
    
    .profile-meta {
        flex-direction: column;
        align-items: center;
    }
    
    .tab-btn {
        padding: 8px 16px;
        font-size: 14px;
    }
}
</style>

<?php include '../footer.php'; ?>