<?php
// admin/admin-sidebar.php
// Komponen sidebar untuk semua halaman admin
?>
<!-- Admin Sidebar -->
<div class="admin-sidebar">
    <div class="admin-logo">
        <div class="logo-circle">
            <img src="../assets/images/logo.png" alt="MERF">
        </div>
        <h3>Admin Panel</h3>
    </div>
    
    <nav class="admin-nav">
        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
        <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Pengguna</span>
        </a>
        <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Produk</span>
        </a>
        <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Pesanan</span>
        </a>
        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-flag"></i>
            <span>Laporan</span>
        </a>
        <a href="blogs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'blogs.php' ? 'active' : ''; ?>">
            <i class="fas fa-blog"></i>
            <span>Blog</span>
        </a>
        <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Kategori</span>
        </a>
        <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Pengaturan</span>
        </a>
        <a href="logs.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Log Aktivitas</span>
        </a>
        
        <div class="admin-nav-divider"></div>
        
        <a href="../index.php">
            <i class="fas fa-home"></i>
            <span>Kembali ke Site</span>
        </a>
    </nav>
</div>

<!-- Mobile Menu Toggle untuk Admin -->
<div class="admin-mobile-toggle">
    <i class="fas fa-bars"></i>
</div>

<script>
// Toggle sidebar di mobile
document.querySelector('.admin-mobile-toggle')?.addEventListener('click', function() {
    document.querySelector('.admin-sidebar').classList.toggle('active');
});
</script>

<style>
.admin-mobile-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: #4C3C27;
    color: white;
    width: 45px;
    height: 45px;
    border-radius: 12px;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .admin-mobile-toggle {
        display: flex;
    }
}
</style>