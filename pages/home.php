<?php
require_once '../config.php';

$page_title = 'Home';
$page_scripts = ['../assets/js/home.js'];

// Get featured products - HAPUS subquery avg_rating
$featured_products = [];
$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.profile_pic 
                       FROM products p 
                       JOIN users u ON p.seller_id = u.user_id 
                       WHERE p.is_available = 1 
                       ORDER BY p.created_at DESC LIMIT 8");
$stmt->execute();
$featured_products = $stmt->fetchAll();

// Get urgent needs
$urgent_needs = [];
$stmt = $pdo->prepare("SELECT p.*, u.full_name, u.profile_pic 
                       FROM products p 
                       JOIN users u ON p.seller_id = u.user_id 
                       WHERE p.category = 'urgent' 
                       AND p.is_available = 1 
                       ORDER BY p.created_at DESC LIMIT 4");
$stmt->execute();
$urgent_needs = $stmt->fetchAll();

// Get recent blogs
$recent_blogs = [];
$stmt = $pdo->prepare("SELECT b.*, u.full_name, u.profile_pic 
                       FROM blogs b 
                       JOIN users u ON b.user_id = u.user_id 
                       WHERE b.is_published = 1 
                       ORDER BY b.created_at DESC LIMIT 3");
$stmt->execute();
$recent_blogs = $stmt->fetchAll();

// Get stats
$stats = [
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products WHERE is_available = 1")->fetchColumn(),
    'total_sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller' AND status = 'active'")->fetchColumn(),
    'total_transactions' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn()
];

include '../header.php';
?>

<!-- Bagian product display menggunakan p.avg_rating langsung -->
<div class="product-rating">
    <div class="stars">
        <?php
        $rating = $product['avg_rating'] ?? 0;
        for($i = 1; $i <= 5; $i++):
            if($i <= floor($rating)):
                echo '<i class="fas fa-star"></i>';
            elseif($i - 0.5 <= $rating):
                echo '<i class="fas fa-star-half-alt"></i>';
            else:
                echo '<i class="far fa-star"></i>';
            endif;
        endfor;
        ?>
    </div>
    <span class="rating-count">(<?php echo getProductRatingCount($product['product_id']); ?>)</span>
</div>



<div class="container">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h1 class="welcome-title">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p class="welcome-subtitle">
                <?php if($user_location): ?>
                Your location: <?php echo htmlspecialchars($user_location); ?>
                <?php else: ?>
                Complete your profile for a more personalized experience.
                <?php endif; ?>
            </p>
        </div>
        <div class="welcome-stats">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $pending_orders; ?></h3>
                    <p>Active Orders</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $unread_messages; ?></h3>
                    <p>New Message</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo getUserFavoritesCount($_SESSION['user_id']); ?></h3>
                    <p>Favorite</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions-section">
        <h2 class="section-title">Quick Action</h2>
        <div class="actions-grid">
            <a href="upload-product.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="action-content">
                    <h3>Sell ​​Products</h3>
                    <p>Upload your products or services</p>
                </div>
            </a>
            
            <a href="search.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="action-content">
                    <h3>Search Products</h3>
                    <p>Find what you need</p>
                </div>
            </a>
            
            <a href="urgent.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="action-content">
                    <h3>Need it Fast?</h3>
                    <p>Post urgent needs</p>
                </div>
            </a>
            
            <a href="messages.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="action-content">
                    <h3>Message</h3>
                    <p>View your conversations</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Nearby Products -->
    <?php if(!empty($nearby_products) && $user_location): ?>
    <div class="nearby-section">
        <div class="section-header">
            <h2 class="section-title">Products Nearby <?php echo htmlspecialchars($user_location); ?></h2>
            <a href="search.php?location=<?php echo urlencode($user_location); ?>" class="btn-outline btn-sm">
                See All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        <div class="products-grid">
            <?php foreach($nearby_products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="<?php echo UPLOAD_PATH . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <div class="badge badge-nearby">
                        <i class="fas fa-map-marker-alt"></i> Near
                    </div>
                </div>
                <div class="product-content">
                    <h3 class="product-title">
                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="price-current"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
                    </div>
                    <div class="product-distance">
                        <i class="fas fa-walking"></i>
                        <span>Near your location</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recommended Products -->
    <?php if(!empty($recommended_products)): ?>
    <div class="recommended-section">
        <div class="section-header">
            <h2 class="section-title">Recommendations for you</h2>
            <p class="section-subtitle">Based on your interests</p>
        </div>
        <div class="products-grid">
            <?php foreach($recommended_products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="<?php echo UPLOAD_PATH . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <?php if($product['avg_rating'] >= 4.0): ?>
                    <div class="badge badge-recommended">
                        <i class="fas fa-star"></i> Recommended
                    </div>
                    <?php endif; ?>
                </div>
                <div class="product-content">
                    <h3 class="product-title">
                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="price-current"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
                    </div>
                    <div class="product-rating">
                        <div class="stars">
                            <?php
                            $rating = $product['avg_rating'] ?? 0;
                            for($i = 1; $i <= 5; $i++):
                                if($i <= floor($rating)):
                                    echo '<i class="fas fa-star"></i>';
                                elseif($i - 0.5 <= $rating):
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                else:
                                    echo '<i class="far fa-star"></i>';
                                endif;
                            endfor;
                            ?>
                        </div>
                        <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activities -->
    <?php if(!empty($recent_activities)): ?>
    <div class="activities-section">
        <h2 class="section-title">Recent Activities</h2>
        <div class="activities-list">
            <?php foreach($recent_activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <?php if($activity['type'] == 'order'): ?>
                    <i class="fas fa-shopping-bag"></i>
                    <?php elseif($activity['type'] == 'favorite'): ?>
                    <i class="fas fa-heart"></i>
                    <?php else: ?>
                    <i class="fas fa-envelope"></i>
                    <?php endif; ?>
                </div>
                <div class="activity-content">
                    <p><?php echo htmlspecialchars($activity['title']); ?></p>
                    <small><?php echo timeAgo($activity['created_at']); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Popular Categories -->
<!-- ===== KATEGORI POPULER - KOTAK ELEGAN ===== -->
<section class="kategori-populer">
    <div class="container">
        <h2 class="section-title">Kategori Populer</h2>
        <div class="kategori-grid">
            
            <!-- Makanan -->
            <div class="kategori-card">
                <div class="kategori-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="kategori-content">
                    <h3>Makanan</h3>
                    <p>200+ produk</p>
                </div>
            </div>
            
            <!-- Preloved -->
            <div class="kategori-card">
                <div class="kategori-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="kategori-content">
                    <h3>Preloved</h3>
                    <p>150+ produk</p>
                </div>
            </div>
            
            <!-- Jasa -->
            <div class="kategori-card">
                <div class="kategori-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <div class="kategori-content">
                    <h3>Jasa</h3>
                    <p>80+ layanan</p>
                </div>
            </div>
            
            <!-- Urgent Needs -->
            <div class="kategori-card">
                <div class="kategori-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="kategori-content">
                    <h3>Urgent Needs</h3>
                    <p>Bantuan cepat</p>
                </div>
            </div>
            
        </div>
    </div>
</section>
<style>
    /* FIX UNTUK MOBILE */
    @media (max-width: 992px) {
        div[style*="grid-template-columns: repeat(4, 1fr)"] {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }
    
    @media (max-width: 576px) {
        div[style*="grid-template-columns: repeat(4, 1fr)"] {
            grid-template-columns: 1fr !important;
        }
        
        div[style*="padding: 35px 20px"] {
            padding: 25px 20px !important;
            flex-direction: row !important;
            text-align: left !important;
            gap: 20px !important;
        }
        
        div[style*="width: 80px; height: 80px;"] {
            width: 60px !important;
            height: 60px !important;
            margin-bottom: 0 !important;
        }
        
        div[style*="font-size: 36px"] {
            font-size: 26px !important;
        }
    }
</style><style>
.welcome-section {
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: var(--radius-lg);
    padding: var(--space-xl);
    color: white;
    margin-bottom: var(--space-xl);
}

.welcome-title {
    font-size: var(--text-3xl);
    margin-bottom: var(--space-xs);
}

.welcome-subtitle {
    opacity: 0.9;
    margin-bottom: var(--space-lg);
}

.welcome-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--space-md);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    background: rgba(255, 255, 255, 0.1);
    padding: var(--space-md);
    border-radius: var(--radius-md);
    backdrop-filter: blur(10px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-circle);
    background: white;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-xl);
}

.stat-content h3 {
    font-size: var(--text-2xl);
    margin-bottom: 4px;
}

.stat-content p {
    opacity: 0.8;
    font-size: var(--text-sm);
}

.quick-actions-section {
    margin-bottom: var(--space-xl);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-md);
}

.action-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: var(--space-md);
    display: flex;
    align-items: center;
    gap: var(--space-md);
    text-decoration: none;
    color: var(--text-primary);
    border: 2px solid var(--border-color);
    transition: all 0.3s ease;
}

.action-card:hover {
    border-color: var(--secondary-color);
    transform: translateY(-5px);
    box-shadow: var(--shadow-md);
}

.action-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-circle);
    background: var(--secondary-color);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-2xl);
}

.action-content h3 {
    font-size: var(--text-lg);
    margin-bottom: 4px;
}

.action-content p {
    color: var(--text-light);
    font-size: var(--text-sm);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-md);
}

.section-subtitle {
    color: var(--text-light);
    margin-top: -10px;
    margin-bottom: var(--space-md);
}

.badge-nearby {
    background: var(--success-color);
    color: white;
}

.badge-recommended {
    background: var(--warning-color);
    color: var(--dark-color);
}

.product-distance {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: var(--text-xs);
    color: var(--success-color);
}

.activities-section {
    margin: var(--space-xl) 0;
}

.activities-list {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: var(--space-md);
}

.activity-item {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    padding: var(--space-sm);
    border-bottom: 1px solid var(--border-light);
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-circle);
    background: var(--bg-light);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
}

.activity-content p {
    margin-bottom: 4px;
    font-weight: 500;
}

.activity-content small {
    color: var(--text-light);
    font-size: var(--text-xs);
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-md);
}

.category-card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    padding: var(--space-md);
    display: flex;
    align-items: center;
    gap: var(--space-md);
    text-decoration: none;
    color: var(--text-primary);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.category-card:hover {
    border-color: var(--secondary-color);
    transform: translateY(-3px);
}

.category-icon {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-circle);
    background: var(--secondary-color);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-xl);
}

.category-content h3 {
    font-size: var(--text-base);
    margin-bottom: 4px;
}

.category-content p {
    color: var(--text-light);
    font-size: var(--text-xs);
}

/* ===== HERO SECTION - Z-INDEX RENDAH ===== */
.hero {
    position: relative !important;
    z-index: 1 !important; /* RENDAH, DI BAWAH HEADER */
}

/* PASTIKAN SECTION LAIN JUGA RENDAH */
section {
    position: relative !important;
    z-index: 1 !important;
}

</style>

<?php include '../footer.php'; ?>