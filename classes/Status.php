<?php
// classes/Status.php

class Status {
    private $conn;
    private $table_name = "status";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($status_name) {
        try {
            $sql = "INSERT INTO {$this->table_name} 
                    (STAT_NAME, STAT_CREATED_AT, STAT_UPDATED_AT) 
                    VALUES (:name, NOW(), NOW())";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':name' => $status_name]);
        } catch (PDOException $e) {
            error_log("Status->create Error: " . $e->getMessage());
            return false;
        }
    }

    public function all() {
        try {
            $sql = "SELECT 
                        STAT_ID as stat_id, 
                        STAT_NAME as status_name, 
                        STAT_CREATED_AT, 
                        STAT_UPDATED_AT 
                    FROM {$this->table_name} 
                    ORDER BY STAT_ID";
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
                    SET STAT_NAME = :new_name, 
                        STAT_UPDATED_AT = NOW() 
                    WHERE STAT_ID = :id";
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
            $sql = "DELETE FROM {$this->table_name} WHERE STAT_ID = :id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':id' => $stat_id]);
        } catch (PDOException $e) {
            error_log("Status->delete Error: " . $e->getMessage());
            return false;
        }
    }
}
?>