<?php
/**
 * ============================================================================
 * EMERGENCY STAFF DATABASE DEBUGGER
 * This will show us EXACTLY what's happening
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=UTF-8');

echo "<h1>🔍 STAFF DATABASE DEBUG</h1>";
echo "<hr>";

// ============================================================================
// TEST 1: Database Connection
// ============================================================================
echo "<h2>TEST 1: Database Connection</h2>";
try {
    require_once '../config/Database.php';
    $database = new Database();
    $db = $database->connect();
    
    if ($db instanceof PDO) {
        echo "✅ <strong>SUCCESS:</strong> Database connected!<br>";
        echo "Connection Type: " . get_class($db) . "<br>";
    } else {
        echo "❌ <strong>ERROR:</strong> Database object is not PDO<br>";
        var_dump($db);
    }
} catch (Exception $e) {
    echo "❌ <strong>FATAL ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    exit;
}

echo "<hr>";

// ============================================================================
// TEST 2: Check if STAFF table exists
// ============================================================================
echo "<h2>TEST 2: Check STAFF Table</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'staff'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ <strong>SUCCESS:</strong> 'staff' table exists<br>";
    } else {
        echo "❌ <strong>ERROR:</strong> 'staff' table NOT FOUND<br>";
        echo "Available tables:<br>";
        $stmt = $db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            echo "- " . htmlspecialchars($row[0]) . "<br>";
        }
        exit;
    }
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";

// ============================================================================
// TEST 3: Check STAFF table structure
// ============================================================================
echo "<h2>TEST 3: STAFF Table Structure</h2>";
try {
    $stmt = $db->query("DESCRIBE staff");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";

// ============================================================================
// TEST 4: Count total staff records
// ============================================================================
echo "<h2>TEST 4: Count Total Staff</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM staff");
    $result = $stmt->fetch();
    
    echo "✅ <strong>Total Staff Records:</strong> " . $result['total'] . "<br>";
    
    if ($result['total'] == 0) {
        echo "⚠️ <strong>WARNING:</strong> No staff records found in database!<br>";
    }
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";

// ============================================================================
// TEST 5: Fetch ALL staff records (RAW SQL)
// ============================================================================
echo "<h2>TEST 5: Fetch ALL Staff (Raw SQL)</h2>";
try {
    $stmt = $db->query("SELECT * FROM staff LIMIT 10");
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($staff)) {
        echo "⚠️ <strong>No staff records found</strong><br>";
    } else {
        echo "✅ <strong>Found " . count($staff) . " staff records</strong><br><br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($staff[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        foreach ($staff as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
}

echo "<hr>";

// ============================================================================
// TEST 6: Test Staff Class
// ============================================================================
echo "<h2>TEST 6: Test Staff Class</h2>";
try {
    require_once '../classes/Staff.php';
    $staff = new Staff($db);
    
    echo "✅ Staff class loaded successfully<br>";
    
    // Test readAll() without search
    echo "<h3>6A. Test readAll() - No Search</h3>";
    $allStaff = $staff->readAll();
    
    if (is_array($allStaff)) {
        echo "✅ readAll() returned array with " . count($allStaff) . " records<br>";
        
        if (!empty($allStaff)) {
            echo "<pre>" . print_r($allStaff[0], true) . "</pre>";
        }
    } else {
        echo "❌ readAll() did NOT return array<br>";
        var_dump($allStaff);
    }
    
    // Test readAll() WITH search
    echo "<h3>6B. Test readAll() - Search 'Mark'</h3>";
    $searchResults = $staff->readAll('Mark');
    
    if (is_array($searchResults)) {
        echo "✅ Search returned array with " . count($searchResults) . " records<br>";
        
        if (!empty($searchResults)) {
            echo "<pre>" . print_r($searchResults, true) . "</pre>";
        } else {
            echo "⚠️ Search returned 0 results<br>";
        }
    } else {
        echo "❌ Search did NOT return array<br>";
        var_dump($searchResults);
    }
    
    // Test with numeric search
    echo "<h3>6C. Test readAll() - Search '1' (ID)</h3>";
    $searchId = $staff->readAll('1');
    
    if (is_array($searchId)) {
        echo "✅ ID Search returned array with " . count($searchId) . " records<br>";
        
        if (!empty($searchId)) {
            echo "<pre>" . print_r($searchId, true) . "</pre>";
        } else {
            echo "⚠️ ID Search returned 0 results<br>";
        }
    } else {
        echo "❌ ID Search did NOT return array<br>";
        var_dump($searchId);
    }
    
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";

// ============================================================================
// TEST 7: Test EXACT query from readAll()
// ============================================================================
echo "<h2>TEST 7: Test Exact readAll() Query</h2>";
try {
    $searchTerm = 'Mark';
    $searchParam = "%{$searchTerm}%";
    
    $query = "SELECT 
                STAFF_ID AS staff_id,
                STAFF_FIRST_NAME AS first_name,
                STAFF_LAST_NAME AS last_name,
                STAFF_MIDDLE_INIT AS middle_init,
                STAFF_CONTACT_NUM AS phone,
                STAFF_EMAIL AS email,
                STAFF_CREATED_AT AS created_at,
                STAFF_UPDATED_AT AS updated_at 
              FROM staff
              WHERE 
                CAST(STAFF_ID AS CHAR) LIKE :search OR
                STAFF_FIRST_NAME LIKE :search OR
                STAFF_MIDDLE_INIT LIKE :search OR
                STAFF_LAST_NAME LIKE :search OR
                STAFF_CONTACT_NUM LIKE :search OR
                STAFF_EMAIL LIKE :search
              ORDER BY STAFF_ID DESC";
    
    echo "<strong>Query:</strong><br>";
    echo "<pre>" . htmlspecialchars($query) . "</pre>";
    echo "<strong>Search Parameter:</strong> " . htmlspecialchars($searchParam) . "<br><br>";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ <strong>Query executed successfully</strong><br>";
    echo "<strong>Results found:</strong> " . count($results) . "<br><br>";
    
    if (!empty($results)) {
        echo "<pre>" . print_r($results, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h2>✅ DEBUG COMPLETE</h2>";
echo "<p><a href='staff_manage.php'>← Back to Staff Management</a></p>";
?>