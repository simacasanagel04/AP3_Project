<?php
// /classes/Status.php

class Status {
    private $conn;
    private $table_name = "status";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($status_name) {
        try {
            $sql = "INSERT INTO {$this->table_name} 
                    (status_name, STATUS_CREATED_AT, STATUS_UPDATED_AT) 
                    VALUES (:name, NOW(), NULL)";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':name' => $status_name]);
        } catch (PDOException $e) {
            error_log("Status->create Error: " . $e->getMessage());
            return false;
        }
    }

    public function all() {
        try {
            // Note: Returns lowercase column names: stat_id, status_name
            $sql = "SELECT 
                        stat_id, 
                        status_name, 
                        STATUS_CREATED_AT, 
                        STATUS_UPDATED_AT 
                    FROM {$this->table_name} 
                    ORDER BY stat_id";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Status->all Error: " . $e->getMessage());
            return [];
        }
    }

    public function update($stat_id, $new_name) {
        try {
            $sql = "UPDATE {$this->table_name} 
                    SET status_name = :new_name, 
                        STATUS_UPDATED_AT = NOW() 
                    WHERE stat_id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':new_name' => $new_name,
                ':id' => $stat_id
            ]);
        } catch (PDOException $e) {
            error_log("Status->update Error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($stat_id) {
        try {
            $sql = "DELETE FROM {$this->table_name} WHERE stat_id = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $stat_id]);
        } catch (PDOException $e) {
            error_log("Status->delete Error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * User Class
 * Handles user authentication, account management, and credential updates
 */
class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function emailExists($email) {
        $sql = "SELECT USER_ID FROM {$this->table} WHERE USER_NAME = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE USER_NAME = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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
}
?>