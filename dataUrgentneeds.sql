-- Tabel untuk urgent needs
CREATE TABLE IF NOT EXISTS urgent_needs (
    need_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(100) NOT NULL,
    max_budget DECIMAL(10,2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    status ENUM('open', 'in_progress', 'completed', 'cancelled') DEFAULT 'open',
    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_urgency (urgency),
    INDEX idx_location (location),
    FULLTEXT idx_search (title, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel untuk offers/response dari helpers
CREATE TABLE IF NOT EXISTS urgent_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    need_id INT NOT NULL,
    helper_id INT NOT NULL,
    message TEXT NOT NULL,
    price_offer DECIMAL(10,2) DEFAULT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (need_id) REFERENCES urgent_needs(need_id) ON DELETE CASCADE,
    FOREIGN KEY (helper_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;