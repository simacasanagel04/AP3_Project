<?php
require_once __DIR__ . '/config/Database.php';

$database = new Database();
$db = $database->connect();

echo "<h2>🔧 ULTIMATE COLLATION FIX</h2>";
echo "<pre>";

try {
    // Step 1: Set database default collation
    echo "Step 1: Setting database default collation...\n";
    $db->exec("ALTER DATABASE railway CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "✅ Database collation updated\n\n";

    // Step 2: Convert ALL tables to utf8mb4_general_ci
    echo "Step 2: Converting all tables...\n";
    $tables = ['appointment', 'doctor', 'patient', 'staff', 'users', 'service', 
               'specialization', 'payment', 'payment_method', 'payment_status', 
               'schedule', 'status', 'medical_record'];
    
    foreach ($tables as $table) {
        $db->exec("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        echo "✅ $table converted\n";
    }
    
    // Step 3: Fix APPT_ID column specifically with BINARY conversion
    echo "\nStep 3: Fixing APPT_ID column with BINARY conversion...\n";
    $db->exec("ALTER TABLE appointment MODIFY APPT_ID VARBINARY(17) NOT NULL");
    $db->exec("ALTER TABLE appointment MODIFY APPT_ID VARCHAR(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
    echo "✅ APPT_ID column fixed with BINARY method\n";
    
    // Step 4: Fix payment APPT_ID foreign key
    echo "\nStep 4: Fixing payment table APPT_ID...\n";
    $db->exec("ALTER TABLE payment MODIFY APPT_ID VARBINARY(17) NOT NULL");
    $db->exec("ALTER TABLE payment MODIFY APPT_ID VARCHAR(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL");
    echo "✅ Payment APPT_ID fixed\n";
    
    echo "\n===========================================\n";
    echo "✅ ALL COLLATION ISSUES FIXED!\n";
    echo "===========================================\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Verify
echo "<h3>Verification:</h3>";
echo "<pre>";
$verify = $db->query("SHOW FULL COLUMNS FROM appointment WHERE Field = 'APPT_ID'")->fetch();
echo "APPT_ID Collation: " . $verify['Collation'] . "\n";
$dbInfo = $db->query("SELECT @@collation_database")->fetchColumn();
echo "Database Collation: " . $dbInfo . "\n";
echo "</pre>";
?>