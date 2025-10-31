<?php
require_once __DIR__ . '/../config/Database.php';

class Staff {
    private $conn;
    private $table = "staff";

    public function __construct($db) {
        $this->conn = $db;
    }

    /** ✅ Get staff by ID */
    public function getStaffById($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE STAFF_ID = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ getStaffById Error: " . $e->getMessage());
            return false;
        }
    }

    /** ✅ Find staff by email */
    public function findByEmail($email) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE STAFF_EMAIL = :email LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ findByEmail Error: " . $e->getMessage());
            return false;
        }
    }

    /** ✅ Create new staff record */
    public function create($data) {
        try {
            $query = "INSERT INTO {$this->table} 
                      (STAFF_FIRST_NAME, STAFF_MIDDLE_INIT, STAFF_LAST_NAME, STAFF_CONTACT_NUM, STAFF_EMAIL)
                      VALUES (:STAFF_FIRST_NAME, :STAFF_MIDDLE_INIT, :STAFF_LAST_NAME, :STAFF_CONTACT_NUM, :STAFF_EMAIL)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":STAFF_FIRST_NAME", $data['STAFF_FIRST_NAME']);
            $stmt->bindParam(":STAFF_MIDDLE_INIT", $data['STAFF_MIDDLE_INIT']);
            $stmt->bindParam(":STAFF_LAST_NAME", $data['STAFF_LAST_NAME']);
            $stmt->bindParam(":STAFF_CONTACT_NUM", $data['STAFF_CONTACT_NUM']);
            $stmt->bindParam(":STAFF_EMAIL", $data['STAFF_EMAIL']);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("❌ create Error: " . $e->getMessage());
            return false;
        }
    }

    /** ✅ Read all staff (with optional search) */
    public function readAll($search = null) {
        try {
            $query = "SELECT * FROM {$this->table}";
            
            if ($search) {
                $query .= " WHERE 
                    STAFF_ID LIKE :search OR
                    STAFF_FIRST_NAME LIKE :search OR
                    STAFF_MIDDLE_INIT LIKE :search OR
                    STAFF_LAST_NAME LIKE :search OR
                    STAFF_CONTACT_NUM LIKE :search OR
                    STAFF_EMAIL LIKE :search";
            }

            $stmt = $this->conn->prepare($query);

            if ($search) {
                $searchTerm = "%{$search}%";
                $stmt->bindParam(":search", $searchTerm);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ readAll Error: " . $e->getMessage());
            return [];
        }
    }

    /** ✅ Update staff record */
    public function update($data) {
        try {
            $query = "UPDATE {$this->table}
                      SET STAFF_FIRST_NAME = :STAFF_FIRST_NAME,
                          STAFF_MIDDLE_INIT = :STAFF_MIDDLE_INIT,
                          STAFF_LAST_NAME = :STAFF_LAST_NAME,
                          STAFF_CONTACT_NUM = :STAFF_CONTACT_NUM,
                          STAFF_EMAIL = :STAFF_EMAIL
                      WHERE STAFF_ID = :STAFF_ID";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":STAFF_ID", $data['STAFF_ID']);
            $stmt->bindParam(":STAFF_FIRST_NAME", $data['STAFF_FIRST_NAME']);
            $stmt->bindParam(":STAFF_MIDDLE_INIT", $data['STAFF_MIDDLE_INIT']);
            $stmt->bindParam(":STAFF_LAST_NAME", $data['STAFF_LAST_NAME']);
            $stmt->bindParam(":STAFF_CONTACT_NUM", $data['STAFF_CONTACT_NUM']);
            $stmt->bindParam(":STAFF_EMAIL", $data['STAFF_EMAIL']);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("❌ update Error: " . $e->getMessage());
            return false;
        }
    }

    /** ✅ Read one staff record */
    public function readOne($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE STAFF_ID = :id LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("❌ readOne Error: " . $e->getMessage());
            return false;
        }
    }
}
?>
