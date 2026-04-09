<?php
require_once '../config.php';

if(!isLoggedIn()) {
    redirect('../auth/signin.php', 'Please login first', 'error');
}

$page_title = 'My Favorites';

// Get favorite products
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$sql = "
    SELECT p.*, u.full_name, u.profile_pic,
    (SELECT AVG(rating_value) FROM ratings WHERE product_id = p.product_id) as avg_rating
    FROM products p
    JOIN users u ON p.seller_id = u.user_id
    JOIN favorites f ON p.product_id = f.product_id
    WHERE f.user_id = ? AND p.is_available = 1
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $limit, $offset]);
$favorites = $stmt->fetchAll();

// Get total count
$count_sql = "
    SELECT COUNT(*) FROM favorites f
    JOIN products p ON f.product_id = p.product_id
    WHERE f.user_id = ? AND p.is_available = 1
";
$stmt = $pdo->prepare($count_sql);
$stmt->execute([$_SESSION['user_id']]);
$total_favorites = $stmt->fetchColumn();

include '../header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">My Favorites</h1>
        <p class="page-subtitle">Products and services you've saved</p>
    </div>
    
    <?php if(empty($favorites)): ?>
    <div class="empty-state">
        <i class="fas fa-heart"></i>
        <h3>No favorites yet</h3>
        <p>Save products you like by clicking the heart button</p>
        <a href="../index.php" class="btn btn-primary">Explore Products</a>
    </div>
    <?php else: ?>
    <div class="favorites-grid">
        <?php foreach($favorites as $product): ?>
        <div class="product-card">
            <div class="product-image">
                <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                    <img src="<?php echo getProductImageUrl($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                </a>
                <?php if($product['discounted_price']): ?>
                <div class="badge badge-discount">
                    -<?php echo round((($product['price'] - $product['discounted_price']) / $product['price']) * 100); ?>%
                </div>
                <?php endif; ?>
                <button class="product-wishlist active" onclick="toggleFavorite(<?php echo $product['product_id']; ?>)">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
            <div class="product-content">
                <h3 class="product-title">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
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
                    <span class="rating-count">(<?php echo getProductRatingCount($product['product_id']); ?>)</span>
                </div>
                <div class="product-seller">
                    <div class="seller-avatar">
                        <a href="profile.php?id=<?php echo $product['seller_id']; ?>">
                            <img src="<?php echo getUserAvatar($product['seller_id']); ?>" alt="<?php echo htmlspecialchars($product['full_name']); ?>">
                        </a>
                    </div>
                    <a href="profile.php?id=<?php echo $product['seller_id']; ?>" class="seller-name">
                        <?php echo htmlspecialchars($product['full_name']); ?>
                    </a>
                </div>
                <div class="product-actions">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary btn-sm">
                        View Details
                    </a>
                    <?php 
                    $phone = $product['seller_phone'] ?? '';
                    if(!empty($phone)):
                        $wa_number = preg_replace('/[^0-9]/', '', $phone);
                        if(substr($wa_number, 0, 1) == '0') $wa_number = '62' . substr($wa_number, 1);
                        $message = "Hello%20" . urlencode($product['full_name']) . "%2C%0A%0A";
                        $message .= "I'm%20interested%20in%20*" . urlencode($product['name']) . "*%20from%20my%20favorites%20list.%0A";
                        $message .= "Price%3A%20" . urlencode(formatCurrency($product['discounted_price'] ?? $product['price'])) . "%0A";
                        $message .= "Is%20it%20still%20available%3F";
                        $wa_link = "https://wa.me/$wa_number?text=$message";
                    ?>
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn btn-outline btn-sm">
                        <i class="fab fa-whatsapp"></i> Chat
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if($total_favorites > $limit): ?>
    <div class="pagination">
        <?php if($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>" class="pagination-item">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <?php endif; ?>
        
        <?php
        $total_pages = ceil($total_favorites / $limit);
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $start_page + 4);
        
        for($i = $start_page; $i <= $end_page; $i++):
        ?>
        <a href="?page=<?php echo $i; ?>" class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?>" class="pagination-item">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleFavorite(productId) {
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
            // Remove product from favorites list
            const productCard = document.querySelector(`[onclick="toggleFavorite(${productId})"]`)?.closest('.product-card');
            if(productCard) {
                productCard.remove();
                
                // Update message if no more favorites
                if(document.querySelectorAll('.product-card').length === 0) {
                    document.querySelector('.favorites-grid').innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-heart"></i>
                            <h3>No favorites yet</h3>
                            <p>Save products you like by clicking the heart button</p>
                            <a href="../index.php" class="btn btn-primary">Explore Products</a>
                        </div>
                    `;
                }
            }
            
            // Show notification
            showNotification(data.message, data.is_favorited ? 'success' : 'info');
        }
    });
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>

<style>
.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.product-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.product-actions .btn {
    flex: 1;
    justify-content: center;
}

/* Product Card Styles */
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

.badge {
    position: absolute;
    top: 10px;
    left: 10px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    z-index: 2;
}

.badge-discount {
    background: #DC3545;
    color: white;
}

.product-wishlist {
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
    color: #DC3545;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 2;
    transition: all 0.3s;
}

.product-wishlist:hover {
    transform: scale(1.1);
}

.product-content {
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

.price-current {
    font-size: 18px;
    font-weight: 700;
    color: #4C3C27;
}

.price-original {
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

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 30px;
    font-weight: 600;
    font-size: 13px;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #4C3C27;
    color: white;
}

.btn-primary:hover {
    background: #2C2416;
}

.btn-outline {
    background: transparent;
    border: 2px solid #4C3C27;
    color: #4C3C27;
}

.btn-outline:hover {
    background: #4C3C27;
    color: white;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
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

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-top: 40px;
}

.pagination-item {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px 16px;
    border: 1px solid #E8E3D9;
    border-radius: 8px;
    color: #2C2416;
    text-decoration: none;
    transition: all 0.3s;
    min-width: 40px;
}

.pagination-item:hover {
    background: #F5F3EE;
}

.pagination-item.active {
    background: #4C3C27;
    color: white;
    border-color: #4C3C27;
}

/* Notification */
.notification {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease;
    z-index: 10000;
    max-width: 400px;
}

.notification-success {
    border-left: 4px solid #28A745;
}

.notification-success i {
    color: #28A745;
}

.notification-info {
    border-left: 4px solid #17A2B8;
}

.notification-info i {
    color: #17A2B8;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>

<?php include '../footer.php'; ?>