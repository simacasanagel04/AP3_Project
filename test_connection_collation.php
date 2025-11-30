<?php
require_once __DIR__ . '/../config/Database.php';

$database = new Database();
$db = $database->connect();

$result = $db->query("SELECT 
    @@character_set_connection as charset,
    @@collation_connection as collation
")->fetch();

echo "<h1>Connection Test</h1>";
echo "Character Set: <strong>" . $result['charset'] . "</strong><br>";
echo "Collation: <strong>" . $result['collation'] . "</strong><br><br>";

if ($result['collation'] === 'utf8mb4_general_ci') {
    echo "<span style='color: green; font-size: 20px;'>✅ SUCCESS! cp850 is DEFEATED!</span>";
} else {
    echo "<span style='color: red; font-size: 20px;'>❌ FAILED! Still using: " . $result['collation'] . "</span>";
}
?>