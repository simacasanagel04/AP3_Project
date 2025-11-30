<?php
class Database {
    private $host = 'shinkansen.proxy.rlwy.net';
    private $db_name = 'railway';
    private $username = 'root';
    private $password = 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
    private $port = '30981';
    
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                // CRITICAL: This MUST override cp850
                PDO::MYSQL_ATTR_INIT_COMMAND => 
                    "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci';"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            $this->conn->exec("SET SESSION collation_connection = 'utf8mb4_general_ci'");
            
            // TRIPLE INSURANCE: Force it again after connection
            $this->conn->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_general_ci'");
            $this->conn->exec("SET character_set_connection = 'utf8mb4'");
            $this->conn->exec("SET collation_connection = 'utf8mb4_general_ci'");
            
            // Timezone
            $this->conn->exec("SET time_zone = '+08:00'");
            date_default_timezone_set('Asia/Manila');

        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}