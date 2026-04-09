<?php
/**
 * Order Class
 */
class Order {
    private $pdo;
    private $order_id;
    private $data;
    
    public function __construct($pdo, $order_id = null) {
        $this->pdo = $pdo;
        if($order_id) {
            $this->load($order_id);
        }
    }
    
    public function load($order_id) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, 
            p.name as product_name, p.image as product_image, p.price as product_price,
            p.discounted_price as product_discounted_price,
            seller.full_name as seller_name, seller.phone as seller_phone,
            buyer.full_name as buyer_name, buyer.phone as buyer_phone
            FROM orders o
            JOIN products p ON o.product_id = p.product_id
            JOIN users seller ON o.seller_id = seller.user_id
            JOIN users buyer ON o.buyer_id = buyer.user_id
            WHERE o.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $this->data = $stmt->fetch();
        
        if($this->data) {
            $this->order_id = $order_id;
            return true;
        }
        return false;
    }
    
    public function create($data) {
        // Generate order code
        $order_code = 'ORD' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO orders 
            (order_code, buyer_id, seller_id, product_id, quantity, total_price, 
             delivery_fee, delivery_address, notes, payment_method, status, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending')
        ");
        
        $result = $stmt->execute([
            $order_code,
            $data['buyer_id'],
            $data['seller_id'],
            $data['product_id'],
            $data['quantity'] ?? 1,
            $data['total_price'],
            $data['delivery_fee'] ?? 0,
            $data['delivery_address'] ?? '',
            $data['notes'] ?? '',
            $data['payment_method'] ?? 'cash'
        ]);
        
        if($result) {
            $this->order_id = $this->pdo->lastInsertId();
            $this->load($this->order_id);
            
            // Send notification to seller
            $this->sendNotification(
                $data['seller_id'],
                'order',
                'Pesanan Baru #' . $order_code,
                'Anda menerima pesanan baru'
            );
            
            return ['success' => true, 'order_id' => $this->order_id, 'order_code' => $order_code];
        }
        
        return ['success' => false, 'message' => 'Gagal membuat pesanan'];
    }
    
    public function updateStatus($status, $notes = null) {
        $stmt = $this->pdo->prepare("
            UPDATE orders SET status = ?, admin_notes = ?, updated_at = NOW() WHERE order_id = ?
        ");
        
        $result = $stmt->execute([$status, $notes, $this->order_id]);
        
        if($result) {
            $this->load($this->order_id);
            
            // Send notification to buyer
            $status_text = $this->getStatusText($status);
            $this->sendNotification(
                $this->data['buyer_id'],
                'order',
                'Update Pesanan #' . $this->data['order_code'],
                'Status pesanan Anda diperbarui menjadi: ' . $status_text
            );
            
            return true;
        }
        
        return false;
    }
    
    public function updatePaymentStatus($status) {
        $stmt = $this->pdo->prepare("
            UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE order_id = ?
        ");
        
        $result = $stmt->execute([$status, $this->order_id]);
        
        if($result && $status == 'paid') {
            // If payment is completed, update order status to confirmed
            $this->updateStatus('confirmed');
        }
        
        return $result;
    }
    
    private function getStatusText($status) {
        $statuses = [
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Dikonfirmasi',
            'processing' => 'Diproses',
            'shipped' => 'Dikirim',
            'delivered' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];
        
        return $statuses[$status] ?? $status;
    }
    
    private function sendNotification($user_id, $type, $title, $message) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, title, message, reference_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$user_id, $type, $title, $message, $this->order_id]);
    }
    
    public function cancel($reason = null) {
        return $this->updateStatus('cancelled', $reason);
    }
    
    public function complete() {
        return $this->updateStatus('delivered');
    }
    
    public function getTotal() {
        $total = $this->data['total_price'] + $this->data['delivery_fee'];
        return $total;
    }
    
    public function isBuyer($user_id) {
        return $this->data['buyer_id'] == $user_id;
    }
    
    public function isSeller($user_id) {
        return $this->data['seller_id'] == $user_id;
    }
    
    public function canCancel($user_id) {
        if(!$this->isBuyer($user_id) && !$this->isSeller($user_id)) {
            return false;
        }
        
        $cancellable_statuses = ['pending', 'confirmed'];
        return in_array($this->data['status'], $cancellable_statuses);
    }
    
    // Getters
    public function getId() { return $this->order_id; }
    public function getData() { return $this->data; }
    public function getCode() { return $this->data['order_code'] ?? ''; }
    public function getStatus() { return $this->data['status'] ?? ''; }
    public function getPaymentStatus() { return $this->data['payment_status'] ?? ''; }
    public function getBuyerId() { return $this->data['buyer_id'] ?? 0; }
    public function getSellerId() { return $this->data['seller_id'] ?? 0; }
    public function getProductId() { return $this->data['product_id'] ?? 0; }
    public function getQuantity() { return $this->data['quantity'] ?? 1; }
    public function getCreatedAt() { return $this->data['created_at'] ?? ''; }
}
?>