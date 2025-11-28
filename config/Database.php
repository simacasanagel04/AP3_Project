<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Railway provides environment variables via $_ENV, $_SERVER, and getenv()
        // We need to check all three methods for maximum compatibility
        
        $this->host = $this->getEnvVar('DB_HOST') ?: 'localhost';
        $this->port = $this->getEnvVar('DB_PORT') ?: '3306';
        $this->db_name = $this->getEnvVar('DB_NAME') ?: 'railway';
        $this->username = $this->getEnvVar('DB_USER') ?: 'root';
        $this->password = $this->getEnvVar('DB_PASSWORD') ?: '';
    }

    /**
     * Get environment variable from multiple sources
     * Railway can inject vars in different ways, so we check all of them
     */
    private function getEnvVar($key) {
        // Try $_ENV first (most reliable in Railway)
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        
        // Try $_SERVER
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        
        // Try getenv() as fallback
        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }
        
        return null;
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
            // Enhanced error logging
            error_log("=== DATABASE CONNECTION ERROR ===");
            error_log("Error: " . $e->getMessage());
            error_log("Host: {$this->host}");
            error_log("Port: {$this->port}");
            error_log("Database: {$this->db_name}");
            error_log("User: {$this->username}");
            error_log("Password: " . ($this->password ? '[SET]' : '[EMPTY]'));
            
            // Show error to user
            die("Connection failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}