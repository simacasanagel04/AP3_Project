<?php
require_once __DIR__ . '/../config/Database.php';

class Staff {
    private $conn;
    private $table = "staff"; 

    // Add public properties for easy access
    public $STAFF_ID;
    public $STAFF_FIRST_NAME;
    public $STAFF_LAST_NAME;
    public $STAFF_MIDDLE_INIT;
    public $STAFF_CONTACT_NUM;
    public $STAFF_EMAIL;

    public function __construct($db) {
        $this->conn = $db;
    }

    /** Get staff by ID */
    public function getStaffById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE STAFF_ID = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("getStaffById Error: " . $e->getMessage());
            return false;
        }
    }

    /** Find staff by email */
    public function findByEmail($email) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE STAFF_EMAIL = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("findByEmail Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a new Staff record.
     * @return int|string The new STAFF_ID on success, or an error message string on failure.
     */
    public function create($data, $user_type = null) {
        // Only Super Admin can add staff, but included $user_type check for security
        if ($user_type !== 'super_admin') {
            return "Access denied: Only Super Admin can add staff.";
        }

        // Check for required fields
        $required = ['first_name', 'last_name', 'phone', 'email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return "Missing required field: " . $field;
            }
        }

        // Ensure STAFF_EMAIL is unique (Alternate Key from ERD)
        if ($this->emailExists($data['email'])) {
            return "The email address is already registered as a staff member.";
        }

        // Use correct, case-sensitive column names
        $sql = "INSERT INTO {$this->table} 
                (STAFF_FIRST_NAME, STAFF_LAST_NAME, STAFF_MIDDLE_INIT, STAFF_CONTACT_NUM, STAFF_EMAIL, STAFF_CREATED_AT, STAFF_UPDATED_AT) 
                VALUES (:first_name, :last_name, :middle_init, :phone, :email, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        try {
            $stmt->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':middle_init' => $data['middle_init'] ?? null,
                ':phone' => $data['phone'],
                ':email' => $data['email']
            ]);
            return $this->conn->lastInsertId();
        } catch (PDOException $e) {
            return "Staff creation failed: " . $e->getMessage();
        }
    }

    /** Read all staff (with optional search) */
    public function readAll($search = null) {
        try {
            // Base query with explicit column mapping
            $query = "SELECT 
                        STAFF_ID AS staff_id,
                        STAFF_FIRST_NAME AS first_name,
                        STAFF_LAST_NAME AS last_name,
                        STAFF_MIDDLE_INIT AS middle_init,
                        STAFF_CONTACT_NUM AS phone,
                        STAFF_EMAIL AS email,
                        STAFF_CREATED_AT AS created_at,
                        STAFF_UPDATED_AT AS updated_at 
                    FROM " . $this->table;

            // Check if search term exists and is not empty
            $hasSearch = !empty($search) && trim($search) !== '';

            // Add WHERE clause only if search exists
            if ($hasSearch) {
                $query .= " WHERE 
                    CONCAT(STAFF_ID, '') LIKE :search OR
                    STAFF_FIRST_NAME LIKE :search OR
                    COALESCE(STAFF_MIDDLE_INIT, '') LIKE :search OR
                    STAFF_LAST_NAME LIKE :search OR
                    STAFF_CONTACT_NUM LIKE :search OR
                    COALESCE(STAFF_EMAIL, '') LIKE :search";
            }

            $query .= " ORDER BY STAFF_ID DESC";

            // Prepare statement
            $stmt = $this->conn->prepare($query);

            // Bind parameter ONLY if search exists
            if ($hasSearch) {
                $searchTerm = "%" . trim($search) . "%";
                $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
            }

            // Execute query
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log for debugging
            error_log("Staff readAll - Search: '" . ($search ?? 'NONE') . "', Results: " . count($results));
            
            return $results;
            
        } catch (PDOException $e) {
            error_log("Staff readAll Error: " . $e->getMessage());
            error_log("Query: " . ($query ?? 'N/A'));
            error_log("Search term: " . ($search ?? 'N/A'));
            return [];
        }
    }

    /**
     * Update an existing Staff record.
     * This method also needs to return a string for consistency in the module display.
     */
    public function update($data, $user_type = null) {
        if ($user_type !== 'super_admin') {
            return "Access denied: Only Super Admin can update staff.";
        }

        if (empty($data['staff_id'])) {
            return "Staff ID is required for update.";
        }

        $sql = "UPDATE {$this->table} 
                SET STAFF_FIRST_NAME = :first_name, 
                    STAFF_LAST_NAME = :last_name, 
                    STAFF_MIDDLE_INIT = :middle_init, 
                    STAFF_CONTACT_NUM = :phone, 
                    STAFF_EMAIL = :email, 
                    STAFF_UPDATED_AT = NOW() 
                WHERE STAFF_ID = :staff_id";
        $stmt = $this->conn->prepare($sql);
        try {
            $stmt->execute([
                ':first_name' => $data['first_name'],
                ':last_name' => $data['last_name'],
                ':middle_init' => $data['middle_init'] ?? null,
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':staff_id' => $data['staff_id']
            ]);
            return "Staff ID: {$data['staff_id']} updated successfully.";
        } catch (PDOException $e) {
            return "Staff update failed: " . $e->getMessage();
        }
    }

    /** Update staff profile (for staff editing their own profile) */
    public function updateProfile() {
        try {
            $query = "UPDATE {$this->table} 
                      SET STAFF_FIRST_NAME = :first_name,
                          STAFF_MIDDLE_INIT = :middle_init,
                          STAFF_LAST_NAME = :last_name,
                          STAFF_CONTACT_NUM = :contact_num,
                          STAFF_EMAIL = :email,
                          STAFF_UPDATED_AT = NOW()
                      WHERE STAFF_ID = :staff_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':staff_id', $this->STAFF_ID);
            $stmt->bindParam(':first_name', $this->STAFF_FIRST_NAME);
            $stmt->bindParam(':middle_init', $this->STAFF_MIDDLE_INIT);
            $stmt->bindParam(':last_name', $this->STAFF_LAST_NAME);
            $stmt->bindParam(':contact_num', $this->STAFF_CONTACT_NUM);
            $stmt->bindParam(':email', $this->STAFF_EMAIL);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updateProfile Error: " . $e->getMessage());
            return false;
        }
    }

    /** Update staff password */
    public function updatePassword($staff_id, $hashed_password) {
        try {
            $query = "UPDATE users 
                      SET PASSWORD = :password, 
                          USER_UPDATED_AT = NOW() 
                      WHERE STAFF_ID = :staff_id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("updatePassword Error: " . $e->getMessage());
            return false;
        }
    }

    /** Get staff password from users table */
    public function getStaffPassword($staff_id) {
        try {
            $query = "SELECT PASSWORD FROM users WHERE STAFF_ID = :staff_id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':staff_id', $staff_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['PASSWORD'] : false;
        } catch (PDOException $e) {
            error_log("getStaffPassword Error: " . $e->getMessage());
            return false;
        }
    }

    /** Read one staff record */
    public function readOne($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE STAFF_ID = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("readOne Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a Staff record and their linked User account.
     */
    public function deleteStaff($staff_id, $user_type) {
        if ($user_type !== 'super_admin') {
            return "Access denied: Only Super Admin can delete staff.";
        }

        // 1. Delete linked user account (Crucial for data integrity)
        $user = new User($this->conn); // Assumes User class exists
        $user->deleteLinkedAccount($staff_id, 'staff');

        // 2. Delete staff record
        $sql = "DELETE FROM {$this->table} WHERE STAFF_ID = :id";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return "Database Error on Prepare.";
        }
        if ($stmt->execute([':id' => $staff_id])) {
            return "Staff ID: {$staff_id} and linked user account deleted successfully.";
        } else {
            return "Failed to delete staff ID: {$staff_id}.";
        }
    }

    /** View all staff records. */
    public function viewAllStaff($user_type) {
        if ($user_type !== 'super_admin') {
            return "Access denied: Cannot view staff records.";
        }

        $sql = "SELECT 
                    STAFF_ID AS staff_id, 
                    STAFF_FIRST_NAME AS first_name, 
                    STAFF_LAST_NAME AS last_name, 
                    STAFF_MIDDLE_INIT AS middle_init, 
                    STAFF_CONTACT_NUM AS phone, 
                    STAFF_EMAIL AS email, 
                    STAFF_CREATED_AT AS created_at, 
                    STAFF_UPDATED_AT AS updated_at 
                FROM {$this->table} 
                ORDER BY staff_id DESC";
        $stmt = $this->conn->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /** Search staff records by name. */
    public function searchStaff($search_term, $user_type) {
        if ($user_type !== 'super_admin') {
            return "Access denied: Cannot search staff records.";
        }

        $search = "%" . $search_term . "%";
        $sql = "SELECT 
                    STAFF_ID AS staff_id, 
                    STAFF_FIRST_NAME AS first_name, 
                    STAFF_LAST_NAME AS last_name, 
                    STAFF_MIDDLE_INIT AS middle_init, 
                    STAFF_CONTACT_NUM AS phone, 
                    STAFF_EMAIL AS email, 
                    STAFF_CREATED_AT AS created_at, 
                    STAFF_UPDATED_AT AS updated_at 
                FROM {$this->table} 
                WHERE STAFF_FIRST_NAME LIKE ? OR STAFF_LAST_NAME LIKE ? OR STAFF_EMAIL LIKE ? 
                ORDER BY staff_id DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$search, $search, $search]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if the STAFF_EMAIL already exists in the STAFF table.
     */
    private function emailExists($email) {
        $sql = "SELECT STAFF_ID FROM {$this->table} WHERE STAFF_EMAIL = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }
}
?>