<?php
// Cek session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default values
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : '';
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? 'customer') : '';
$profilePic = $isLoggedIn && isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : 'default-profile.png';
?>
<!DOCTYPE html>
<html lang="id">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>MERF Marketplace</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
      <!-- CSS untuk food-->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/food.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">


    <style>

/* ===== HEADER STYLES - FIXED VERSION ===== */
.header {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: sticky !important;
    top: 0 !important;
    z-index: 999999 !important; /* SATUAN! SUPER TINGGI */
    width: 100%;
    border-bottom: 3px solid #C9B59C;
    isolation: isolate;
}

.header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 0;
    max-width: 1200px;
    margin: 0 auto;
    width: 100%;
    position: relative;
    z-index: 999999;
}

/* LOGO */
.logo a {
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
}

.logo-circle {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #C9B59C;
}

.logo-circle img {
    width: 30px;
    height: 30px;
    object-fit: contain;
}

/* LOGO IMAGE - TANPA BACKGROUND */
.logo-full {
    width: 60px;
    height: 60px;
    object-fit: contain;
    display: block;
}

.logo-text {
    font-weight: 700;
    font-size: 24px;
    color: #4C3C27;
    letter-spacing: 1px;
    text-transform: uppercase;
    line-height: 1;
}

.logo a:hover .logo-text {
    color: #300C0C;
}

/* SEARCH BAR */
.search-bar {
    flex: 1;
    max-width: 400px;
    margin: 0 20px;
    position: relative;
    z-index: 999999;
}

.search-form {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 50px 12px 20px;
    border: 2px solid #E8E3D9;
    border-radius: 30px;
    font-size: 14px;
    transition: all 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: #C9B59C;
    box-shadow: 0 0 0 3px rgba(201,181,156,0.2);
}

.search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: #4C3C27;
    color: white;
    border: none;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
}

.search-btn:hover {
    background: #2C2416;
    transform: translateY(-50%) scale(1.05);
}

/* NAVIGATION */
.main-nav {
    position: relative;
    z-index: 999999;
}

.main-nav .nav-list {
    display: flex;
    list-style: none;
    gap: 5px;
    margin: 0;
    padding: 0;
}

.nav-list a {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    text-decoration: none;
    color: #6D6D6D;
    font-size: 14px;
    border-radius: 25px;
    transition: all 0.3s;
    position: relative;
    z-index: 999999;
    pointer-events: auto;
}

.nav-list a:hover {
    background: #F5F3EE;
    color: #4C3C27;
}

.nav-list a.active {
    background: #C9B59C;
    color: #4C3C27;
    font-weight: 600;
}

/* USER ACTIONS */
.user-actions {
    display: flex;
    align-items: center;
    gap: 15px;
    position: relative;
    z-index: 999999;
}

/* UPLOAD BUTTON */
.upload-btn {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.upload-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76,60,39,0.2);
}

/* AUTH BUTTONS */
.auth-buttons {
    display: flex;
    gap: 10px;
}

.btn-login {
    padding: 10px 24px;
    border: 2px solid #4C3C27;
    border-radius: 25px;
    color: #4C3C27;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-login:hover {
    background: #4C3C27;
    color: white;
}

.btn-signup {
    padding: 10px 24px;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    border-radius: 25px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-signup:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76,60,39,0.2);
}

/* USER DROPDOWN */
.user-dropdown {
    position: relative;
    z-index: 1000000;
}

.user-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px;
    background: none;
    border: none;
    cursor: pointer;
    border-radius: 30px;
    transition: all 0.3s;
}

.user-btn:hover {
    background: #F5F3EE;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #C9B59C;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-btn span {
    font-weight: 500;
    color: #2C2416;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* DROPDOWN CONTENT */
.user-dropdown-content {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 250px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    margin-top: 0;
    padding-top: 10px;  /* moved gap inside so hover area stays connected */
    z-index: 1000000 !important;
    border: 1px solid #E8E3D9;
}

.user-dropdown:hover .user-dropdown-content {
    display: block;
}

.user-dropdown-content a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.user-dropdown-content a:hover {
    background: #F5F3EE;
    padding-left: 25px;
}

.dropdown-divider {
    height: 1px;
    background: #E8E3D9;
    margin: 8px 0;
}

/* HERO SECTION - Z-INDEX RENDAH */
.hero,
.hero-section,
section:not(.header) {
    position: relative;
    z-index: 1 !important;
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .search-bar {
        max-width: 300px;
    }
    
    .nav-list a {
        padding: 6px 12px;
        font-size: 13px;
    }
}

@media (max-width: 768px) {
    .search-bar,
    .main-nav {
        display: none;
    }
    
    .logo-full {
        width: 50px;
        height: 50px;
    }
    
    .logo-text {
        font-size: 20px;
    }
    
    .upload-btn span {
        display: none;
    }
    
    .user-btn span {
        display: none;
    }
}

@media (max-width: 480px) {
    .logo-text {
        display: none;
    }
    
    .logo-full {
        width: 45px;
        height: 45px;
    }
}    </style>
</head>
<body>
    <header class="header">
        <div class="header-inner">
            <!-- Logo -->
<div class="logo">
    <a href="<?php echo SITE_URL; ?>index.php">
        <!-- LOGO FULL - TANPA BACKGROUND COKLAT -->
        <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="MERF Logo" class="logo-full">
        <!-- TEKS ECOMMERCE DI SAMPING LOGO -->
        <span class="logo-text">MERFV</span>
    </a>
</div>            
            
            <!-- Navigation -->
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="<?php echo SITE_URL; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/food.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'food.php') ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Food</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/preloved.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'preloved.php') ? 'active' : ''; ?>"><i class="fas fa-tshirt"></i> Preloved</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/service.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'service.php') ? 'active' : ''; ?>"><i class="fas fa-hands-helping"></i> Service</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/urgent.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'urgent.php') ? 'active' : ''; ?>"><i class="fas fa-clock"></i> Urgent</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/blog.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'blog.php') ? 'active' : ''; ?>"><i class="fas fa-blog"></i> Blog</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/help.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'help.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle"></i> Help</a></li>
                </ul>
            </nav>
            
            <!-- User Actions - PASTIKAN INI YANG DIGUNAKAN -->
            <div class="user-actions">
                <?php if($isLoggedIn): ?>
                    <!-- TAMPILAN UNTUK USER SUDAH LOGIN -->
                    
                    <!-- Upload Button -->
                    <a href="<?php echo SITE_URL; ?>pages/upload-product.php" class="upload-btn">
                        <i class="fas fa-plus"></i> Sell
                    </a>
                    
                    <!-- User Dropdown -->
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar">
                                <img src="<?php echo SITE_URL . UPLOAD_PATH . $profilePic; ?>" alt="Profile">
                            </div>
                            <span><?php echo htmlspecialchars($userName); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="user-dropdown-content">
                            <a href="<?php echo SITE_URL; ?>pages/profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <a href="<?php echo SITE_URL; ?>pages/profile.php#products"><i class="fas fa-box"></i> My Products</a>
                            <a href="<?php echo SITE_URL; ?>pages/my-orders.php"><i class="fas fa-shopping-bag"></i> Order</a>
                            <a href="<?php echo SITE_URL; ?>pages/favorites.php"><i class="fas fa-heart"></i> Favorite</a>
                            <?php if($userRole == 'seller' || $userRole == 'admin'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo SITE_URL; ?>pages/seller-dashboard.php"><i class="fas fa-chart-line"></i> Seller Dashboard</a>
                            <?php endif; ?>
                            <?php if($userRole == 'admin'): ?>
                            <a href="<?php echo SITE_URL; ?>admin/dashboard.php"><i class="fas fa-crown"></i> Admin Panel</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo SITE_URL; ?>auth/logout.php" style="color: #DC3545;"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- TAMPILAN UNTUK USER BELUM LOGIN - PASTIKAN TIDAK ADA DUPLIKASI -->
                    <div class="auth-buttons">
                        <a href="<?php echo SITE_URL; ?>auth/signin.php" class="btn-login">log in</a>
                        <a href="<?php echo SITE_URL; ?>auth/signup.php" class="btn-signup">Sign in<Obj></Obj></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
        <?php endif; ?>