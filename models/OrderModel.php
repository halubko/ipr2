<?php

class OrderModel {
    private $conn; 
    private $table_name = "orders";

    public $id;
    public $user_id;
    public $total_amount;
    public $status;
    public $created_at;


    public function __construct($db) { 
        $this->conn = $db;
    }

    private function getAllowedStatuses() {
        return ['new', 'processing', 'shipped', 'delivered', 'cancelled'];
    }


    public function create(array $data): int|false {
        $query = "INSERT INTO " . $this->table_name . 
                 " SET user_id=:user_id, total_amount=:total_amount, status=:status";

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($data['user_id'] ?? 0));
        $this->total_amount = htmlspecialchars(strip_tags($data['total_amount'] ?? 0.00));
        
        $allowed_statuses = $this->getAllowedStatuses();
        $this->status = in_array($data['status'] ?? 'new', $allowed_statuses) ? 
                        ($data['status'] ?? 'new') : 'new'; 
        
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":total_amount", $this->total_amount);
        $stmt->bindParam(":status", $this->status);

        if ($stmt->execute()) {
            return (int)$this->conn->lastInsertId();
        }
        return false;
    }

    
    public function readAll() {
        $query = "SELECT id, user_id, total_amount, status, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute()) {
             return $stmt;
        }
        return false;
    }

    public function readOne() {
        $query = "SELECT id, user_id, total_amount, status, created_at FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->user_id = $row['user_id'];
            $this->total_amount = $row['total_amount'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }


    public function update(array $data) {
        $query = "UPDATE " . $this->table_name . 
                 " SET user_id = :user_id, total_amount = :total_amount, status = :status WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->user_id = htmlspecialchars(strip_tags($data['user_id'] ?? $this->user_id));
        $this->total_amount = htmlspecialchars(strip_tags($data['total_amount'] ?? $this->total_amount));
        
        $allowed_statuses = $this->getAllowedStatuses();
        $new_status = $data['status'] ?? $this->status; 
        $this->status = in_array($new_status, $allowed_statuses) ? $new_status : $this->status;
        
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':status', $this->status);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);

        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}