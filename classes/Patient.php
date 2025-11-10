<?php
// Patient.php
// inside the classes folder of the project

class Patient {
    private $conn;
    private $table_patient = "patient";
    private $table_appointment = "appointment";
    private $table_doctor = "doctor";

    public function __construct($db) {
        $this->conn = $db;
    }

    //  READ OPERATIONS
    public function findById($pat_id) {
        try {
            // Get row number (position in ordered list)
            $sqlRowNum = "SELECT COUNT(*) as row_num 
                          FROM {$this->table_patient} 
                          WHERE PAT_ID <= :pat_id 
                          ORDER BY PAT_ID";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':pat_id' => (int)$pat_id]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get patient details
            $sql = "SELECT 
                        p.PAT_ID as pat_id, 
                        p.PAT_FIRST_NAME as pat_first_name, 
                        p.PAT_MIDDLE_INIT as pat_middle_init, 
                        p.PAT_LAST_NAME as pat_last_name, 
                        p.PAT_DOB as pat_dob, 
                        p.PAT_GENDER as pat_gender, 
                        p.PAT_CONTACT_NUM as pat_contact_num, 
                        p.PAT_EMAIL as pat_email, 
                        p.PAT_ADDRESS as pat_address,
                        p.PAT_CREATED_AT as pat_created_at, 
                        p.PAT_UPDATED_AT as pat_updated_at,
                        COUNT(a.APPT_ID) as total_appointments,
                        DATE_FORMAT(p.PAT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.PAT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.PAT_ID = a.PAT_ID
                    WHERE p.PAT_ID = :pat_id
                    GROUP BY p.PAT_ID";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':pat_id' => (int)$pat_id]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($patient) {
                $patient['row_number'] = $rowData['row_num'];
                return $patient;
            }

            return false; // Not found
        } catch (PDOException $e) {
            error_log("Error in findById(): " . $e->getMessage());
            return false;
        }
    }

    public function all() {
        try {
            $sql = "SELECT 
                        p.PAT_ID as pat_id, 
                        p.PAT_FIRST_NAME as pat_first_name, 
                        p.PAT_MIDDLE_INIT as pat_middle_init, 
                        p.PAT_LAST_NAME as pat_last_name, 
                        p.PAT_DOB as pat_dob, 
                        p.PAT_GENDER as pat_gender, 
                        p.PAT_CONTACT_NUM as pat_contact_num, 
                        p.PAT_EMAIL as pat_email, 
                        p.PAT_ADDRESS as pat_address,
                        COUNT(a.APPT_ID) as total_appointments,
                        DATE_FORMAT(p.PAT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.PAT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(p.PAT_DOB, '%M %d, %Y') as formatted_dob
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.PAT_ID = a.PAT_ID
                    GROUP BY p.PAT_ID
                    ORDER BY p.PAT_ID";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in all(): " . $e->getMessage());
            return [];
        }
    }

    public function allPaginated($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT 
                        p.PAT_ID as pat_id, 
                        p.PAT_FIRST_NAME as pat_first_name, 
                        p.PAT_MIDDLE_INIT as pat_middle_init, 
                        p.PAT_LAST_NAME as pat_last_name, 
                        p.PAT_DOB as pat_dob, 
                        p.PAT_GENDER as pat_gender, 
                        p.PAT_CONTACT_NUM as pat_contact_num, 
                        p.PAT_EMAIL as pat_email, 
                        p.PAT_ADDRESS as pat_address,
                        COUNT(a.APPT_ID) as total_appointments,
                        DATE_FORMAT(p.PAT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.PAT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(p.PAT_DOB, '%M %d, %Y') as formatted_dob
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.PAT_ID = a.PAT_ID
                    GROUP BY p.PAT_ID
                    ORDER BY p.PAT_ID
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in allPaginated(): " . $e->getMessage());
            return [];
        }
    }

    public function getPatientAppointments($pat_id) {
        try {
            $sql = "SELECT 
                        a.APPT_ID as app_id, 
                        a.APPT_DATE as app_date, 
                        a.APPT_TIME as app_time, 
                        a.STAT_ID as app_status, 
                        d.DOC_FIRST_NAME as doc_first_name, 
                        d.DOC_MIDDLE_INIT as doc_middle_init, 
                        d.DOC_LAST_NAME as doc_last_name, 
                        s.SPEC_NAME as doc_specialization,
                        CONCAT(d.DOC_LAST_NAME, ', ', d.DOC_FIRST_NAME, ' ', COALESCE(d.DOC_MIDDLE_INIT, '')) as doctor_name,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date,
                        DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_app_time
                    FROM {$this->table_appointment} a
                    INNER JOIN {$this->table_doctor} d ON a.DOC_ID = d.DOC_ID
                    LEFT JOIN specialization s ON d.SPEC_ID = s.SPEC_ID
                    WHERE a.PAT_ID = :pat_id
                    ORDER BY a.APPT_DATE DESC, a.APPT_TIME DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':pat_id' => (int)$pat_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPatientAppointments(): " . $e->getMessage());
            return [];
        }
    }

    public function count() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table_patient}";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error in count(): " . $e->getMessage());
            return 0;
        }
    }

    public function getAllForDropdown() {
        try {
            $sql = "SELECT 
                        p.PAT_ID as pat_id, 
                        CONCAT(p.PAT_LAST_NAME, ', ', p.PAT_FIRST_NAME, ' ', COALESCE(p.PAT_MIDDLE_INIT, '')) as full_name,
                        COUNT(a.APPT_ID) as appointment_count
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.PAT_ID = a.PAT_ID
                    GROUP BY p.PAT_ID
                    ORDER BY p.PAT_LAST_NAME, p.PAT_FIRST_NAME";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllForDropdown(): " . $e->getMessage());
            return [];
        }
    }

    public function searchWithAppointments($searchTerm) {
        try {
            $sql = "SELECT 
                        p.PAT_ID as pat_id, 
                        p.PAT_FIRST_NAME as pat_first_name, 
                        p.PAT_MIDDLE_INIT as pat_middle_init, 
                        p.PAT_LAST_NAME as pat_last_name, 
                        p.PAT_DOB as pat_dob, 
                        p.PAT_GENDER as pat_gender, 
                        p.PAT_CONTACT_NUM as pat_contact_num, 
                        p.PAT_EMAIL as pat_email, 
                        p.PAT_ADDRESS as pat_address,
                        COUNT(a.APPT_ID) as total_appointments,
                        MAX(a.APPT_DATE) as last_appointment_date,
                        DATE_FORMAT(p.PAT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(MAX(a.APPT_DATE), '%M %d, %Y') as formatted_last_appointment
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.PAT_ID = a.PAT_ID
                    WHERE p.PAT_FIRST_NAME LIKE :search 
                       OR p.PAT_LAST_NAME LIKE :search 
                       OR p.PAT_ID LIKE :search
                       OR p.PAT_CONTACT_NUM LIKE :search
                    GROUP BY p.PAT_ID
                    ORDER BY p.PAT_LAST_NAME, p.PAT_FIRST_NAME";

            $stmt = $this->conn->prepare($sql);
            $searchParam = '%' . trim($searchTerm) . '%';
            $stmt->execute([':search' => $searchParam]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in searchWithAppointments(): " . $e->getMessage());
            return [];
        }
    }

    //  CREATE / UPDATE / DELETE

    public function create($data) {
        try {
            // INSERT PATIENT
            $sql = "INSERT INTO {$this->table_patient} 
                    (PAT_FIRST_NAME, PAT_MIDDLE_INIT, PAT_LAST_NAME, PAT_DOB, 
                     PAT_GENDER, PAT_CONTACT_NUM, PAT_EMAIL, PAT_ADDRESS, 
                     PAT_CREATED_AT, PAT_UPDATED_AT)
                    VALUES 
                    (:pat_first_name, :pat_middle_init, :pat_last_name, :pat_dob,
                     :pat_gender, :pat_contact_num, :pat_email, :pat_address, 
                     NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);

            $params = [
                ':pat_first_name'   => trim($data['pat_first_name']),
                ':pat_middle_init'  => !empty($data['pat_middle_init']) ? trim($data['pat_middle_init']) : '',
                ':pat_last_name'    => trim($data['pat_last_name']),
                ':pat_dob'          => $data['pat_dob'],
                ':pat_gender'       => trim($data['pat_gender']),
                ':pat_contact_num'  => trim($data['pat_contact_num']),
                ':pat_email'        => trim($data['pat_email']),
                ':pat_address'      => trim($data['pat_address'])
            ];

            $exec = $stmt->execute($params);

            if ($exec) {
                $id = $this->conn->lastInsertId();
                error_log("Last Insert ID: " . $id);
                
                if ($id && $id > 0) {
                    error_log("SUCCESS! Returning ID: " . $id);
                    return (int)$id;
                } else {
                    error_log("ERROR: lastInsertId returned: " . var_export($id, true));
                    return false;
                }
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("EXECUTE FAILED! Error info: " . print_r($errorInfo, true));
                return false;
            }

        } catch (PDOException $e) {
            error_log("PDO EXCEPTION in create(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        } catch (Exception $e) {
            error_log("GENERAL EXCEPTION in create(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }

    public function update($data) {
        try {
            $sql = "UPDATE {$this->table_patient} 
                    SET 
                        PAT_FIRST_NAME   = :pat_first_name,
                        PAT_MIDDLE_INIT  = :pat_middle_init,
                        PAT_LAST_NAME    = :pat_last_name,
                        PAT_DOB          = :pat_dob,
                        PAT_GENDER       = :pat_gender,
                        PAT_CONTACT_NUM  = :pat_contact_num,
                        PAT_EMAIL        = :pat_email,
                        PAT_ADDRESS      = :pat_address,
                        PAT_UPDATED_AT   = NOW()
                    WHERE PAT_ID = :pat_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':pat_id'           => (int)$data['pat_id'],
                ':pat_first_name'   => trim($data['pat_first_name']),
                ':pat_middle_init'  => !empty($data['pat_middle_init']) ? trim($data['pat_middle_init']) : null,
                ':pat_last_name'    => trim($data['pat_last_name']),
                ':pat_dob'          => $data['pat_dob'],
                ':pat_gender'       => trim($data['pat_gender']),
                ':pat_contact_num'  => trim($data['pat_contact_num']),
                ':pat_email'        => trim($data['pat_email']),
                ':pat_address'      => trim($data['pat_address'])
            ]);
        } catch (PDOException $e) {
            error_log("Error in update(): " . $e->getMessage());
            return false;
        }
    }

    public function delete($pat_id) {
        try {
            $sql = "DELETE FROM {$this->table_patient} WHERE PAT_ID = :pat_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':pat_id' => (int)$pat_id]);
        } catch (PDOException $e) {
            error_log("Error in delete(): " . $e->getMessage());
            return false;
        }
    }
}
?>