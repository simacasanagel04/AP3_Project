<?php
// config/Database.php

class Database {
    private $conn;

    public function connect() {
        if ($this->conn !== null) {
            return $this->conn;
        }

        $databaseUrl = getenv("DATABASE_URL");

        if (!$databaseUrl) {
            die("DATABASE_URL is not set. Please add it in Heroku Config Vars.");
        }

        try {
            $url = parse_url($databaseUrl);

            $host = $url['host'];
            $username = $url['user'];
            $password = $url['pass'];
            $dbname = ltrim($url['path'], '/');
            $port = isset($url['port']) ? $url['port'] : 3306;

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            die("Database Connection Failed: " . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
