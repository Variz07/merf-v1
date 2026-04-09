-- ============================================
-- MERF MARKETPLACE DATABASE SETUP
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS projek_merfv1;
USE projek_merfv1;

-- ============================================
-- TABLES
-- ============================================

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    student_id VARCHAR(20),
    university VARCHAR(100),
    is_student ENUM('yes', 'no') DEFAULT 'yes',
    dob DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    dorm_address TEXT,
    profile_pic VARCHAR(255) DEFAULT 'default-profile.png',
    bio TEXT,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_sales INT DEFAULT 0,
    role ENUM('customer', 'seller', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    category ENUM('food', 'preloved', 'service', 'urgent') NOT NULL,
    subcategory VARCHAR(50),
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discounted_price DECIMAL(10,2),
    quantity INT DEFAULT 1,
    image VARCHAR(255) NOT NULL,
    location VARCHAR(100) DEFAULT 'SBH',
    is_available BOOLEAN DEFAULT TRUE,
    delivery_option ENUM('pickup', 'delivery', 'both') DEFAULT 'both',
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    avg_rating DECIMAL(3,2) DEFAULT 0.00,
    total_views INT DEFAULT 0,
    total_likes INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_seller (seller_id),
    INDEX idx_available (is_available),
    INDEX idx_featured (is_featured),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(20) UNIQUE NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    delivery_address TEXT,
    notes TEXT,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'transfer', 'qris', 'other') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ratings table
CREATE TABLE ratings (
    rating_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating_value INT CHECK (rating_value BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Favorites table
CREATE TABLE favorites (
    fav_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (product_id, user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT DEFAULT NULL,
    blog_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (blog_id) REFERENCES blogs(blog_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_blog (blog_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blogs table
CREATE TABLE blogs (
    blog_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    category ENUM('tips', 'review', 'news', 'story') DEFAULT 'tips',
    views INT DEFAULT 0,
    likes INT DEFAULT 0,
    shares INT DEFAULT 0,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_published (is_published),
    FULLTEXT idx_blog_search (title, content)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blog likes table
CREATE TABLE blog_likes (
    like_id INT AUTO_INCREMENT PRIMARY KEY,
    blog_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_blog_like (blog_id, user_id),
    FOREIGN KEY (blog_id) REFERENCES blogs(blog_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Messages table
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_conversation (sender_id, receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('message', 'order', 'like', 'comment', 'rating', 'system') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports table
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    reported_user_id INT DEFAULT NULL,
    reported_product_id INT DEFAULT NULL,
    report_type ENUM('user', 'product', 'comment', 'scam', 'other') NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'resolved', 'dismissed') DEFAULT 'pending',
    admin_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reported_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (reported_product_id) REFERENCES products(product_id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'content_mod', 'support') DEFAULT 'content_mod',
    permissions TEXT,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site settings table
CREATE TABLE site_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Remember tokens for auto login
CREATE TABLE remember_tokens (
    token_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DEFAULT DATA
-- ============================================

-- Insert default admin (password: admin123)
INSERT INTO admin (username, email, password, full_name, role) VALUES 
('admin', 'admin@merf.com', '$2y$10$HashHerePlaceholder', 'System Administrator', 'super_admin');

-- Insert default settings
INSERT INTO site_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'MERF Marketplace', 'text'),
('site_description', 'Platform E-Commerce untuk Mahasiswa President University', 'text'),
('admin_email', 'admin@merf.com', 'text'),
('currency', 'IDR', 'text'),
('enable_registration', 'true', 'boolean'),
('require_student_id', 'false', 'boolean'),
('auto_verify_students', 'true', 'boolean'),
('min_product_price', '1000', 'number'),
('max_product_images', '5', 'number'),
('max_upload_size', '5', 'number'),
('auto_approve_products', 'true', 'boolean'),
('order_expiry', '60', 'number'),
('max_orders_per_day', '10', 'number'),
('maintenance_mode', 'false', 'boolean'),
('maintenance_message', 'Website sedang dalam perawatan. Silakan kembali beberapa saat lagi.', 'text');

-- Insert sample users
INSERT INTO users (full_name, email, password, phone, university, dorm_address, profile_pic, role) VALUES
('Admin User', 'admin@merf.com', '$2y$10$HashHerePlaceholder', '081234567890', 'President University', 'SBH Tower Admin', 'admin.jpg', 'admin'),
('Kayla Vegasus', 'kayla@example.com', '$2y$10$HashHerePlaceholder', '081234567891', 'President University', 'SBH Tower A Lantai 3', 'kayla.jpg', 'seller'),
('Budi Utomo', 'budi@example.com', '$2y$10$HashHerePlaceholder', '081234567892', NULL, 'Cikarang', 'budi.jpg', 'seller'),
('Customer Test', 'customer@example.com', '$2y$10$HashHerePlaceholder', '081234567893', 'President University', 'NBH Tower B', 'default-profile.png', 'customer');

-- Insert sample products
INSERT INTO products (seller_id, name, category, subcategory, description, price, discounted_price, image, location) VALUES
(2, 'DIMSUM MENTAI', 'food', 'dimsum', 'Dimsum mentai isi 8 pcs (besar)', 25000, NULL, 'dimsum.jpg', 'SBH'),
(2, 'BIHUN GORENG', 'food', 'makanan', 'Antar SBH, NBH, kos, apart, rumah, kantor', 20000, NULL, 'bihun.jpg', 'SBH'),
(3, 'Service Laptop', 'service', 'repair', 'Menerima berbagai macam kerusakan laptop', 50000, 45000, 'laptop.jpg', 'SBH'),
(3, 'Cleaning Service', 'service', 'cleaning', 'Jasa cleaning room untuk mahasiswa', 150000, 120000, 'cleaning.jpg', 'SBH'),
(2, 'Sweater Thrifting', 'preloved', 'clothes', 'Sweater thrifting kondisi bagus', 75000, 50000, 'sweater.jpg', 'SBH'),
(2, 'Skincare Set', 'preloved', 'skincare', 'Skincare fullset untuk kulit berminyak', 120000, 100000, 'skincare.jpg', 'SBH');

-- Insert sample blogs
INSERT INTO blogs (user_id, title, content, category, image) VALUES
(2, 'Tips Jualan Online untuk Mahasiswa', 'Berbagi pengalaman jualan makanan dan barang preloved di kampus...', 'tips', 'blog1.jpg'),
(3, 'Cara Merawat Laptop Agar Awet', 'Panduan lengkap merawat laptop untuk mahasiswa...', 'tips', 'blog2.jpg');

-- ============================================
-- TRIGGERS
-- ============================================

-- Update user rating when product gets new rating
DELIMITER //
CREATE TRIGGER update_seller_rating 
AFTER INSERT ON ratings
FOR EACH ROW
BEGIN
    DECLARE seller_id_val INT;
    DECLARE avg_rating_val DECIMAL(3,2);
    
    -- Get seller ID from product
    SELECT seller_id INTO seller_id_val 
    FROM products WHERE product_id = NEW.product_id;
    
    -- Calculate average rating for seller
    SELECT AVG(r.rating_value) INTO avg_rating_val
    FROM ratings r
    JOIN products p ON r.product_id = p.product_id
    WHERE p.seller_id = seller_id_val;
    
    -- Update seller rating
    UPDATE users 
    SET rating = COALESCE(avg_rating_val, 0)
    WHERE user_id = seller_id_val;
END//
DELIMITER ;

-- Update order count for seller when order is delivered
DELIMITER //
CREATE TRIGGER update_seller_sales 
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        UPDATE users 
        SET total_sales = total_sales + 1 
        WHERE user_id = NEW.seller_id;
    END IF;
END//
DELIMITER ;

-- ============================================
-- STORED PROCEDURES
-- ============================================

-- Procedure to get monthly sales report
DELIMITER //
CREATE PROCEDURE GetMonthlySalesReport(IN year_param INT)
BEGIN
    SELECT 
        MONTH(created_at) as month,
        COUNT(*) as total_orders,
        SUM(total_price) as total_revenue,
        AVG(total_price) as avg_order_value
    FROM orders
    WHERE YEAR(created_at) = year_param 
        AND status = 'delivered'
    GROUP BY MONTH(created_at)
    ORDER BY month;
END//
DELIMITER ;

-- Procedure to get top sellers
DELIMITER //
CREATE PROCEDURE GetTopSellers(IN limit_param INT)
BEGIN
    SELECT 
        u.user_id,
        u.full_name,
        u.profile_pic,
        COUNT(o.order_id) as total_sales,
        SUM(o.total_price) as total_revenue,
        u.rating
    FROM users u
    JOIN orders o ON u.user_id = o.seller_id
    WHERE o.status = 'delivered'
        AND u.role = 'seller'
        AND u.status = 'active'
    GROUP BY u.user_id
    ORDER BY total_sales DESC
    LIMIT limit_param;
END//
DELIMITER ;

-- Procedure to cleanup old notifications
DELIMITER //
CREATE PROCEDURE CleanupOldNotifications(IN days_old INT)
BEGIN
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY)
        AND is_read = TRUE;
END//
DELIMITER ;

-- ============================================
-- VIEWS
-- ============================================

-- View for active products with seller info
CREATE VIEW v_active_products AS
SELECT 
    p.*,
    u.full_name as seller_name,
    u.profile_pic as seller_avatar,
    u.rating as seller_rating
FROM products p
JOIN users u ON p.seller_id = u.user_id
WHERE p.is_available = TRUE 
    AND u.status = 'active';

-- View for order details
CREATE VIEW v_order_details AS
SELECT 
    o.*,
    p.name as product_name,
    p.image as product_image,
    seller.full_name as seller_name,
    seller.phone as seller_phone,
    buyer.full_name as buyer_name,
    buyer.phone as buyer_phone
FROM orders o
JOIN products p ON o.product_id = p.product_id
JOIN users seller ON o.seller_id = seller.user_id
JOIN users buyer ON o.buyer_id = buyer.user_id;

-- View for user statistics
CREATE VIEW v_user_stats AS
SELECT 
    u.user_id,
    u.full_name,
    u.email,
    u.role,
    u.status,
    u.created_at,
    (SELECT COUNT(*) FROM products p WHERE p.seller_id = u.user_id AND p.is_available = TRUE) as total_products,
    (SELECT COUNT(*) FROM orders o WHERE o.seller_id = u.user_id AND o.status = 'delivered') as total_sales,
    (SELECT COUNT(*) FROM orders o WHERE o.buyer_id = u.user_id) as total_purchases,
    u.rating
FROM users u;

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_products_created ON products(created_at);
CREATE INDEX idx_users_created ON users(created_at);
CREATE INDEX idx_messages_created ON messages(created_at);
CREATE INDEX idx_notifications_created ON notifications(created_at);

-- Fulltext indexes for search
ALTER TABLE products ADD FULLTEXT(product_search) AGAINST('name' 'description');
ALTER TABLE blogs ADD FULLTEXT(blog_search) AGAINST('title' 'content');

-- ============================================
-- COMMENTS
-- ============================================

COMMIT;