<?php
/**
 * ============================================================================
 * DATABASE CONNECTION CLASS - RAILWAY MYSQL
 * CRITICAL FIX: Forces utf8mb4_general_ci collation at connection level
 * Prevents Windows cp850_general_ci default from causing collation mismatches
 * ============================================================================
 */

class Database {
    // Railway MySQL Connection Details
    private $host = 'shinkansen.proxy.rlwy.net';
    private $db_name = 'railway';
    private $username = 'root';
    private $password = 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
    private $port = '30981';
    
    public $conn;

    /**
     * Establishes PDO connection with FORCED utf8mb4_general_ci collation
     * This overrides Windows default cp850_general_ci collation
     */
    public function connect() {
        $this->conn = null;

        try {
            // Build DSN with explicit charset
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            // PDO Options - CRITICAL: Force UTF-8 collation on connection init
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                // THIS EXECUTES IMMEDIATELY ON CONNECTION - Critical for Windows systems
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
            ];
            
            // Create PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // ADDITIONAL SAFEGUARDS: Force all charset/collation variables
            // This ensures Windows cp850 is completely overridden
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
            $this->conn->exec("SET collation_connection = 'utf8mb4_general_ci'");
            $this->conn->exec("SET character_set_client = 'utf8mb4'");
            $this->conn->exec("SET character_set_results = 'utf8mb4'");
            $this->conn->exec("SET character_set_connection = 'utf8mb4'");
            
            // Set timezone to Philippines (GMT+8)
            $this->conn->exec("SET time_zone = '+08:00'");
            date_default_timezone_set('Asia/Manila');

            // Optional: Log successful connection for debugging
            error_log("✅ Database connected successfully with utf8mb4_general_ci collation");

        } catch (PDOException $e) {
            // Set proper headers for error response
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            
            // Log the error
            error_log("❌ Database connection failed: " . $e->getMessage());
            
            // Die with error message
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}