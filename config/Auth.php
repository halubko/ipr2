<?php

class Auth {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function checkApiKey() {
        $headers = getallheaders();
        $apiKey = $headers['X-API-Key'] ?? ''; 
        
        if (empty($apiKey)) {
            $this->sendUnauthorized("Missing X-API-Key in header.");
            exit();
        }

        $query = "SELECT id FROM api_keys WHERE api_key = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $apiKey);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $this->sendUnauthorized("Invalid API Key.");
            exit();
        }
          
        return true; 
    }
    
    private function sendUnauthorized($message) {
        http_response_code(401); 
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(["message" => "Unauthorized. " . $message]);
    }
}