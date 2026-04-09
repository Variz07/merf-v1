<?php
require_once '../config.php';

$page_title = 'Preloved';

// Cek apakah user adalah seller atau admin
$can_upload = isLoggedIn() && (isSeller() || isAdmin());

// Get all preloved categories
$categories = getPrelovedCategoriesWithCounts();

// Get active category filter
$active_category = $_GET['category'] ?? 'all';

include '../header.php';
?>

<div class="preloved-page">
    <!-- HEADER SECTION -->
    <div class="preloved-header">
        <div class="container">
            <h1 class="preloved-title">Preloved</h1>
            <p class="preloved-subtitle">Discover quality second-hand items at the best prices</p>
            
            <!-- SEARCH BAR (OPTIONAL) -->
            <div class="preloved-search">
                <form action="search.php" method="GET" class="search-form">
                    <input type="hidden" name="category" value="preloved">
                    <input type="text" name="q" placeholder="Search preloved items..." class="search-input">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
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
                    <h3>Sell Your Preloved Items</h3>
                    <p>Give your items a second life and earn money</p>
                </div>
                <a href="upload-product-preloved.php" class="upload-btn">
                    <i class="fas fa-plus-circle"></i> Upload New Item
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- CATEGORY TABS -->
        <div class="category-tabs">
            <a href="?category=all" class="category-tab <?php echo $active_category == 'all' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                All Items
            </a>
            <?php foreach($categories as $key => $cat): ?>
            <a href="?category=<?php echo $key; ?>" class="category-tab <?php echo $active_category == $key ? 'active' : ''; ?>"
               style="<?php echo $active_category == $key ? 'background: ' . $cat['color'] . '; border-color: ' . $cat['color'] . ';' : ''; ?>">
                <i class="fas <?php echo $cat['icon']; ?>"></i>
                <?php echo $cat['name']; ?> (<?php echo $cat['count']; ?>)
            </a>
            <?php endforeach; ?>
        </div>

        <!-- DISPLAY CATEGORIES -->
        <?php if($active_category == 'all'): ?>
            <!-- SHOW ALL CATEGORIES -->
            <?php foreach($categories as $key => $cat): ?>
                <?php
                $products = getPrelovedProducts($key, 4);
                if(!empty($products)):
                ?>
                <div class="category-section" id="category-<?php echo $key; ?>">
                    <div class="category-header">
                        <div class="category-title-wrapper">
                            <div class="category-icon" style="background: <?php echo $cat['color']; ?>">
                                <i class="fas <?php echo $cat['icon']; ?>"></i>
                            </div>
                            <h2 class="category-name"><?php echo $cat['name']; ?></h2>
                        </div>
                        <a href="?category=<?php echo $key; ?>" class="view-all-link">
                            See All (<?php echo $cat['count']; ?>) <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <div class="product-row">
                        <?php foreach($products as $product): ?>
                        <div class="product-card">
                            <?php if($cat['badge']): ?>
                            <div class="product-badge" style="background: <?php echo $cat['color']; ?>">
                                <?php echo $cat['badge']; ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php if($product['discounted_price']): ?>
                            <div class="discount-badge">-<?php echo round((($product['price'] - $product['discounted_price']) / $product['price']) * 100); ?>%</div>
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
                                
                                <?php if($product['avg_rating'] > 0): ?>
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
                                <?php endif; ?>
                                
                                <div class="product-seller">
                                    <div class="seller-avatar">
                                        <img src="<?php echo getUserAvatar($product['seller_id']); ?>" alt="<?php echo htmlspecialchars($product['full_name']); ?>">
                                    </div>
                                    <span class="seller-name"><?php echo htmlspecialchars($product['full_name']); ?></span>
                                </div>
                                
                                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="view-details-btn">
                                    View Details
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
        <?php else: ?>
            <!-- SHOW SINGLE CATEGORY WITH ALL PRODUCTS -->
            <?php
            $category_name = $categories[$active_category]['name'] ?? 'Category';
            $products = getPrelovedProducts($active_category, 12);
            ?>
            
            <div class="category-header">
                <h2 class="category-title"><?php echo $category_name; ?></h2>
                <a href="preloved.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to All Categories
                </a>
            </div>
            
            <?php if(empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No items in this category yet</h3>
                <p>Be the first to sell in this category!</p>
                <?php if($can_upload): ?>
                <a href="upload-product-preloved.php" class="btn-primary">Upload Item</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="products-grid">
                <?php foreach($products as $product): ?>
                <div class="product-card">
                    <?php if($categories[$active_category]['badge']): ?>
                    <div class="product-badge" style="background: <?php echo $categories[$active_category]['color']; ?>">
                        <?php echo $categories[$active_category]['badge']; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($product['discounted_price']): ?>
                    <div class="discount-badge">-<?php echo round((($product['price'] - $product['discounted_price']) / $product['price']) * 100); ?>%</div>
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
                        
                        <?php if($product['avg_rating'] > 0): ?>
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
                        <?php endif; ?>
                        
                        <div class="product-seller">
                            <div class="seller-avatar">
                                <img src="<?php echo getUserAvatar($product['seller_id']); ?>" alt="<?php echo htmlspecialchars($product['full_name']); ?>">
                            </div>
                            <span class="seller-name"><?php echo htmlspecialchars($product['full_name']); ?></span>
                        </div>
                        
                        <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="view-details-btn">
                            View Details
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
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
/* ===== PRELOVED PAGE - UPDATED STYLES ===== */
.preloved-page {
    background: #F9F7F4;
    min-height: 100vh;
    padding-bottom: 50px;
}

/* HEADER */
.preloved-header {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    padding: 50px 0 60px;
    margin-bottom: 30px;
    color: white;
    text-align: center;
}

.preloved-title {
    font-size: 42px;
    font-weight: 700;
    margin-bottom: 10px;
}

.preloved-subtitle {
    font-size: 18px;
    opacity: 0.9;
    margin-bottom: 30px;
}

/* SEARCH BAR */
.preloved-search {
    max-width: 500px;
    margin: 0 auto;
}

.preloved-search .search-form {
    position: relative;
    display: flex;
    align-items: center;
}

.preloved-search .search-input {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border: none;
    border-radius: 40px;
    font-size: 16px;
    background: white;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.preloved-search .search-input:focus {
    outline: none;
}

.preloved-search .search-btn {
    position: absolute;
    right: 5px;
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #4C3C27;
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* SELLER UPLOAD SECTION */
.seller-upload-section {
    margin-bottom: 30px;
}

.upload-card {
    background: linear-gradient(135deg, #E8F0FE, #D9E6F5);
    border-radius: 16px;
    padding: 25px 30px;
    display: flex;
    align-items: center;
    gap: 20px;
    border: 1px solid #4C6EF5;
    box-shadow: 0 4px 12px rgba(76, 110, 245, 0.1);
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
    color: #2C3E50;
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

/* CATEGORY TABS */
.category-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 30px;
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

/* CATEGORY SECTION */
.category-section {
    margin-bottom: 50px;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #E8E3D9;
}

.category-title-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.category-name {
    font-size: 22px;
    font-weight: 600;
    color: #2C2416;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4C3C27;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.view-all-link:hover {
    gap: 12px;
}

.back-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #4C3C27;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

/* PRODUCT ROW */
.product-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

/* PRODUCT CARD */
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

.product-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    color: white;
    z-index: 2;
}

.discount-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #DC3545;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 3;
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

.product-location i {
    color: #4C3C27;
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

.view-details-btn {
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

.view-details-btn:hover {
    background: #4C3C27;
    color: white;
}

/* EMPTY STATE */
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

.btn-primary {
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
    .product-row,
    .products-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .preloved-title {
        font-size: 32px;
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
    
    .product-row,
    .products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .category-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}

@media (max-width: 576px) {
    .product-row,
    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../footer.php'; ?>