<?php
/**
 * Product Class
 */
class Product {
    private $pdo;
    private $product_id;
    private $data;
    
    public function __construct($pdo, $product_id = null) {
        $this->pdo = $pdo;
        if($product_id) {
            $this->load($product_id);
        }
    }
    
    public function load($product_id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.full_name, u.profile_pic, u.rating as seller_rating,
            (SELECT AVG(rating_value) FROM ratings WHERE product_id = p.product_id) as avg_rating,
            (SELECT COUNT(*) FROM ratings WHERE product_id = p.product_id) as rating_count
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.product_id = ? AND p.is_available = 1
        ");
        $stmt->execute([$product_id]);
        $this->data = $stmt->fetch();
        
        if($this->data) {
            $this->product_id = $product_id;
            return true;
        }
        return false;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products 
            (seller_id, name, category, subcategory, description, price, discounted_price, 
             quantity, image, location, delivery_option, delivery_fee, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $data['seller_id'],
            $data['name'],
            $data['category'],
            $data['subcategory'] ?? null,
            $data['description'],
            $data['price'],
            $data['discounted_price'] ?? null,
            $data['quantity'] ?? 1,
            $data['image'],
            $data['location'],
            $data['delivery_option'] ?? 'both',
            $data['delivery_fee'] ?? 0
        ]);
        
        if($result) {
            $this->product_id = $this->pdo->lastInsertId();
            $this->load($this->product_id);
        }
        
        return $result;
    }
    
    public function update($data) {
        $sql = "UPDATE products SET ";
        $params = [];
        $updates = [];
        
        foreach($data as $key => $value) {
            if($key !== 'product_id') {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        $sql .= implode(', ', $updates) . ", updated_at = NOW() WHERE product_id = ?";
        $params[] = $this->product_id;
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if($result) {
            $this->load($this->product_id);
        }
        
        return $result;
    }
    
    public function delete() {
        $stmt = $this->pdo->prepare("UPDATE products SET is_available = 0 WHERE product_id = ?");
        return $stmt->execute([$this->product_id]);
    }
    
    public function incrementViews() {
        $stmt = $this->pdo->prepare("UPDATE products SET total_views = total_views + 1 WHERE product_id = ?");
        return $stmt->execute([$this->product_id]);
    }
    
    public function addRating($user_id, $rating, $review = null) {
        // Check if already rated
        $check_stmt = $this->pdo->prepare("SELECT rating_id FROM ratings WHERE product_id = ? AND user_id = ?");
        $check_stmt->execute([$this->product_id, $user_id]);
        
        if($check_stmt->fetch()) {
            return ['success' => false, 'message' => 'Anda sudah memberikan rating'];
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO ratings (product_id, user_id, rating_value, review) 
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([$this->product_id, $user_id, $rating, $review]);
        
        if($result) {
            // Update average rating
            $this->updateAverageRating();
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Gagal menambahkan rating'];
    }
    
    private function updateAverageRating() {
        $stmt = $this->pdo->prepare("
            UPDATE products SET avg_rating = (
                SELECT AVG(rating_value) FROM ratings WHERE product_id = ?
            ) WHERE product_id = ?
        ");
        return $stmt->execute([$this->product_id, $this->product_id]);
    }
    
    public function toggleFavorite($user_id) {
        // Check if already favorited
        $check_stmt = $this->pdo->prepare("SELECT fav_id FROM favorites WHERE product_id = ? AND user_id = ?");
        $check_stmt->execute([$this->product_id, $user_id]);
        
        if($check_stmt->fetch()) {
            // Remove from favorites
            $stmt = $this->pdo->prepare("DELETE FROM favorites WHERE product_id = ? AND user_id = ?");
            $result = $stmt->execute([$this->product_id, $user_id]);
            return ['success' => $result, 'is_favorited' => false];
        } else {
            // Add to favorites
            $stmt = $this->pdo->prepare("INSERT INTO favorites (product_id, user_id) VALUES (?, ?)");
            $result = $stmt->execute([$this->product_id, $user_id]);
            return ['success' => $result, 'is_favorited' => true];
        }
    }
    
    public function getRelated($limit = 4) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, u.full_name, u.profile_pic 
            FROM products p 
            JOIN users u ON p.seller_id = u.user_id 
            WHERE p.category = ? AND p.product_id != ? AND p.is_available = 1 
            ORDER BY RAND() 
            LIMIT ?
        ");
        $stmt->execute([$this->data['category'], $this->product_id, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getReviews($limit = null) {
        $sql = "
            SELECT r.*, u.full_name, u.profile_pic 
            FROM ratings r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.product_id = ? 
            ORDER BY r.created_at DESC
        ";
        
        if($limit) {
            $sql .= " LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->product_id, $limit]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->product_id]);
        }
        
        return $stmt->fetchAll();
    }
    
    public function getImages() {
        if(!empty($this->data['image'])) {
            $images = json_decode($this->data['image'], true);
            if(json_last_error() === JSON_ERROR_NONE && is_array($images)) {
                return $images;
            }
            return [$this->data['image']];
        }
        return ['default-product.jpg'];
    }
    
    // Getters
    public function getId() { return $this->product_id; }
    public function getData() { return $this->data; }
    public function getName() { return $this->data['name'] ?? ''; }
    public function getDescription() { return $this->data['description'] ?? ''; }
    public function getPrice() { return $this->data['price'] ?? 0; }
    public function getDiscountedPrice() { return $this->data['discounted_price'] ?? null; }
    public function getFinalPrice() { 
        return $this->data['discounted_price'] ?? $this->data['price'] ?? 0; 
    }
    public function getCategory() { return $this->data['category'] ?? ''; }
    public function getSellerId() { return $this->data['seller_id'] ?? 0; }
    public function getSellerName() { return $this->data['full_name'] ?? ''; }
    public function getRating() { return $this->data['avg_rating'] ?? 0; }
    public function getRatingCount() { return $this->data['rating_count'] ?? 0; }
    public function isAvailable() { return ($this->data['is_available'] ?? 0) == 1; }
    public function getLocation() { return $this->data['location'] ?? ''; }
}
?>