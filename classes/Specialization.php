<?php
// /classes/Specialization.php
class Specialization {
    private $conn;
    private $table = "specialization";

    public function __construct($db) {
        $this->conn = $db;
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /** 
     * Helper function: Convert to Sentence Case 
     * e.g., "cardiology" -> "Cardiology"
     */
    private function toSentenceCase($string) {
        if (empty($string)) return $string;
        return mb_convert_case(trim($string), MB_CASE_TITLE, 'UTF-8');
    }

    // Get all specializations
    public function all() {
        try {
            $sql = "SELECT 
                        SPEC_ID, 
                        SPEC_NAME,
                        DATE_FORMAT(SPEC_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(SPEC_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at 
                    FROM {$this->table} 
                    ORDER BY SPEC_NAME ASC";
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in all(): " . $e->getMessage());
            return [];
        }
    }

    // Get all specializations with search (FIXED FOR cp850 COLLATION)
    public function readAll($search = null) {
        try {
            $sql = "SELECT 
                        SPEC_ID, 
                        SPEC_NAME,
                        DATE_FORMAT(SPEC_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(SPEC_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at 
                    FROM {$this->table}";

            // Check if search term exists and is not empty
            $hasSearch = !empty($search) && trim($search) !== '';

            if ($hasSearch) {
                // FIX: Use direct string interpolation to avoid cp850/utf8mb4 mismatch
                $searchEscaped = $this->conn->quote('%' . trim($search) . '%');
                
                $sql .= " WHERE 
                    CAST(SPEC_ID AS CHAR) LIKE {$searchEscaped} OR
                    SPEC_NAME LIKE {$searchEscaped}";
            }

            $sql .= " ORDER BY SPEC_NAME ASC";

            // Execute without parameter binding (avoids collation mismatch)
            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in readAll(): " . $e->getMessage());
            return [];
        }
    }

    // Get specialization by ID
    public function findById($spec_id) {
        try {
            $sql = "SELECT 
                        SPEC_ID, 
                        SPEC_NAME,
                        SPEC_CREATED_AT,
                        SPEC_UPDATED_AT 
                    FROM {$this->table} 
                    WHERE SPEC_ID = :spec_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':spec_id' => $spec_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in findById(): " . $e->getMessage());
            return false;
        }
    }

    // Get one specialization by ID (alias for findById)
    public function readOne($spec_id) {
        return $this->findById($spec_id);
    }

    // CREATE: Add a new specialization (WITH SENTENCE CASE)
    public function create($data) {
        $spec_name = is_array($data) ? trim($data['SPEC_NAME']) : trim($data);
        
        // Apply Sentence Case
        $spec_name = $this->toSentenceCase($spec_name);

        // Check if name already exists (Alternate Key: AK_SPEC_NAME)
        if ($this->nameExists($spec_name)) {
            return "Error: Specialization name already exists.";
        }

        $sql = "INSERT INTO {$this->table} 
                (SPEC_NAME, SPEC_CREATED_AT, SPEC_UPDATED_AT) 
                VALUES (:spec_name, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        try {
            return $stmt->execute([':spec_name' => $spec_name]);
        } catch (PDOException $e) {
            error_log("Specialization Creation Error: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE: Edit specialization name (WITH SENTENCE CASE)
    public function update($data) {
        $spec_id = is_array($data) ? $data['SPEC_ID'] : $data['spec_id'];
        $spec_name = is_array($data) ? trim($data['SPEC_NAME']) : trim($data['spec_name']);
        
        // Apply Sentence Case
        $spec_name = $this->toSentenceCase($spec_name);

        // Check if the new name exists for a DIFFERENT ID
        $sql_check = "SELECT SPEC_ID FROM {$this->table} WHERE SPEC_NAME = ? AND SPEC_ID != ? LIMIT 1";
        $stmt_check = $this->conn->prepare($sql_check);
        $stmt_check->execute([$spec_name, $spec_id]);
        if ($stmt_check->rowCount() > 0) {
            return "Error: Specialization name already exists.";
        }

        $sql = "UPDATE {$this->table} 
                SET SPEC_NAME = :spec_name, SPEC_UPDATED_AT = NOW() 
                WHERE SPEC_ID = :spec_id";
        $stmt = $this->conn->prepare($sql);
        try {
            return $stmt->execute([
                ':spec_name' => $spec_name,
                ':spec_id' => $spec_id
            ]);
        } catch (PDOException $e) {
            error_log("Specialization Update Error: " . $e->getMessage());
            return false;
        }
    }

    // DELETE: Delete a specialization
    public function delete($spec_id) {
        $sql = "DELETE FROM {$this->table} WHERE SPEC_ID = :spec_id";
        $stmt = $this->conn->prepare($sql);
        try {
            return $stmt->execute([':spec_id' => $spec_id]);
        } catch (PDOException $e) {
            // Check for FK constraint violation (doctors still linked)
            if ($e->getCode() == 23000) {
                return "Error: Cannot delete specialization. It is currently linked to one or more Doctors.";
            }
            error_log("Specialization Deletion Error: " . $e->getMessage());
            return false;
        }
    }

    // Get services by specialization - NOW USES PROPER FOREIGN KEY
    public function getServicesBySpecialization($spec_id) {
        try {
            $sql = "SELECT 
                        SERV_ID, 
                        SERV_NAME, 
                        SERV_DESCRIPTION, 
                        SERV_PRICE 
                    FROM service 
                    WHERE SPEC_ID = :spec_id 
                    ORDER BY SERV_NAME";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':spec_id' => $spec_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getServicesBySpecialization(): " . $e->getMessage());
            return [];
        }
    }

    // Read doctors grouped by specialization (for the "View doctors who specialize..." requirement)
    public function getDoctorsBySpecialization() {
        $sql = "SELECT 
                    s.SPEC_NAME, 
                    s.SPEC_ID, 
                    COUNT(d.DOC_ID) AS doctor_count 
                FROM {$this->table} s 
                LEFT JOIN doctor d ON s.SPEC_ID = d.SPEC_ID 
                GROUP BY s.SPEC_ID, s.SPEC_NAME 
                ORDER BY s.SPEC_NAME ASC";
        $stmt = $this->conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helper function to check if a name exists (for the AK)
    public function nameExists($spec_name) {
        $sql = "SELECT SPEC_ID FROM {$this->table} WHERE SPEC_NAME = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([trim($spec_name)]);
        return $stmt->rowCount() > 0;
    }
}
?>