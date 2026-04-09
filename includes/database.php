<?php
/**
 * Database connection and base functions
 */

class Database {
    private $pdo;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if(self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // Helper methods
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($data);
    }
    
    public function update($table, $data, $where) {
        $set = '';
        foreach($data as $key => $value) {
            $set .= "$key = :$key, ";
        }
        $set = rtrim($set, ', ');
        
        $sql = "UPDATE $table SET $set WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($data);
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    public function select($table, $columns = '*', $where = '', $params = [], $order = '', $limit = '') {
        $sql = "SELECT $columns FROM $table";
        
        if($where) {
            $sql .= " WHERE $where";
        }
        
        if($order) {
            $sql .= " ORDER BY $order";
        }
        
        if($limit) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function selectOne($table, $columns = '*', $where = '', $params = []) {
        $result = $this->select($table, $columns, $where, $params, '', '1');
        return $result ? $result[0] : null;
    }
    
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as count FROM $table";
        
        if($where) {
            $sql .= " WHERE $where";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}

// Global database instance
$db = Database::getInstance();
$pdo = $db->getConnection();
?>