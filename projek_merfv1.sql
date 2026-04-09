-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Feb 2026 pada 09.05
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `projek_merfv1`
--

DELIMITER $$
--
-- Prosedur
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CleanupOldNotifications` (IN `days_old` INT)   BEGIN
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL days_old DAY)
        AND is_read = TRUE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetMonthlySalesReport` (IN `year_param` INT)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetTopSellers` (IN `limit_param` INT)   BEGIN
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
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','content_mod','support') DEFAULT 'content_mod',
  `permissions` text DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`admin_id`, `username`, `email`, `password`, `full_name`, `role`, `permissions`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin@merf.com', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'super_admin', NULL, NULL, '2026-02-11 13:57:13');

-- --------------------------------------------------------

--
-- Struktur dari tabel `blogs`
--

CREATE TABLE `blogs` (
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category` enum('tips','review','news','story') DEFAULT 'tips',
  `views` int(11) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `shares` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `blogs`
--

INSERT INTO `blogs` (`blog_id`, `user_id`, `title`, `content`, `image`, `category`, `views`, `likes`, `shares`, `is_published`, `created_at`) VALUES
(1, 2, 'Tips Jualan Online untuk Mahasiswa', 'Berbagi pengalaman jualan makanan dan barang preloved di kampus...', 'blog1.jpg', 'tips', 0, 0, 0, 1, '2026-02-11 14:10:36'),
(2, 3, 'Cara Merawat Laptop Agar Awet', 'Panduan lengkap merawat laptop untuk mahasiswa...', 'blog2.jpg', 'tips', 0, 0, 0, 1, '2026-02-11 14:10:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `blog_likes`
--

CREATE TABLE `blog_likes` (
  `like_id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_edited` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `favorites`
--

CREATE TABLE `favorites` (
  `fav_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `messages`
--

CREATE TABLE `messages` (
  `message_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('message','order','like','comment','rating','system') NOT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `type`, `title`, `message`, `reference_id`, `is_read`, `created_at`) VALUES
(1, 5, 'system', 'Selamat Datang!', 'Terima kasih telah bergabung dengan MERF Marketplace', NULL, 0, '2026-02-11 14:39:34'),
(2, 2, 'order', 'Pesanan Baru', 'Anda menerima pesanan baru untuk BIHUN GORENG', 1, 0, '2026-02-11 14:41:19'),
(3, 6, 'system', 'Selamat Datang!', 'Terima kasih telah bergabung dengan MERF Marketplace', NULL, 0, '2026-02-13 01:32:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_code` varchar(20) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `delivery_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash','transfer','qris','other') DEFAULT 'cash',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`order_id`, `order_code`, `buyer_id`, `seller_id`, `product_id`, `quantity`, `total_price`, `delivery_fee`, `delivery_address`, `notes`, `status`, `payment_method`, `payment_status`, `created_at`, `updated_at`) VALUES
(1, 'ORD2026021163D519', 5, 2, 2, 1, 20000.00, 0.00, NULL, '', 'pending', 'cash', 'pending', '2026-02-11 14:41:19', '2026-02-11 14:41:19');

--
-- Trigger `orders`
--
DELIMITER $$
CREATE TRIGGER `update_seller_sales` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        UPDATE users 
        SET total_sales = total_sales + 1 
        WHERE user_id = NEW.seller_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('food','preloved','service','urgent') NOT NULL,
  `subcategory` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discounted_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `image` varchar(255) NOT NULL,
  `location` varchar(100) DEFAULT 'SBH',
  `is_available` tinyint(1) DEFAULT 1,
  `delivery_option` enum('pickup','delivery','both') DEFAULT 'both',
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `avg_rating` decimal(3,2) DEFAULT 0.00,
  `total_views` int(11) DEFAULT 0,
  `total_likes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`product_id`, `seller_id`, `name`, `category`, `subcategory`, `description`, `price`, `discounted_price`, `quantity`, `image`, `location`, `is_available`, `delivery_option`, `delivery_fee`, `avg_rating`, `total_views`, `total_likes`, `created_at`, `updated_at`) VALUES
(1, 2, 'DIMSUM MENTAI', 'food', 'dimsum', 'Dimsum mentai isi 8 pcs (besar)', 25000.00, NULL, 1, 'dimsum.jpg', 'SBH', 1, 'both', 0.00, 0.00, 13, 0, '2026-02-11 14:10:36', '2026-02-20 05:26:15'),
(2, 2, 'BIHUN GORENG', 'food', 'makanan', 'Antar SBH, NBH, kos, apart, rumah, kantor', 20000.00, NULL, 1, 'bihun.jpg', 'SBH', 1, 'both', 0.00, 0.00, 12, 0, '2026-02-11 14:10:36', '2026-02-20 05:26:18'),
(3, 3, 'Service Laptop', 'service', 'repair', 'Menerima berbagai macam kerusakan laptop', 50000.00, 45000.00, 1, 'laptop.jpg', 'SBH', 1, 'both', 0.00, 0.00, 2, 0, '2026-02-11 14:10:36', '2026-02-20 05:26:25'),
(4, 3, 'Cleaning Service', 'service', 'cleaning', 'Jasa cleaning room untuk mahasiswa', 150000.00, 120000.00, 1, 'cleaning.jpg', 'SBH', 1, 'both', 0.00, 0.00, 1, 0, '2026-02-11 14:10:36', '2026-02-13 01:54:16'),
(5, 2, 'Sweater Thrifting', 'preloved', 'clothes', 'Sweater thrifting kondisi bagus', 75000.00, 50000.00, 1, 'sweater.jpg', 'SBH', 1, 'both', 0.00, 0.00, 6, 0, '2026-02-11 14:10:36', '2026-02-13 06:44:59'),
(6, 2, 'Skincare Set', 'preloved', 'skincare', 'Skincare fullset untuk kulit berminyak', 120000.00, 100000.00, 1, 'skincare.jpg', 'SBH', 1, 'both', 0.00, 0.00, 3, 0, '2026-02-11 14:10:36', '2026-02-13 03:58:06'),
(7, 5, 'Healthy food', 'food', '', 'Healthy food, only 10K per piece! Fresh, tasty, and nutritious. Free delivery around SSB area.', 10000.00, NULL, 10, '6997f330dc671.jpg', 'SBH', 1, 'both', 0.00, 0.00, 1, 0, '2026-02-20 05:37:52', '2026-02-20 05:37:52'),
(8, 5, 'Enjoy our crispy banana chips —', 'food', '', 'perfectly crunchy and topped with your choice of chocolate, tiramisu, or cheese. A delightful mix of sweet and savory flavors that make snacking more exciting.\r\n- Price: Only 15K per pack\r\n- Toppings: Chocolate, tiramisu, cheese\r\n- Delivery: Free delivery around SSB area\r\nTreat yourself to a tasty snack that’s fresh, fun, and affordable!', 15000.00, NULL, 10, '6997f6cc43c88.jpg', 'SBH', 1, 'both', 0.00, 0.00, 1, 0, '2026-02-20 05:53:16', '2026-02-20 05:53:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating_value` int(11) DEFAULT NULL CHECK (`rating_value` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Trigger `ratings`
--
DELIMITER $$
CREATE TRIGGER `update_seller_rating` AFTER INSERT ON `ratings` FOR EACH ROW BEGIN
    DECLARE seller_id_val INT;
    DECLARE avg_rating_val DECIMAL(3,2);
    
    
    SELECT seller_id INTO seller_id_val 
    FROM products WHERE product_id = NEW.product_id;
    
    
    SELECT AVG(r.rating_value) INTO avg_rating_val
    FROM ratings r
    JOIN products p ON r.product_id = p.product_id
    WHERE p.seller_id = seller_id_val;
    
    
    UPDATE users 
    SET rating = COALESCE(avg_rating_val, 0)
    WHERE user_id = seller_id_val;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `reporter_id` int(11) NOT NULL,
  `reported_user_id` int(11) DEFAULT NULL,
  `reported_product_id` int(11) DEFAULT NULL,
  `report_type` enum('user','product','comment','scam','other') NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `site_settings`
--

CREATE TABLE `site_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `site_settings`
--

INSERT INTO `site_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `updated_at`) VALUES
(1, 'site_name', 'MERF Marketplace', 'text', '2026-02-11 13:57:15'),
(2, 'site_description', 'Platform E-Commerce untuk Mahasiswa President University', 'text', '2026-02-11 13:57:15'),
(3, 'currency', 'IDR', 'text', '2026-02-11 13:57:15'),
(4, 'min_order_price', '10000', 'number', '2026-02-11 13:57:15'),
(5, 'max_upload_size', '5242880', 'number', '2026-02-11 13:57:15'),
(6, 'allowed_image_types', 'jpg,jpeg,png,gif', 'text', '2026-02-11 13:57:15'),
(7, 'enable_registration', 'true', 'boolean', '2026-02-11 13:57:15'),
(8, 'maintenance_mode', 'false', 'boolean', '2026-02-11 13:57:15');

-- --------------------------------------------------------

--
-- Struktur dari tabel `urgent_needs`
--

CREATE TABLE `urgent_needs` (
  `need_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(100) NOT NULL,
  `max_budget` decimal(10,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('open','in_progress','completed','cancelled') DEFAULT 'open',
  `urgency` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `urgent_offers`
--

CREATE TABLE `urgent_offers` (
  `offer_id` int(11) NOT NULL,
  `need_id` int(11) NOT NULL,
  `helper_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `price_offer` decimal(10,2) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `university` varchar(100) DEFAULT NULL,
  `is_student` enum('yes','no') DEFAULT 'yes',
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dorm_address` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT 'default-profile.png',
  `bio` text DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_sales` int(11) DEFAULT 0,
  `role` enum('customer','seller','admin') DEFAULT 'customer',
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `student_id`, `university`, `is_student`, `dob`, `gender`, `address`, `dorm_address`, `profile_pic`, `bio`, `rating`, `total_sales`, `role`, `status`, `created_at`, `last_login`) VALUES
(1, 'Admin User', 'admin@merf.com', '081234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'President University', 'yes', NULL, NULL, NULL, 'SBH Tower Admin', 'admin.jpg', NULL, 0.00, 0, 'admin', 'active', '2026-02-11 14:10:36', NULL),
(2, 'Kayla Vegasus', 'kayla@example.com', '081234567891', '$2y$10$YourHashHere', NULL, 'President University', 'yes', NULL, NULL, NULL, 'SBH Tower A Lantai 3', 'contohpict.jpg\r\n', NULL, 0.00, 0, 'seller', 'active', '2026-02-11 14:10:36', NULL),
(3, 'Budi Utomo', 'budi@example.com', '081234567892', '$2y$10$HashHerePlaceholder', NULL, NULL, 'yes', NULL, NULL, NULL, 'Cikarang', 'budi.jpg', NULL, 0.00, 0, 'seller', 'active', '2026-02-11 14:10:36', NULL),
(4, 'Customer Test', 'customer@example.com', '081234567893', '$2y$10$HashHerePlaceholder', NULL, 'President University', 'yes', NULL, NULL, NULL, 'NBH Tower B', 'default-profile.png', NULL, 0.00, 0, 'customer', 'active', '2026-02-11 14:10:36', NULL),
(5, 'Kaylavegasus', 'nurainmuzdalifah179@gmail.com', '+6281355503439', '$2y$10$Vzu9m8kj0VECwNkPUnSb4uOLfmlo7tpX8hekG2xVT8d1dIIzw5nI2', '001202500033', 'President University', 'yes', '2007-12-22', 'Female', 'Jalan jakarta', 'Gorontalo', 'profile_698c94a6331db.jpg', 'Haii', 0.00, 0, 'admin', 'active', '2026-02-11 14:39:34', '2026-02-20 05:29:32'),
(6, 'Cust1@gmail.com', 'kaylavegasus@gmail.com', '081355503439', '$2y$10$uUscPEh/O0wXdcWsEw0NIOHKSz/oAx19vkKIjiCj5B9k5Cs6EB666', '001202500033', 'PresidentUniversity', 'yes', '2007-02-12', 'Female', '', 'Pavi', 'profile_698e7f1fd279d.png', 'hehehe', 0.00, 0, 'customer', 'active', '2026-02-13 01:32:16', NULL);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_active_products`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_active_products` (
`product_id` int(11)
,`seller_id` int(11)
,`name` varchar(200)
,`category` enum('food','preloved','service','urgent')
,`subcategory` varchar(50)
,`description` text
,`price` decimal(10,2)
,`discounted_price` decimal(10,2)
,`quantity` int(11)
,`image` varchar(255)
,`location` varchar(100)
,`is_available` tinyint(1)
,`delivery_option` enum('pickup','delivery','both')
,`delivery_fee` decimal(10,2)
,`avg_rating` decimal(3,2)
,`total_views` int(11)
,`total_likes` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`seller_name` varchar(100)
,`seller_avatar` varchar(255)
,`seller_rating` decimal(3,2)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_order_details`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_order_details` (
`order_id` int(11)
,`order_code` varchar(20)
,`buyer_id` int(11)
,`seller_id` int(11)
,`product_id` int(11)
,`quantity` int(11)
,`total_price` decimal(10,2)
,`delivery_fee` decimal(10,2)
,`delivery_address` text
,`notes` text
,`status` enum('pending','confirmed','processing','shipped','delivered','cancelled')
,`payment_method` enum('cash','transfer','qris','other')
,`payment_status` enum('pending','paid','failed')
,`created_at` timestamp
,`updated_at` timestamp
,`product_name` varchar(200)
,`product_image` varchar(255)
,`seller_name` varchar(100)
,`seller_phone` varchar(15)
,`buyer_name` varchar(100)
,`buyer_phone` varchar(15)
);

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_user_stats`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_user_stats` (
`user_id` int(11)
,`full_name` varchar(100)
,`email` varchar(100)
,`role` enum('customer','seller','admin')
,`status` enum('active','inactive','banned')
,`created_at` timestamp
,`total_products` bigint(21)
,`total_sales` bigint(21)
,`total_purchases` bigint(21)
,`rating` decimal(3,2)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_active_products`
--
DROP TABLE IF EXISTS `v_active_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_products`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`seller_id` AS `seller_id`, `p`.`name` AS `name`, `p`.`category` AS `category`, `p`.`subcategory` AS `subcategory`, `p`.`description` AS `description`, `p`.`price` AS `price`, `p`.`discounted_price` AS `discounted_price`, `p`.`quantity` AS `quantity`, `p`.`image` AS `image`, `p`.`location` AS `location`, `p`.`is_available` AS `is_available`, `p`.`delivery_option` AS `delivery_option`, `p`.`delivery_fee` AS `delivery_fee`, `p`.`avg_rating` AS `avg_rating`, `p`.`total_views` AS `total_views`, `p`.`total_likes` AS `total_likes`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, `u`.`full_name` AS `seller_name`, `u`.`profile_pic` AS `seller_avatar`, `u`.`rating` AS `seller_rating` FROM (`products` `p` join `users` `u` on(`p`.`seller_id` = `u`.`user_id`)) WHERE `p`.`is_available` = 1 AND `u`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_order_details`
--
DROP TABLE IF EXISTS `v_order_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_order_details`  AS SELECT `o`.`order_id` AS `order_id`, `o`.`order_code` AS `order_code`, `o`.`buyer_id` AS `buyer_id`, `o`.`seller_id` AS `seller_id`, `o`.`product_id` AS `product_id`, `o`.`quantity` AS `quantity`, `o`.`total_price` AS `total_price`, `o`.`delivery_fee` AS `delivery_fee`, `o`.`delivery_address` AS `delivery_address`, `o`.`notes` AS `notes`, `o`.`status` AS `status`, `o`.`payment_method` AS `payment_method`, `o`.`payment_status` AS `payment_status`, `o`.`created_at` AS `created_at`, `o`.`updated_at` AS `updated_at`, `p`.`name` AS `product_name`, `p`.`image` AS `product_image`, `seller`.`full_name` AS `seller_name`, `seller`.`phone` AS `seller_phone`, `buyer`.`full_name` AS `buyer_name`, `buyer`.`phone` AS `buyer_phone` FROM (((`orders` `o` join `products` `p` on(`o`.`product_id` = `p`.`product_id`)) join `users` `seller` on(`o`.`seller_id` = `seller`.`user_id`)) join `users` `buyer` on(`o`.`buyer_id` = `buyer`.`user_id`)) ;

-- --------------------------------------------------------

--
-- Struktur untuk view `v_user_stats`
--
DROP TABLE IF EXISTS `v_user_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_user_stats`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`role` AS `role`, `u`.`status` AS `status`, `u`.`created_at` AS `created_at`, (select count(0) from `products` `p` where `p`.`seller_id` = `u`.`user_id` and `p`.`is_available` = 1) AS `total_products`, (select count(0) from `orders` `o` where `o`.`seller_id` = `u`.`user_id` and `o`.`status` = 'delivered') AS `total_sales`, (select count(0) from `orders` `o` where `o`.`buyer_id` = `u`.`user_id`) AS `total_purchases`, `u`.`rating` AS `rating` FROM `users` AS `u` ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`blog_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `blog_likes`
--
ALTER TABLE `blog_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_blog_like` (`blog_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indeks untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`fav_id`),
  ADD UNIQUE KEY `unique_fav` (`product_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_messages_created` (`created_at`);

--
-- Indeks untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_notifications_created` (`created_at`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_orders_created` (`created_at`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_products_created` (`created_at`);

--
-- Indeks untuk tabel `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_rating` (`product_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`);

--
-- Indeks untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `reporter_id` (`reporter_id`),
  ADD KEY `reported_user_id` (`reported_user_id`),
  ADD KEY `reported_product_id` (`reported_product_id`);

--
-- Indeks untuk tabel `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indeks untuk tabel `urgent_needs`
--
ALTER TABLE `urgent_needs`
  ADD PRIMARY KEY (`need_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_urgency` (`urgency`),
  ADD KEY `idx_location` (`location`);
ALTER TABLE `urgent_needs` ADD FULLTEXT KEY `idx_search` (`title`,`description`);

--
-- Indeks untuk tabel `urgent_offers`
--
ALTER TABLE `urgent_offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `need_id` (`need_id`),
  ADD KEY `helper_id` (`helper_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_created` (`created_at`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `blogs`
--
ALTER TABLE `blogs`
  MODIFY `blog_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `blog_likes`
--
ALTER TABLE `blog_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `favorites`
--
ALTER TABLE `favorites`
  MODIFY `fav_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `messages`
--
ALTER TABLE `messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `urgent_needs`
--
ALTER TABLE `urgent_needs`
  MODIFY `need_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `urgent_offers`
--
ALTER TABLE `urgent_offers`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `blogs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `blog_likes`
--
ALTER TABLE `blog_likes`
  ADD CONSTRAINT `blog_likes_ibfk_1` FOREIGN KEY (`blog_id`) REFERENCES `blogs` (`blog_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`comment_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reported_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `urgent_needs`
--
ALTER TABLE `urgent_needs`
  ADD CONSTRAINT `urgent_needs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `urgent_offers`
--
ALTER TABLE `urgent_offers`
  ADD CONSTRAINT `urgent_offers_ibfk_1` FOREIGN KEY (`need_id`) REFERENCES `urgent_needs` (`need_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `urgent_offers_ibfk_2` FOREIGN KEY (`helper_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
