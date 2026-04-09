<?php
/**
 * Session management functions
 */

// Start session if not started
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session variables after login
function setUserSession($user) {
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['profile_pic'] = $user['profile_pic'];
    $_SESSION['login_time'] = time();
    
    // Update last login
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
}

// Destroy session on logout
function destroySession() {
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(), '', 0, '/');
}

// Check session timeout (30 minutes)
function checkSessionTimeout() {
    if(isset($_SESSION['login_time'])) {
        $inactive = 1800; // 30 minutes in seconds
        $session_life = time() - $_SESSION['login_time'];
        
        if($session_life > $inactive) {
            destroySession();
            return false;
        }
        
        $_SESSION['login_time'] = time(); // Update activity time
    }
    return true;
}

// Check if user has permission
function hasPermission($required_role) {
    if(!isLoggedIn()) {
        return false;
    }
    
    $user_role = $_SESSION['user_role'];
    
    // Role hierarchy
    $hierarchy = [
        'customer' => 1,
        'seller' => 2,
        'admin' => 3
    ];
    
    $user_level = $hierarchy[$user_role] ?? 0;
    $required_level = $hierarchy[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// Check if current user is admin
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

// Check if current user is seller
function isSeller() {
    $role = getCurrentUserRole();
    return $role === 'seller' || $role === 'admin';
}

// Generate CSRF token
function generateCsrfToken() {
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function validateCsrfToken($token) {
    if(!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}
?>