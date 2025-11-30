<?php
/**
 * ============================================================================
 * FILE: config/Database.php
 * PURPOSE: Database connection class
 * 
 * COLLATION FIX: Allows natural cp850 connection, queries handle collation
 * ============================================================================
 */

class Database {
    // Hardcoded connection for Railway
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
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set timezone only - let connection use its natural collation
            $this->conn->exec("SET time_zone = '+08:00'");
            date_default_timezone_set('Asia/Manila');

        } catch (PDOException $e) {
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}