<?php
// test_connection.php
require_once 'config/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">🔌 Database Connection Test</h4>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            echo "<h5>Environment Variables:</h5>";
                            echo "<ul class='list-group mb-4'>";
                            echo "<li class='list-group-item'><strong>Host:</strong> " . (getenv('MYSQLHOST') ?: 'NOT SET') . "</li>";
                            echo "<li class='list-group-item'><strong>Database:</strong> " . (getenv('MYSQLDATABASE') ?: 'NOT SET') . "</li>";
                            echo "<li class='list-group-item'><strong>User:</strong> " . (getenv('MYSQLUSER') ?: 'NOT SET') . "</li>";
                            echo "<li class='list-group-item'><strong>Port:</strong> " . (getenv('MYSQLPORT') ?: 'NOT SET') . "</li>";
                            echo "<li class='list-group-item'><strong>Password:</strong> " . (getenv('MYSQLPASSWORD') ? '***SET***' : 'NOT SET') . "</li>";
                            echo "</ul>";

                            echo "<h5>PHP Extensions:</h5>";
                            echo "<ul class='list-group mb-4'>";
                            echo "<li class='list-group-item'><strong>PDO:</strong> " . (extension_loaded('pdo') ? '✅ Loaded' : '❌ Not Loaded') . "</li>";
                            echo "<li class='list-group-item'><strong>PDO_MYSQL:</strong> " . (extension_loaded('pdo_mysql') ? '✅ Loaded' : '❌ Not Loaded') . "</li>";
                            echo "<li class='list-group-item'><strong>MySQLi:</strong> " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not Loaded') . "</li>";
                            echo "</ul>";

                            $database = new Database();
                            $conn = $database->connect();

                            if ($conn) {
                                echo "<div class='alert alert-success'>";
                                echo "<h5>Connection Successful!</h5>";
                                
                                // Get database info
                                $stmt = $conn->query("SELECT DATABASE() as db, VERSION() as version");
                                $info = $stmt->fetch();
                                
                                echo "<p><strong>Connected Database:</strong> {$info['db']}</p>";
                                echo "<p><strong>MySQL Version:</strong> {$info['version']}</p>";
                                echo "</div>";

                                // Show tables
                                echo "<h5>Tables in Database:</h5>";
                                $stmt = $conn->query("SHOW TABLES");
                                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                if (count($tables) > 0) {
                                    echo "<ul class='list-group'>";
                                    foreach ($tables as $table) {
                                        echo "<li class='list-group-item'>📁 $table</li>";
                                    }
                                    echo "</ul>";
                                } else {
                                    echo "<div class='alert alert-warning'>No tables found in database yet.</div>";
                                }
                            }

                        } catch (Exception $e) {
                            echo "<div class='alert alert-danger'>";
                            echo "<h5>Connection Failed</h5>";
                            echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "</div>";
                        }
                        ?>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">← Back to Home</a>
                            <a href="public/login.php" class="btn btn-success">Go to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
