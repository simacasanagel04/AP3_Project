<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Use custom DB_ variables for cross-project connection
        $this->host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
        $this->port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
        $this->db_name = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'railway';
        $this->username = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
    }

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}