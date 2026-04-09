<?php
// ============================================================
//  seller-dashboard-data.php
//  Fetches ALL real data from your database for the dashboard.
//
//  HOW IT WORKS:
//  - $pdo already exists from config.php (loaded before this)
//  - We use "prepared statements" with ? placeholders to safely
//    pass the seller's user_id into every query
//  - Every query only pulls data for the LOGGED-IN seller
//  - When a product is added/deleted elsewhere on the site,
//    this file automatically reflects that — no extra work needed
// ============================================================

// The logged-in seller's ID — comes from the session
$seller_id = (int) $_SESSION['user_id'];


// ── STYLE MAPS ────────────────────────────────────────────────
// Your DB category values: food, preloved, service, urgent
// We map them to display names, badge colours, and icons

$category_style = [
    'food'     => ['label' => 'Food',     'bg' => '#dcfce7', 'text' => '#166534', 'dot' => '#22c55e', 'icon' => 'fa-utensils'],
    'preloved' => ['label' => 'Preloved', 'bg' => '#fef3c7', 'text' => '#92400e', 'dot' => '#f59e0b', 'icon' => 'fa-tshirt'],
    'service'  => ['label' => 'Service',  'bg' => '#f3e8ff', 'text' => '#6b21a8', 'dot' => '#a855f7', 'icon' => 'fa-hands-helping'],
    'urgent'   => ['label' => 'Urgent',   'bg' => '#fee2e2', 'text' => '#991b1b', 'dot' => '#ef4444', 'icon' => 'fa-clock'],
];

// Your DB order statuses: pending, confirmed, processing, shipped, delivered, cancelled
$status_style = [
    'pending'    => ['label' => 'Pending',    'color' => '#d97706', 'bg' => '#fffbeb'],
    'confirmed'  => ['label' => 'Confirmed',  'color' => '#2563eb', 'bg' => '#eff6ff'],
    'processing' => ['label' => 'Processing', 'color' => '#7c3aed', 'bg' => '#f5f3ff'],
    'shipped'    => ['label' => 'Shipped',    'color' => '#0891b2', 'bg' => '#ecfeff'],
    'delivered'  => ['label' => 'Delivered',  'color' => '#16a34a', 'bg' => '#f0fdf4'],
    'cancelled'  => ['label' => 'Cancelled',  'color' => '#dc2626', 'bg' => '#fef2f2'],
];


// ── 1. STAT CARDS ─────────────────────────────────────────────

// Total revenue: sum of all delivered orders for this seller
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total_price), 0)
    FROM orders
    WHERE seller_id = ?
      AND status = 'delivered'
");
$stmt->execute([$seller_id]);
$total_revenue = (float) $stmt->fetchColumn();

// Orders today: all statuses, placed today
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM orders
    WHERE seller_id = ?
      AND DATE(created_at) = CURDATE()
");
$stmt->execute([$seller_id]);
$orders_today = (int) $stmt->fetchColumn();

// Active products: is_available = 1 for this seller
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE seller_id = ?
      AND is_available = 1
");
$stmt->execute([$seller_id]);
$active_products = (int) $stmt->fetchColumn();

// Store rating + review count
// rating is stored in the users table and updated by your trigger
$stmt = $pdo->prepare("
    SELECT u.rating,
           COUNT(r.rating_id) AS total_reviews
    FROM users u
    LEFT JOIN products p ON p.seller_id = u.user_id
    LEFT JOIN ratings  r ON r.product_id = p.product_id
    WHERE u.user_id = ?
    GROUP BY u.user_id
");
$stmt->execute([$seller_id]);
$rating_row    = $stmt->fetch();
$store_rating  = $rating_row ? number_format((float) $rating_row['rating'], 1) : '0.0';
$total_reviews = $rating_row ? (int) $rating_row['total_reviews'] : 0;

// Revenue % change: this week vs last week
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 THEN total_price ELSE 0 END) AS this_week,
        SUM(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
                  AND created_at <  DATE_SUB(CURDATE(), INTERVAL 7  DAY)
                 THEN total_price ELSE 0 END) AS last_week
    FROM orders
    WHERE seller_id = ?
      AND status = 'delivered'
");
$stmt->execute([$seller_id]);
$rev_row       = $stmt->fetch();
$rev_this_week = (float)($rev_row['this_week'] ?? 0);
$rev_last_week = (float)($rev_row['last_week'] ?? 0);

if ($rev_last_week > 0) {
    $rev_pct = round((($rev_this_week - $rev_last_week) / $rev_last_week) * 100);
} else {
    $rev_pct = $rev_this_week > 0 ? 100 : 0;
}
$rev_label = ($rev_pct >= 0 ? '↑ ' : '↓ ') . abs($rev_pct) . '% this week';

// Assemble the 4 stat cards
$dash_stats = [
    [
        'label' => 'Total Revenue',
        'value' => 'Rp ' . number_format($total_revenue, 0, ',', '.'),
        'sub'   => $rev_label,
        'icon'  => 'fa-money-bill-wave',
        'color' => '#f97316',
        'light' => '#fff7ed',
    ],
    [
        'label' => 'Orders Today',
        'value' => $orders_today,
        'sub'   => 'As of ' . date('H:i'),
        'icon'  => 'fa-shopping-cart',
        'color' => '#22c55e',
        'light' => '#f0fdf4',
    ],
    [
        'label' => 'Active Products',
        'value' => $active_products,
        'sub'   => 'In your store',
        'icon'  => 'fa-box',
        'color' => '#3b82f6',
        'light' => '#eff6ff',
    ],
    [
        'label' => 'Store Rating',
        'value' => $store_rating . ' ⭐',
        'sub'   => 'From ' . $total_reviews . ' reviews',
        'icon'  => 'fa-star',
        'color' => '#a855f7',
        'light' => '#f3e8ff',
    ],
];


// ── 2. TOP SELLING PRODUCTS ───────────────────────────────────
// Products ranked by quantity sold (delivered orders only)

$stmt = $pdo->prepare("
    SELECT
        p.product_id,
        p.name,
        p.category,
        COALESCE(SUM(o.quantity),    0) AS total_sold,
        COALESCE(SUM(o.total_price), 0) AS total_revenue
    FROM products p
    LEFT JOIN orders o ON  o.product_id = p.product_id
                       AND o.status     = 'delivered'
    WHERE p.seller_id = ?
    GROUP BY p.product_id, p.name, p.category
    ORDER BY total_sold DESC
    LIMIT 6
");
$stmt->execute([$seller_id]);
$products_raw = $stmt->fetchAll();

$top_products = [];
foreach ($products_raw as $row) {

    // Trend: sales this week vs last week for this product
    $t = $pdo->prepare("
        SELECT
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7  DAY)
                     THEN quantity ELSE 0 END) AS this_week,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                      AND created_at <  DATE_SUB(NOW(), INTERVAL 7  DAY)
                     THEN quantity ELSE 0 END) AS last_week
        FROM orders
        WHERE product_id = ?
          AND status = 'delivered'
    ");
    $t->execute([$row['product_id']]);
    $tw_row = $t->fetch();

    $tw = (int)($tw_row['this_week'] ?? 0);
    $lw = (int)($tw_row['last_week'] ?? 0);
    $trend = ($lw > 0) ? round((($tw - $lw) / $lw) * 100) : ($tw > 0 ? 100 : 0);

    $top_products[] = [
        'name'     => $row['name'],
        'category' => $row['category'],
        'sold'     => (int)   $row['total_sold'],
        'revenue'  => (float) $row['total_revenue'],
        'trend'    => $trend,
    ];
}


// ── 3. RECENT ORDERS ─────────────────────────────────────────
// Latest 10 orders for this seller, newest first

$stmt = $pdo->prepare("
    SELECT
        o.order_code,
        o.status,
        o.total_price,
        o.created_at,
        p.name      AS product_name,
        p.category  AS product_category,
        u.full_name AS buyer_name
    FROM orders   o
    JOIN products p ON p.product_id = o.product_id
    JOIN users    u ON u.user_id    = o.buyer_id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute([$seller_id]);
$orders_raw = $stmt->fetchAll();

$recent_orders = [];
foreach ($orders_raw as $row) {

    // Convert timestamp to a friendly "X min/hr/day ago" string
    $diff = time() - strtotime($row['created_at']);
    if      ($diff < 60)    $time_ago = 'Just now';
    elseif  ($diff < 3600)  $time_ago = floor($diff / 60)   . ' min ago';
    elseif  ($diff < 86400) $time_ago = floor($diff / 3600)  . ' hr ago';
    else                    $time_ago = floor($diff / 86400) . ' day ago';

    // Show last 6 chars of order_code: "ORD2026021163D519" → "63D519"
    $short_id = substr($row['order_code'], -6);

    $recent_orders[] = [
        'id'       => $short_id,
        'customer' => $row['buyer_name'],
        'product'  => $row['product_name'],
        'category' => $row['product_category'],
        'amount'   => (float) $row['total_price'],
        'status'   => $row['status'],
        'time'     => $time_ago,
    ];
}


// ── 4. DAILY SALES CHART DATA (last 7 days) ───────────────────
// Used by the stacked bar chart — one column per day, split by category

$chart_days = [];
$chart_data = ['food' => [], 'preloved' => [], 'service' => [], 'urgent' => []];

for ($i = 6; $i >= 0; $i--) {
    $date         = date('Y-m-d', strtotime("-{$i} days"));
    $chart_days[] = date('D', strtotime("-{$i} days")); // "Mon", "Tue" ...

    foreach (array_keys($chart_data) as $cat) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(o.total_price), 0)
            FROM orders   o
            JOIN products p ON p.product_id = o.product_id
            WHERE o.seller_id      = ?
              AND p.category       = ?
              AND DATE(o.created_at) = ?
              AND o.status NOT IN ('cancelled')
        ");
        $stmt->execute([$seller_id, $cat, $date]);
        $chart_data[$cat][] = (float) $stmt->fetchColumn();
    }
}


// ── 5. DONUT CHART — REVENUE BY CATEGORY ──────────────────────
// Total delivered revenue per category (all time)

$category_revenue = [];
foreach (array_keys($chart_data) as $cat) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(o.total_price), 0)
        FROM orders   o
        JOIN products p ON p.product_id = o.product_id
        WHERE o.seller_id = ?
          AND p.category  = ?
          AND o.status    = 'delivered'
    ");
    $stmt->execute([$seller_id, $cat]);
    $val = (float) $stmt->fetchColumn();
    if ($val > 0) {
        $category_revenue[$cat] = $val;
    }
}

// Safety: if seller has no revenue yet, show empty placeholders
// so the donut chart doesn't throw an error
if (empty($category_revenue)) {
    $category_revenue = ['food' => 0, 'preloved' => 0, 'service' => 0, 'urgent' => 0];
}

// Used for percentage calculation in the donut legend
$total_revenue_breakdown = array_sum($category_revenue) ?: 1;
