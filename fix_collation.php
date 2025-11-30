<?php
require_once __DIR__ . '/config/Database.php';

$database = new Database();
$db = $database->connect();

echo "<h2>🔧 Fixing Database Collation Issues</h2>";
echo "<pre>";

try {
    // Set database to utf8mb4_general_ci
    echo "Step 1: Setting database collation...\n";
    $db->exec("ALTER DATABASE railway CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Database collation set to utf8mb4_general_ci\n\n";

    // Fix appointment table and APPT_ID column
    echo "Step 2: Fixing appointment table...\n";
    $db->exec("ALTER TABLE appointment CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "Appointment table converted\n\n";

    // Fix APPT_ID column specifically
    echo "Step 3: Fixing APPT_ID column...\n";
    $db->exec("ALTER TABLE appointment MODIFY APPT_ID VARCHAR(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
    echo "APPT_ID column fixed\n\n";

    // Fix all other tables that might have the issue
    $tables = ['doctor', 'patient', 'staff', 'users', 'service', 'specialization', 
               'payment', 'payment_method', 'payment_status', 'schedule', 'status', 'medical_record'];
    
    echo "Step 4: Converting all other tables...\n";
    foreach ($tables as $table) {
        try {
            $db->exec("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            echo "$table converted\n";
        } catch (Exception $e) {
            echo "$table: " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
    echo "===========================================\n";
    echo "COLLATION FIX COMPLETED!\n";
    echo "===========================================\n";
    echo "\nNow try booking an appointment again.\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Verify the fix
echo "<h3>Verification:</h3>";
echo "<pre>";
$check = $db->query("SELECT TABLE_NAME, TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'railway'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($check as $row) {
    echo "{$row['TABLE_NAME']}: {$row['TABLE_COLLATION']}\n";
}
echo "</pre>";
?>