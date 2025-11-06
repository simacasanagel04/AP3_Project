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
            $sqlRowNum = "SELECT COUNT(*) as row_num FROM {$this->table_doctor} WHERE doc_id <= :doc_id ORDER BY doc_id";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':doc_id' => trim($doc_id)]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            $sql = "SELECT d.doc_id, d.doc_first_name, d.doc_middle_init, d.doc_last_name,
                    d.doc_contact_num, d.doc_email,
                    d.doc_created_at, d.doc_updated_at,
                    s.spec_name,
                    COUNT(a.app_id) as total_appointments,
                    DATE_FORMAT(d.doc_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(d.doc_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.spec_id = s.spec_id
                    LEFT JOIN {$this->table_appointment} a ON d.doc_id = a.doc_id
                    WHERE d.doc_id = :doc_id
                    GROUP BY d.doc_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':doc_id' => trim($doc_id)]);
            $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($doctor) {
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
            $sql = "SELECT d.doc_id, d.doc_first_name, d.doc_middle_init, d.doc_last_name,
                    d.doc_contact_num, d.doc_email,
                    s.spec_name,
                    COUNT(a.app_id) as total_appointments,
                    DATE_FORMAT(d.doc_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(d.doc_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.spec_id = s.spec_id
                    LEFT JOIN {$this->table_appointment} a ON d.doc_id = a.doc_id
                    GROUP BY d.doc_id
                    ORDER BY d.doc_id";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all doctors: " . $e->getMessage());
            return [];
        }
    }

    // Display all with pagination and JOIN
    public function allPaginated($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT d.doc_id, d.doc_first_name, d.doc_middle_init, d.doc_last_name,
                    d.doc_contact_num, d.doc_email,
                    s.spec_name,
                    COUNT(a.app_id) as total_appointments,
                    DATE_FORMAT(d.doc_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(d.doc_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.spec_id = s.spec_id
                    LEFT JOIN {$this->table_appointment} a ON d.doc_id = a.doc_id
                    GROUP BY d.doc_id
                    ORDER BY d.doc_id
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated doctors: " . $e->getMessage());
            return [];
        }
    }

    // Get doctor appointments with JOIN to patient
    public function getDoctorAppointments($doc_id) {
        try {
            $sql = "SELECT a.app_id, a.app_date, a.app_time, a.app_status, a.app_reason,
                    p.pat_first_name, p.pat_middle_init, p.pat_last_name,
                    CONCAT(p.pat_last_name, ', ', p.pat_first_name, ' ', p.pat_middle_init) as patient_name,
                    DATE_FORMAT(a.app_date, '%M %d, %Y') as formatted_app_date,
                    DATE_FORMAT(a.app_time, '%h:%i %p') as formatted_app_time
                    FROM {$this->table_appointment} a
                    INNER JOIN {$this->table_patient} p ON a.pat_id = p.pat_id
                    WHERE a.doc_id = :doc_id
                    ORDER BY a.app_date DESC, a.app_time DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':doc_id' => trim($doc_id)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching doctor appointments: " . $e->getMessage());
            return [];
        }
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

    // Get doctor names for dropdown (intelligent display) with JOIN to show appointment count
    public function getAllForDropdown() {
        try {
            $sql = "SELECT d.doc_id,
                    CONCAT(d.doc_last_name, ', ', d.doc_first_name, ' ', d.doc_middle_init, ' (', s.spec_name, ')') as full_name,
                    COUNT(a.app_id) as appointment_count
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.spec_id = s.spec_id
                    LEFT JOIN {$this->table_appointment} a ON d.doc_id = a.doc_id
                    GROUP BY d.doc_id
                    ORDER BY d.doc_last_name, d.doc_first_name";

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
            $sql = "SELECT d.doc_id, d.doc_first_name, d.doc_middle_init, d.doc_last_name,
                    d.doc_contact_num, d.doc_email,
                    s.spec_name,
                    COUNT(a.app_id) as total_appointments,
                    MAX(a.app_date) as last_appointment_date,
                    DATE_FORMAT(d.doc_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(MAX(a.app_date), '%M %d, %Y') as formatted_last_appointment
                    FROM {$this->table_doctor} d
                    LEFT JOIN {$this->table_specialization} s ON d.spec_id = s.spec_id
                    LEFT JOIN {$this->table_appointment} a ON d.doc_id = a.doc_id
                    WHERE d.doc_first_name LIKE :search
                    OR d.doc_last_name LIKE :search
                    OR d.doc_id LIKE :search
                    OR d.doc_contact_num LIKE :search
                    GROUP BY d.doc_id
                    ORDER BY d.doc_last_name, d.doc_first_name";

            $stmt = $this->conn->prepare($sql);
            $searchParam = '%' . trim($searchTerm) . '%';
            $stmt->execute([':search' => $searchParam]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching doctors: " . $e->getMessage());
            return [];
        }
    }

    // CREATE
// In classes/Doctor.php - Replace the create method with this:

    public function create($doctor) {
        try {
            // Don't include doc_id - let database auto-increment
            $sql = "INSERT INTO doctor
                    (DOC_FIRST_NAME, DOC_MIDDLE_INIT, DOC_LAST_NAME, DOC_CONTACT_NUM,
                    DOC_EMAIL, SPEC_ID, DOC_CREATED_AT, DOC_UPDATED_AT)
                    VALUES (:doc_first_name, :doc_middle_init, :doc_last_name, :doc_contact_num,
                    :doc_email, :spec_id, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':doc_first_name'   => trim($doctor['doc_first_name']),
                ':doc_middle_init'  => trim($doctor['doc_middle_init']),
                ':doc_last_name'    => trim($doctor['doc_last_name']),
                ':doc_contact_num'  => trim($doctor['doc_contact_num']),
                ':doc_email'        => trim($doctor['doc_email']),
                ':spec_id'          => $doctor['spec_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error creating doctor: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE
    public function update($doctor) {
        try {
            $sql = "UPDATE {$this->table_doctor}
                    SET doc_first_name = :doc_first_name,
                        doc_middle_init = :doc_middle_init,
                        doc_last_name = :doc_last_name,
                        doc_contact_num = :doc_contact_num,
                        doc_email = :doc_email,
                        spec_id = :spec_id,
                        doc_updated_at = NOW()
                    WHERE doc_id = :doc_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':doc_id'           => trim($doctor['doc_id']),
                ':doc_first_name'   => trim($doctor['doc_first_name']),
                ':doc_middle_init'  => trim($doctor['doc_middle_init']),
                ':doc_last_name'    => trim($doctor['doc_last_name']),
                ':doc_contact_num'  => trim($doctor['doc_contact_num']),
                ':doc_email'        => trim($doctor['doc_email']),
                ':spec_id'          => $doctor['spec_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error updating doctor: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($doc_id) {
        try {
            $sql = "DELETE FROM {$this->table_doctor} WHERE doc_id = :doc_id";
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
                       SET doc_first_name = ?, doc_middle_init = ?, doc_last_name = ?, 
                           doc_contact_num = ?, doc_email = ?, doc_updated_at = NOW() 
                       WHERE doc_id = ?";
            $stmtDoc = $this->conn->prepare($sqlDoc);
            $stmtDoc->execute([$first, $middle, $last, $contact, $email, $doc_id]);

            // Update USERS table (email + password)
            $sqlUser = "UPDATE USERS SET USER_NAME = ?, USER_UPDATED_AT = NOW()";
            $params = [$email];

            if (!empty($new_password)) {
                $sqlUser .= ", PASSWORD = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            $sqlUser .= " WHERE USER_NAME = (SELECT doc_email FROM {$this->table_doctor} WHERE doc_id = ?) OR PAT_ID = ?";
            $params[] = $doc_id;
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
            $stmt = $this->conn->prepare("SELECT doc_email FROM {$this->table_doctor} WHERE doc_id = ?");
            $stmt->execute([$doc_id]);
            $email = $stmt->fetchColumn();

            // Delete from USERS
            $stmtUser = $this->conn->prepare("DELETE FROM USERS WHERE USER_NAME = ? OR PAT_ID = ?");
            $stmtUser->execute([$email, $doc_id]);

            // Delete from DOCTORS
            $stmtDoc = $this->conn->prepare("DELETE FROM {$this->table_doctor} WHERE doc_id = ?");
            $stmtDoc->execute([$doc_id]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Account deletion failed: " . $e->getMessage());
            return false;
        }
    }

    // LEGACY METHOD (kept for compatibility)
    public function updateProfileLegacy($doc_id, $first, $middle, $last, $contact) {
        $sql = "UPDATE DOCTORS SET doc_first_name = ?, doc_middle_init = ?, doc_last_name = ?, doc_contact_num = ?, doc_updated_at = NOW() WHERE doc_id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$first, $middle, $last, $contact, $doc_id]);
    }
}
?>
