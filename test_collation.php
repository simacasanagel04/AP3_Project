<?php
/**
 * Test if Database.php correctly sets utf8mb4_general_ci collation
 */
require_once __DIR__ . '/config/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">🔍 Database Collation Test</h4>
            </div>
            <div class="card-body">
                <?php
                try {
                    $database = new Database();
                    $conn = $database->connect();

                    echo "<div class='alert alert-success'>";
                    echo "<strong>✅ Connection Successful!</strong>";
                    echo "</div>";

                    echo "<h5 class='mt-4'>Collation Variables:</h5>";
                    echo "<table class='table table-bordered'>";
                    echo "<thead class='table-light'><tr><th>Variable</th><th>Value</th><th>Status</th></tr></thead>";
                    echo "<tbody>";

                    $stmt = $conn->query("SHOW VARIABLES LIKE 'collation%'");
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($results as $row) {
                        $expected = 'utf8mb4_general_ci';
                        $isCorrect = ($row['Value'] === $expected || $row['Variable_name'] === 'collation_server');
                        $badge = $isCorrect ? 
                            "<span class='badge bg-success'>✅ Correct</span>" : 
                            "<span class='badge bg-danger'>❌ Wrong</span>";
                        
                        $rowClass = $isCorrect ? '' : 'table-danger';
                        
                        echo "<tr class='$rowClass'>";
                        echo "<td><strong>{$row['Variable_name']}</strong></td>";
                        echo "<td>{$row['Value']}</td>";
                        echo "<td>$badge</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";

                    // Check character set variables
                    echo "<h5 class='mt-4'>Character Set Variables:</h5>";
                    echo "<table class='table table-bordered'>";
                    echo "<thead class='table-light'><tr><th>Variable</th><th>Value</th><th>Status</th></tr></thead>";
                    echo "<tbody>";

                    $stmt = $conn->query("SHOW VARIABLES LIKE 'character_set%'");
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($results as $row) {
                        // Skip filesystem and system which use different charsets
                        if (in_array($row['Variable_name'], ['character_set_filesystem', 'character_set_system'])) {
                            continue;
                        }
                        
                        $expected = 'utf8mb4';
                        $isCorrect = ($row['Value'] === $expected);
                        $badge = $isCorrect ? 
                            "<span class='badge bg-success'>✅ Correct</span>" : 
                            "<span class='badge bg-danger'>❌ Wrong</span>";
                        
                        $rowClass = $isCorrect ? '' : 'table-danger';
                        
                        echo "<tr class='$rowClass'>";
                        echo "<td><strong>{$row['Variable_name']}</strong></td>";
                        echo "<td>{$row['Value']}</td>";
                        echo "<td>$badge</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";

                    // Final verdict
                    $stmt = $conn->query("SELECT @@collation_connection as conn_collation");
                    $collation = $stmt->fetch()['conn_collation'];
                    
                    if ($collation === 'utf8mb4_general_ci') {
                        echo "<div class='alert alert-success mt-4'>";
                        echo "<h5>✅ ALL SYSTEMS GO!</h5>";
                        echo "<p>Your connection is using <strong>utf8mb4_general_ci</strong> collation.</p>";
                        echo "<p>You can now book appointments without collation errors!</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='alert alert-danger mt-4'>";
                        echo "<h5>❌ COLLATION MISMATCH!</h5>";
                        echo "<p>Connection collation is: <strong>$collation</strong></p>";
                        echo "<p>Expected: <strong>utf8mb4_general_ci</strong></p>";
                        echo "<p>Please check your Database.php file.</p>";
                        echo "</div>";
                    }

                } catch (Exception $e) {
                    echo "<div class='alert alert-danger'>";
                    echo "<strong>❌ Connection Failed:</strong> " . htmlspecialchars($e->getMessage());
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
</body>
</html>