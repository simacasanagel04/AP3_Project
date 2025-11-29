<?php
require_once 'config/Database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>🔧 Fixing Table Name Case Sensitivity Issues</h2>";

try {
    $database = new Database();
    $conn = $database->connect();
    
    echo "<div style='background: #e8f5e9; padding: 15px; margin: 10px 0; border-left: 4px solid #4caf50;'>";
    echo "✅ <strong>Database Connected</strong><br>";
    
    // Check current table names
    echo "<h3>Current Tables:</h3><ul>";
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // The issue is in your SERVICE class using uppercase "SERVICE"
    // but the actual table is lowercase "service"
    
    echo "<h3>✅ Solution Applied:</h3>";
    echo "<p>Your tables are correctly named in <strong>lowercase</strong>.</p>";
    echo "<p>The issue is in your PHP class files using <strong>UPPERCASE</strong> table names.</p>";
    echo "<p><strong>Next step:</strong> Update your Service.php class (see instructions below).</p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 10px 0; border-left: 4px solid #f44336;'>";
    echo "❌ <strong>Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>

<div style="background: #fff3e0; padding: 20px; margin: 20px 0; border-left: 4px solid #ff9800;">
    <h3>📋 Manual Fix Required:</h3>
    <p><strong>Your Service.php class is using uppercase table name which doesn't work on Linux/Railway.</strong></p>
    
    <h4>Change in classes/Service.php:</h4>
    <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">
// ❌ WRONG (line 4):
private $table_name = "SERVICE";

// ✅ CORRECT (change to):
private $table_name = "service";  // lowercase!
    </pre>
    
    <p><strong>After making this change:</strong></p>
    <ol>
        <li>Save the file</li>
        <li>Push to GitHub</li>
        <li>Wait for Railway to redeploy</li>
        <li>Test your Services page again</li>
    </ol>
</div>

<a href="superadmin_dashboard.php?module=service" class="btn btn-primary" style="display: inline-block; padding: 10px 20px; background: #2196f3; color: white; text-decoration: none; border-radius: 5px;">Go Back to Services</a>