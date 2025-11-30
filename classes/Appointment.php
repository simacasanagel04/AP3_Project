<?php
/**
 * ============================================================================
 * FILE: classes/Appointment.php
 * PURPOSE: Appointment management class
 * ============================================================================
 */

require_once __DIR__ . "/../config/Database.php";

class Appointment {
    private $conn;
    private $table = "appointment";

    public function __construct($db) {
        $this->conn = $db;
        error_log("Appointment->__construct: DB connected: " . (is_object($this->conn) ? 'YES' : 'NO'));
    }

    /** CREATE */
    public function create($data) {
        $user_type = $_SESSION['user_type'] ?? 'unknown';
        $user_type_lower = strtolower(str_replace('_', '', $user_type));
        
        $is_superadmin = in_array($user_type_lower, ['superadmin', 'super_admin']);
        $is_staff = ($user_type_lower === 'staff');
        $is_patient = ($user_type_lower === 'patient');

        $pat_id_from_form = $data['pat_id'] ?? null;

        if ($is_patient) {
            return "Error: Patient appointments not yet implemented.";
        } elseif ($is_superadmin || $is_staff) {
            if (empty($pat_id_from_form)) {
                return "Error: Patient ID is missing. Select a patient from the form.";
            }
            $data['PAT_ID'] = $pat_id_from_form;
        } else {
            return "Error: Authentication required.";
        }

        $data['APPT_ID'] = $this->generateNewApptId();
        $data['STAT_ID'] = $data['stat_id'] ?? 1;
        $data['DOC_ID'] = $data['doc_id'] ?? null;
        $data['SERV_ID'] = $data['serv_id'] ?? null;

        if (empty($data['PAT_ID']) || empty($data['DOC_ID']) || empty($data['SERV_ID'])) {
            error_log("Appointment->create: Missing required IDs");
            return "Error: Missing required information (Patient, Doctor, or Service).";
        }

        $sql = "INSERT INTO appointment (
            APPT_ID, APPT_DATE, APPT_TIME, PAT_ID, DOC_ID, SERV_ID, STAT_ID, APPT_CREATED_AT
        ) VALUES (
            :APPT_ID, :APPT_DATE, :APPT_TIME, :PAT_ID, :DOC_ID, :SERV_ID, :STAT_ID, NOW()
        )";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':APPT_ID', $data['APPT_ID']);
            $stmt->bindValue(':APPT_DATE', $data['APPT_DATE']);
            $stmt->bindValue(':APPT_TIME', $data['APPT_TIME']);
            $stmt->bindValue(':PAT_ID', $data['PAT_ID'], PDO::PARAM_INT);
            $stmt->bindValue(':DOC_ID', $data['DOC_ID'], PDO::PARAM_INT);
            $stmt->bindValue(':SERV_ID', $data['SERV_ID'], PDO::PARAM_INT);
            $stmt->bindValue(':STAT_ID', $data['STAT_ID'], PDO::PARAM_INT);
            
            $success = $stmt->execute();

            if (!$success) {
                error_log("Appointment->create Failed: " . print_r($stmt->errorInfo(), true));
                return "Database error: " . print_r($stmt->errorInfo(), true);
            }

            error_log("Appointment->create: Success, ID: {$data['APPT_ID']}");
            return ['success' => true, 'appt_id' => $data['APPT_ID']];
        } catch (PDOException $e) {
            error_log("Appointment->create Exception: " . $e->getMessage());
            return "Database Exception: " . $e->getMessage();
        }
    }

    private function generateNewApptId() {
        $year = date('Y');
        
        $sql = "SELECT APPT_ID 
                FROM appointment 
                WHERE APPT_ID LIKE :prefix
                ORDER BY APPT_ID DESC 
                LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':prefix', $year . '-%');
        $stmt->execute();
        $lastId = $stmt->fetchColumn();
        
        $nextSequence = $lastId ? ((int)substr($lastId, 8)) + 1 : 1;
        $month = date('m');
        return $year . '-' . $month . '-' . str_pad($nextSequence, 7, '0', STR_PAD_LEFT);
    }

    /** READ ALL (with optional search) */
    public function readAll($search = null) {
        error_log("=== readAll() called with search: " . var_export($search, true) . " ===");
        
        $query = "SELECT
            a.APPT_ID, 
            a.APPT_DATE, 
            a.APPT_TIME,
            a.APPT_CREATED_AT, 
            a.APPT_UPDATED_AT,
            a.PAT_ID, 
            a.DOC_ID, 
            a.SERV_ID, 
            a.STAT_ID,
            COALESCE(p.PAT_FIRST_NAME, 'Unknown') AS patient_first_name,
            COALESCE(p.PAT_LAST_NAME, 'Patient') AS patient_last_name,
            COALESCE(d.DOC_FIRST_NAME, 'Dr.') AS doctor_first_name,
            COALESCE(d.DOC_LAST_NAME, 'Unknown') AS doctor_last_name,
            COALESCE(s.SERV_NAME, 'N/A') AS service_name,
            COALESCE(st.STAT_NAME, 'Unknown') AS status_name
        FROM appointment a
        LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
        LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
        LEFT JOIN service s ON a.SERV_ID = s.SERV_ID
        LEFT JOIN status st ON a.STAT_ID = st.STAT_ID";

        if (!empty($search) && trim($search) !== '') {
            $query .= " WHERE a.APPT_ID LIKE :search";
        }

        $query .= " ORDER BY a.APPT_CREATED_AT DESC";

        try {
            error_log("Executing query: " . $query);
            
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log("❌ Failed to prepare statement");
                error_log("PDO Error: " . print_r($this->conn->errorInfo(), true));
                return [];
            }
            
            if (!empty($search) && trim($search) !== '') {
                $like = "%" . $search . "%";
                $stmt->bindParam(':search', $like, PDO::PARAM_STR);
                error_log("Binding search parameter: " . $like);
            }
            
            $executeResult = $stmt->execute();
            
            if (!$executeResult) {
                error_log("❌ Execute failed");
                error_log("Statement Error: " . print_r($stmt->errorInfo(), true));
                return [];
            }
            
            error_log("✅ Execute succeeded");
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("✅ readAll(): Returned " . count($result) . " rows");
            
            if (!empty($result)) {
                error_log("Sample row keys: " . implode(', ', array_keys($result[0])));
            } else {
                error_log("⚠️ Query returned 0 rows");
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("❌ readAll() EXCEPTION: " . $e->getMessage());
            error_log("SQL State: " . $e->getCode());
            return [];
        }
    }

    /** READ BY PATIENT */
    public function readByPatient($pat_id, $search = null) {
        $query = "SELECT
            a.APPT_ID, 
            a.APPT_DATE, 
            a.APPT_TIME,
            a.APPT_CREATED_AT, 
            a.APPT_UPDATED_AT,
            a.PAT_ID, 
            a.DOC_ID, 
            a.SERV_ID, 
            a.STAT_ID,
            COALESCE(p.PAT_FIRST_NAME, 'Unknown') AS patient_first_name,
            COALESCE(p.PAT_LAST_NAME, 'Patient') AS patient_last_name,
            COALESCE(d.DOC_FIRST_NAME, 'Dr.') AS doctor_first_name,
            COALESCE(d.DOC_LAST_NAME, 'Unknown') AS doctor_last_name,
            COALESCE(s.SERV_NAME, 'N/A') AS service_name,
            COALESCE(st.STAT_NAME, 'Unknown') AS status_name
        FROM appointment a
        LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
        LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
        LEFT JOIN service s ON a.SERV_ID = s.SERV_ID
        LEFT JOIN status st ON a.STAT_ID = st.STAT_ID
        WHERE a.PAT_ID = :pat_id";

        if (!empty($search)) {
            $query .= " AND a.APPT_ID LIKE :search";
        }

        $query .= " ORDER BY a.APPT_CREATED_AT DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pat_id', $pat_id, PDO::PARAM_INT);
            
            if (!empty($search)) {
                $like = "%$search%";
                $stmt->bindParam(':search', $like, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("readByPatient(): Returned " . count($result) . " rows");
            return $result;
            
        } catch (PDOException $e) {
            error_log("readByPatient() ERROR: " . $e->getMessage());
            return [];
        }
    }

    public function getByPatientId($pat_id) {
        try {
            $sql = "SELECT 
                        a.APPT_ID as app_id,
                        a.APPT_DATE as app_date,
                        a.APPT_TIME as app_time,
                        a.STAT_ID as app_status,
                        a.APPT_CREATED_AT,
                        a.DOC_ID as doc_id,
                        a.SERV_ID as serv_id,
                        d.DOC_FIRST_NAME,
                        d.DOC_LAST_NAME,
                        d.DOC_MIDDLE_INIT,
                        d.SPEC_ID as spec_id,
                        s.SPEC_NAME as doc_specialization,
                        srv.SERV_NAME as service_name,
                        srv.SERV_PRICE as service_price,
                        CONCAT(d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME, ' ', COALESCE(d.DOC_MIDDLE_INIT, '')) as doctor_name,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date,
                        DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_app_time
                    FROM {$this->table} a
                    LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
                    LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
                    LEFT JOIN service srv ON a.SERV_ID = srv.SERV_ID
                    WHERE a.PAT_ID = :pat_id
                    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':pat_id' => (int)$pat_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getByPatientId(): " . $e->getMessage());
            return [];
        }
    }

    public function readOne($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE APPT_ID = :APPT_ID");
        $stmt->bindParam(':APPT_ID', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($app_id) {
        try {
            $sql = "SELECT 
                        a.*,
                        d.DOC_FIRST_NAME,
                        d.DOC_LAST_NAME,
                        d.SPEC_ID,
                        s.SPEC_NAME as doc_specialization,
                        srv.SERV_NAME as service_name,
                        p.PAT_FIRST_NAME,
                        p.PAT_LAST_NAME
                    FROM {$this->table} a
                    LEFT JOIN doctor d ON a.DOC_ID = d.DOC_ID
                    LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
                    LEFT JOIN service srv ON a.SERV_ID = srv.SERV_ID
                    LEFT JOIN patient p ON a.PAT_ID = p.PAT_ID
                    WHERE a.APPT_ID = :app_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':app_id' => $app_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in findById(): " . $e->getMessage());
            return false;
        }
    }

    public function update($data) {
        $query = "UPDATE {$this->table} SET
            APPT_DATE = :APPT_DATE, 
            APPT_TIME = :APPT_TIME,
            PAT_ID = :PAT_ID, 
            DOC_ID = :DOC_ID,
            SERV_ID = :SERV_ID, 
            STAT_ID = :STAT_ID,
            APPT_UPDATED_AT = NOW()
        WHERE APPT_ID = :APPT_ID";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':APPT_DATE', $data['APPT_DATE']);
        $stmt->bindParam(':APPT_TIME', $data['APPT_TIME']);
        $stmt->bindParam(':PAT_ID', $data['PAT_ID'], PDO::PARAM_INT);
        $stmt->bindParam(':DOC_ID', $data['DOC_ID'], PDO::PARAM_INT);
        $stmt->bindParam(':SERV_ID', $data['SERV_ID'], PDO::PARAM_INT);
        $stmt->bindParam(':STAT_ID', $data['STAT_ID'], PDO::PARAM_INT);
        $stmt->bindParam(':APPT_ID', $data['APPT_ID']);

        $success = $stmt->execute();
        error_log("Appointment->update: " . ($success ? "Success" : "Failed"));
        return $success;
    }

    public function updateStatus($data) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET STAT_ID = :stat_id,
                        APPT_UPDATED_AT = NOW()
                    WHERE APPT_ID = :app_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':app_id' => $data['app_id'],
                ':stat_id' => $data['stat_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error in updateStatus(): " . $e->getMessage());
            return false;
        }
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE APPT_ID = :APPT_ID");
        $stmt->bindParam(':APPT_ID', $id, PDO::PARAM_STR);
        $success = $stmt->execute();
        error_log("Appointment->delete: " . ($success ? "Deleted ID: $id" : "Failed"));
        return $success;
    }
}
?>