<?php
/**
 * User Class
 */
class User {
    private $pdo;
    private $user_id;
    private $full_name;
    private $email;
    private $role;
    private $profile_pic;
    
    public function __construct($pdo, $user_id = null) {
        $this->pdo = $pdo;
        if($user_id) {
            $this->load($user_id);
        }
    }
    
    public function load($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if($user) {
            $this->user_id = $user['user_id'];
            $this->full_name = $user['full_name'];
            $this->email = $user['email'];
            $this->role = $user['role'];
            $this->profile_pic = $user['profile_pic'];
            return true;
        }
        return false;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users 
            (full_name, email, password, phone, student_id, university, 
             is_student, dob, gender, address, dorm_address, profile_pic, bio, role) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['full_name'],
            $data['email'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['phone'] ?? null,
            $data['student_id'] ?? null,
            $data['university'] ?? null,
            $data['is_student'] ?? 'yes',
            $data['dob'] ?? null,
            $data['gender'] ?? null,
            $data['address'] ?? null,
            $data['dorm_address'] ?? null,
            $data['profile_pic'] ?? 'default-profile.png',
            $data['bio'] ?? null,
            $data['role'] ?? 'customer'
        ]);
    }
    
    public function update($data) {
        $sql = "UPDATE users SET ";
        $params = [];
        $updates = [];
        
        foreach($data as $key => $value) {
            if($key !== 'user_id') {
                $updates[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        $sql .= implode(', ', $updates) . " WHERE user_id = ?";
        $params[] = $this->user_id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function delete() {
        $stmt = $this->pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
        return $stmt->execute([$this->user_id]);
    }
    
    public function getProducts($limit = null) {
        $sql = "SELECT * FROM products WHERE seller_id = ? AND is_available = 1 ORDER BY created_at DESC";
        if($limit) {
            $sql .= " LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->user_id, $limit]);
        } else {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->user_id]);
        }
        return $stmt->fetchAll();
    }
    
    public function getOrders($status = null) {
        $sql = "SELECT o.*, p.name as product_name, p.image 
                FROM orders o 
                JOIN products p ON o.product_id = p.product_id 
                WHERE o.buyer_id = ?";
        
        $params = [$this->user_id];
        
        if($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getStats() {
        return [
            'total_products' => $this->countProducts(),
            'total_orders' => $this->countOrders(),
            'total_reviews' => $this->countReviews(),
            'avg_rating' => $this->getAverageRating()
        ];
    }
    
    private function countProducts() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND is_available = 1");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn();
    }
    
    private function countOrders() {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'delivered'");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn();
    }
    
    private function countReviews() {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM ratings r 
            JOIN products p ON r.product_id = p.product_id 
            WHERE p.seller_id = ?
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn();
    }
    
    private function getAverageRating() {
        $stmt = $this->pdo->prepare("
            SELECT AVG(r.rating_value) FROM ratings r 
            JOIN products p ON r.product_id = p.product_id 
            WHERE p.seller_id = ?
        ");
        $stmt->execute([$this->user_id]);
        $rating = $stmt->fetchColumn();
        return round($rating, 1);
    }
    
    // Getters
    public function getId() { return $this->user_id; }
    public function getName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
    public function getProfilePic() { return $this->profile_pic; }
    public function isSeller() { return $this->role == 'seller' || $this->role == 'admin'; }
    public function isAdmin() { return $this->role == 'admin'; }
}
?>