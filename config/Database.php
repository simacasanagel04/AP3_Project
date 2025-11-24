<?php
// config/Database.php

class Database {
    private $conn;

    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        // 1️⃣ Try Heroku DATABASE_URL first (public host)
        $databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_PUBLIC_URL');

        if ($databaseUrl) {
            $url = parse_url($databaseUrl);
            $host = $url['host'] ?? 'shinkansen.proxy.rlwy.net';
            $port = $url['port'] ?? 3306;
            $dbname = ltrim($url['path'] ?? 'railway', '/');
            $username = $url['user'] ?? 'root';
            $password = $url['pass'] ?? '';
        } else {
            // Fallback to Railway internal variables
            $host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
            $port = getenv('MYSQLPORT') ?: 3306;
            $dbname = getenv('MYSQLDATABASE') ?: 'railway';
            $username = getenv('MYSQLUSER') ?: 'root';
            $password = getenv('MYSQLPASSWORD') ?: '';
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        // PDO SSL options for public host
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // skip verification
            PDO::MYSQL_ATTR_SSL_CA => null,                  // no CA file, Railway public allows skip
        ];

        try {
            $this->conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
