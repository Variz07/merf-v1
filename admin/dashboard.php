<?php
ob_start();
session_start();

require_once dirname(__DIR__) . '/config.php';

// Check if user is admin
if(!isLoggedIn() || $_SESSION['user_role'] !== 'admin'){
    header('Location: ../index.php');
    exit();
}

$page_title = 'Admin Dashboard';

// Get statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn(),
    'pending_orders' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'reported_items' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'today_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'active_sellers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller' AND status = 'active'")->fetchColumn()
];

// Get recent activities
$recent_activities = [];
$stmt = $pdo->query("
    (SELECT 'user' as type, full_name as title, created_at as date FROM users ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'product' as type, name as title, created_at as date FROM products ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'order' as type, CONCAT('Order #', order_code) as title, created_at as date FROM orders ORDER BY created_at DESC LIMIT 5)
    UNION
    (SELECT 'report' as type, CONCAT('Report #', report_id) as title, created_at as date FROM reports ORDER BY created_at DESC LIMIT 5)
    ORDER BY date DESC LIMIT 15
");
$recent_activities = $stmt->fetchAll();

// Get chart data (last 30 days)
$chart_data = [];
for($i = 30; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $chart_data[] = [
        'date' => $date,
        'orders' => $stmt->fetchColumn()
    ];
}

// Get user distribution
$customer_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$seller_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
$admin_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

// Include header admin khusus
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MERF Marketplace</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <?php include 'admin-sidebar.php'; ?>
        
        <!-- Admin Content -->
        <div class="admin-content">
            <!-- Page Header -->
            <div class="admin-header">
                <h1>Dashboard</h1>
                <div class="admin-actions">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <a href="../auth/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="admin-stats-grid">
                <div class="admin-stat-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Total Pengguna</p>
                        <small>+<?php echo $stats['today_users']; ?> hari ini</small>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_products']); ?></h3>
                        <p>Total Produk</p>
                        <small><?php echo $stats['active_sellers']; ?> seller aktif</small>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon orders">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_orders']); ?></h3>
                        <p>Total Pesanan</p>
                        <small><?php echo $stats['pending_orders']; ?> pending</small>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon revenue">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Rp <?php echo number_format($stats['total_revenue']); ?></h3>
                        <p>Total Pendapatan</p>
                        <small>Dari transaksi berhasil</small>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="stat-icon reports">
                        <i class="fas fa-flag"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['reported_items']); ?></h3>
                        <p>Laporan Baru</p>
                        <small>Perlu ditinjau</small>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="admin-charts-section">
                <div class="admin-chart-card">
                    <h3><i class="fas fa-chart-line" style="color: #4C3C27; margin-right: 10px;"></i> Statistik Pesanan (30 Hari Terakhir)</h3>
                    <canvas id="ordersChart" height="100"></canvas>
                </div>
                
                <div class="admin-chart-card">
                    <h3><i class="fas fa-chart-pie" style="color: #4C3C27; margin-right: 10px;"></i> Distribusi Pengguna</h3>
                    <canvas id="usersChart" height="100"></canvas>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 25px;">
                <!-- Recent Activities -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-history" style="color: #4C3C27;"></i> Aktivitas Terbaru</h3>
                        <a href="logs.php" class="btn btn-outline btn-sm">Lihat Semua</a>
                    </div>
                    <div class="admin-card-body">
                        <div class="activities-list">
                            <?php foreach($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php switch($activity['type']):
                                        case 'user': echo '<i class="fas fa-user-plus" style="color: #28A745;"></i>'; break;
                                        case 'product': echo '<i class="fas fa-box" style="color: #4C3C27;"></i>'; break;
                                        case 'order': echo '<i class="fas fa-shopping-bag" style="color: #17A2B8;"></i>'; break;
                                        case 'report': echo '<i class="fas fa-flag" style="color: #FFC107;"></i>'; break;
                                    endswitch; ?>
                                </div>
                                <div class="activity-content">
                                    <p><strong><?php echo htmlspecialchars($activity['title']); ?></strong> telah ditambahkan</p>
                                    <small><?php echo timeAgo($activity['date']); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h3><i class="fas fa-bolt" style="color: #4C3C27;"></i> Quick Actions</h3>
                    </div>
                    <div class="admin-card-body">
                        <div class="quick-actions">
                            <a href="users.php?action=add" class="quick-action">
                                <i class="fas fa-user-plus"></i>
                                <span>Tambah Pengguna</span>
                            </a>
                            <a href="products.php?action=verify" class="quick-action">
                                <i class="fas fa-check-circle"></i>
                                <span>Verifikasi Produk</span>
                            </a>
                            <a href="reports.php" class="quick-action">
                                <i class="fas fa-flag"></i>
                                <span>Tinjau Laporan</span>
                            </a>
                            <a href="settings.php" class="quick-action">
                                <i class="fas fa-cog"></i>
                                <span>Pengaturan Site</span>
                            </a>
                            <a href="../pages/upload-product.php" class="quick-action">
                                <i class="fas fa-plus"></i>
                                <span>Tambah Produk</span>
                            </a>
                            <a href="../pages/blog.php?action=create" class="quick-action">
                                <i class="fas fa-blog"></i>
                                <span>Tulis Blog</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Info -->
            <div class="admin-card" style="margin-top: 25px;">
                <div class="admin-card-header">
                    <h3><i class="fas fa-info-circle" style="color: #4C3C27;"></i> Informasi Sistem</h3>
                </div>
                <div class="admin-card-body">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                        <div>
                            <small style="color: #6D6D6D; display: block; margin-bottom: 5px;">PHP Version</small>
                            <strong style="font-size: 18px;"><?php echo phpversion(); ?></strong>
                        </div>
                        <div>
                            <small style="color: #6D6D6D; display: block; margin-bottom: 5px;">Database</small>
                            <strong style="font-size: 18px;">MySQL</strong>
                        </div>
                        <div>
                            <small style="color: #6D6D6D; display: block; margin-bottom: 5px;">Server</small>
                            <strong style="font-size: 18px;"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Apache'; ?></strong>
                        </div>
                        <div>
                            <small style="color: #6D6D6D; display: block; margin-bottom: 5px;">Last Login</small>
                            <strong style="font-size: 14px;"><?php echo date('d M Y H:i'); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ordersCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($chart_data, 'date')); ?>,
            datasets: [{
                label: 'Pesanan',
                data: <?php echo json_encode(array_column($chart_data, 'orders')); ?>,
                borderColor: '#4C3C27',
                backgroundColor: 'rgba(76, 60, 39, 0.05)',
                borderWidth: 3,
                pointBackgroundColor: '#4C3C27',
                pointBorderColor: 'white',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1E1F2A',
                    titleColor: 'white',
                    bodyColor: 'rgba(255,255,255,0.8)',
                    padding: 12,
                    cornerRadius: 8
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0,0,0,0.03)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Users Chart
    const usersCtx = document.getElementById('usersChart').getContext('2d');
    const usersChart = new Chart(usersCtx, {
        type: 'doughnut',
        data: {
            labels: ['Customer', 'Seller', 'Admin'],
            datasets: [{
                data: [
                    <?php echo $customer_count; ?>,
                    <?php echo $seller_count; ?>,
                    <?php echo $admin_count; ?>
                ],
                backgroundColor: [
                    '#C9B59C',
                    '#4C3C27',
                    '#300C0C'
                ],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 12,
                            weight: 500
                        },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => ({
                                    text: `${label}: ${data.datasets[0].data[i]}`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                }));
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>