<?php
// ============================================================
//  pages/seller-dashboard.php
//  Seller Dashboard for MERFV Marketplace
//
//  HOW THIS FILE WORKS:
//  1. Load site config (database, SITE_URL, etc.)
//  2. Load our sample data from data.php
//  3. Include header.php (adds the top navbar)
//  4. Print the HTML dashboard
//  5. Include footer.php at the bottom
// ============================================================

// ── Step 1: Load site config ─────────────────────────────────
require_once '../config.php';

// ── Step 2: Set page title (header.php reads this variable) ──
$page_title = 'Seller Dashboard';

// ── Step 3: Load dashboard data ──────────────────────────────
//  Change the path below if your data.php is somewhere else.
//  For now it sits in the same pages/ folder.
require_once 'seller-dashboard-data.php';

// ── Step 4: Only sellers/admins may view this page ───────────
//  Remove or adjust this block if you handle auth differently.
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/signin.php');
    exit;
}
$allowed_roles = ['seller', 'admin'];
if (!in_array($_SESSION['user_role'] ?? '', $allowed_roles)) {
    header('Location: ' . SITE_URL . 'index.php');
    exit;
}

// ── Step 5: Read active category filter from URL ─────────────
//  When user clicks a filter button, the page reloads with
//  ?cat=food (or preloved, service, urgent, All)
$active_cat = $_GET['cat'] ?? 'All';
$valid_cats = ["All", "food", "preloved", "service", "urgent"];
if (!in_array($active_cat, $valid_cats)) $active_cat = 'All'; // safety check

// Filter products and orders based on selected category
$filtered_products = ($active_cat === 'All')
    ? $top_products
    : array_filter($top_products, fn($p) => $p['category'] === $active_cat);

$filtered_orders = ($active_cat === 'All')
    ? $recent_orders
    : array_filter($recent_orders, fn($o) => $o['category'] === $active_cat);

// ── Step 6: Include the site header (navbar) ─────────────────
include '../header.php';
?>

<!-- Load dashboard CSS -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/seller-dashboard.css">

<!-- Chart.js (free library to draw bar/donut charts) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>


<!-- ══════════════════════════════════════════════════════════
     DASHBOARD PAGE
     ══════════════════════════════════════════════════════════ -->
<div class="dash-page">
<div class="dash-container">


    <!-- ── Page Heading ──────────────────────────────────── -->
    <div class="dash-header">
        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Seller'); ?>! 👋</h1>
            <p><?php echo date('l, j F Y'); ?> &nbsp;·&nbsp; Seller Dashboard</p>
        </div>
        <a href="<?php echo SITE_URL; ?>pages/upload-product.php" class="dash-add-btn">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>


    <!-- ── Category Filter Tabs ──────────────────────────── -->
    <!--
        Clicking a tab reloads the page with ?cat=food etc.
        PHP then filters the products and orders tables above.
    -->
    <div class="dash-filters">
        <span>Filter:</span>

        <?php
        // Build base URL without old ?cat= param
        $base_url = strtok($_SERVER['REQUEST_URI'], '?');

        foreach ($valid_cats as $cat):
            // Decide CSS class for active state
            $is_active = ($cat === $active_cat);
            $css_class = 'filter-btn';
            if ($is_active) {
                $css_class .= ($cat === 'All') ? ' active-all' : ' active-cat';
            }

            // Inline style for colored active category buttons
            $inline = '';
            if ($is_active && $cat !== 'All') {
                $s = $category_style[$cat];
                $inline = "style=\"background:{$s['bg']}; color:{$s['text']}; border-color:{$s['dot']};\"";
            }

            // Icon per category
            $icon = '';
            if ($cat !== 'All') {
                $icon = '<i class="fas ' . $category_style[$cat]['icon'] . '"></i> ';
            }

            $link = $base_url . '?cat=' . urlencode($cat);
        ?>
        <a href="<?php echo $link; ?>" class="<?php echo $css_class; ?>" <?php echo $inline; ?>>
            <?php echo $icon . ($cat === 'All' ? 'All Categories' : $cat); ?>
        </a>
        <?php endforeach; ?>
    </div>


    <!-- ── Stat Cards (top row) ──────────────────────────── -->
    <div class="dash-stats">
        <?php foreach ($dash_stats as $card): ?>
        <div class="stat-card-dash" style="--card-light: <?php echo $card['light']; ?>;">
            <!-- Icon box -->
            <div class="stat-icon-box" style="background: <?php echo $card['light']; ?>; color: <?php echo $card['color']; ?>;">
                <i class="fas <?php echo $card['icon']; ?>"></i>
            </div>

            <div class="stat-label-dash"><?php echo $card['label']; ?></div>
            <div class="stat-value-dash"><?php echo $card['value']; ?></div>
            <div class="stat-sub-dash"><?php echo $card['sub']; ?></div>

            <!-- Decorative corner circle -->
            <div style="
                position:absolute; bottom:-18px; right:-18px;
                width:60px; height:60px; border-radius:50%;
                background:<?php echo $card['light']; ?>; opacity:0.7;
            "></div>
        </div>
        <?php endforeach; ?>
    </div>


    <!-- ── Charts Row ────────────────────────────────────── -->
    <div class="dash-charts">

        <!-- Bar chart: daily sales by category -->
        <div class="dash-card">
            <div class="dash-card-title">Daily Sales by Category</div>
            <div class="dash-card-sub">Last 7 days (Rp thousands)</div>

            <!-- Legend -->
            <div class="chart-legend">
                <?php foreach ($category_style as $cat => $s): ?>
                <span class="legend-item">
                    <span class="legend-dot" style="background:<?php echo $s['dot']; ?>;"></span>
                    <?php echo $cat; ?>
                </span>
                <?php endforeach; ?>
            </div>

            <div class="dash-chart-wrap">
                <canvas id="barChart"></canvas>
            </div>
        </div>

        <!-- Donut chart: revenue breakdown -->
        <div class="dash-card">
            <div class="dash-card-title">Revenue Breakdown</div>
            <div class="dash-card-sub">By category this week</div>

            <div class="donut-wrap">
                <canvas id="donutChart"></canvas>
            </div>

            <!-- Small legend boxes below donut -->
            <div class="donut-grid">
                <?php foreach ($category_revenue as $cat => $val): ?>
                <div class="donut-item">
                    <span class="donut-dot" style="background:<?php echo $category_style[$cat]['dot']; ?>;"></span>
                    <div>
                        <div class="donut-item-label"><?php echo $cat; ?></div>
                        <div class="donut-item-pct">
                            <?php echo round($val / $total_revenue_breakdown * 100); ?>%
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- end .dash-charts -->


    <!-- ── Bottom Row: Products + Orders ─────────────────── -->
    <div class="dash-bottom">

        <!-- Top Products -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div>
                    <div class="dash-card-title">Top Products</div>
                    <div class="dash-card-sub">This week</div>
                </div>
                <a href="<?php echo SITE_URL; ?>pages/my-products.php" class="dash-view-all">
                    View All →
                </a>
            </div>

            <?php if (empty($filtered_products)): ?>
                <div class="empty-state">No products in this category.</div>
            <?php else: ?>
                <?php $rank = 1; foreach ($filtered_products as $p): ?>

                <div class="product-row <?php echo $rank === 1 ? 'top-one' : ''; ?>">

                    <!-- Rank number -->
                    <div class="product-rank" style="
                        background: <?php echo $rank === 1 ? '#f97316' : '#e8e3d9'; ?>;
                        color:       <?php echo $rank === 1 ? 'white'   : '#6b7280'; ?>;
                    ">#<?php echo $rank; ?></div>

                    <!-- Name + badge + sold count -->
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="product-meta">
                            <?php
                            // Category badge
                            $s = $category_style[$p['category']];
                            echo '<span class="cat-badge" style="background:'.$s['bg'].'; color:'.$s['text'].';">'
                                . '<i class="fas '.$s['icon'].'"></i> '
                                . htmlspecialchars($p['category'])
                                . '</span>';
                            ?>
                            <span class="product-sold"><?php echo $p['sold']; ?> sold</span>
                        </div>
                    </div>

                    <!-- Revenue + trend -->
                    <div class="product-stats">
                        <div class="product-revenue">
                            Rp <?php echo number_format($p['revenue'], 0, ',', '.'); ?>
                        </div>
                        <div class="product-trend <?php echo $p['trend'] >= 0 ? 'trend-up' : 'trend-down'; ?>">
                            <?php echo $p['trend'] >= 0 ? '↑' : '↓'; ?>
                            <?php echo abs($p['trend']); ?>%
                        </div>
                    </div>

                </div>
                <?php $rank++; endforeach; ?>
            <?php endif; ?>
        </div>


        <!-- Recent Orders -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div>
                    <div class="dash-card-title">Recent Orders</div>
                    <div class="dash-card-sub">Live updates</div>
                </div>
                <a href="<?php echo SITE_URL; ?>pages/my-orders.php" class="dash-view-all">
                    View All →
                </a>
            </div>

            <?php if (empty($filtered_orders)): ?>
                <div class="empty-state">No orders in this category.</div>
            <?php else: ?>
            <div class="orders-table-wrap">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product & Buyer</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($filtered_orders as $order):
                            $ss = $status_style[$order['status']];
                            $cs = $category_style[$order['category']];
                        ?>
                        <tr>
                            <!-- Order ID + time -->
                            <td>
                                <div class="order-id">#<?php echo htmlspecialchars($order['id']); ?></div>
                                <div class="order-time"><?php echo $order['time']; ?></div>
                            </td>

                            <!-- Product name + customer -->
                            <td>
                                <div class="order-product"><?php echo htmlspecialchars($order['product']); ?></div>
                                <div class="order-customer"><?php echo htmlspecialchars($order['customer']); ?></div>
                            </td>

                            <!-- Category badge -->
                            <td>
                                <span class="cat-badge" style="background:<?php echo $cs['bg']; ?>; color:<?php echo $cs['text']; ?>;">
                                    <i class="fas <?php echo $cs['icon']; ?>"></i>
                                    <?php echo htmlspecialchars($order['category']); ?>
                                </span>
                            </td>

                            <!-- Amount -->
                            <td class="order-amount">
                                Rp <?php echo number_format($order['amount'], 0, ',', '.'); ?>
                            </td>

                            <!-- Status badge -->
                            <td>
                                <span class="status-badge"
                                    style="background:<?php echo $ss['bg']; ?>; color:<?php echo $ss['color']; ?>;">
                                    <?php echo $ss['label']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- end .dash-bottom -->

</div><!-- end .dash-container -->
</div><!-- end .dash-page -->


<!-- ══════════════════════════════════════════════════════════
     CHART.JS SETUP
     We pass PHP data into JavaScript using json_encode().
     json_encode() turns a PHP array into a JSON string that
     JavaScript can read directly — no extra work needed!
     ══════════════════════════════════════════════════════════ -->
<script>
// ── Data from PHP ─────────────────────────────────────────────
const chartDays     = <?php echo json_encode($chart_days); ?>;
const chartData     = <?php echo json_encode($chart_data); ?>;
const categoryDots = {
    food:     '#22c55e',
    preloved: '#f59e0b',
    service:  '#a855f7',
    urgent:   '#ef4444'
};

// ── Bar Chart (Daily Sales) ───────────────────────────────────
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: chartDays,
        datasets: Object.keys(chartData).map(cat => ({
            label: cat,
            data: chartData[cat],
            backgroundColor: categoryDots[cat] + 'cc',  // cc = 80% opacity in hex
            borderColor: categoryDots[cat],
            borderWidth: 0,
            borderRadius: 5,
        }))
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }   // we use our own legend above
        },
        scales: {
            x: {
                stacked: true,           // bars stack on top of each other
                grid: { display: false },
                ticks: { font: { family: 'Poppins', size: 11 }, color: '#9ca3af' }
            },
            y: {
                stacked: true,
                grid: { color: '#f1f5f9' },
                ticks: {
                    font: { family: 'Poppins', size: 10 },
                    color: '#9ca3af',
                    callback: val => val >= 1000 ? (val/1000) + 'k' : val
                }
            }
        }
    }
});


// ── Donut Chart (Revenue Breakdown) ──────────────────────────
const donutCtx = document.getElementById('donutChart').getContext('2d');
const revData   = <?php echo json_encode(array_values($category_revenue)); ?>;
const revLabels = <?php echo json_encode(array_keys($category_revenue)); ?>;
const revColors = revLabels.map(l => categoryDots[l]);

new Chart(donutCtx, {
    type: 'doughnut',
    data: {
        labels: revLabels,
        datasets: [{
            data: revData,
            backgroundColor: revColors,
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',       // makes the hole in the middle
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    // Show "Rp 7,520,000" in tooltip instead of raw number
                    label: ctx => ' Rp ' + (ctx.parsed * 1000).toLocaleString('id-ID')
                }
            }
        }
    }
});
</script>

<?php include '../footer.php'; ?>
