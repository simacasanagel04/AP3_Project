<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Railway provides these environment variables automatically
        $this->host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
        $this->db_name = getenv('MYSQLDATABASE') ?: 'railway';
        $this->username = getenv('MYSQLUSER') ?: 'root';
        $this->password = getenv('MYSQLPASSWORD') ?: '';
        $this->port = getenv('MYSQLPORT') ?: '3306';
    }

    public function connect() {
        $this->conn = null;

        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to associative array
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Disable emulated prepares for better security
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            // Log error for debugging
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Show user-friendly error in production
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }

    // Optional: Method to test connection
    public function testConnection() {
        try {
            $conn = $this->connect();
            if ($conn) {
                return [
                    'status' => 'success',
                    'message' => 'Connected successfully',
                    'database' => $this->db_name,
                    'host' => $this->host
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}