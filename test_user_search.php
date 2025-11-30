<?php
/**
 * ============================================================================
 * FILE: test_user_search.php
 * PURPOSE: Diagnose user search issues with Railway MySQL
 * PLACE THIS FILE IN YOUR PROJECT ROOT DIRECTORY
 * ACCESS: https://your-domain.com/test_user_search.php
 * ============================================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/Database.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>User Search Diagnostic</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5}";
echo ".success{color:green;background:#e8f5e9;padding:10px;margin:10px 0;border-left:4px solid green}";
echo ".error{color:red;background:#ffebee;padding:10px;margin:10px 0;border-left:4px solid red}";
echo ".info{color:#1976d2;background:#e3f2fd;padding:10px;margin:10px 0;border-left:4px solid #1976d2}";
echo ".query{background:#fff;padding:15px;margin:10px 0;border:1px solid #ddd;overflow-x:auto}";
echo "table{border-collapse:collapse;width:100%;margin:15px 0;background:#fff}";
echo "th,td{border:1px solid #ddd;padding:8px;text-align:left}";
echo "th{background:#2196f3;color:white}</style></head><body>";

echo "<h1>🔍 User Search Diagnostic Tool</h1>";
echo "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p><hr>";

// Step 1: Test Database Connection
echo "<h2>Step 1: Database Connection</h2>";
try {
    $database = new Database();
    $db = $database->connect();
    
    if ($db) {
        echo "<div class='success'>✅ Database connected successfully!</div>";
        
        // Check connection charset
        $stmt = $db->query("SELECT @@character_set_client, @@character_set_connection, @@character_set_results, @@collation_connection");
        $charset = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='info'>";
        echo "<strong>Connection Charset:</strong><br>";
        echo "Character Set Client: " . $charset['@@character_set_client'] . "<br>";
        echo "Character Set Connection: " . $charset['@@character_set_connection'] . "<br>";
        echo "Character Set Results: " . $charset['@@character_set_results'] . "<br>";
        echo "Collation Connection: " . $charset['@@collation_connection'];
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}

// Step 2: Check Users Table Structure
echo "<h2>Step 2: Users Table Structure</h2>";
try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    echo "<div class='success'>✅ Users table exists</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking users table: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 3: Count Total Users
echo "<h2>Step 3: Total User Count</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='success'>✅ Total users in database: <strong>{$count['total']}</strong></div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error counting users: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 4: Test Base Query (WITHOUT SEARCH)
echo "<h2>Step 4: Test Base Query (First 5 Users)</h2>";
$base_query = "SELECT 
    u.USER_ID, 
    u.USER_NAME, 
    u.USER_IS_SUPERADMIN, 
    u.PAT_ID, 
    u.STAFF_ID, 
    u.DOC_ID,
    CONCAT(COALESCE(p.PAT_FIRST_NAME, ''), ' ', COALESCE(p.PAT_LAST_NAME, '')) AS patient_name,
    CONCAT(COALESCE(s.STAFF_FIRST_NAME, ''), ' ', COALESCE(s.STAFF_LAST_NAME, '')) AS staff_name,
    CONCAT(COALESCE(d.DOC_FIRST_NAME, ''), ' ', COALESCE(d.DOC_LAST_NAME, '')) AS doctor_name,
    DATE_FORMAT(u.USER_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at 
FROM users u 
LEFT JOIN patient p ON u.PAT_ID = p.PAT_ID 
LEFT JOIN staff s ON u.STAFF_ID = s.STAFF_ID 
LEFT JOIN doctor d ON u.DOC_ID = d.DOC_ID
ORDER BY u.USER_ID DESC
LIMIT 5";

echo "<div class='query'><strong>Query:</strong><br><pre>" . htmlspecialchars($base_query) . "</pre></div>";

try {
    $stmt = $db->query($base_query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<div class='success'>✅ Base query returned " . count($users) . " users</div>";
        echo "<table><tr><th>USER_ID</th><th>USER_NAME</th><th>Patient Name</th><th>Staff Name</th><th>Doctor Name</th></tr>";
        foreach ($users as $u) {
            echo "<tr>";
            echo "<td>{$u['USER_ID']}</td>";
            echo "<td>{$u['USER_NAME']}</td>";
            echo "<td>{$u['patient_name']}</td>";
            echo "<td>{$u['staff_name']}</td>";
            echo "<td>{$u['doctor_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Base query returned 0 results</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Base query failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>SQL Error Code:</strong> " . $e->getCode() . "</div>";
}

// Step 5: Test SEARCH Query (Version 1 - Simple LIKE)
echo "<h2>Step 5: Test Search Query - Version 1 (Simple LIKE)</h2>";
$search_term = 'josefa';
$search_param = '%' . $search_term . '%';

$search_query_v1 = "SELECT 
    u.USER_ID, 
    u.USER_NAME, 
    u.USER_IS_SUPERADMIN, 
    u.PAT_ID, 
    u.STAFF_ID, 
    u.DOC_ID,
    CONCAT(COALESCE(p.PAT_FIRST_NAME, ''), ' ', COALESCE(p.PAT_LAST_NAME, '')) AS patient_name,
    CONCAT(COALESCE(s.STAFF_FIRST_NAME, ''), ' ', COALESCE(s.STAFF_LAST_NAME, '')) AS staff_name,
    CONCAT(COALESCE(d.DOC_FIRST_NAME, ''), ' ', COALESCE(d.DOC_LAST_NAME, '')) AS doctor_name,
    DATE_FORMAT(u.USER_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at 
FROM users u 
LEFT JOIN patient p ON u.PAT_ID = p.PAT_ID 
LEFT JOIN staff s ON u.STAFF_ID = s.STAFF_ID 
LEFT JOIN doctor d ON u.DOC_ID = d.DOC_ID
WHERE u.USER_NAME LIKE :search 
   OR CONCAT(COALESCE(p.PAT_FIRST_NAME, ''), ' ', COALESCE(p.PAT_LAST_NAME, '')) LIKE :search 
   OR CONCAT(COALESCE(s.STAFF_FIRST_NAME, ''), ' ', COALESCE(s.STAFF_LAST_NAME, '')) LIKE :search 
   OR CONCAT(COALESCE(d.DOC_FIRST_NAME, ''), ' ', COALESCE(d.DOC_LAST_NAME, '')) LIKE :search 
ORDER BY u.USER_ID DESC";

echo "<div class='query'><strong>Query:</strong><br><pre>" . htmlspecialchars($search_query_v1) . "</pre>";
echo "<strong>Search Parameter:</strong> <code>" . htmlspecialchars($search_param) . "</code></div>";

try {
    $stmt = $db->prepare($search_query_v1);
    $stmt->execute([':search' => $search_param]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "<div class='success'>✅ Search query returned " . count($results) . " results</div>";
        echo "<table><tr><th>USER_ID</th><th>USER_NAME</th><th>Patient Name</th><th>Staff Name</th><th>Doctor Name</th></tr>";
        foreach ($results as $u) {
            echo "<tr>";
            echo "<td>{$u['USER_ID']}</td>";
            echo "<td>{$u['USER_NAME']}</td>";
            echo "<td>{$u['patient_name']}</td>";
            echo "<td>{$u['staff_name']}</td>";
            echo "<td>{$u['doctor_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Search query returned 0 results</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Search query failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>SQL Error Code:</strong> " . $e->getCode() . "</div>";
    echo "<div class='info'><strong>SQL State:</strong> " . $stmt->errorInfo()[0] . "</div>";
}

// Step 6: Test SEARCH Query (Version 2 - BINARY Comparison for case-sensitivity)
echo "<h2>Step 6: Test Search Query - Version 2 (Case-Insensitive BINARY)</h2>";

$search_query_v2 = "SELECT 
    u.USER_ID, 
    u.USER_NAME
FROM users u 
LEFT JOIN patient p ON u.PAT_ID = p.PAT_ID 
LEFT JOIN staff s ON u.STAFF_ID = s.STAFF_ID 
LEFT JOIN doctor d ON u.DOC_ID = d.DOC_ID
WHERE LOWER(u.USER_NAME) LIKE LOWER(:search)
   OR LOWER(CONCAT(COALESCE(p.PAT_FIRST_NAME, ''), ' ', COALESCE(p.PAT_LAST_NAME, ''))) LIKE LOWER(:search)
   OR LOWER(CONCAT(COALESCE(s.STAFF_FIRST_NAME, ''), ' ', COALESCE(s.STAFF_LAST_NAME, ''))) LIKE LOWER(:search)
   OR LOWER(CONCAT(COALESCE(d.DOC_FIRST_NAME, ''), ' ', COALESCE(d.DOC_LAST_NAME, ''))) LIKE LOWER(:search)
ORDER BY u.USER_ID DESC";

echo "<div class='query'><strong>Query:</strong><br><pre>" . htmlspecialchars($search_query_v2) . "</pre></div>";

try {
    $stmt = $db->prepare($search_query_v2);
    $stmt->execute([':search' => $search_param]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) > 0) {
        echo "<div class='success'>✅ Case-insensitive search returned " . count($results) . " results</div>";
        echo "<table><tr><th>USER_ID</th><th>USER_NAME</th></tr>";
        foreach ($results as $u) {
            echo "<tr><td>{$u['USER_ID']}</td><td>{$u['USER_NAME']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ Case-insensitive search returned 0 results</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Case-insensitive search failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 7: Check specific user exists
echo "<h2>Step 7: Check if 'josefa' user exists</h2>";
try {
    $stmt = $db->query("SELECT USER_ID, USER_NAME, PAT_ID FROM users WHERE USER_NAME LIKE '%josefa%'");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<div class='success'>✅ Found user: ID={$user['USER_ID']}, NAME={$user['USER_NAME']}, PAT_ID={$user['PAT_ID']}</div>";
        
        // Check patient record
        if ($user['PAT_ID']) {
            $stmt2 = $db->prepare("SELECT PAT_FIRST_NAME, PAT_LAST_NAME FROM patient WHERE PAT_ID = ?");
            $stmt2->execute([$user['PAT_ID']]);
            $patient = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($patient) {
                echo "<div class='success'>✅ Patient record found: {$patient['PAT_FIRST_NAME']} {$patient['PAT_LAST_NAME']}</div>";
            } else {
                echo "<div class='error'>❌ Patient record NOT found for PAT_ID={$user['PAT_ID']}</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ No user found with 'josefa' in username</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 8: Test User Class
echo "<h2>Step 8: Test User Class</h2>";
try {
    require_once 'classes/User.php';
    $user_obj = new User($db);
    
    echo "<div class='info'>Testing User->all() method...</div>";
    $all_users = $user_obj->all();
    echo "<div class='success'>✅ User->all() returned " . count($all_users) . " users</div>";
    
    echo "<div class='info'>Testing User->search('josefa') method...</div>";
    $search_results = $user_obj->search('josefa');
    echo "<div class='success'>✅ User->search('josefa') returned " . count($search_results) . " results</div>";
    
    if (count($search_results) > 0) {
        echo "<table><tr><th>USER_ID</th><th>USER_NAME</th><th>User Type</th></tr>";
        foreach ($search_results as $u) {
            echo "<tr><td>{$u['USER_ID']}</td><td>{$u['USER_NAME']}</td><td>{$u['user_type']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ User class test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div class='info'><strong>Stack Trace:</strong><br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></div>";
}

echo "<hr><h2>✅ Diagnostic Complete</h2>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If all tests pass ✅, the issue is in user-module.php error handling</li>";
echo "<li>If search tests fail ❌, the issue is with the query or data encoding</li>";
echo "<li>If connection fails ❌, check Railway MySQL credentials</li>";
echo "</ol>";

echo "</body></html>";
?>