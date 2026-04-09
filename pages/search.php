<?php
require_once '../config.php';

$page_title = 'Hasil Pencarian';
$page_scripts = ['../assets/js/search.js'];

$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$location = $_GET['location'] ?? '';
$sort = $_GET['sort'] ?? 'relevance';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

if(empty($query) && empty($category) && empty($location)) {
    redirect('../index.php');
}

// Build search query - HAPUS subquery avg_rating
$sql = "
    SELECT p.*, u.full_name, u.profile_pic,
    MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE) as relevance
    FROM products p
    JOIN users u ON p.seller_id = u.user_id
    WHERE p.is_available = 1
";

$params = ['query' => $query];

if(!empty($query)) {
    $sql .= " AND (MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE) 
                OR p.name LIKE :query_like 
                OR p.description LIKE :query_like)";
    $params['query_like'] = "%$query%";
}

if(!empty($category)) {
    $sql .= " AND p.category = :category";
    $params['category'] = $category;
}

if(!empty($min_price)) {
    $sql .= " AND (p.discounted_price IS NOT NULL AND p.discounted_price >= :min_price 
                OR p.price >= :min_price)";
    $params['min_price'] = $min_price;
}

if(!empty($max_price)) {
    $sql .= " AND (p.discounted_price IS NOT NULL AND p.discounted_price <= :max_price 
                OR p.price <= :max_price)";
    $params['max_price'] = $max_price;
}

if(!empty($location)) {
    $sql .= " AND p.location LIKE :location";
    $params['location'] = "%$location%";
}

// Get total count - QUERY TERPISAH
$count_sql = "
    SELECT COUNT(*) FROM products p
    JOIN users u ON p.seller_id = u.user_id
    WHERE p.is_available = 1
";

$count_params = [];

if(!empty($query)) {
    $count_sql .= " AND (MATCH(p.name, p.description) AGAINST(:query IN BOOLEAN MODE) 
                    OR p.name LIKE :query_like 
                    OR p.description LIKE :query_like)";
    $count_params['query'] = $query;
    $count_params['query_like'] = "%$query%";
}

if(!empty($category)) {
    $count_sql .= " AND p.category = :category";
    $count_params['category'] = $category;
}

if(!empty($min_price)) {
    $count_sql .= " AND (p.discounted_price IS NOT NULL AND p.discounted_price >= :min_price 
                    OR p.price >= :min_price)";
    $count_params['min_price'] = $min_price;
}

if(!empty($max_price)) {
    $count_sql .= " AND (p.discounted_price IS NOT NULL AND p.discounted_price <= :max_price 
                    OR p.price <= :max_price)";
    $count_params['max_price'] = $max_price;
}

if(!empty($location)) {
    $count_sql .= " AND p.location LIKE :location";
    $count_params['location'] = "%$location%";
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($count_params);
$total_results = $stmt->fetchColumn();

// Add sorting
switch($sort) {
    case 'price_low':
        $sql .= " ORDER BY COALESCE(p.discounted_price, p.price) ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY COALESCE(p.discounted_price, p.price) DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.avg_rating DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.created_at DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.total_views DESC";
        break;
    default:
        if(!empty($query)) {
            $sql .= " ORDER BY relevance DESC";
        } else {
            $sql .= " ORDER BY p.created_at DESC";
        }
}

// Add pagination
$sql .= " LIMIT :limit OFFSET :offset";
$params['limit'] = $limit;
$params['offset'] = $offset;

// Execute
$stmt = $pdo->prepare($sql);
foreach($params as $key => $value) {
    if(is_int($value)) {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$results = $stmt->fetchAll();

// Sisanya sama...

// Get search suggestions
$suggestions = [];
if(!empty($query)) {
    $suggest_stmt = $pdo->prepare("
        SELECT DISTINCT name FROM products 
        WHERE name LIKE :suggest 
        AND is_available = 1 
        LIMIT 5
    ");
    $suggest_stmt->execute(['suggest' => "$query%"]);
    $suggestions = $suggest_stmt->fetchAll(PDO::FETCH_COLUMN);
}

include '../header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Hasil Pencarian</h1>
        <p class="page-subtitle">
            <?php if(!empty($query)): ?>
            Menampilkan hasil untuk "<?php echo htmlspecialchars($query); ?>"
            <?php endif; ?>
            <?php if(!empty($category)): ?>
            dalam kategori <?php echo getCategoryName($category); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Search Bar -->
    <div class="search-container">
        <form method="GET" class="search-form-large">
            <div class="search-input-group">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                       placeholder="Cari makanan, pakaian, jasa..." class="search-input-large">
                <button type="submit" class="search-btn-large">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
            
            <div class="search-filters">
                <select name="category" class="filter-select">
                    <option value="">Semua Kategori</option>
                    <option value="food" <?php echo $category == 'food' ? 'selected' : ''; ?>>Makanan</option>
                    <option value="preloved" <?php echo $category == 'preloved' ? 'selected' : ''; ?>>Preloved</option>
                    <option value="service" <?php echo $category == 'service' ? 'selected' : ''; ?>>Jasa</option>
                    <option value="urgent" <?php echo $category == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
                
                <input type="number" name="min_price" placeholder="Harga Min" 
                       value="<?php echo htmlspecialchars($min_price); ?>" class="filter-input">
                <input type="number" name="max_price" placeholder="Harga Max" 
                       value="<?php echo htmlspecialchars($max_price); ?>" class="filter-input">
                
                <input type="text" name="location" placeholder="Lokasi" 
                       value="<?php echo htmlspecialchars($location); ?>" class="filter-input">
                
                <select name="sort" class="filter-select">
                    <option value="relevance" <?php echo $sort == 'relevance' ? 'selected' : ''; ?>>Relevansi</option>
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Harga Terendah</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Harga Tertinggi</option>
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Rating Tertinggi</option>
                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Paling Populer</option>
                </select>
                
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Terapkan
                </button>
            </div>
        </form>
    </div>
    
    <!-- Search Suggestions -->
    <?php if(!empty($suggestions)): ?>
    <div class="search-suggestions">
        <h4>Pencarian terkait:</h4>
        <div class="suggestions-list">
            <?php foreach($suggestions as $suggestion): ?>
            <a href="?q=<?php echo urlencode($suggestion); ?>&category=<?php echo $category; ?>" class="suggestion-tag">
                <?php echo htmlspecialchars($suggestion); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Results Summary -->
    <div class="results-summary">
        <p>Ditemukan <?php echo $total_results; ?> hasil 
        <?php if($page > 1): ?>
        (halaman <?php echo $page; ?>)
        <?php endif; ?>
        </p>
    </div>
    
    <!-- Search Results -->
    <?php if(empty($results)): ?>
    <div class="empty-state">
        <i class="fas fa-search"></i>
        <h3>Tidak ditemukan hasil</h3>
        <p>Coba dengan kata kunci yang berbeda atau hapus beberapa filter</p>
        <a href="../index.php" class="btn btn-primary">Kembali ke Beranda</a>
    </div>
    <?php else: ?>
    <div class="search-results">
        <div class="product-grid">
            <?php foreach($results as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="<?php echo UPLOAD_PATH . $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <?php if($product['discounted_price']): ?>
                    <div class="badge badge-discount">
                        -<?php echo round((($product['price'] - $product['discounted_price']) / $product['price']) * 100); ?>%
                    </div>
                    <?php endif; ?>
                    <?php if(isLoggedIn()): ?>
                    <button class="product-wishlist <?php echo isProductFavorited($product['product_id'], $_SESSION['user_id']) ? 'active' : ''; ?>" 
                            onclick="toggleFavorite(<?php echo $product['product_id']; ?>)">
                        <i class="fas fa-heart"></i>
                    </button>
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
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary btn-block mt-2">Lihat Detail</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_results > $limit): ?>
        <div class="pagination">
            <?php
            $total_pages = ceil($total_results / $limit);
            $query_params = $_GET;
            unset($query_params['page']);
            $query_string = http_build_query($query_params);
            ?>
            
            <?php if($page > 1): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="pagination-item">
                <i class="fas fa-chevron-left"></i> Sebelumnya
            </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);
            
            for($i = $start_page; $i <= $end_page; $i++):
            ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>" 
               class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="pagination-item">
                Berikutnya <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
// Search suggestions
const searchInput = document.querySelector('.search-input-large');
const suggestionsContainer = document.querySelector('.search-suggestions');

searchInput?.addEventListener('input', function() {
    const query = this.value.trim();
    
    if(query.length < 2) {
        suggestionsContainer?.style.display = 'none';
        return;
    }
    
    fetch(`includes/search-suggestions.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(suggestions => {
            if(suggestions.length > 0) {
                const suggestionsHTML = suggestions.map(suggestion => 
                    `<a href="?q=${encodeURIComponent(suggestion)}" class="suggestion-tag">${suggestion}</a>`
                ).join('');
                
                suggestionsContainer.innerHTML = `
                    <h4>Pencarian terkait:</h4>
                    <div class="suggestions-list">
                        ${suggestionsHTML}
                    </div>
                `;
                suggestionsContainer.style.display = 'block';
            } else {
                suggestionsContainer.style.display = 'none';
            }
        });
});

// Auto-submit on filter change (optional)
document.querySelectorAll('.filter-select, .filter-input').forEach(input => {
    input.addEventListener('change', function() {
        if(this.value) {
            this.closest('form').submit();
        }
    });
});
</script>

<style>
.search-container {
    margin-bottom: 30px;
}

.search-form-large {
    background: var(--bg-card);
    padding: 20px;
    border-radius: 10px;
    box-shadow: var(--shadow-sm);
}

.search-input-group {
    display: flex;
    margin-bottom: 15px;
}

.search-input-large {
    flex: 1;
    padding: 12px 20px;
    border: 2px solid var(--border-color);
    border-right: none;
    border-radius: 8px 0 0 8px;
    font-size: 16px;
}

.search-btn-large {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0 30px;
    border-radius: 0 8px 8px 0;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-select, .filter-input {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
}

.filter-input {
    width: 120px;
}

.btn-filter {
    background: var(--secondary-color);
    color: var(--primary-color);
    border: none;
    padding: 8px 15px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.search-suggestions {
    margin-bottom: 20px;
    padding: 15px;
    background: var(--bg-light);
    border-radius: 8px;
}

.search-suggestions h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: var(--text-secondary);
}

.suggestions-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.suggestion-tag {
    background: var(--bg-card);
    color: var(--text-primary);
    padding: 6px 12px;
    border-radius: 15px;
    text-decoration: none;
    font-size: 13px;
    border: 1px solid var(--border-color);
    transition: all 0.2s;
}

.suggestion-tag:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.results-summary {
    margin-bottom: 20px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.results-summary p {
    margin: 0;
    color: var(--text-secondary);
}
</style>

<?php include '../footer.php'; ?>