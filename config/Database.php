<?php
class Database {

    private $conn;

    public function connect() {

        // pull the Supabase DB URL from Heroku
        $databaseUrl = getenv("DATABASE_URL");

        if (!$databaseUrl) {
            die("ERROR: DATABASE_URL is missing in Heroku config.");
        }

        // parse Heroku PostgreSQL URL
        $url = parse_url($databaseUrl);

        $host = $url["host"];
        $username = $url["user"];
        $password = $url["pass"];
        $dbname = ltrim($url["path"], '/');
        $port = $url["port"] ?? 5432;

        try {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
            $this->conn = new PDO($dsn, $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $this->conn;

        } catch (PDOException $e) {
            die("SUPABASE CONNECTION FAILED: " . $e->getMessage());
        }
    }
}
?>
