<?php
require_once 'config.php';
$page_title = 'Home';
$page_scripts = ['assets/js/home.js'];

// Get featured products
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

include 'header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Find Everything Students Need on One Platform</h1>
            <p class="hero-subtitle">Food, preloved items, services, and urgent assistance for President University students</p>
            <div class="hero-search">
                <form action="search.php" method="GET" class="search-form">
                    <input type="text" name="q" placeholder="Search food, clothing, services, or assistance..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<!-- ===== STATS SECTION - ELEGANT 4-COLUMN BOXES ===== -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            
            <!-- ACTIVE PRODUCTS -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['total_products']); ?></h3>
                    <p class="stat-label">Active Products</p>
                    <span class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i> +12% this week
                    </span>
                </div>
            </div>
            
            <!-- TRUSTED SELLERS -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['total_sellers']); ?></h3>
                    <p class="stat-label">Trusted Sellers</p>
                    <span class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i> +5 new sellers
                    </span>
                </div>
            </div>
            
            <!-- SUCCESSFUL TRANSACTIONS -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['total_transactions']); ?></h3>
                    <p class="stat-label">Successful Transactions</p>
                    <span class="stat-trend">
                        <i class="fas fa-check-circle"></i> 100% secure
                    </span>
                </div>
            </div>
            
            <!-- ACTIVE USERS -->
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <h3 class="stat-number"><?php echo number_format($stats['total_users']); ?></h3>
                    <p class="stat-label">Active Users</p>
                    <span class="stat-trend positive">
                        <i class="fas fa-arrow-up"></i> +<?php echo $stats['today_users'] ?? 0; ?> today
                    </span>
                </div>
            </div>
            
        </div>
    </div>
</section>
<style> 
    /* ===== CSS NTUK BAGIAN ATAS  ===== */
.stats-section {
    padding: 30px 0 50px;
    background: linear-gradient(180deg, #FFFEFC 0%, #F9F7F4 100%);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}

/* STAT CARD - KOTAK PUTIH ELEGAN */
.stat-card {
    background: white;
    border-radius: 20px;
    padding: 30px 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    border: 1px solid #E8E3D9;
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 20px rgba(0,0,0,0.02);
}

/* DECORATION LINE */
.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, #4C3C27, #C9B59C);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.stat-card:hover {
    transform: translateY(-8px);
    border-color: #C9B59C;
    box-shadow: 0 20px 35px rgba(76, 60, 39, 0.1);
}

.stat-card:hover::before {
    opacity: 1;
}

/* STAT ICON */
.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    background: linear-gradient(145deg, #F5F3EE, #EAE7E0);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #4C3C27;
    transition: all 0.4s ease;
    flex-shrink: 0;
}

.stat-card:hover .stat-icon {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    transform: scale(1.05) rotate(5deg);
}

/* STAT CONTENT */
.stat-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 36px;
    font-weight: 800;
    color: #2C2416;
    line-height: 1.1;
    margin-bottom: 6px;
    font-family: 'Montserrat', 'Poppins', sans-serif;
}

.stat-label {
    font-size: 15px;
    font-weight: 600;
    color: #6D6D6D;
    margin-bottom: 8px;
    letter-spacing: 0.3px;
}

/* STAT TREND */
.stat-trend {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 30px;
    background: #F5F3EE;
    color: #4A4A4A;
    width: fit-content;
}

.stat-trend.positive {
    background: rgba(40, 167, 69, 0.1);
    color: #28A745;
}

.stat-trend i {
    font-size: 11px;
}

.stat-trend .fa-check-circle {
    color: #28A745;
}

/* CARD VARIATIONS - WARNA BERBEDA UNTUK SETIAP CARD */
.stat-card:nth-child(1) .stat-icon {
    color: #4C3C27;
}
.stat-card:nth-child(1):hover .stat-icon {
    background: linear-gradient(135deg, #4C3C27, #2C2416);
}

.stat-card:nth-child(2) .stat-icon {
    color: #C9B59C;
}
.stat-card:nth-child(2):hover .stat-icon {
    background: linear-gradient(135deg, #C9B59C, #8B7356);
}

.stat-card:nth-child(3) .stat-icon {
    color: #17A2B8;
}
.stat-card:nth-child(3):hover .stat-icon {
    background: linear-gradient(135deg, #17A2B8, #0F6674);
}

.stat-card:nth-child(4) .stat-icon {
    color: #28A745;
}
.stat-card:nth-child(4):hover .stat-icon {
    background: linear-gradient(135deg, #28A745, #1E7A34);
}

/* ===== ANIMASI ===== */
.stat-card {
    animation: fadeInUp 0.6s ease backwards;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .stat-card {
        padding: 25px 20px;
    }
    
    .stat-number {
        font-size: 32px;
    }
}

@media (max-width: 768px) {
    .stats-section {
        padding: 20px 0 40px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .stat-card {
        padding: 20px 18px;
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 28px;
        margin-bottom: 10px;
    }
    
    .stat-content {
        align-items: center;
    }
    
    .stat-number {
        font-size: 28px;
    }
    
    .stat-label {
        font-size: 14px;
    }
    
    .stat-trend {
        font-size: 11px;
        padding: 4px 10px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .stat-card {
        flex-direction: row;
        text-align: left;
        padding: 18px 20px;
    }
    
    .stat-icon {
        margin-bottom: 0;
        width: 55px;
        height: 55px;
        font-size: 24px;
    }
    
    .stat-content {
        align-items: flex-start;
    }
    
    .stat-number {
        font-size: 26px;
    }
}
</style> 


<!-- Categories Section -->
<!-- ===== POPULAR CATEGORIES - CLICKABLE CARDS ===== -->
<section class="popular-categories">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Popular Categories</h2>
            <a href="all-categories.php" class="view-all-link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="categories-grid">
            
            <!-- FOOD CARD - Link to food.php -->
            <a href="pages/food.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="category-content">
                    <h3>Food</h3>
                    <p>200+ products</p>
                </div>
                <div class="category-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- PRELOVED CARD - Link to preloved.php -->
            <a href="pages/preloved.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <div class="category-content">
                    <h3>Preloved</h3>
                    <p>150+ products</p>
                </div>
                <div class="category-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- SERVICES CARD - Link to service.php -->
            <a href="pages/service.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-hands-helping"></i>
                </div>
                <div class="category-content">
                    <h3>Services</h3>
                    <p>80+ services</p>
                </div>
                <div class="category-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
            <!-- URGENT NEEDS CARD - Link to urgent.php -->
            <a href="pages/urgent.php" class="category-card">
                <div class="category-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="category-content">
                    <h3>Urgent Needs</h3>
                    <p>Quick assistance</p>
                </div>
                <div class="category-arrow">
                    <i class="fas fa-chevron-right"></i>
                </div>
            </a>
            
        </div>
    </div>
</section>

<style>

/* ===== POPULAR CATEGORIES - CLICKABLE CARDS (FINAL VERSION) ===== */
.popular-categories {
    padding: 50px 0;
    background: #F9F7F4;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: #2C2416;
    position: relative;
    padding-left: 16px;
}

.section-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(to bottom, #4C3C27, #C9B59C);
    border-radius: 2px;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4C3C27;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    padding: 10px 20px;
    border-radius: 40px;
    transition: all 0.3s;
}

.view-all-link:hover {
    background: white;
    gap: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}

/* CATEGORIES GRID - 4 COLUMNS */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

/* CATEGORY CARD - CLICKABLE */
.category-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    position: relative;
    overflow: hidden;
}

.category-card:hover {
    transform: translateY(-5px);
    border-color: #4C3C27;
    box-shadow: 0 12px 28px rgba(76, 60, 39, 0.1);
}

/* HOVER BACKGROUND EFFECT */
.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 1;
}

.category-card:hover::before {
    opacity: 0.03;
}

/* CATEGORY ICON */
.category-icon {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    background: #F5F3EE;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    color: #4C3C27;
    transition: all 0.3s;
    z-index: 2;
}

.category-card:hover .category-icon {
    background: #4C3C27;
    color: white;
    transform: scale(1.05);
}

/* CATEGORY CONTENT */
.category-content {
    flex: 1;
    z-index: 2;
}

.category-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 6px;
    transition: color 0.3s;
}

.category-card:hover .category-content h3 {
    color: #4C3C27;
}

.category-content p {
    font-size: 14px;
    color: #6D6D6D;
    transition: color 0.3s;
}

.category-card:hover .category-content p {
    color: #4C3C27;
}

/* ARROW ICON */
.category-arrow {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #F5F3EE;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4C3C27;
    font-size: 14px;
    transition: all 0.3s;
    z-index: 2;
}

.category-card:hover .category-arrow {
    background: #4C3C27;
    color: white;
    transform: translateX(5px);
}

/* WARNA KHUSUS UNTUK MASING-MASING KATEGORI */
.category-card:nth-child(1):hover .category-icon {
    background: #FF6B6B;
}

.category-card:nth-child(2):hover .category-icon {
    background: #4C6EF5;
}

.category-card:nth-child(3):hover .category-icon {
    background: #20C997;
}

.category-card:nth-child(4):hover .category-icon {
    background: #FF8787;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .section-title {
        font-size: 24px;
    }
}

@media (max-width: 768px) {
    .popular-categories {
        padding: 40px 0;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .categories-grid {
        gap: 15px;
    }
    
    .category-card {
        padding: 16px;
    }
    
    .category-icon {
        width: 48px;
        height: 48px;
        font-size: 22px;
    }
    
    .category-content h3 {
        font-size: 16px;
    }
    
    .category-content p {
        font-size: 13px;
    }
}

@media (max-width: 576px) {
    .categories-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .view-all-link {
        align-self: flex-start;
    }
}

</style>

<!-- Featured Products -->
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured Products</h2>
            <a href="pages/food.php" class="btn-outline btn-sm">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="product-grid">
            <?php 
            // Gunakan fungsi yang sama dengan food page
            $featured_products = getFoodProducts(8);
            foreach($featured_products as $product): 
            ?>
            <div class="product-card">
                <div class="product-image">
                    <a href="pages/product-detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <?php if($product['discounted_price']): ?>
                    <div class="badge badge-discount">DISCOUNT</div>
                    <?php endif; ?>
                    <?php if(isLoggedIn()): ?>
                    <button class="product-wishlist <?php echo isProductFavorited($product['product_id'], $_SESSION['user_id']) ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(<?php echo $product['product_id']; ?>)">
                        <i class="<?php echo isProductFavorited($product['product_id'], $_SESSION['user_id']) ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="product-content">
                    <h3 class="product-title">
                        <a href="pages/product-detail.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-price">
                        <span class="price-current"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
                        <?php if($product['discounted_price']): ?>
                        <span class="price-original"><?php echo formatCurrency($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($product['location']); ?></span>
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
                        <span class="rating-count">(<?php echo $product['rating_count'] ?? 0; ?>)</span>
                    </div>
                    <div class="product-seller">
                        <div class="seller-avatar">
                            <img src="<?php echo getUserAvatar($product['seller_id']); ?>" alt="<?php echo htmlspecialchars($product['full_name']); ?>">
                        </div>
                        <span class="seller-name"><?php echo htmlspecialchars($product['full_name']); ?></span>
                    </div>
                    <a href="pages/product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary btn-block mt-2">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
/* ===== BLOG SECTION HOMEPAGE - MOMENT CARDS ===== */
.blog-section-home {
    padding: 60px 0;
    background: #F9F7F4;
    margin-top: 20px;
}

/* HEADER WRAPPER */
.blog-header-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: #4C3C27;
    position: relative;
    padding-left: 20px;
}

.section-title::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 5px;
    background: linear-gradient(to bottom, #4C3C27, #C9B59C);
    border-radius: 3px;
}

.btn-view-all {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #4C3C27;
    font-weight: 600;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 30px;
    transition: all 0.3s;
}

.btn-view-all:hover {
    background: rgba(76, 60, 39, 0.05);
    gap: 15px;
}

.btn-view-all i {
    font-size: 14px;
}

/* BLOG GRID - 3 KOLOM */
.blog-moment-grid-home {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
}

/* MOMENT CARD - KOTAK PUTIH ELEGAN */
.moment-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    border: 1px solid #E8E3D9;
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

.moment-card:hover {
    transform: translateY(-6px);
    border-color: #C9B59C;
    box-shadow: 0 15px 35px rgba(76, 60, 39, 0.08);
}

/* DECORATION LINE */
.moment-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #4C3C27, #C9B59C);
    opacity: 0;
    transition: opacity 0.3s;
}

.moment-card:hover::before {
    opacity: 1;
}

/* HEADER CARD */
.moment-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.author-avatar {
    width: 55px;
    height: 55px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    flex-shrink: 0;
}

.author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-info {
    flex: 1;
}

.author-name {
    font-size: 16px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 6px;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}

.post-category {
    background: #F0EDE5;
    padding: 4px 12px;
    border-radius: 20px;
    color: #4C3C27;
    font-weight: 600;
    font-size: 12px;
}

.post-time {
    color: #8E8E8E;
}

/* CONTENT CARD */
.moment-content {
    flex: 1;
    margin-bottom: 20px;
}

.moment-title {
    font-size: 18px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 12px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.moment-text {
    font-size: 14px;
    line-height: 1.6;
    color: #5C5C5C;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* FOOTER CARD */
.moment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 18px;
    border-top: 1px solid #F0EDE5;
}

.post-stats {
    display: flex;
    gap: 16px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #8E8E8E;
    font-size: 13px;
}

.stat-item i {
    font-size: 14px;
}

.stat-item .fa-eye { color: #4C3C27; }
.stat-item .fa-heart { color: #DC3545; }
.stat-item .fa-comment { color: #17A2B8; }

.btn-read-more {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #4C3C27;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 25px;
    background: #F5F3EE;
    transition: all 0.3s;
}

.btn-read-more:hover {
    background: #4C3C27;
    color: white;
    gap: 10px;
}

.btn-read-more i {
    font-size: 12px;
    transition: transform 0.3s;
}

.btn-read-more:hover i {
    transform: translateX(4px);
}

/* MOBILE VIEW ALL */
.mobile-view-all {
    display: none;
    text-align: center;
    margin-top: 30px;
}

.btn-view-all-mobile {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: white;
    color: #4C3C27;
    font-weight: 600;
    padding: 14px 30px;
    border-radius: 40px;
    text-decoration: none;
    border: 1px solid #4C3C27;
    transition: all 0.3s;
    width: 100%;
}

.btn-view-all-mobile:hover {
    background: #4C3C27;
    color: white;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .blog-moment-grid-home {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .blog-section-home {
        padding: 40px 0;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .btn-view-all {
        display: none; /* HIDE DI DESKTOP */
    }
    
    .mobile-view-all {
        display: block;
    }
    
    .blog-moment-grid-home {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .moment-card {
        padding: 20px;
    }
    
    .author-avatar {
        width: 50px;
        height: 50px;
    }
    
    .moment-title {
        font-size: 17px;
    }
}

@media (max-width: 480px) {
    .moment-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .post-stats {
        width: 100%;
        justify-content: space-around;
    }
    
    .btn-read-more {
        width: 100%;
        justify-content: center;
    }
}
</style>

<!-- ===== JASA & LAYANAN - KOTAK KECIL ELEGAN ===== -->
<section class="jasa-home-section">
    <div class="container">
        <!-- HEADER SECTION -->
        <div class="jasa-home-header">
            <div>
                <h2 class="section-title">Services & Offers</h2>
                <p class="section-subtitle">Discover various services and skills from trusted students</p>
            </div>
            <a href="service.php" class="btn-view-all-small">
                See Others <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- JASA GRID - SMALL BOX 4 COLUMNS -->
        <div class="jasa-home-grid">
            <?php
            // Fetch latest 4 service products from database
            $stmt = $pdo->prepare("
                SELECT p.*, u.full_name, u.profile_pic, u.phone as seller_phone, u.rating as seller_rating,
                (SELECT COUNT(*) FROM orders WHERE product_id = p.product_id AND status = 'delivered') as terjual
                FROM products p
                JOIN users u ON p.seller_id = u.user_id
                WHERE p.category = 'service' 
                AND p.is_available = 1 
                AND u.status = 'active'
                ORDER BY p.created_at DESC
                LIMIT 4
            ");
            $stmt->execute();
            $jasa_products = $stmt->fetchAll();
            
            if(empty($jasa_products)):
            ?>
            <!-- DEFAULT SERVICE PRODUCTS - JASTIP FOOD -->
            <div class="jasa-home-card jastip-card">
                <div class="jasa-home-category">Food Proxy</div>
                <div class="jasa-home-content">
                    <h3 class="jasa-home-title">JASTIP DONATSU</h3>
                    <div class="jasa-home-seller">
                        <span class="seller-name">Donatsu</span>
                    </div>
                    <div class="jasa-home-desc">
                        <span class="label">PO Schedule & Delivery</span>
                        <span class="time">PO Closes: 19.15</span>
                        <span class="time">Delivery: 19.40</span>
                        <div class="shipping-info">
                            <span class="shipping-item">SBH: Free delivery</span>
                            <span class="shipping-item">NBH, Monroe & nearby boarding houses: Delivery 4K</span>
                        </div>
                    </div>
                    <div class="jasa-home-footer">
                        <span class="jasa-home-price">Rp 50.000</span>
                        <?php 
                        $wa_link_default = "https://wa.me/6281234567890?text=Halo%20Donatsu%2C%20saya%20tertarik%20dengan%20JASTIP%20DONATSU%20Anda.";
                        ?>
                        <a href="<?php echo $wa_link_default; ?>" target="_blank" class="btn-hire-wa">
                            <i class="fab fa-whatsapp"></i> Hire
                        </a>
                    </div>
                </div>
            </div>

            <div class="jasa-home-card jastip-card">
                <div class="jasa-home-category">Food Proxy</div>
                <div class="jasa-home-content">
                    <h3 class="jasa-home-title">JASTIP CHEESE CUT</h3>
                    <div class="jasa-home-seller">
                        <span class="seller-name">Bandung</span>
                    </div>
                    <div class="jasa-home-desc">
                        <span class="label">AVAILABLE TOMORROW 15.00–20.00</span>
                        <div class="price-options">
                            <span class="price-item">regular: 50</span>
                            <span class="price-item">no topping: 25</span>
                        </div>
                        <div class="shipping-info">
                            <span class="shipping-item">SBH: FREE / NBH: 5K</span>
                            <span class="shipping-item">boarding houses near PU: 5K</span>
                        </div>
                        <span class="payment-badge">QRIS & transfer available ✅</span>
                    </div>
                    <div class="jasa-home-footer">
                        <span class="jasa-home-price">Rp 50.000</span>
                        <a href="https://wa.me/6281234567890?text=Halo%20Bandung%2C%20saya%20tertarik%20dengan%20JASTIP%20CHEESE%20CUIT%20Anda." target="_blank" class="btn-hire-wa">
                            <i class="fab fa-whatsapp"></i> Hire
                        </a>
                    </div>
                </div>
            </div>

            <div class="jasa-home-card jastip-card">
                <div class="jasa-home-category">Food Proxy</div>
                <div class="jasa-home-content">
                    <h3 class="jasa-home-title">JASTIP GACOAN</h3>
                    <div class="jasa-home-seller">
                        <span class="seller-name">Gacoan</span>
                    </div>
                    <div class="jasa-home-desc">
                        <span class="label highlight">AVAILABLE NOW – GACOAN!!</span>
                        <div class="price-options">
                            <span class="price-item">Lvl 0–5: 15K</span>
                            <span class="price-item">Shrimp Cheese: 15K</span>
                            <span class="price-item">Green Tea: 14K</span>
                        </div>
                        <span class="free-ongkir">FREE DELIVERY!!!</span>
                    </div>
                    <div class="jasa-home-footer">
                        <span class="jasa-home-price">Rp 15.000</span>
                        <a href="https://wa.me/6281234567890?text=Halo%20Gacoan%2C%20saya%20tertarik%20dengan%20JASTIP%20GACOAN%20Anda." target="_blank" class="btn-hire-wa">
                            <i class="fab fa-whatsapp"></i> Hire
                        </a>
                    </div>
                </div>
            </div>

            <div class="jasa-home-card">
                <div class="jasa-home-category">Courses</div>
                <div class="jasa-home-content">
                    <h3 class="jasa-home-title">CALCULUS COURSE</h3>
                    <div class="jasa-home-seller">
                        <span class="seller-name">Budi Utomo</span>
                        <span class="seller-rating">
                            <i class="fas fa-star"></i> 0.0
                        </span>
                    </div>
                    <div class="jasa-home-desc">
                        <p>Intensive Calculus Class – understanding concepts and practice problems</p>
                        <span class="feature-item">✓ Experienced mentor ready to guide</span>
                        <span class="feature-item">✓ Active study community for discussion and Q&A</span>
                    </div>
                    <div class="jasa-home-footer">
                        <span class="jasa-home-price">Rp 250.000</span>
                        <a href="https://wa.me/6281234567890?text=Halo%20Budi%20Utomo%2C%20saya%20tertarik%20dengan%20CALCULUS%20COURSE%20Anda." target="_blank" class="btn-hire-wa">
                            <i class="fab fa-whatsapp"></i> Hire
                        </a>
                    </div>
                </div>
            </div>

            <?php else: ?>
                <?php foreach($jasa_products as $jasa): ?>
                <?php 
                // Format WhatsApp number
                $phone = $jasa['seller_phone'] ?? '';
                $wa_number = preg_replace('/[^0-9]/', '', $phone);
                if(substr($wa_number, 0, 1) == '0') {
                    $wa_number = '62' . substr($wa_number, 1);
                }
                if(substr($wa_number, 0, 2) != '62' && strlen($wa_number) > 0) {
                    $wa_number = '62' . $wa_number;
                }
                $message = "Hello%20" . urlencode($jasa['full_name']) . "%2C%0A%0A";
                $message .= "I%20am%20interested%20in%20the%20service%20*" . urlencode($jasa['name']) . "*%20that%20you%20offer.%0A%0A";
                $message .= "Is%20it%20still%20available%3F";
                $wa_link = "https://wa.me/$wa_number?text=$message";
                ?>
                <div class="jasa-home-card">
                    <?php if($jasa['subcategory'] == 'jastip'): ?>
                    <div class="jasa-home-category">Food Proxy</div>
                    <?php else: ?>
                    <div class="jasa-home-category"><?php echo ucfirst($jasa['subcategory'] ?? 'Service'); ?></div>
                    <?php endif; ?>
                    <div class="jasa-home-content">
                        <h3 class="jasa-home-title"><?php echo htmlspecialchars($jasa['name']); ?></h3>
                        <div class="jasa-home-seller">
                            <span class="seller-name"><?php echo htmlspecialchars($jasa['full_name']); ?></span>
                            <span class="seller-rating">
                                <i class="fas fa-star"></i> <?php echo number_format($jasa['seller_rating'] ?? 0, 1); ?>
                            </span>
                        </div>
                        <div class="jasa-home-desc">
                            <p><?php echo htmlspecialchars(substr($jasa['description'], 0, 100)); ?>...</p>
                            <?php if($jasa['delivery_fee'] > 0): ?>
                            <span class="shipping-item">Delivery fee: <?php echo formatCurrency($jasa['delivery_fee']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="jasa-home-footer">
                            <div class="price-section">
                                <span class="jasa-home-price"><?php echo formatCurrency($jasa['discounted_price'] ?? $jasa['price']); ?></span>
                                <?php if($jasa['discounted_price']): ?>
                                <span class="jasa-original"><?php echo formatCurrency($jasa['price']); ?></span>
                                <?php endif; ?>
                                <span class="jasa-sold">Sold <?php echo $jasa['terjual'] ?? 0; ?></span>
                            </div>
                            <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-hire-wa">
                                <i class="fab fa-whatsapp"></i> Hire
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>