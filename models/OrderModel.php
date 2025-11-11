<?php
class OrderModel {
    private $conn;
    private $table_name = "orders";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function readAll() {
        $query = "SELECT id, user_id, total_amount, status, created_at, updated_at FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne($id) {
        $query = "SELECT id, user_id, total_amount, status, created_at, updated_at FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($user_id, $total_amount, $status) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, total_amount, status) VALUES (:user_id, :total_amount, :status)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':total_amount', $total_amount);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $set_parts = [];
        $bind_params = [];
        
        foreach ($data as $key => $value) {
            $set_parts[] = "`{$key}` = :{$key}";
            $bind_params[":{$key}"] = $value; 
        }

        if (empty($set_parts)) {
            return 0; 
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $set_parts) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        foreach ($bind_params as $key => &$value) {
            $param_type = PDO::PARAM_STR;
            if ($key === ':user_id') {
                $param_type = PDO::PARAM_INT;
            }
            $stmt->bindParam($key, $value, $param_type);
        }

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount();
            }
            return false; 
        } catch (PDOException $e) {
            return false; 
        }
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        return false;
    }
}
?>