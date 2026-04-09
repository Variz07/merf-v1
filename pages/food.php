<?php
require_once '../config.php';

$page_title = 'Food & Beverages';

// Cek apakah user adalah seller atau admin
$can_upload = isLoggedIn() && (isSeller() || isAdmin());

// Get active category filter
$active_category = $_GET['category'] ?? 'all';

// Get products menggunakan fungsi yang sama
$products = getFoodProducts(null, $active_category);

// Get categories with counts
$categories = getFoodCategoriesWithCounts();

// Hitung total products
$total_products = count($products);

// Hitung total sellers
$seller_count = $pdo->query("SELECT COUNT(DISTINCT seller_id) FROM products WHERE category = 'food' AND is_available = 1")->fetchColumn();

include '../header.php';
?>

<div class="food-page">
    <!-- HERO SECTION -->
    <div class="food-hero">
        <div class="container">
            <h1>Food & Beverages</h1>
            <p>Discover various food and beverage options from trusted sellers</p>
            
            <!-- SEARCH -->
            <div class="search-box">
                <form action="search.php" method="GET" style="display: flex; width: 100%;">
                    <input type="hidden" name="category" value="food">
                    <input type="text" name="q" placeholder="Search food, drinks, or sellers...">
                    <button type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- SELLER UPLOAD SECTION - HANYA UNTUK SELLER DAN ADMIN -->

        <?php if($can_upload): ?>
            <div class="seller-upload-section">
                <div class="upload-card">
                    <div class="upload-icon">
                        <i class="fas fa-store"></i>
                    </div>
                <div class="upload-content">
                    <h3>Sell Your Food Products</h3>
                    <p>Share your culinary creations with the community and start earning</p>
            </div>
                <a href="upload-product-food.php" class="upload-btn">  <!-- UBAH INI -->
                        <i class="fas fa-plus-circle"></i> Upload New Product
                </a>
        </div>
    </div>
<?php endif; ?>   

<!-- STATS -->
        <div class="stats-row">
            <div class="stat-item">
                <span class="stat-number"><?php echo $total_products; ?></span>
                <span class="stat-label">Products Available</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $seller_count; ?></span>
                <span class="stat-label">Active Sellers</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">4.8</span>
                <span class="stat-label">Average Rating</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">15min</span>
                <span class="stat-label">Estimate</span>
            </div>
        </div>

        <!-- CATEGORY TABS -->
        <div class="category-tabs">
            <a href="?category=all" class="category-tab <?php echo $active_category == 'all' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                All Items (<?php echo $total_products; ?>)
            </a>
            <?php foreach($categories as $key => $cat): ?>
            <a href="?category=<?php echo $key; ?>" class="category-tab <?php echo $active_category == $key ? 'active' : ''; ?>" 
               style="<?php echo $active_category == $key ? 'background: ' . $cat['color'] . '; border-color: ' . $cat['color'] . ';' : ''; ?>">
                <i class="fas <?php echo $cat['icon']; ?>"></i>
                <?php echo $cat['name']; ?> (<?php echo $cat['count']; ?>)
            </a>
            <?php endforeach; ?>
        </div>

        <!-- RESULT & SORT -->
        <div class="result-bar">
            <p class="result-text">Showing <strong><?php echo $total_products; ?></strong> of <strong><?php echo $total_products; ?></strong> products</p>
            <div class="sort-dropdown">
                <select onchange="window.location.href='?sort='+this.value<?php echo $active_category != 'all' ? '&category='.$active_category : ''; ?>">
                    <option value="newest">Newest</option>
                    <option value="popular">Most Popular</option>
                    <option value="rating">Highest Rating</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                </select>
            </div>
        </div>

        <!-- PRODUCTS GRID -->
        <?php if(empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-utensils"></i>
            <h3>No food products yet</h3>
            <p>Be the first to sell food on MERF!</p>
            <?php if($can_upload): ?>
            <a href="upload-product.php?category=food" class="primary-btn">Upload Product</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach($products as $product): ?>
            <div class="product-card">
                <?php if($product['discounted_price']): 
                    $discount = round((($product['price'] - $product['discounted_price']) / $product['price']) * 100);
                ?>
                <div class="discount-badge">-<?php echo $discount; ?>%</div>
                <?php endif; ?>
                
                <div class="product-image">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <?php if(isLoggedIn()): ?>
                    <button class="wishlist-btn <?php echo isProductFavorited($product['product_id'], $_SESSION['user_id']) ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(<?php echo $product['product_id']; ?>)">
                        <i class="<?php echo isProductFavorited($product['product_id'], $_SESSION['user_id']) ? 'fas' : 'far'; ?> fa-heart"></i>
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h3 class="product-title">
                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    
                    <div class="product-price">
                        <span class="current-price"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
                        <?php if($product['discounted_price']): ?>
                        <span class="original-price"><?php echo formatCurrency($product['price']); ?></span>
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
                    
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="view-btn">
                        View Details
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleFavorite(productId) {
    if(!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
        window.location.href = '../auth/signin.php';
        return;
    }
    
    fetch('../includes/toggle-favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}
</script>

<style>
/* ===== FOOD PAGE STYLES - CONSOLIDATED ===== */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #F9F7F4;
    color: #2C2416;
}

.food-page {
    min-height: 100vh;
    padding-bottom: 50px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* HERO SECTION */
.food-hero {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    padding: 60px 0;
    margin-bottom: 40px;
    color: white;
    text-align: center;
}

.food-hero h1 {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 10px;
}

.food-hero p {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 30px;
}

/* SEARCH BAR */
.search-box {
    max-width: 500px;
    margin: 0 auto;
    display: flex;
    background: white;
    border-radius: 50px;
    padding: 5px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.search-box input {
    flex: 1;
    padding: 15px 20px;
    border: none;
    border-radius: 50px;
    font-size: 16px;
    background: transparent;
}

.search-box input:focus {
    outline: none;
}

.search-box button {
    background: #4C3C27;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
}

/* SELLER UPLOAD SECTION */
.seller-upload-section {
    margin-bottom: 30px;
}

.upload-card {
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
    border-radius: 16px;
    padding: 25px 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    border: 1px solid #FFD700;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.1);
}

.upload-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #4C3C27;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 28px;
    flex-shrink: 0;
}

.upload-content {
    flex: 1;
}

.upload-content h3 {
    font-size: 18px;
    font-weight: 700;
    color: #856404;
    margin-bottom: 5px;
}

.upload-content p {
    color: #6D6D6D;
    font-size: 14px;
    margin: 0;
}

.upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 28px;
    background: #4C3C27;
    color: white;
    text-decoration: none;
    border-radius: 40px;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s;
    white-space: nowrap;
    flex-shrink: 0;
}

.upload-btn:hover {
    background: #2C2416;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(76,60,39,0.2);
}

/* STATS ROW */
.stats-row {
    display: flex;
    justify-content: space-between;
    background: white;
    border-radius: 12px;
    padding: 25px 30px;
    margin-bottom: 30px;
    border: 1px solid #E8E3D9;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #4C3C27;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #6D6D6D;
}

/* CATEGORY TABS */
.category-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
    padding: 10px 0;
    border-bottom: 1px solid #E8E3D9;
}

.category-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: white;
    border: 1px solid #E8E3D9;
    border-radius: 40px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
}

.category-tab i {
    color: #6D6D6D;
}

.category-tab:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
}

.category-tab.active {
    background: #4C3C27;
    color: white;
    border-color: #4C3C27;
}

.category-tab.active i {
    color: white;
}

/* RESULT BAR */
.result-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 15px 0;
    border-bottom: 1px solid #E8E3D9;
}

.result-text {
    color: #6D6D6D;
    font-size: 15px;
    margin: 0;
}

.result-text strong {
    color: #4C3C27;
}

.sort-dropdown select {
    padding: 8px 30px 8px 15px;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    font-size: 14px;
    color: #2C2416;
    background: white;
    cursor: pointer;
}

/* PRODUCT GRID */
.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.product-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #E8E3D9;
    overflow: hidden;
    transition: all 0.3s;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(76,60,39,0.1);
    border-color: #C9B59C;
}

.discount-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #DC3545;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    z-index: 2;
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
    transition: transform 0.3s;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.wishlist-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    background: white;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #999;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 2;
}

.wishlist-btn:hover {
    color: #DC3545;
    transform: scale(1.1);
}

.wishlist-btn.active {
    color: #DC3545;
}

.product-info {
    padding: 16px;
}

.product-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
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
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #4C3C27;
}

.original-price {
    font-size: 13px;
    color: #999;
    text-decoration: line-through;
}

.product-location {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #6D6D6D;
    font-size: 12px;
    margin-bottom: 8px;
}

.product-rating {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-bottom: 12px;
}

.stars {
    color: #FFC107;
    display: flex;
    gap: 2px;
    font-size: 12px;
}

.rating-count {
    color: #6D6D6D;
    font-size: 12px;
}

.product-seller {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-top: 12px;
    border-top: 1px solid #F0EDE5;
}

.seller-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #C9B59C;
}

.seller-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.seller-name {
    font-size: 13px;
    font-weight: 500;
    color: #6D6D6D;
}

.view-btn {
    display: block;
    text-align: center;
    padding: 10px;
    margin-top: 12px;
    background: #F5F3EE;
    color: #2C2416;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.3s;
}

.view-btn:hover {
    background: #4C3C27;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #E8E3D9;
}

.empty-state i {
    font-size: 48px;
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
    margin-bottom: 20px;
}

.primary-btn {
    display: inline-block;
    padding: 12px 24px;
    background: #4C3C27;
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .food-hero h1 {
        font-size: 32px;
    }
    
    .stats-row {
        flex-direction: column;
        gap: 20px;
    }
    
    .upload-card {
        flex-direction: column;
        text-align: center;
        padding: 20px;
    }
    
    .upload-icon {
        margin: 0 auto;
    }
    
    .upload-btn {
        width: 100%;
        justify-content: center;
    }
    
    .result-bar {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .product-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../footer.php'; ?>