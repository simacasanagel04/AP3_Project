<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Read from environment - startup script will set these
        $this->host = getenv('DB_HOST') ?: 'shinkansen.proxy.rlwy.net';
        $this->port = getenv('DB_PORT') ?: '30981';
        $this->db_name = getenv('DB_NAME') ?: 'railway';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
    }

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}