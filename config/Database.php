<?php
/**
 * ============================================================================
 * FILE: config/Database.php
 * PURPOSE: Database connection class for Railway MySQL with cp850 collation
 * COLLATION: cp850_general_ci (DO NOT CHANGE - matches database schema)
 * ============================================================================
 */

class Database {
    // Railway connection credentials
    private $host = 'shinkansen.proxy.rlwy.net';
    private $db_name = 'railway';
    private $username = 'root';
    private $password = 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
    private $port = '30981';
    
    public $conn;

    public function connect() {
        $this->conn = null;

        try {
            // DSN WITHOUT charset - let MySQL use database default (cp850)
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false // Prevent connection pooling issues
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set character set and collation for cp850
            $this->conn->exec("SET NAMES 'cp850'");
            $this->conn->exec("SET CHARACTER SET cp850");
            $this->conn->exec("SET collation_connection = 'cp850_general_ci'");
            
            // Set timezone
            $this->conn->exec("SET time_zone = '+08:00'");
            date_default_timezone_set('Asia/Manila');

        } catch (PDOException $e) {
            // Log error for debugging
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Display user-friendly error
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            die("⚠️ Database Connection Failed. Please check your Railway MySQL service.<br><small>Error: " . htmlspecialchars($e->getMessage()) . "</small>");
        }

        return $this->conn;
    }
}