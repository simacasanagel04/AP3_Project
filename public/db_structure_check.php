<?php
/**
 * ============================================================================
 * DATABASE STRUCTURE ANALYZER
 * Shows character sets, collations, and actual data
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 RAILWAY DATABASE STRUCTURE ANALYSIS</h1>";
echo "<hr>";

try {
    require_once '../config/Database.php';
    $database = new Database();
    $db = $database->connect();
    
    // ============================================================================
    // TEST 1: Database and Table Character Set/Collation
    // ============================================================================
    echo "<h2>TEST 1: Character Set & Collation</h2>";
    
    // Check database collation
    $stmt = $db->query("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME 
                        FROM information_schema.SCHEMATA 
                        WHERE SCHEMA_NAME = 'railway'");
    $dbInfo = $stmt->fetch();
    
    echo "<strong>Database Charset:</strong> " . htmlspecialchars($dbInfo['DEFAULT_CHARACTER_SET_NAME']) . "<br>";
    echo "<strong>Database Collation:</strong> " . htmlspecialchars($dbInfo['DEFAULT_COLLATION_NAME']) . "<br><br>";
    
    // Check staff table collation
    $stmt = $db->query("SELECT TABLE_COLLATION 
                        FROM information_schema.TABLES 
                        WHERE TABLE_SCHEMA = 'railway' AND TABLE_NAME = 'staff'");
    $tableInfo = $stmt->fetch();
    
    echo "<strong>Staff Table Collation:</strong> " . htmlspecialchars($tableInfo['TABLE_COLLATION']) . "<br>";
    
    echo "<hr>";
    
    // ============================================================================
    // TEST 2: Column-level Character Set/Collation
    // ============================================================================
    echo "<h2>TEST 2: Column Character Sets</h2>";
    
    $stmt = $db->query("SELECT COLUMN_NAME, CHARACTER_SET_NAME, COLLATION_NAME 
                        FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = 'railway' 
                        AND TABLE_NAME = 'staff' 
                        AND CHARACTER_SET_NAME IS NOT NULL");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Charset</th><th>Collation</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($col['CHARACTER_SET_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($col['COLLATION_NAME']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // ============================================================================
    // TEST 3: Test LIKE search with different patterns
    // ============================================================================
    echo "<h2>TEST 3: LIKE Search Pattern Testing</h2>";
    
    // Test 1: Exact match
    echo "<h3>3A. Exact Match Test</h3>";
    $stmt = $db->prepare("SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME 
                          FROM staff 
                          WHERE STAFF_FIRST_NAME = :name");
    $stmt->execute([':name' => 'Mark']);
    $result = $stmt->fetchAll();
    echo "<strong>WHERE STAFF_FIRST_NAME = 'Mark':</strong> " . count($result) . " results<br>";
    if (!empty($result)) {
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
    
    // Test 2: LIKE without %
    echo "<h3>3B. LIKE Test (no wildcards)</h3>";
    $stmt = $db->prepare("SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME 
                          FROM staff 
                          WHERE STAFF_FIRST_NAME LIKE :name");
    $stmt->execute([':name' => 'Mark']);
    $result = $stmt->fetchAll();
    echo "<strong>WHERE STAFF_FIRST_NAME LIKE 'Mark':</strong> " . count($result) . " results<br>";
    if (!empty($result)) {
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
    
    // Test 3: LIKE with %
    echo "<h3>3C. LIKE Test (with wildcards)</h3>";
    $stmt = $db->prepare("SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME 
                          FROM staff 
                          WHERE STAFF_FIRST_NAME LIKE :name");
    $stmt->execute([':name' => '%Mark%']);
    $result = $stmt->fetchAll();
    echo "<strong>WHERE STAFF_FIRST_NAME LIKE '%Mark%':</strong> " . count($result) . " results<br>";
    if (!empty($result)) {
        echo "<pre>" . print_r($result, true) . "</pre>";
    }
    
    // Test 4: Case-insensitive search
    echo "<h3>3D. Case Sensitivity Test</h3>";
    $tests = ['mark', 'MARK', 'Mark', 'mArK'];
    foreach ($tests as $testName) {
        $stmt = $db->prepare("SELECT STAFF_ID, STAFF_FIRST_NAME 
                              FROM staff 
                              WHERE STAFF_FIRST_NAME LIKE :name");
        $stmt->execute([':name' => "%{$testName}%"]);
        $count = $stmt->rowCount();
        echo "<strong>Search '$testName':</strong> {$count} results<br>";
    }
    
    echo "<hr>";
    
    // ============================================================================
    // TEST 4: Test Multiple OR Conditions
    // ============================================================================
    echo "<h2>TEST 4: Multiple OR Conditions (Full Query Simulation)</h2>";
    
    $searchTerm = '%Mark%';
    $query = "SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME 
              FROM staff 
              WHERE 
                STAFF_FIRST_NAME LIKE :search1 OR
                STAFF_LAST_NAME LIKE :search2";
    
    echo "<strong>Query:</strong><br><pre>" . htmlspecialchars($query) . "</pre>";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':search1', $searchTerm, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll();
    
    echo "<strong>Results:</strong> " . count($results) . "<br>";
    if (!empty($results)) {
        echo "<pre>" . print_r($results, true) . "</pre>";
    }
    
    echo "<hr>";
    
    // ============================================================================
    // TEST 5: Raw Data Hex Dump
    // ============================================================================
    echo "<h2>TEST 5: Raw Data Hex Dump (Check for hidden characters)</h2>";
    
    $stmt = $db->query("SELECT STAFF_ID, 
                               STAFF_FIRST_NAME, 
                               HEX(STAFF_FIRST_NAME) as first_name_hex,
                               LENGTH(STAFF_FIRST_NAME) as first_name_length
                        FROM staff 
                        WHERE STAFF_ID IN (1, 17)");
    $hexData = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Hex</th><th>Length</th></tr>";
    foreach ($hexData as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['STAFF_ID']) . "</td>";
        echo "<td>" . htmlspecialchars($row['STAFF_FIRST_NAME']) . "</td>";
        echo "<td style='font-family: monospace;'>" . htmlspecialchars($row['first_name_hex']) . "</td>";
        echo "<td>" . htmlspecialchars($row['first_name_length']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    
    // ============================================================================
    // TEST 6: Connection Collation
    // ============================================================================
    echo "<h2>TEST 6: Current Connection Settings</h2>";
    
    $vars = [
        'character_set_client',
        'character_set_connection',
        'character_set_results',
        'collation_connection'
    ];
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    
    foreach ($vars as $var) {
        $stmt = $db->query("SHOW VARIABLES LIKE '{$var}'");
        $result = $stmt->fetch();
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($result['Variable_name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($result['Value']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; background: #ffe0e0; border: 2px solid red;'>";
    echo "<strong>ERROR:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "<hr>";
echo "<h2>✅ ANALYSIS COMPLETE</h2>";
echo "<p><a href='staff_manage.php'>← Back to Staff Management</a></p>";
?>