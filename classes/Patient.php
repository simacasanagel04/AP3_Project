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
                          WHERE pat_id <= :pat_id 
                          ORDER BY pat_id";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':pat_id' => (int)$pat_id]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get patient details
            $sql = "SELECT 
                        p.pat_id, 
                        p.pat_first_name, 
                        p.pat_middle_init, 
                        p.pat_last_name, 
                        p.pat_dob, 
                        p.pat_gender, 
                        p.pat_contact_num, 
                        p.pat_email, 
                        p.pat_address,
                        p.pat_created_at, 
                        p.pat_updated_at,
                        COUNT(a.app_id) as total_appointments,
                        DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.pat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.pat_id = a.pat_id
                    WHERE p.pat_id = :pat_id
                    GROUP BY p.pat_id";

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
                        p.pat_id, 
                        p.pat_first_name, 
                        p.pat_middle_init, 
                        p.pat_last_name, 
                        p.pat_dob, 
                        p.pat_gender, 
                        p.pat_contact_num, 
                        p.pat_email, 
                        p.pat_address,
                        COUNT(a.app_id) as total_appointments,
                        DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.pat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(p.pat_dob, '%M %d, %Y') as formatted_dob
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.pat_id = a.pat_id
                    GROUP BY p.pat_id
                    ORDER BY p.pat_id";

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
                        p.pat_id, 
                        p.pat_first_name, 
                        p.pat_middle_init, 
                        p.pat_last_name, 
                        p.pat_dob, 
                        p.pat_gender, 
                        p.pat_contact_num, 
                        p.pat_email, 
                        p.pat_address,
                        COUNT(a.app_id) as total_appointments,
                        DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.pat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(p.pat_dob, '%M %d, %Y') as formatted_dob
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.pat_id = a.pat_id
                    GROUP BY p.pat_id
                    ORDER BY p.pat_id
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
                        a.app_id, 
                        a.app_date, 
                        a.app_time, 
                        a.app_status, 
                        a.app_reason,
                        d.doc_first_name, 
                        d.doc_middle_init, 
                        d.doc_last_name, 
                        d.doc_specialization,
                        CONCAT(d.doc_last_name, ', ', d.doc_first_name, ' ', COALESCE(d.doc_middle_init, '')) as doctor_name,
                        DATE_FORMAT(a.app_date, '%M %d, %Y') as formatted_app_date,
                        DATE_FORMAT(a.app_time, '%h:%i %p') as formatted_app_time
                    FROM {$this->table_appointment} a
                    INNER JOIN {$this->table_doctor} d ON a.doc_id = d.doc_id
                    WHERE a.pat_id = :pat_id
                    ORDER BY a.app_date DESC, a.app_time DESC";

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
                        p.pat_id, 
                        CONCAT(p.pat_last_name, ', ', p.pat_first_name, ' ', COALESCE(p.pat_middle_init, '')) as full_name,
                        COUNT(a.app_id) as appointment_count
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.pat_id = a.pat_id
                    GROUP BY p.pat_id
                    ORDER BY p.pat_last_name, p.pat_first_name";

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
                        p.pat_id, 
                        p.pat_first_name, 
                        p.pat_middle_init, 
                        p.pat_last_name, 
                        p.pat_dob, 
                        p.pat_gender, 
                        p.pat_contact_num, 
                        p.pat_email, 
                        p.pat_address,
                        COUNT(a.app_id) as total_appointments,
                        MAX(a.app_date) as last_appointment_date,
                        DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(MAX(a.app_date), '%M %d, %Y') as formatted_last_appointment
                    FROM {$this->table_patient} p
                    LEFT JOIN {$this->table_appointment} a ON p.pat_id = a.pat_id
                    WHERE p.pat_first_name LIKE :search 
                       OR p.pat_last_name LIKE :search 
                       OR p.pat_id LIKE :search
                       OR p.pat_contact_num LIKE :search
                    GROUP BY p.pat_id
                    ORDER BY p.pat_last_name, p.pat_first_name";

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
                    (pat_first_name, pat_middle_init, pat_last_name, pat_dob, 
                     pat_gender, pat_contact_num, pat_email, pat_address, 
                     pat_created_at, pat_updated_at)
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
                        pat_first_name   = :pat_first_name,
                        pat_middle_init  = :pat_middle_init,
                        pat_last_name    = :pat_last_name,
                        pat_dob          = :pat_dob,
                        pat_gender       = :pat_gender,
                        pat_contact_num  = :pat_contact_num,
                        pat_email        = :pat_email,
                        pat_address      = :pat_address,
                        pat_updated_at   = NOW()
                    WHERE pat_id = :pat_id";

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
            $sql = "DELETE FROM {$this->table_patient} WHERE pat_id = :pat_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':pat_id' => (int)$pat_id]);
        } catch (PDOException $e) {
            error_log("Error in delete(): " . $e->getMessage());
            return false;
        }
    }
}
?>