<?php
// 1. Database Connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'exam';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Role-Based Access Control
function hasAccess($role, $action) {
    $accessMatrix = [
        'super_admin' => ['add', 'view', 'view_all', 'delete', 'update'],
        'staff'       => ['view', 'view_all'],
        'doctor'      => [],
        'patient'     => []
    ];
    return in_array($action, $accessMatrix[$role]);
    
}

// 3. Add New Staff
function addStaff($conn, $role, $data) {
    if (!hasAccess($role, 'add')) return "Access Denied";

    $stmt = $conn->prepare("INSERT INTO staff (first_name, last_name, position, email, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $data['first_name'], $data['last_name'], $data['position'], $data['email'], $data['phone']);
    return $stmt->execute() ? "Staff added successfully" : "Error adding staff";
}

// 4. View Staff by Name
function searchStaff($conn, $role, $name) {
    if (!hasAccess($role, 'view')) return "Access Denied";

    $search = "%{$name}%";
    $stmt = $conn->prepare("SELECT * FROM staff WHERE first_name LIKE ? OR last_name LIKE ?");
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    return $stmt->get_result();
}

// 5. View All Staff
function viewAllStaff($conn, $role) {
    if (!hasAccess($role, 'view_all')) return "Access Denied";

    return $conn->query("SELECT * FROM staff");
}

// 6. Delete Staff
function deleteStaff($conn, $role, $staff_id) {
    if (!hasAccess($role, 'delete')) return "Access Denied";

    $stmt = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
    $stmt->bind_param("i", $staff_id);
    return $stmt->execute() ? "Staff deleted successfully" : "Error deleting staff";
}

// 7. Update Staff
function updateStaff($conn, $role, $data) {
    if (!hasAccess($role, 'update')) return "Access Denied";

    $stmt = $conn->prepare("UPDATE staff SET first_name = ?, last_name = ?, position = ?, email = ?, phone = ? WHERE staff_id = ?");
    $stmt->bind_param("sssssi", $data['first_name'], $data['last_name'], $data['position'], $data['email'], $data['phone'], $data['staff_id']);
    return $stmt->execute() ? "Staff updated successfully" : "Error updating staff";
}
/*
// 8. Example Usage
$role = 'super_admin'; // Replace with session-based role
$data = [
    'staff_id'   => 1,
    'first_name' => 'Juan',
    'last_name'  => 'Dela Cruz',
    'position'   => 'Nurse',
    'email'      => 'juan@example.com',
    'phone'      => '09123456789'
]; 

// Uncomment to test each function:
// echo addStaff($conn, $role, $data);
// echo updateStaff($conn, $role, $data);
// echo deleteStaff($conn, $role, $data['staff_id']);
// $result = searchStaff($conn, $role, 'Juan');
// $result = viewAllStaff($conn, $role); */
?>
