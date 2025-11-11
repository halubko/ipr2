<?php
class Database {
    private $host = "localhost";
    private $db_name = "ipr_2";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            die("Ошибка подключения к БД: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>