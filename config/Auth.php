<?php
class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function authenticate() {
        $headers = getallheaders();
        $apiKey = isset($headers['X-API-Key']) ? $headers['X-API-Key'] : (isset($headers['x-api-key']) ? $headers['x-api-key'] : null);
        
        if (!$apiKey) {
            $this->sendUnauthorized();
        }
        
        $query = "SELECT api_key FROM api_keys WHERE is_active = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $is_authenticated = false;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (password_verify($apiKey, $row['api_key'])) {
                $is_authenticated = true;
                break;
            }
        }

        if (!$is_authenticated) {
            $this->sendUnauthorized();
        }
    }

    private function sendUnauthorized() {
        http_response_code(401);
        echo json_encode(array("message" => "Auth error: Incorrect or missing API-key."));
        exit;
    }
}
?>