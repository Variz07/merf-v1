<?php
// product-card.php - Komponen card produk untuk digunakan di berbagai halaman
?>
<div class="product-card">
    <!-- DISCOUNT BADGE -->
    <?php if(!empty($product['discounted_price']) && $product['discounted_price'] < $product['price']): 
        $discount = round((($product['price'] - $product['discounted_price']) / $product['price']) * 100);
    ?>
    <div class="discount-badge">-<?php echo $discount; ?>%</div>
    <?php endif; ?>
    
    <!-- PRODUCT IMAGE -->
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
    
    <!-- PRODUCT INFO -->
    <div class="product-info">
        <h3 class="product-title">
            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                <?php echo htmlspecialchars($product['name']); ?>
            </a>
        </h3>
        
        <div class="product-price">
            <span class="current-price"><?php echo formatCurrency($product['discounted_price'] ?? $product['price']); ?></span>
            <?php if(!empty($product['discounted_price']) && $product['discounted_price'] < $product['price']): ?>
            <span class="original-price"><?php echo formatCurrency($product['price']); ?></span>
            <?php endif; ?>
        </div>
        
        <div class="product-meta">
            <span class="meta-item">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($product['location']); ?>
            </span>
            <span class="meta-item">
                <i class="fas fa-shopping-bag"></i> <?php echo $product['sold'] ?? 0; ?> sold
            </span>
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
            <span class="rating-text">(<?php echo $product['rating_count'] ?? 0; ?>)</span>
        </div>
        
        <div class="product-seller">
            <div class="seller-avatar">
                <img src="<?php echo getUserAvatar($product['seller_id']); ?>" alt="<?php echo htmlspecialchars($product['full_name']); ?>">
            </div>
            <span class="seller-name"><?php echo htmlspecialchars($product['full_name']); ?></span>
        </div>
        
        <div class="product-actions">
            <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn-detail">
                <i class="fas fa-eye"></i> Details
            </a>
            <?php 
            $phone = $product['seller_phone'] ?? '';
            if(!empty($phone)):
                $wa_number = preg_replace('/[^0-9]/', '', $phone);
                if(substr($wa_number, 0, 1) == '0') $wa_number = '62' . substr($wa_number, 1);
                $message = "Hello%20" . urlencode($product['full_name']) . "%2C%0A%0A";
                $message .= "I'm%20interested%20in%20*" . urlencode($product['name']) . "*%0A";
                $message .= "Price%3A%20" . urlencode(formatCurrency($product['discounted_price'] ?? $product['price']));
                $wa_link = "https://wa.me/$wa_number?text=$message";
            ?>
            <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-wa">
                <i class="fab fa-whatsapp"></i> Chat
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>