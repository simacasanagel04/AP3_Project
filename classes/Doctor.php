<?php
// classes/Doctor.php
class Doctor {
    private $conn;
    private $table_doctor = "doctor";
    private $table_patient = "patient";
    private $table_appointment = "appointment";
    private $table_specialization = "specialization";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Search by ID - Returns doctor data with row number and appointments
    public function findById($doc_id) {
        try {
            // Get row number
            $sqlRowNum = "SELECT COUNT(*) as row_num FROM {$this->table_doctor} WHERE DOC_ID <= :doc_id ORDER BY DOC_ID";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':doc_id' => trim($doc_id)]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get doctor data
            $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_MIDDLE_INIT, d.DOC_LAST_NAME,
                           d.DOC_CONTACT_NUM, d.DOC_EMAIL, d.SPEC_ID,
                           d.DOC_CREATED_AT, d.DOC_UPDATED_AT,
                           s.SPEC_NAME,
                           COUNT(a.APPT_ID) as total_appointments,
                           DATE_FORMAT(d.DOC_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                           DATE_FORMAT(d.DOC_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.SPEC_ID = s.SPEC_ID
                    LEFT JOIN {$this->table_appointment} a ON d.DOC_ID = a.DOC_ID
                    WHERE d.DOC_ID = :doc_id
                    GROUP BY d.DOC_ID";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':doc_id' => trim($doc_id)]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($doctor) {
                // Convert to lowercase for consistency with existing code
                $doctor = $this->convertKeysToLowercase($doctor);
                $doctor['row_number'] = $rowData['row_num'];
                return $doctor;
            }

            return 0;
        } catch (PDOException $e) {
            error_log("Error finding doctor: " . $e->getMessage());
            return 0;
        }
    }

    // Display all doctors
    public function all() {
        try {
            $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_MIDDLE_INIT, d.DOC_LAST_NAME,
                           d.DOC_CONTACT_NUM, d.DOC_EMAIL, d.SPEC_ID,
                           s.SPEC_NAME,
                           DATE_FORMAT(d.DOC_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                           DATE_FORMAT(d.DOC_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.SPEC_ID = s.SPEC_ID
                    ORDER BY d.DOC_ID";

            $stmt = $this->conn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert all results to lowercase keys
            return array_map([$this, 'convertKeysToLowercase'], $results);
        } catch (PDOException $e) {
            error_log("Error fetching all doctors: " . $e->getMessage());
            return [];
        }
    }

    // Display all with pagination and JOIN
    public function allPaginated($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_MIDDLE_INIT, d.DOC_LAST_NAME,
                           d.DOC_CONTACT_NUM, d.DOC_EMAIL, d.SPEC_ID,
                           s.SPEC_NAME,
                           DATE_FORMAT(d.DOC_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                           DATE_FORMAT(d.DOC_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.SPEC_ID = s.SPEC_ID
                    ORDER BY d.DOC_ID
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map([$this, 'convertKeysToLowercase'], $results);
        } catch (PDOException $e) {
            error_log("Error fetching paginated doctors: " . $e->getMessage());
            return [];
        }
    }

    // Get doctor appointments with JOIN to patient
    public function getDoctorAppointments($doc_id) {
        try {
            $sql = "SELECT a.APPT_ID, a.APPT_DATE, a.APPT_TIME,
                           p.PAT_FIRST_NAME, p.PAT_MIDDLE_INIT, p.PAT_LAST_NAME,
                           CONCAT(p.PAT_LAST_NAME, ', ', p.PAT_FIRST_NAME, ' ', p.PAT_MIDDLE_INIT) as patient_name,
                           DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date,
                           DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_app_time,
                           s.STAT_NAME as app_status_name
                    FROM {$this->table_appointment} a
                    INNER JOIN {$this->table_patient} p ON a.PAT_ID = p.PAT_ID
                    INNER JOIN `status` s ON a.STAT_ID = s.STAT_ID
                    WHERE a.DOC_ID = :doc_id
                    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':doc_id' => trim($doc_id)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching doctor appointments: " . $e->getMessage());
            return [];
        }
    }

    public function getDoctorsBySpecialization($spec_id) {
        $query = "SELECT DOC_ID, DOC_FIRST_NAME, DOC_LAST_NAME 
                  FROM {$this->table_doctor} 
                  WHERE SPEC_ID = ? 
                  ORDER BY DOC_LAST_NAME ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $spec_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get total count for pagination
    public function count() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table_doctor}";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting doctors: " . $e->getMessage());
            return 0;
        }
    }

    // Get doctor names for dropdown
    public function getAllForDropdown() {
        try {
            $sql = "SELECT d.DOC_ID,
                           CONCAT(d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME, ' ', d.DOC_MIDDLE_INIT, ' (', s.SPEC_NAME, ')') as full_name
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.SPEC_ID = s.SPEC_ID
                    ORDER BY d.DOC_LAST_NAME, d.DOC_FIRST_NAME";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching dropdown doctors: " . $e->getMessage());
            return [];
        }
    }

    // Search doctors with appointments info using JOIN
    public function searchWithAppointments($searchTerm) {
        try {
            $sql = "SELECT d.DOC_ID, d.DOC_FIRST_NAME, d.DOC_MIDDLE_INIT, d.DOC_LAST_NAME,
                           d.DOC_CONTACT_NUM, d.DOC_EMAIL, d.SPEC_ID,
                           s.SPEC_NAME,
                           DATE_FORMAT(d.DOC_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                           DATE_FORMAT(d.DOC_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.SPEC_ID = s.SPEC_ID
                    WHERE d.DOC_FIRST_NAME LIKE :search
                       OR d.DOC_LAST_NAME LIKE :search
                       OR d.DOC_ID LIKE :search
                       OR d.DOC_CONTACT_NUM LIKE :search
                       OR s.SPEC_NAME LIKE :search
                    ORDER BY d.DOC_LAST_NAME, d.DOC_FIRST_NAME";

            $stmt = $this->conn->prepare($sql);
            $searchParam = '%' . trim($searchTerm) . '%';
            $stmt->execute([':search' => $searchParam]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map([$this, 'convertKeysToLowercase'], $results);
        } catch (PDOException $e) {
            error_log("Error searching doctors: " . $e->getMessage());
            return [];
        }
    }

    // CREATE
    public function create($doctor) {
        try {
            $sql = "INSERT INTO {$this->table_doctor}
                    (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM,
                     DOC_EMAIL, SPEC_ID, DOC_CREATED_AT, DOC_UPDATED_AT)
                    VALUES (:doc_first_name, :doc_middle_init, :doc_last_name, :doc_contact_num,
                            :doc_email, :spec_id, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            
            $success = $stmt->execute([
                ':doc_first_name' => trim($doctor['doc_first_name']),
                ':doc_middle_init' => trim($doctor['doc_middle_init']),
                ':doc_last_name' => trim($doctor['doc_last_name']),
                ':doc_contact_num' => trim($doctor['doc_contact_num']),
                ':doc_email' => trim($doctor['doc_email']),
                ':spec_id' => $doctor['spec_id']
            ]);
            
            if ($success) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();

            if ($errorCode === '23000') {
                if (strpos($errorMessage, 'AK_DOC_CONTACT_NUM') !== false || strpos($errorMessage, 'DOC_CONTACT_NUM') !== false) {
                    return "DUPLICATE_CONTACT_NUMBER";
                }
            }
            
            error_log("Error creating doctor: " . $errorMessage);
            return "Database Error: " . $errorMessage;
        }
    }

    // UPDATE
    public function update($doctor) {
        try {
            $sql = "UPDATE {$this->table_doctor}
                    SET DOC_FIRST_NAME = :doc_first_name,
                        DOC_MIDDLE_INIT = :doc_middle_init,
                        DOC_LAST_NAME = :doc_last_name,
                        DOC_CONTACT_NUM = :doc_contact_num,
                        DOC_EMAIL = :doc_email,
                        SPEC_ID = :spec_id,
                        DOC_UPDATED_AT = NOW()
                    WHERE DOC_ID = :doc_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                ':doc_id' => trim($doctor['doc_id']),
                ':doc_first_name' => trim($doctor['doc_first_name']),
                ':doc_middle_init' => trim($doctor['doc_middle_init']),
                ':doc_last_name' => trim($doctor['doc_last_name']),
                ':doc_contact_num' => trim($doctor['doc_contact_num']),
                ':doc_email' => trim($doctor['doc_email']),
                ':spec_id' => $doctor['spec_id']
            ]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Error updating doctor: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($doc_id) {
        try {
            $sql = "DELETE FROM {$this->table_doctor} WHERE DOC_ID = :doc_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':doc_id' => trim($doc_id)]);
        } catch (PDOException $e) {
            error_log("Error deleting doctor: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE PROFILE (Doctor + User Table)
    public function updateProfile($doc_id, $first, $middle, $last, $contact, $email, $new_password = '') {
        try {
            $this->conn->beginTransaction();

            // Update DOCTOR table
            $sqlDoc = "UPDATE {$this->table_doctor}
                       SET DOC_FIRST_NAME = ?, DOC_MIDDLE_INIT = ?, DOC_LAST_NAME = ?,
                           DOC_CONTACT_NUM = ?, DOC_EMAIL = ?, DOC_UPDATED_AT = NOW()
                       WHERE DOC_ID = ?";
            $stmtDoc = $this->conn->prepare($sqlDoc);
            $stmtDoc->execute([$first, $middle, $last, $contact, $email, $doc_id]);

            // Update USERS table
            $sqlUser = "UPDATE users SET USER_NAME = ?, USER_UPDATED_AT = NOW()";
            $params = [$email];

            if (!empty($new_password)) {
                $sqlUser .= ", PASSWORD = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sqlUser .= " WHERE DOC_ID = ?";
            $params[] = $doc_id;

            $stmtUser = $this->conn->prepare($sqlUser);
            $stmtUser->execute($params);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Profile update failed: " . $e->getMessage());
            return false;
        }
    }

    // DELETE ACCOUNT (Doctor + User Table)
    public function deleteAccount($doc_id) {
        try {
            $this->conn->beginTransaction();

            // Get email for user deletion
            $stmt = $this->conn->prepare("SELECT DOC_EMAIL FROM {$this->table_doctor} WHERE DOC_ID = ?");
            $stmt->execute([$doc_id]);
            $email = $stmt->fetchColumn();

            // Delete from USERS
            $stmtUser = $this->conn->prepare("DELETE FROM users WHERE USER_NAME = ? OR DOC_ID = ?");
            $stmtUser->execute([$email, $doc_id]);

            // Delete from DOCTORS
            $stmtDoc = $this->conn->prepare("DELETE FROM {$this->table_doctor} WHERE DOC_ID = ?");
            $stmtDoc->execute([$doc_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Account deletion failed: " . $e->getMessage());
            return false;
        }
    }

    // Helper function to convert array keys to lowercase
    private function convertKeysToLowercase($array) {
        return array_change_key_case($array, CASE_LOWER);
    }
}
?>