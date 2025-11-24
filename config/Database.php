<?php
// config/Database.php

class Database {
    private $conn;

    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        // 1️⃣ Check Heroku DATABASE_URL first
        $databaseUrl = getenv('DATABASE_URL');

        if ($databaseUrl) {
            // Parse DATABASE_URL (Heroku format)
            $url = parse_url($databaseUrl);
            $host = $url['host'] ?? 'mysql.railway.internal';
            $port = $url['port'] ?? 3306;
            $dbname = ltrim($url['path'] ?? 'railway', '/');
            $username = $url['user'] ?? 'root';
            $password = $url['pass'] ?? 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
        } else {
            // 2️⃣ Fallback to Railway environment variables
            $host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
            $port = getenv('MYSQLPORT') ?: 3306;
            $dbname = getenv('MYSQLDATABASE') ?: 'railway';
            $username = getenv('MYSQLUSER') ?: 'root';
            $password = getenv('MYSQLPASSWORD') ?: 'BJscjrBkAzQTWQlFnMNuuWjHxYUirDeh';
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        try {
            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
