<?php
// classes/MedicalRecord.php

class MedicalRecord {
    private $conn;
    private $table_name = "medical_record";

    private $medrec_id;
    private $diagnosis;
    private $prescription;
    private $visit_date;
    private $created_at;
    private $appt_id;

    public function __construct($db){
        $this->conn = $db;
    }

    // Setters
    public function setMedRecId($id) { $this->medrec_id = $id; }
    public function setDiagnosis($diagnosis) { $this->diagnosis = $diagnosis; }
    public function setPrescription($prescription) { $this->prescription = $prescription; }
    public function setVisitDate($date) { $this->visit_date = $date; }
    public function setCreatedAt($datetime) { $this->created_at = $datetime; }
    public function setApptId($id) { $this->appt_id = $id; }

    // Getters
    public function getMedRecId() { return $this->medrec_id; }
    public function getDiagnosis() { return $this->diagnosis; }
    public function getPrescription() { return $this->prescription; }
    public function getVisitDate() { return $this->visit_date; }
    public function getCreatedAt() { return $this->created_at; }
    public function getApptId() { return $this->appt_id; }

    // --- Utility Methods for Module (Required by Controller logic) ---
    
    /**
     * Fetches all medical records with patient and doctor details.
     * @return array|false Returns an array of associative arrays or false on failure.
     */
    public function all() {
        try {
            $stmt = $this->readAllWithDetails();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in MedicalRecord::all(): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Fetches a single medical record by ID, including join data.
     * @param int $id The medical record ID.
     * @return array|false Returns a single associative array or false if not found.
     */
    public function get($id) {
        try {
            $stmt = $this->readAllWithDetails($id);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row : false;
        } catch (PDOException $e) {
            error_log("Error in MedicalRecord::get(): " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prepares data and calls the internal create method.
     * @param array $data Associative array with form data (appt_id, diagnosis, prescription, visit_date).
     * @return bool|string True on success, error message string on failure.
     */
    public function createRecord(array $data): bool|string {
        if (empty($data['appt_id']) || empty($data['diagnosis']) || empty($data['prescription']) || empty($data['visit_date'])) {
            return "Missing required fields.";
        }
        
        $this->setApptId($data['appt_id']);
        $this->setDiagnosis($data['diagnosis']);
        $this->setPrescription($data['prescription']);
        $this->setVisitDate($data['visit_date']);
        $this->setCreatedAt(date('Y-m-d H:i:s'));

        try {
            if ($this->create()) {
                return true;
            } else {
                return "Database execution failed.";
            }
        } catch (PDOException $e) {
            // Log the error for debugging, but return a user-friendly message
            error_log("MedicalRecord Create Error: " . $e->getMessage());
            return "Database Error: Please ensure Appointment ID is valid and exists. Error details: " . $e->getMessage();
        }
    }

    /**
     * Prepares data and calls the internal update method.
     * @param array $data Associative array with form data (medrec_id, diagnosis, prescription, visit_date).
     * @return bool|string True on success, error message string on failure.
     */
    public function updateRecord(array $data): bool|string {
        if (empty($data['medrec_id']) || empty($data['diagnosis']) || empty($data['prescription']) || empty($data['visit_date'])) {
            return "Missing required fields.";
        }
        
        $this->setMedRecId($data['medrec_id']);
        $this->setDiagnosis($data['diagnosis']);
        $this->setPrescription($data['prescription']);
        $this->setVisitDate($data['visit_date']);

        try {
            if ($this->update()) {
                return true;
            } else {
                return "Database execution failed. Record might not exist.";
            }
        } catch (PDOException $e) {
            error_log("MedicalRecord Update Error: " . $e->getMessage());
            return "Database Error: " . $e->getMessage();
        }
    }

    /**
     * Sets ID and calls the internal delete method.
     * @param int $id The medical record ID.
     * @return bool|string True on success, error message string on failure.
     */
    public function deleteRecord(int $id): bool|string {
        $this->setMedRecId($id);
        try {
            if ($this->delete()) {
                return true;
            } else {
                return "Database execution failed. Record might not exist.";
            }
        } catch (PDOException $e) {
            error_log("MedicalRecord Delete Error: " . $e->getMessage());
            return "Database Error: " . $e->getMessage();
        }
    }

    // --- Core CRUD methods (PDO Statement Executors) ---
    public function create(){
        $query = "INSERT INTO {$this->table_name} (MED_REC_DIAGNOSIS, MED_REC_PRESCRIPTION, MED_REC_VISIT_DATE, MED_REC_CREATED_AT, APPT_ID)
                  VALUES (:diagnosis, :prescription, :visit_date, :created_at, :appt_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':diagnosis', $this->diagnosis);
        $stmt->bindParam(':prescription', $this->prescription);
        $stmt->bindParam(':visit_date', $this->visit_date);
        $stmt->bindParam(':created_at', $this->created_at);
        $stmt->bindParam(':appt_id', $this->appt_id);

        if($stmt->execute()){
            $this->medrec_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll(){
        $query = "SELECT * FROM {$this->table_name} ORDER BY MED_REC_VISIT_DATE DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

   public function readAllWithDetails($medrec_id = null, $appt_id = null) {
        $query = "
            SELECT
                mr.MED_REC_ID,
                mr.MED_REC_VISIT_DATE,
                mr.MED_REC_DIAGNOSIS,
                mr.MED_REC_PRESCRIPTION,
                mr.MED_REC_CREATED_AT,
                a.APPT_ID,
                CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS PATIENT_NAME,
                CONCAT('Dr. ', d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS DOCTOR_NAME
            FROM
                medical_record mr
            JOIN
                appointment a ON mr.APPT_ID = a.APPT_ID
            JOIN
                patient p ON a.PAT_ID = p.PAT_ID
            JOIN
                doctor d ON a.DOC_ID = d.DOC_ID
        ";

        $where_clauses = [];
        $bind_params = [];

        if (!empty($medrec_id)) {
            $where_clauses[] = "mr.MED_REC_ID = :medrec_id";
            $bind_params[':medrec_id'] = $medrec_id;
        }

        if (!empty($appt_id)) {
            $where_clauses[] = "mr.APPT_ID = :appt_id";
            $bind_params[':appt_id'] = $appt_id;
        }

        if (!empty($where_clauses)) {
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $query .= " ORDER BY mr.MED_REC_VISIT_DATE DESC, mr.MED_REC_ID DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($bind_params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }

        $stmt->execute();
        return $stmt;
    }
    
    public function readOne(){
        $query = "SELECT * FROM {$this->table_name} WHERE MED_REC_ID = :medrec_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':medrec_id', $this->medrec_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row){
            $this->diagnosis = $row['MED_REC_DIAGNOSIS'];
            $this->prescription = $row['MED_REC_PRESCRIPTION'];
            $this->visit_date = $row['MED_REC_VISIT_DATE'];
            $this->created_at = $row['MED_REC_CREATED_AT'];
            $this->appt_id = $row['APPT_ID'];
            return true;
        }
        return false;
    }

    public function update(){
        $query = "UPDATE {$this->table_name}
                  SET MED_REC_DIAGNOSIS = :diagnosis, MED_REC_PRESCRIPTION = :prescription, MED_REC_VISIT_DATE = :visit_date
                  WHERE MED_REC_ID = :medrec_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':diagnosis', $this->diagnosis);
        $stmt->bindParam(':prescription', $this->prescription);
        $stmt->bindParam(':visit_date', $this->visit_date);
        $stmt->bindParam(':medrec_id', $this->medrec_id);

        return $stmt->execute();
    }

    public function delete(){
        $query = "DELETE FROM {$this->table_name} WHERE MED_REC_ID = :medrec_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':medrec_id', $this->medrec_id);
        return $stmt->execute();
    }

    public function search($keyword) {
        $keyword = '%' . $keyword . '%';
        $query = "
            SELECT
                mr.MED_REC_ID,
                mr.MED_REC_VISIT_DATE,
                mr.MED_REC_DIAGNOSIS,
                mr.MED_REC_PRESCRIPTION,
                mr.APPT_ID,
                CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) AS PATIENT_NAME,
                CONCAT('Dr. ', d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) AS DOCTOR_NAME
            FROM medical_record mr
            JOIN appointment a ON mr.APPT_ID = a.APPT_ID
            JOIN patient p ON a.PAT_ID = p.PAT_ID
            JOIN doctor d ON a.DOC_ID = d.DOC_ID
            WHERE 
                mr.APPT_ID LIKE :keyword OR
                CONCAT(p.PAT_FIRST_NAME, ' ', p.PAT_LAST_NAME) LIKE :keyword OR
                CONCAT(d.DOC_FIRST_NAME, ' ', d.DOC_LAST_NAME) LIKE :keyword OR
                mr.MED_REC_DIAGNOSIS LIKE :keyword OR
                mr.MED_REC_PRESCRIPTION LIKE :keyword
            ORDER BY mr.MED_REC_VISIT_DATE DESC
            LIMIT 100
        ";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':keyword', $keyword, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Search Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Renders the medical record creation/update form using an internal template.
     * This separates the PHP class logic (Model) from the HTML structure (View).
     * @param string $style 'plain' or 'bootstrap' to select the view template.
     */
    public static function renderForm(MedicalRecord $record, $formAction, $submitName, $submitValue, $style = 'plain') {
        // 1. PREPARE & SANITIZE DATA (Controller Logic)
        $is_update = !empty($record->getMedRecId());
        $medrec_id = $is_update ? htmlspecialchars($record->getMedRecId()) : '';
        $appt_id = $is_update ? htmlspecialchars($record->getApptId()) : '';
        $visit_date = $is_update ? htmlspecialchars($record->getVisitDate()) : date('Y-m-d');
        $diagnosis = $is_update ? htmlspecialchars($record->getDiagnosis()) : '';
        $prescription = $is_update ? htmlspecialchars($record->getPrescription()) : '';
        
        // 2. RENDER VIEW (Uses Output Buffering)
        ob_start();
        
        // --- TEMPLATE CONTENT BLOCK (Ideally in an external file like 'medical_record_form_plain.php') ---
        if ($style === 'plain'):
        ?>
            <form method='POST' action='?action=<?php echo $formAction; ?>'>
            
                <?php if ($is_update): ?>
                    <input type='hidden' name='medrec_id' value='<?php echo $medrec_id; ?>'>
                    <p><strong>Record ID:</strong> <?php echo $medrec_id; ?></p>
                    <p><strong>Appointment ID:</strong> <?php echo $appt_id; ?></p>
                <?php else: ?>
                    <p>
                        <label for='appt_id'>Appointment ID (Required):</label><br>
                        <input type='text' id='appt_id' name='appt_id' required placeholder='e.g., 2025-01-0000001'>
                    </p>
                <?php endif; ?>

                <p>
                    <label for='visit_date'>Visit Date (YYYY-MM-DD):</label><br>
                    <input type='date' id='visit_date' name='visit_date' value='<?php echo $visit_date; ?>' required>
                </p>

                <p>
                    <label for='diagnosis'>Diagnosis:</label><br>
                    <textarea id='diagnosis' name='diagnosis' rows='4' cols='50' required><?php echo $diagnosis; ?></textarea>
                </p>

                <p>
                    <label for='prescription'>Prescription:</label><br>
                    <textarea id='prescription' name='prescription' rows='4' cols='50' required><?php echo $prescription; ?></textarea>
                </p>

                <p>
                    <input type='submit' name='<?php echo $submitName; ?>' value='<?php echo $submitValue; ?>'>
                    <a href="?action=view" style="margin-left: 10px;">Cancel</a>
                </p>
            </form>
        <?php 
        // Example of a Bootstrap template block (if needed in the future)
        elseif ($style === 'bootstrap'): 
        // ... (Bootstrap HTML structure goes here)
        endif; 
        // --- END TEMPLATE CONTENT BLOCK ---

        // 3. RETURN RENDERED CONTENT
        return ob_get_clean();
    }
}
?>