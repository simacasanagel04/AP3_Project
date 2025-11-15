<?php
// /classes/User.php

class User {
    private $conn;
    private $table = "users";
    private $table_doctor = "doctor";
    private $table_staff = "staff";
    private $table_patient = "patient";

    public function __construct($db) {
        $this->conn = $db;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Helper method to determine user type based on FKs
    public function getUserType($user) {
        if ($user['USER_IS_SUPERADMIN'] == 1) {
            return 'Super Admin';
        } elseif ($user['DOC_ID'] !== null) {
            return 'Doctor';
        } elseif ($user['STAFF_ID'] !== null) {
            return 'Staff';
        } elseif ($user['PAT_ID'] !== null) {
            return 'Patient';
        } else {
            return 'Unassigned';
        }
    }

    // Helper method for the base query (used by all read methods)
    private function getBaseUserQuery() {
        return "SELECT 
                    u.USER_ID, 
                    u.USER_NAME, 
                    u.USER_IS_SUPERADMIN, 
                    u.PAT_ID, 
                    u.STAFF_ID, 
                    u.DOC_ID,
                    CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS patient_name,
                    CONCAT(s.STAFF_FIRST_NAME, ' ', s.STAFF_LAST_NAME) AS staff_name,
                    CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS doctor_name,
                    DATE_FORMAT(u.USER_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at 
                FROM {$this->table} u 
                LEFT JOIN {$this->table_patient} p ON u.PAT_ID = p.PAT_ID 
                LEFT JOIN {$this->table_staff} s ON u.STAFF_ID = s.STAFF_ID 
                LEFT JOIN {$this->table_doctor} d ON u.DOC_ID = d.DOC_ID";
    }

    // Check if a username already exists
    public function emailExists($email) {
        $sql = "SELECT USER_ID FROM {$this->table} WHERE USER_NAME = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    // Find user by username
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE USER_NAME = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- DASHBOARD READ METHODS ---
    // Read ALL users
    public function all() {
        try {
            $sql = $this->getBaseUserQuery() . " ORDER BY u.USER_ID DESC";
            $stmt = $this->conn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$user) {
                $user['user_type'] = $this->getUserType($user);
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching all users: " . $e->getMessage());
            return [];
        }
    }

    // Read users filtered by type (doctor, patient, staff, superadmin)
    public function allByType($type) {
        $whereClause = "";
        // This switch statement is the fix. We only check for the presence of the key,
        // and always exclude superadmins.
        switch ($type) {
            case 'doctor':
                $whereClause = "u.DOC_ID IS NOT NULL AND u.USER_IS_SUPERADMIN = 0";
                break;
            case 'staff':
                $whereClause = "u.STAFF_ID IS NOT NULL AND u.USER_IS_SUPERADMIN = 0";
                break;
            case 'patient':
                $whereClause = "u.PAT_ID IS NOT NULL AND u.USER_IS_SUPERADMIN = 0";
                break;
            case 'superadmin':
                $whereClause = "u.USER_IS_SUPERADMIN = 1";
                break;
            default:
                $whereClause = "u.USER_IS_SUPERADMIN = 0";
                break;
        }

        try {
            $sql = $this->getBaseUserQuery() . " WHERE " . $whereClause . " ORDER BY u.USER_ID DESC";
            $stmt = $this->conn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // The loop below correctly assigns the final user_type for display based on priority
            foreach ($results as &$user) {
                $user['user_type'] = $this->getUserType($user);
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Error fetching users by type: " . $e->getMessage());
            return [];
        }
    }

    // Search users by username or linked name
    public function search($searchTerm) {
        try {
            $searchParam = '%' . trim($searchTerm) . '%';
            $sql = $this->getBaseUserQuery() . " 
                    WHERE u.USER_NAME LIKE :search 
                       OR CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) LIKE :search 
                       OR CONCAT(s.STAFF_FIRST_NAME, ' ', s.STAFF_LAST_NAME) LIKE :search 
                       OR CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) LIKE :search 
                    ORDER BY u.USER_ID DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':search' => $searchParam]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$user) {
                $user['user_type'] = $this->getUserType($user);
            }
            return $results;
        } catch (PDOException $e) {
            error_log("Error searching users: " . $e->getMessage());
            return [];
        }
    }

    // DELETE by USER_ID (Used by 'Revoke Access' in user-module.php)
    public function delete($user_id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE USER_ID = :user_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':user_id' => $user_id]);
        } catch (PDOException $e) {
            error_log("Error deleting user record: " . $e->getMessage());
            return false;
        }
    }

    // --- CREDENTIALS & ACCOUNT MANAGEMENT ---
    public function create($data) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (USER_NAME, PASSWORD, PAT_ID, DOC_ID, STAFF_ID, USER_CREATED_AT, USER_UPDATED_AT, USER_IS_SUPERADMIN) 
                    VALUES (:user_name, :password, :pat_id, :doc_id, :staff_id, NOW(), NOW(), 0)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':user_name' => $data['user_name'],
                ':password' => $data['password'],
                ':pat_id' => $data['pat_id'] ?? null,
                ':doc_id' => $data['doc_id'] ?? null,
                ':staff_id' => $data['staff_id'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }

    // Add user account for Staff, Doctor, or Patient with linked record ID
    public function addLinkedAccount($data) {
        $fields = [
            'staff' => 'STAFF_ID',
            'doctor' => 'DOC_ID',
            'patient' => 'PAT_ID'
        ];
        $linked_id = null;
        $user_type = $data['user_type'] ?? 'patient';

        if (isset($data['linked_id'])) {
            $linked_id = (int)$data['linked_id'];
        } elseif (isset($data['doc_id'])) {
            $linked_id = (int)$data['doc_id'];
            $user_type = 'doctor';
        } elseif (isset($data['staff_id'])) {
            $linked_id = (int)$data['staff_id'];
            $user_type = 'staff';
        } elseif (isset($data['pat_id'])) {
            $linked_id = (int)$data['pat_id'];
            $user_type = 'patient';
        }

        $linked_column = $fields[$user_type] ?? 'PAT_ID';

        if (empty($linked_id) || $linked_id <= 0) {
            return "Missing linked ID for user creation.";
        }

        if ($this->emailExists($data['user_name'])) {
            return "Username already exists.";
        }

        $sql = "INSERT INTO {$this->table} 
                (USER_NAME, PASSWORD, {$linked_column}, USER_CREATED_AT, USER_IS_SUPERADMIN) 
                VALUES (:user_name, :password, :linked_id, NOW(), 0)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === FALSE) {
            return "Database Error (Prepare Failed): " . print_r($this->conn->errorInfo(), true);
        }

        $hashed = $data['password']; // Already hashed externally
        try {
            $success = $stmt->execute([
                ':user_name' => $data['user_name'],
                ':password' => $hashed,
                ':linked_id' => $linked_id
            ]);
        } catch (PDOException $e) {
            error_log("User Creation Error: " . $e->getMessage());
            return "Database Insertion Failed: " . $e->getMessage();
        }

        return $success ? true : "Failed to create user account (Execution failed).";
    }

    // Delete a user linked to a specific staff/doctor/patient
    public function deleteLinkedAccount($linked_id, $user_type = 'patient') {
        $fields = [
            'staff' => 'STAFF_ID',
            'doctor' => 'DOC_ID',
            'patient' => 'PAT_ID'
        ];
        $linked_column = $fields[$user_type] ?? 'PAT_ID';
        $sql = "DELETE FROM {$this->table} WHERE {$linked_column} = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':id' => $linked_id]);
    }

    public function updateCredentials($user_id, $email, $new_password = '') {
        $sql = "UPDATE {$this->table} SET USER_NAME = ?, USER_UPDATED_AT = NOW()";
        $params = [$email];
        if (!empty($new_password)) {
            $sql .= ", PASSWORD = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE USER_ID = ?";
        $params[] = $user_id;
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function updatePatientCredentials($pat_id, $email, $new_password = '') {
        $sql = "UPDATE {$this->table} SET USER_NAME = ?, USER_UPDATED_AT = NOW()";
        $params = [$email];
        if (!empty($new_password)) {
            $sql .= ", PASSWORD = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE PAT_ID = ?";
        $params[] = $pat_id;
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateDoctorCredentials($doc_id, $email, $new_password = '') {
        $sql = "UPDATE {$this->table} SET USER_NAME = ?, USER_UPDATED_AT = NOW()";
        $params = [$email];
        if (!empty($new_password)) {
            $sql .= ", PASSWORD = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE DOC_ID = ?";
        $params[] = $doc_id;
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function updateStaffCredentials($staff_id, $email, $new_password = '') {
        $sql = "UPDATE {$this->table} SET USER_NAME = ?, USER_UPDATED_AT = NOW()";
        $params = [$email];
        if (!empty($new_password)) {
            $sql .= ", PASSWORD = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        }
        $sql .= " WHERE STAFF_ID = ?";
        $params[] = $staff_id;
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteByPatientId($pat_id) {
        $sql = "DELETE FROM {$this->table} WHERE PAT_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$pat_id]);
    }

    public function deleteByDoctorId($doc_id) {
        $sql = "DELETE FROM {$this->table} WHERE DOC_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$doc_id]);
    }

    public function deleteByStaffId($staff_id) {
        $sql = "DELETE FROM {$this->table} WHERE STAFF_ID = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$staff_id]);
    }

    public function updateLastLogin($user_id) {
        try {
            $sql = "UPDATE {$this->table} SET USER_LAST_LOGIN = NOW() WHERE USER_ID = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Error updating last login: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE method (for other modules, not used in this dashboard)
    public function update($data) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET USER_NAME = :username, 
                        PASSWORD = :password_hash, 
                        USER_IS_SUPERADMIN = :is_superadmin, 
                        USER_UPDATED_AT = NOW() 
                    WHERE USER_ID = :user_id";
            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                ':user_id' => $data['USER_ID'],
                ':username' => $data['USER_NAME'],
                ':password_hash' => $data['PASSWORD'],
                ':is_superadmin' => $data['USER_IS_SUPERADMIN']
            ]);
            return $success;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                return "Error: Username (Email) already exists.";
            }
            error_log("Error updating user record: " . $e->getMessage());
            return false;
        }
    }
}
?>