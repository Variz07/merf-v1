<?php
require_once '../config.php';

if(!isLoggedIn()) {
    redirect('../auth/signin.php', 'Silakan login terlebih dahulu', 'error');
}

$page_title = 'Pesanan Saya';

// Handle order actions
$action = $_GET['action'] ?? '';
$order_id = $_GET['id'] ?? 0;

if($action == 'cancel' && $order_id) {
    $order = new Order($pdo, $order_id);
    if($order->isBuyer($_SESSION['user_id']) && $order->canCancel($_SESSION['user_id'])) {
        if($order->cancel('Dibatalkan oleh pembeli')) {
            redirect('my-orders.php', 'Pesanan berhasil dibatalkan');
        }
    }
    redirect('my-orders.php', 'Tidak dapat membatalkan pesanan', 'error');
}

if($action == 'complete' && $order_id) {
    $order = new Order($pdo, $order_id);
    if($order->isBuyer($_SESSION['user_id']) && $order->getStatus() == 'shipped') {
        if($order->complete()) {
            redirect('my-orders.php', 'Pesanan berhasil diselesaikan');
        }
    }
    redirect('my-orders.php', 'Tidak dapat menyelesaikan pesanan', 'error');
}

// Get filter
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$sql = "
    SELECT o.*, 
    p.name as product_name, p.image as product_image,
    seller.full_name as seller_name, seller.profile_pic as seller_avatar
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN users seller ON o.seller_id = seller.user_id
    WHERE o.buyer_id = ?
";

$params = [$_SESSION['user_id']];

if($status) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM ($sql) as count_query";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();

// Add ordering and pagination
$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get status counts
$status_counts = [];
$status_stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM orders 
    WHERE buyer_id = ? 
    GROUP BY status
");
$status_stmt->execute([$_SESSION['user_id']]);
$status_counts = $status_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include '../header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Pesanan Saya</h1>
        <p class="page-subtitle">Kelola dan lacak pesanan Anda</p>
    </div>
    
    <!-- Stats -->
    <div class="order-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $status_counts['pending'] ?? 0; ?></h3>
                <p>Pending</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $status_counts['confirmed'] ?? 0; ?></h3>
                <p>Confirmed</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $status_counts['shipped'] ?? 0; ?></h3>
                <p>Shipped</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $status_counts['delivered'] ?? 0; ?></h3>
                <p>Delivered</p>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs -->
    <div class="order-tabs">
        <a href="my-orders.php" class="tab <?php echo empty($status) ? 'active' : ''; ?>">
            Semua
            <span class="badge"><?php echo $total_orders; ?></span>
        </a>
        
        <a href="?status=pending" class="tab <?php echo $status == 'pending' ? 'active' : ''; ?>">
            Pending
            <span class="badge"><?php echo $status_counts['pending'] ?? 0; ?></span>
        </a>
        
        <a href="?status=confirmed" class="tab <?php echo $status == 'confirmed' ? 'active' : ''; ?>">
            Confirmed
            <span class="badge"><?php echo $status_counts['confirmed'] ?? 0; ?></span>
        </a>
        
        <a href="?status=shipped" class="tab <?php echo $status == 'shipped' ? 'active' : ''; ?>">
            Shipped
            <span class="badge"><?php echo $status_counts['shipped'] ?? 0; ?></span>
        </a>
        
        <a href="?status=delivered" class="tab <?php echo $status == 'delivered' ? 'active' : ''; ?>">
            Delivered
            <span class="badge"><?php echo $status_counts['delivered'] ?? 0; ?></span>
        </a>
        
        <a href="?status=cancelled" class="tab <?php echo $status == 'cancelled' ? 'active' : ''; ?>">
            Cancelled
            <span class="badge"><?php echo $status_counts['cancelled'] ?? 0; ?></span>
        </a>
    </div>
    
    <!-- Orders List -->
    <div class="orders-list">
        <?php if(empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-bag"></i>
            <h3>Belum ada pesanan</h3>
            <p>Mulai berbelanja dan lihat pesanan Anda di sini</p>
            <a href="../index.php" class="btn btn-primary">Belanja Sekarang</a>
        </div>
        <?php else: ?>
        <?php foreach($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div class="order-info">
                    <h3>Order #<?php echo $order['order_code']; ?></h3>
                    <div class="order-meta">
                        <span class="order-date"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></span>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php 
                            $status_text = [
                                'pending' => 'Menunggu Konfirmasi',
                                'confirmed' => 'Dikonfirmasi',
                                'processing' => 'Diproses',
                                'shipped' => 'Dikirim',
                                'delivered' => 'Selesai',
                                'cancelled' => 'Dibatalkan'
                            ];
                            echo $status_text[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                        <span class="order-payment">
                            <?php 
                            $payment_text = [
                                'pending' => 'Belum Bayar',
                                'paid' => 'Sudah Bayar',
                                'failed' => 'Gagal'
                            ];
                            echo $payment_text[$order['payment_status']] ?? $order['payment_status'];
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="order-total">
                    <span class="total-label">Total:</span>
                    <span class="total-amount"><?php echo formatCurrency($order['total_price'] + $order['delivery_fee']); ?></span>
                </div>
            </div>
            
            <div class="order-body">
                <div class="order-product">
                    <img src="<?php echo UPLOAD_PATH . $order['product_image']; ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                    <div class="product-info">
                        <h4><?php echo htmlspecialchars($order['product_name']); ?></h4>
                        <p>Quantity: <?php echo $order['quantity']; ?> × <?php echo formatCurrency($order['total_price'] / $order['quantity']); ?></p>
                        <?php if($order['notes']): ?>
                        <p class="order-notes">Catatan: <?php echo htmlspecialchars($order['notes']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="order-seller">
                    <img src="<?php echo getUserAvatar($order['seller_id']); ?>" alt="<?php echo htmlspecialchars($order['seller_name']); ?>">
                    <div class="seller-info">
                        <h4>Seller: <?php echo htmlspecialchars($order['seller_name']); ?></h4>
                        <p>Hubungi seller untuk informasi pengiriman</p>
                    </div>
                    <a href="messages.php?to=<?php echo $order['seller_id']; ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-message"></i> Chat Seller
                    </a>
                </div>
            </div>
            
            <div class="order-footer">
                <div class="order-actions">
                    <?php if($order['status'] == 'pending'): ?>
                    <a href="?id=<?php echo $order['order_id']; ?>&action=cancel" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Yakin ingin membatalkan pesanan ini?')">
                        <i class="fas fa-times"></i> Batalkan
                    </a>
                    <?php endif; ?>
                    
                    <?php if($order['status'] == 'shipped'): ?>
                    <a href="?id=<?php echo $order['order_id']; ?>&action=complete" 
                       class="btn btn-success btn-sm"
                       onclick="return confirm('Konfirmasi pesanan sudah diterima?')">
                        <i class="fas fa-check"></i> Konfirmasi Diterima
                    </a>
                    <?php endif; ?>
                    
                    <?php if($order['status'] == 'delivered'): ?>
                    <a href="product-detail.php?id=<?php echo $order['product_id']; ?>#reviews" 
                       class="btn btn-primary btn-sm">
                        <i class="fas fa-star"></i> Beri Ulasan
                    </a>
                    <?php endif; ?>
                    
                    <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-eye"></i> Detail
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Pagination -->
        <?php if($total_orders > $limit): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-item">
                <i class="fas fa-chevron-left"></i> Sebelumnya
            </a>
            <?php endif; ?>
            
            <?php
            $total_pages = ceil($total_orders / $limit);
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $start_page + 4);
            
            for($i = $start_page; $i <= $end_page; $i++):
            ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
               class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-item">
                Berikutnya <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.order-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: var(--bg-card);
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid var(--border-color);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: var(--secondary-color);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-content h3 {
    font-size: 24px;
    margin: 0 0 5px 0;
    color: var(--text-primary);
}

.stat-content p {
    margin: 0;
    color: var(--text-light);
    font-size: 14px;
}

.order-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    overflow-x: auto;
    padding-bottom: 10px;
}

.tab {
    padding: 10px 20px;
    border-radius: 20px;
    background: var(--bg-card);
    color: var(--text-secondary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--border-color);
    white-space: nowrap;
    transition: all 0.2s;
}

.tab:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.tab.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

.tab:not(.active) .badge {
    background: var(--bg-hover);
    color: var(--text-secondary);
}

.orders-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.order-card {
    background: var(--bg-card);
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--border-color);
}

.order-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.order-info h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.order-meta {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.order-date {
    color: var(--text-light);
    font-size: 14px;
}

.order-status {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #cce5ff; color: #004085; }
.status-processing { background: #d1ecf1; color: #0c5460; }
.status-shipped { background: #d4edda; color: #155724; }
.status-delivered { background: #d1e7dd; color: #0f5132; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.order-payment {
    font-size: 14px;
    color: var(--text-secondary);
}

.order-total {
    text-align: right;
}

.total-label {
    display: block;
    font-size: 12px;
    color: var(--text-light);
    margin-bottom: 5px;
}

.total-amount {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
}

.order-body {
    padding: 20px;
    border-bottom: 1px solid var(--border-light);
}

.order-product {
    display: flex;
    gap: 15px;
    margin-bottom: 20px;
}

.order-product img {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
}

.product-info h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.product-info p {
    margin: 0 0 5px 0;
    color: var(--text-secondary);
    font-size: 14px;
}

.order-notes {
    font-style: italic;
    color: var(--text-light) !important;
}

.order-seller {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid var(--border-light);
}

.order-seller img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.seller-info {
    flex: 1;
}

.seller-info h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.seller-info p {
    margin: 0;
    color: var(--text-light);
    font-size: 13px;
}

.order-footer {
    padding: 15px 20px;
    background: var(--bg-light);
}

.order-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .order-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-total {
        text-align: left;
    }
    
    .order-actions {
        flex-wrap: wrap;
        justify-content: flex-start;
    }
}
</style>

<?php include '../footer.php'; ?>