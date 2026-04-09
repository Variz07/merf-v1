<?php
require_once '../config.php';

$page_title = 'Jasa & Layanan';
$page_scripts = ['../assets/js/service.js'];

// Get filter parameters
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;

// Build query untuk semua jasa
$sql = "SELECT p.*, u.full_name, u.profile_pic, u.phone as seller_phone, u.rating as seller_rating,
        (SELECT COUNT(*) FROM orders WHERE product_id = p.product_id AND status = 'delivered') as terjual
        FROM products p 
        JOIN users u ON p.seller_id = u.user_id 
        WHERE p.category = 'service' AND p.is_available = 1 AND u.status = 'active'";
$params = [];

if($category) {
    $sql .= " AND p.subcategory = ?";
    $params[] = $category;
}

if($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM products p 
              JOIN users u ON p.seller_id = u.user_id 
              WHERE p.category = 'service' AND p.is_available = 1 AND u.status = 'active'";
$count_params = [];

if($category) {
    $count_sql .= " AND p.subcategory = ?";
    $count_params[] = $category;
}

if($search) {
    $count_sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR u.full_name LIKE ?)";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
    $count_params[] = "%$search%";
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($count_params);
$total_services = $stmt->fetchColumn();

// Add sorting
switch($sort) {
    case 'price_low':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.avg_rating DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY terjual DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

// Add pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = ($page - 1) * $limit;

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll();

// Get all service categories untuk filter
$categories = [
    'jastip' => ['name' => 'Jastip Food', 'icon' => 'fa-shopping-bag', 'color' => '#FFE5B4'],
    'courses' => ['name' => 'Courses', 'icon' => 'fa-graduation-cap', 'color' => '#E3F2FD'],
    'repair' => ['name' => 'Repair', 'icon' => 'fa-tools', 'color' => '#FFE0B2'],
    'beauty' => ['name' => 'Beauty', 'icon' => 'fa-spa', 'color' => '#FCE4EC'],
    'cleaning' => ['name' => 'Cleaning', 'icon' => 'fa-broom', 'color' => '#E8F5E9'],
    'design' => ['name' => 'Design', 'icon' => 'fa-paint-brush', 'color' => '#E1F5FE'],
    'laundry' => ['name' => 'Laundry', 'icon' => 'fa-tshirt', 'color' => '#FFF3E0'],
    'photography' => ['name' => 'Photography', 'icon' => 'fa-camera', 'color' => '#F3E5F5'],
    'tutoring' => ['name' => 'Tutoring', 'icon' => 'fa-chalkboard-teacher', 'color' => '#E8EAF6'],
    'other' => ['name' => 'Other', 'icon' => 'fa-hands-helping', 'color' => '#F5F5F5']
];

include '../header.php';
?>

<div class="service-page">
    <!-- HERO SECTION -->
    <div class="service-hero">
        <div class="container">
            <h1 class="service-hero-title">Services & Skills</h1>
            <p class="service-hero-subtitle">Discover various services and skills from trusted students</p>
            
            <!-- SEARCH BAR -->
            <div class="service-search">
                <form method="GET" class="search-form">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Search services, courses, personal shopping..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- CATEGORY CHIPS -->
        <div class="category-chips">
            <a href="service.php" class="chip <?php echo empty($category) ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>All Services</span>
            </a>
            <?php foreach($categories as $key => $cat): ?>
            <a href="?category=<?php echo $key; ?>" class="chip <?php echo $category == $key ? 'active' : ''; ?>" style="<?php echo $category == $key ? 'background: ' . $cat['color'] . '; border-color: ' . $cat['color'] . ';' : ''; ?>">
                <i class="fas <?php echo $cat['icon']; ?>"></i>
                <span><?php echo $cat['name']; ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- UPLOAD CARD FOR SELLERS -->
        <?php if(isLoggedIn()): ?>
        <div class="upload-prompt-card">
            <div class="upload-prompt-content">
                <div class="upload-icon">
                    <i class="fas fa-store"></i>
                </div>
                <div class="upload-text">
                    <h3>Sell Your Service or Skill!</h3>
                    <p>Share your expertise and earn extra income</p>
                </div>
                <a href="upload-service.php" class="btn-upload-service">
                    <i class="fas fa-plus-circle"></i>
                    Upload Service
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- SORT BAR -->
        <div class="sort-bar">
            <div class="result-count">
                <p>Showing <strong><?php echo count($services); ?></strong> of <strong><?php echo $total_services; ?></strong> services</p>
            </div>
            <div class="sort-options">
                <select onchange="window.location.href='?sort='+this.value<?php echo $category ? '+\'&category='.$category.'\'' : ''; ?><?php echo $search ? '+\'&search='.urlencode($search).'\'' : ''; ?>">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                    <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Highest Rating</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Lowest Price</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Highest Price</option>
                </select>
            </div>
        </div>

        <!-- SERVICES GRID -->
        <?php if(empty($services)): ?>
        <div class="empty-state">
            <i class="fas fa-hands-helping"></i>
            <h3>No services available yet</h3>
            <p>Be the first to offer a service!</p>
            <?php if(isLoggedIn()): ?>
            <a href="upload-service.php" class="btn-empty">
                <i class="fas fa-plus-circle"></i>
                Upload Service Now
            </a>
            <?php else: ?>
            <a href="../auth/signin.php" class="btn-empty">
                Login to Upload Service
            </a>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="services-grid">
            <?php foreach($services as $service): ?>
            <?php 
            // Format nomor WhatsApp
            $phone = $service['seller_phone'] ?? '';
            $wa_number = preg_replace('/[^0-9]/', '', $phone);
            if(substr($wa_number, 0, 1) == '0') {
                $wa_number = '62' . substr($wa_number, 1);
            }
            if(substr($wa_number, 0, 2) != '62' && strlen($wa_number) > 0) {
                $wa_number = '62' . $wa_number;
            }
            
            // Template pesan WhatsApp
            $message = "Halo%20" . urlencode($service['full_name']) . "%2C%0A%0A";
            $message .= "Saya%20tertarik%20dengan%20jasa%20*" . urlencode($service['name']) . "*%20yang%20Anda%20tawarkan%20di%20MERF%20Marketplace.%0A%0A";
            
            if($service['subcategory'] == 'jastip') {
                $message .= "Detail%20Jastip%3A%0A";
                $message .= "-%20Produk%3A%20" . urlencode($service['description']) . "%0A";
                $message .= "-%20Harga%3A%20" . urlencode(formatCurrency($service['price'])) . "%0A";
                $message .= "-%20Lokasi%3A%20" . urlencode($service['location']) . "%0A%0A";
            } else {
                $message .= "Detail%20Jasa%3A%0A";
                $message .= "-%20" . urlencode(substr($service['description'], 0, 100)) . "%0A";
                $message .= "-%20Harga%3A%20" . urlencode(formatCurrency($service['price'])) . "%0A%0A";
            }
            
            $message .= "Apakah%20masih%20tersedia%3F";
            
            $wa_link = $wa_number ? "https://wa.me/$wa_number?text=$message" : '#';
            ?>
            
            <div class="service-card service-<?php echo $service['subcategory'] ?? 'other'; ?>">
                <!-- CATEGORY BADGE -->
                <div class="service-category">
                    <?php 
                    $cat_key = $service['subcategory'] ?? 'other';
                    $cat_name = $categories[$cat_key]['name'] ?? ucfirst($cat_key);
                    $cat_icon = $categories[$cat_key]['icon'] ?? 'fa-hands-helping';
                    ?>
                    <i class="fas <?php echo $cat_icon; ?>"></i>
                    <span><?php echo $cat_name; ?></span>
                </div>
                
                <!-- SELLER INFO -->
                <div class="service-seller">
                    <div class="seller-avatar">
                        <img src="<?php echo getUserAvatar($service['seller_id']); ?>" alt="<?php echo htmlspecialchars($service['full_name']); ?>">
                    </div>
                    <div class="seller-info">
                        <h4 class="seller-name"><?php echo htmlspecialchars($service['full_name']); ?></h4>
                        <div class="seller-rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format($service['seller_rating'] ?? 0, 1); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- SERVICE CONTENT -->
                <div class="service-content">
                    <h3 class="service-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                    
                    <?php if($service['subcategory'] == 'jastip'): ?>
                    <div class="jastip-details">
                        <?php 
                        // Parse deskripsi untuk Jastip
                        $desc_lines = explode("\n", $service['description']);
                        ?>
                        <?php foreach($desc_lines as $line): ?>
                            <?php if(trim($line)): ?>
                            <div class="detail-item">
                                <?php 
                                if(strpos($line, 'Close PO') !== false) echo '<i class="fas fa-clock"></i>';
                                elseif(strpos($line, 'Pengantaran') !== false) echo '<i class="fas fa-truck"></i>';
                                elseif(strpos($line, 'FREE') !== false) echo '<i class="fas fa-gift"></i>';
                                elseif(strpos($line, 'SBH') !== false) echo '<i class="fas fa-map-marker-alt"></i>';
                                elseif(strpos($line, 'reg:') !== false) echo '<i class="fas fa-tag"></i>';
                                else echo '<i class="fas fa-info-circle"></i>';
                                ?>
                                <span><?php echo htmlspecialchars($line); ?></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if($service['delivery_fee'] > 0): ?>
                        <div class="detail-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Ongkir: <?php echo formatCurrency($service['delivery_fee']); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if(strpos(strtolower($service['description']), 'free ongkir') !== false): ?>
                        <div class="free-ongkir-badge">
                            <i class="fas fa-check-circle"></i> FREE ONGKIR!
                        </div>
                        <?php endif; ?>
                        
                        <?php if(strpos(strtolower($service['description']), 'qris') !== false || strpos(strtolower($service['description']), 'tf') !== false): ?>
                        <div class="payment-badge">
                            <i class="fas fa-mobile-alt"></i> Bisa QRIS / TF
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <p class="service-description">
                        <?php echo nl2br(htmlspecialchars(substr($service['description'], 0, 150))); ?>...
                    </p>
                    <?php endif; ?>
                    
                    <!-- FEATURES -->
                    <?php if($service['subcategory'] == 'courses'): ?>
                    <div class="service-features">
                        <?php 
                        $features = [
                            'Mentor berpengalaman',
                            'Komunitas belajar',
                            'Modul lengkap',
                            'Sertifikat'
                        ];
                        ?>
                        <?php foreach(array_slice($features, 0, 3) as $feature): ?>
                        <span class="feature-badge">
                            <i class="fas fa-check"></i> <?php echo $feature; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- FOOTER -->
                <div class="service-footer">
                    <div class="price-section">
                        <span class="current-price"><?php echo formatCurrency($service['discounted_price'] ?? $service['price']); ?></span>
                        <?php if($service['discounted_price']): ?>
                        <span class="original-price"><?php echo formatCurrency($service['price']); ?></span>
                        <?php endif; ?>
                        <span class="sold-count">Terjual <?php echo $service['terjual'] ?? 0; ?></span>
                    </div>
                    
                    <?php if($wa_number): ?>
                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-hire">
                        <i class="fab fa-whatsapp"></i> Hire
                    </a>
                    <?php else: ?>
                    <button class="btn-hire disabled" disabled>
                        <i class="fab fa-whatsapp"></i> No Contact
                    </button>
                    <?php endif; ?>
                </div>
                
                <?php if($service['seller_rating'] >= 4.5): ?>
                <div class="premium-badge">
                    <i class="fas fa-crown"></i> Premium Seller
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- PAGINATION -->
        <?php if($total_services > $limit): ?>
        <div class="pagination">
            <?php
            $total_pages = ceil($total_services / $limit);
            $query_params = $_GET;
            unset($query_params['page']);
            $query_string = http_build_query($query_params);
            ?>
            
            <?php if($page > 1): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="pagination-prev">
                <i class="fas fa-chevron-left"></i> Sebelumnya
            </a>
            <?php endif; ?>
            
            <div class="pagination-numbers">
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $start_page + 4);
                
                for($i = $start_page; $i <= $end_page; $i++):
                ?>
                <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>" class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            
            <?php if($page < $total_pages): ?>
            <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="pagination-next">
                Berikutnya <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* ===== SERVICE PAGE - FULL INTEGRATION ===== */
.service-page {
    background: #FFFEFC;
    min-height: 100vh;
}

/* HERO SECTION */
.service-hero {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    padding: 60px 0 80px;
    margin-bottom: 40px;
    position: relative;
    color: white;
}

.service-hero::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 60px;
    background: linear-gradient(to right bottom, transparent 50%, #FFFEFC 50%);
}

.service-hero-title {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 15px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.service-hero-subtitle {
    font-size: 20px;
    opacity: 0.95;
    margin-bottom: 35px;
}

/* SEARCH BAR */
.service-search {
    max-width: 700px;
    margin-top: 30px;
}

.service-search .search-form {
    position: relative;
    display: flex;
    align-items: center;
    background: white;
    border-radius: 60px;
    padding: 5px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.service-search .search-icon {
    position: absolute;
    left: 25px;
    color: #999;
    font-size: 18px;
}

.service-search .search-input {
    flex: 1;
    padding: 16px 25px 16px 55px;
    border: none;
    border-radius: 60px;
    font-size: 16px;
    background: transparent;
}

.service-search .search-input:focus {
    outline: none;
}

.service-search .search-btn {
    background: #4C3C27;
    color: white;
    border: none;
    padding: 14px 35px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    margin-right: 5px;
}

.service-search .search-btn:hover {
    background: #2C2416;
    transform: translateY(-2px);
}

/* CATEGORY CHIPS */
.category-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 30px;
}

.chip {
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

.chip i {
    color: #6D6D6D;
}

.chip:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
}

.chip.active {
    background: #4C3C27;
    border-color: #4C3C27;
    color: white;
}

.chip.active i {
    color: white;
}

/* UPLOAD PROMPT CARD */
.upload-prompt-card {
    background: linear-gradient(105deg, #FFF9E6, #FFF3CD);
    border-radius: 20px;
    padding: 25px 30px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #FFD700;
}

.upload-prompt-content {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 1;
}

.upload-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-icon i {
    font-size: 28px;
    color: white;
}

.upload-text h3 {
    font-size: 18px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 5px;
}

.upload-text p {
    color: #6D6D6D;
    font-size: 14px;
}

.btn-upload-service {
    background: #4C3C27;
    color: white;
    padding: 14px 28px;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
    white-space: nowrap;
}

.btn-upload-service:hover {
    background: #2C2416;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(76,60,39,0.2);
}

/* SORT BAR */
.sort-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 15px 20px;
    background: white;
    border-radius: 12px;
    border: 1px solid #E8E3D9;
}

.result-count p {
    color: #6D6D6D;
    font-size: 14px;
}

.result-count strong {
    color: #4C3C27;
}

.sort-options select {
    padding: 10px 30px 10px 16px;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    font-size: 14px;
    color: #2C2416;
    background: white;
    cursor: pointer;
}

/* SERVICES GRID */
.services-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 25px;
    margin-bottom: 40px;
}

/* SERVICE CARD */
.service-card {
    background: white;
    border-radius: 20px;
    border: 1px solid #E8E3D9;
    overflow: hidden;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
    position: relative;
}

.service-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 15px 35px rgba(76,60,39,0.1);
    border-color: #C9B59C;
}

/* CATEGORY BADGE */
.service-category {
    background: #F0EDE5;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid #E8E3D9;
}

.service-category i {
    font-size: 16px;
    color: #4C3C27;
}

.service-category span {
    font-weight: 600;
    font-size: 13px;
    color: #2C2416;
}

/* SELLER INFO */
.service-seller {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid #F0EDE5;
}

.seller-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #C9B59C;
}

.seller-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.seller-info {
    flex: 1;
}

.seller-name {
    font-size: 15px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 4px;
}

.seller-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #FFC107;
    font-size: 13px;
}

.seller-rating span {
    color: #6D6D6D;
}

/* SERVICE CONTENT */
.service-content {
    padding: 16px;
    flex: 1;
}

.service-title {
    font-size: 18px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 12px;
    line-height: 1.4;
}

.service-description {
    color: #5C5C5C;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 12px;
}

/* JASTIP SPECIFIC */
.jastip-details {
    background: #F9F7F4;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 12px;
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 8px;
    font-size: 13px;
    color: #2C2416;
}

.detail-item i {
    color: #4C3C27;
    min-width: 18px;
    margin-top: 2px;
}

.free-ongkir-badge {
    display: inline-block;
    background: #28A745;
    color: white;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 8px;
}

.payment-badge {
    display: inline-block;
    background: #4C3C27;
    color: white;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    margin-top: 8px;
    margin-left: 8px;
}

/* SERVICE FEATURES */
.service-features {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.feature-badge {
    background: #E8F5E9;
    color: #2E7D32;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* FOOTER */
.service-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-top: 1px solid #F0EDE5;
    background: #F9F7F4;
}

.price-section {
    display: flex;
    flex-direction: column;
}

.current-price {
    font-size: 18px;
    font-weight: 700;
    color: #4C3C27;
}

.original-price {
    font-size: 12px;
    color: #999;
    text-decoration: line-through;
}

.sold-count {
    font-size: 11px;
    color: #28A745;
    margin-top: 2px;
}

/* HIRE BUTTON */
.btn-hire {
    background: #25D366;
    color: white;
    padding: 10px 20px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
}

.btn-hire:hover {
    background: #128C7E;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(37,211,102,0.3);
}

.btn-hire.disabled {
    background: #ccc;
    cursor: not-allowed;
    opacity: 0.7;
}

/* PREMIUM BADGE */
.premium-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    color: #2C2416;
    padding: 6px 14px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 10px rgba(255,215,0,0.3);
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 20px;
    border: 1px solid #E8E3D9;
}

.empty-state i {
    font-size: 64px;
    color: #C9B59C;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #2C2416;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6D6D6D;
    margin-bottom: 30px;
}

.btn-empty {
    display: inline-block;
    background: #4C3C27;
    color: white;
    padding: 14px 32px;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.btn-empty:hover {
    background: #2C2416;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(76,60,39,0.2);
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 50px;
    margin-bottom: 40px;
}

.pagination-prev,
.pagination-next {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.pagination-prev:hover,
.pagination-next:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
}

.pagination-numbers {
    display: flex;
    gap: 8px;
}

.page-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.page-number:hover {
    background: #F5F3EE;
}

.page-number.active {
    background: #4C3C27;
    color: white;
}

/* RESPONSIVE */
@media (max-width: 1200px) {
    .services-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .service-hero-title {
        font-size: 40px;
    }
    
    .upload-prompt-card {
        flex-direction: column;
        text-align: center;
    }
    
    .upload-prompt-content {
        flex-direction: column;
        text-align: center;
        margin-bottom: 15px;
    }
}

@media (max-width: 768px) {
    .services-grid {
        grid-template-columns: 1fr;
    }
    
    .service-hero {
        padding: 40px 0 60px;
    }
    
    .service-hero-title {
        font-size: 32px;
    }
    
    .service-hero-subtitle {
        font-size: 16px;
    }
    
    .service-search .search-form {
        flex-direction: column;
        background: transparent;
        padding: 0;
        gap: 15px;
    }
    
    .service-search .search-input {
        background: white;
        border-radius: 50px;
        padding: 14px 20px 14px 50px;
    }
    
    .service-search .search-btn {
        width: 100%;
        margin-right: 0;
    }
    
    .sort-bar {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .sort-options select {
        width: 100%;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .category-chips {
        gap: 8px;
    }
    
    .chip {
        padding: 8px 16px;
        font-size: 13px;
    }
    
    .service-footer {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .btn-hire {
        width: 100%;
        justify-content: center;
    }
}

/* SERVICE CARD COLORS */
.service-jastip .service-category {
    background: #FFE5B4;
}

.service-courses .service-category {
    background: #E3F2FD;
}

.service-repair .service-category {
    background: #FFE0B2;
}

.service-beauty .service-category {
    background: #FCE4EC;
}

.service-cleaning .service-category {
    background: #E8F5E9;
}

.service-design .service-category {
    background: #E1F5FE;
}

.service-laundry .service-category {
    background: #FFF3E0;
}

.service-photography .service-category {
    background: #F3E5F5;
}

.service-tutoring .service-category {
    background: #E8EAF6;
}
</style>

<?php include '../footer.php'; ?>