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
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Database Connection Test</h4>
                    </div>
                    <div class="card-body">
                        
                        <!-- DEBUGGING: Show all environment variable sources -->
                        <h5 class="text-danger">Debug: Environment Variables</h5>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Variable</th>
                                        <th>$_ENV</th>
                                        <th>$_SERVER</th>
                                        <th>getenv()</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $vars = ['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
                                    foreach ($vars as $var) {
                                        echo "<tr>";
                                        echo "<td><strong>{$var}</strong></td>";
                                        echo "<td>" . (isset($_ENV[$var]) ? ($_var === 'DB_PASSWORD' ? '***' : $_ENV[$var]) : '<span class="text-muted">NOT SET</span>') . "</td>";
                                        echo "<td>" . (isset($_SERVER[$var]) ? ($var === 'DB_PASSWORD' ? '***' : $_SERVER[$var]) : '<span class="text-muted">NOT SET</span>') . "</td>";
                                        echo "<td>" . (getenv($var) ? ($var === 'DB_PASSWORD' ? '***' : getenv($var)) : '<span class="text-muted">NOT SET</span>') . "</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <h5>PHP Extensions:</h5>
                        <ul class='list-group mb-4'>
                            <li class='list-group-item'><strong>PDO:</strong> <?= extension_loaded('pdo') ? 'Loaded' : 'Not Loaded' ?></li>
                            <li class='list-group-item'><strong>PDO_MYSQL:</strong> <?= extension_loaded('pdo_mysql') ? 'Loaded' : 'Not Loaded' ?></li>
                            <li class='list-group-item'><strong>MySQLi:</strong> <?= extension_loaded('mysqli') ? 'Loaded' : 'Not Loaded' ?></li>
                        </ul>

                        <hr>

                        <h5>Connection Test:</h5>
                        <?php
                        try {
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