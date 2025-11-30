<?php
/**
 * ============================================================================
 * DATABASE CONNECTION CLASS - RAILWAY MYSQL
 * ============================================================================
 * 
 * COLLATION FIX APPLIED:
 * - Forces utf8mb4_general_ci collation at connection level
 * - Prevents Windows cp850_general_ci default from causing collation mismatches
 * - Works with MySQL 9.4.0 server default (utf8mb4_0900_ai_ci)
 * 
 * ISSUE RESOLVED:
 * - All VARCHAR columns in database are explicitly set to utf8mb4_general_ci
 * - Server global collation changed to utf8mb4_general_ci
 * - Connection collation forced on every PHP connection
 * ============================================================================
 */

class Database {
    // ========================================================================
    // RAILWAY MYSQL CONNECTION DETAILS
    // ========================================================================
    private $host = 'shinkansen.proxy.rlwy.net';
    private $db_name = 'railway';
    private $username = 'root';
    private $password = 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
    private $port = '30981';
    
    public $conn;

    /**
     * ========================================================================
     * ESTABLISH PDO CONNECTION WITH FORCED utf8mb4_general_ci COLLATION
     * ========================================================================
     */
    public function connect() {
        $this->conn = null;

        try {
            // ================================================================
            // STEP 1: BUILD DSN WITH EXPLICIT CHARSET
            // ================================================================
            // charset=utf8mb4 ensures 4-byte UTF-8 support (emojis, etc.)
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            
            // ================================================================
            // STEP 2: CONFIGURE PDO OPTIONS
            // ================================================================
            $options = [
                // Throw exceptions on database errors
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                
                // Return associative arrays by default
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                
                // Use real prepared statements (security)
                PDO::ATTR_EMULATE_PREPARES => false,
                
                // CRITICAL: Execute immediately on connection
                // This overrides Windows cp850_general_ci default
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_general_ci"
            ];
            
            // ================================================================
            // STEP 3: CREATE PDO CONNECTION
            // ================================================================
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // ================================================================
            // STEP 4: ADDITIONAL SAFEGUARDS - FORCE COLLATION SETTINGS
            // ================================================================
            // These commands ensure Windows cp850 is completely overridden
            // Even if INIT_COMMAND fails, these will catch it
            
            // Set character set and collation (most important)
            $this->conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
            
            // Explicitly set connection collation
            $this->conn->exec("SET collation_connection = 'utf8mb4_general_ci'");
            
            // Ensure all charset variables are utf8mb4
            $this->conn->exec("SET character_set_client = 'utf8mb4'");
            $this->conn->exec("SET character_set_results = 'utf8mb4'");
            $this->conn->exec("SET character_set_connection = 'utf8mb4'");
            
            // ================================================================
            // STEP 5: SET TIMEZONE TO PHILIPPINES (GMT+8)
            // ================================================================
            $this->conn->exec("SET time_zone = '+08:00'");
            date_default_timezone_set('Asia/Manila');

            // ================================================================
            // OPTIONAL: LOG SUCCESSFUL CONNECTION (for debugging)
            // ================================================================
            // Comment out in production to reduce log size
            error_log(" Database connected successfully with utf8mb4_general_ci collation");

        } catch (PDOException $e) {
            // ================================================================
            // ERROR HANDLING
            // ================================================================
            
            // Set proper headers for error response
            header('Content-Type: text/html; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            
            // Log the error for debugging
            error_log("Database connection failed: " . $e->getMessage());
            
            // Display user-friendly error message and stop execution
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}