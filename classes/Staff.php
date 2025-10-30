<?php
class Staff {
private $pdo = null;
private $mysqli = null;

public function __construct($db = null) {
// Accept injected connection (PDO or mysqli)
if ($db instanceof PDO) {
 $this->pdo = $db;
 return;
}
if ($db instanceof mysqli) {
 $this->mysqli = $db;
 return;
}

// Fallback: create mysqli connection if none provided
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'exam';

$this->mysqli = new mysqli($host, $user, $pass, $dbname);
if ($this->mysqli->connect_error) {
 die("Connection failed: " . $this->mysqli->connect_error);
}
}
// Role-Based Access Control
private function hasAccess($role, $action) {
$accessMatrix = [
 'super_admin' => ['add', 'view', 'view_all', 'delete', 'update'],
 'staff'=> ['view', 'view_all'],
 'doctor'=> [],
 'patient'=> []
];
return isset($accessMatrix[$role]) && in_array($action, $accessMatrix[$role]);
}

// Add New Staff (Assuming the INSERT query uses the short column names, this remains UNFIXED 
    // unless you change your database schema or all other methods)
// $data: ['first_name','last_name','position','email','phone']
public function addStaff(array $data, $role = 'super_admin') {
if (!$this->hasAccess($role, 'add')) return "Access Denied";

$sql = "INSERT INTO staff (first_name, last_name, position, email, phone) VALUES (:first_name, :last_name, :position, :email, :phone)";

if ($this->pdo) {
 $stmt = $this->pdo->prepare($sql);
 return $stmt->execute([
':first_name' => $data['first_name'] ?? null,
':last_name'=> $data['last_name'] ?? null,
':position'=> $data['position'] ?? null,
':email'=> $data['email'] ?? null,
':phone'=> $data['phone'] ?? null,
 ]) ? "Staff added successfully" : "Error adding staff";
}

$stmt = $this->mysqli->prepare("INSERT INTO staff (first_name, last_name, position, email, phone) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param(
 "sssss",
 $data['first_name'],
 $data['last_name'],
 $data['position'],
 $data['email'],
 $data['phone']
);
return $stmt->execute() ? "Staff added successfully" : "Error adding staff";
}

// Search Staff by name - returns array of rows or empty array / "Access Denied"
public function searchStaff($keyword, $role = 'super_admin') {
if (!$this->hasAccess($role, 'view')) return "Access Denied";

$search = "%{$keyword}%";
        // FIX: Update SELECT statement to use aliases for compatibility
$sql = "SELECT 
            STAFF_ID AS staff_id, 
            STAFF_FIRST_NAME AS first_name, 
            STAFF_LAST_NAME AS last_name, 
            '' AS position,
            STAFF_EMAIL AS email, 
            STAFF_CONTACT_NUM AS phone 
            FROM staff 
            WHERE STAFF_FIRST_NAME LIKE :s OR STAFF_LAST_NAME LIKE :s";

if ($this->pdo) {
 $stmt = $this->pdo->prepare($sql);
 $stmt->execute([':s' => $search]);
 return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

        // FIX: Update MySQLi query and bind parameters to use actual database column names
$stmt = $this->mysqli->prepare("SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_CONTACT_NUM FROM staff WHERE STAFF_FIRST_NAME LIKE ? OR STAFF_LAST_NAME LIKE ?");
$stmt->bind_param("ss", $search, $search);
$stmt->execute();
$res = $stmt->get_result();
        // Since MySQLi doesn't support aliasing results easily, we'll manually format the rows after fetch
        $rows = $res->fetch_all(MYSQLI_ASSOC);
        return $this->formatStaffResults($rows);
}

// View All Staff - returns array of rows or "Access Denied"
public function viewAllStaff($role = 'super_admin') {
if (!$this->hasAccess($role, 'view_all')) return "Access Denied";

        // FIX: Use specific column names with aliases to match the expected keys in staff-module.php
$sql = "SELECT 
            STAFF_ID AS staff_id, 
            STAFF_FIRST_NAME AS first_name, 
            STAFF_LAST_NAME AS last_name, 
            '' AS position, 
            STAFF_EMAIL AS email, 
            STAFF_CONTACT_NUM AS phone 
            FROM staff";

if ($this->pdo) {
 $stmt = $this->pdo->query($sql);
 return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
        
        // FIX: For MySQLi, query the exact columns and manually map
$res = $this->mysqli->query("SELECT STAFF_ID, STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_EMAIL, STAFF_CONTACT_NUM FROM staff");
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        return $this->formatStaffResults($rows);
}

    // Helper function to map MySQLi results to expected keys
    private function formatStaffResults(array $rows) {
        $formatted = [];
        foreach ($rows as $row) {
            $formatted[] = [
                'staff_id' => $row['STAFF_ID'],
                'first_name' => $row['STAFF_FIRST_NAME'],
                'last_name' => $row['STAFF_LAST_NAME'],
                'position' => '', // Hardcoded, as position column is missing in DB screenshot
                'email' => $row['STAFF_EMAIL'],
                'phone' => $row['STAFF_CONTACT_NUM'],
            ];
        }
        return $formatted;
    }

// Delete Staff
public function deleteStaff($staff_id, $role = 'super_admin') {
if (!$this->hasAccess($role, 'delete')) return "Access Denied";

        // FIX: Use actual database column name in WHERE clause
if ($this->pdo) {
 $stmt = $this->pdo->prepare("DELETE FROM staff WHERE STAFF_ID = :id");
 return $stmt->execute([':id' => $staff_id]) ? "Staff deleted successfully" : "Error deleting staff";
}

        // FIX: Use actual database column name in WHERE clause
$stmt = $this->mysqli->prepare("DELETE FROM staff WHERE STAFF_ID = ?");
$stmt->bind_param("i", $staff_id);
return $stmt->execute() ? "Staff deleted successfully" : "Error deleting staff";
}


// Update Staff
// $data must include staff_id
public function updateStaff(array $data, $role = 'super_admin') {
if (!$this->hasAccess($role, 'update')) return "Access Denied";

        // This query assumes your database has 'first_name', 'last_name', etc.
        // It should be changed to use the full column names (STAFF_FIRST_NAME, etc.) if your DB supports it.
        // For PDO, we stick to the original query structure for now, assuming the database column names will be fixed later.
$sql = "UPDATE staff SET first_name = :first_name, last_name = :last_name, position = :position, email = :email, phone = :phone WHERE staff_id = :staff_id";

if ($this->pdo) {
 $stmt = $this->pdo->prepare($sql);
 return $stmt->execute([
':first_name' => $data['first_name'] ?? null,
':last_name'=> $data['last_name'] ?? null,
':position'=> $data['position'] ?? null,
':email'=> $data['email'] ?? null,
':phone'=> $data['phone'] ?? null,
':staff_id'=> $data['staff_id'] ?? null,
 ]) ? "Staff updated successfully" : "Error updating staff";
}

$stmt = $this->mysqli->prepare("UPDATE staff SET first_name = ?, last_name = ?, position = ?, email = ?, phone = ? WHERE staff_id = ?");
$stmt->bind_param(
 "sssssi",
 $data['first_name'],
 $data['last_name'],
 $data['position'],
 $data['email'],
 $data['phone'],
 $data['staff_id']
);
return $stmt->execute() ? "Staff updated successfully" : "Error updating staff";
}

public function __destruct() {
if ($this->mysqli) $this->mysqli->close();
}
}
?>