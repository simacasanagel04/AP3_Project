<?php
// config/Database.php
class Database { 

    private $conn;

    public function connect() {

        // 1️⃣ CHECK IF RUNNING ON HEROKU → USE JAWSDB
        $jawsDB = getenv("JAWSDB_URL");

        if ($jawsDB) {
            $url = parse_url($jawsDB);

            $host = $url["host"];
            $username = $url["user"];
            $password = $url["pass"];
            $dbname = ltrim($url["path"], '/');
            $port = isset($url["port"]) ? $url["port"] : 3306;

            try {
                $dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";
                $this->conn = new PDO($dsn, $username, $password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                return $this->conn;

            } catch (PDOException $e) {
                die("Heroku DB Connection Failed: " . $e->getMessage());
            }
        }

        // 2️⃣ LOCALHOST CONNECTION (XAMPP)
        $host = "localhost";
        $dbname = "medical_booking";
        $username = "root";
        $password = "";

        try {
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $this->conn;

        } catch (PDOException $e) {
            die("Local DB Connection Failed: " . $e->getMessage());
        }
    }
}
?>
