<?php
// /classes/Payment.php
class Payment {
    private $conn;
    private $table_payment = "payment";
    private $table_payment_method = "payment_method";
    private $table_payment_status = "payment_status";
    private $table_appointment = "appointment";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Search by ID - Returns payment data with row number
    public function findById($paymt_id) {
        try {
            // Get row number
            $sqlRowNum = "SELECT COUNT(*) as row_num FROM {$this->table_payment} WHERE PAYMT_ID <= :paymt_id ORDER BY PAYMT_ID";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':paymt_id' => (int)trim($paymt_id)]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get payment data
            $sql = "SELECT 
                        p.PAYMT_ID as paymt_id,
                        p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                        p.PAYMT_DATE as paymt_date,
                        pm.PYMT_METH_NAME as pymt_meth_name,
                        ps.PYMT_STAT_NAME as pymt_stat_name,
                        a.APPT_ID as app_id,
                        a.APPT_DATE as app_date,
                        DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                        DATE_FORMAT(p.PYMT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.PYMT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    LEFT JOIN {$this->table_appointment} a ON p.APPT_ID = a.APPT_ID
                    WHERE p.PAYMT_ID = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':paymt_id' => (int)trim($paymt_id)]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                $payment['row_number'] = $rowData['row_num'];
                return $payment;
            }
            return 0;
        } catch (PDOException $e) {
            error_log("Error finding payment: " . $e->getMessage());
            return 0;
        }
    }

    // Display all payments
    public function all() {
        try {
            $sql = "SELECT 
                        p.PAYMT_ID as paymt_id,
                        p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                        p.PAYMT_DATE as paymt_date,
                        pm.PYMT_METH_NAME as pymt_meth_name,
                        ps.PYMT_STAT_NAME as pymt_stat_name,
                        a.APPT_ID as app_id,
                        a.APPT_DATE as app_date,
                        DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                        DATE_FORMAT(p.PYMT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.PYMT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    LEFT JOIN {$this->table_appointment} a ON p.APPT_ID = a.APPT_ID
                    ORDER BY p.PAYMT_ID DESC";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all payments: " . $e->getMessage());
            return [];
        }
    }

    // Display all with pagination and JOIN
    public function allPaginated($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT 
                        p.PAYMT_ID as paymt_id,
                        p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                        p.PAYMT_DATE as paymt_date,
                        pm.PYMT_METH_NAME as pymt_meth_name,
                        ps.PYMT_STAT_NAME as pymt_stat_name,
                        a.APPT_ID as app_id,
                        a.APPT_DATE as app_date,
                        DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                        DATE_FORMAT(p.PYMT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at,
                        DATE_FORMAT(p.PYMT_UPDATED_AT, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    LEFT JOIN {$this->table_appointment} a ON p.APPT_ID = a.APPT_ID
                    ORDER BY p.PAYMT_ID DESC
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated payments: " . $e->getMessage());
            return [];
        }
    }

    // Get payment details with JOIN to appointment
    public function getPaymentDetails($paymt_id) {
        try {
            $sql = "SELECT 
                        p.PAYMT_ID as paymt_id,
                        p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                        p.PAYMT_DATE as paymt_date,
                        pm.PYMT_METH_NAME as pymt_meth_name,
                        ps.PYMT_STAT_NAME as pymt_stat_name,
                        a.APPT_ID as app_id,
                        a.APPT_DATE as app_date,
                        a.APPT_TIME as app_time,
                        CONCAT(pat.PAT_LAST_NAME, ', ', pat.PAT_FIRST_NAME, ' ', COALESCE(pat.PAT_MIDDLE_INIT, '')) as patient_name,
                        CONCAT(doc.DOC_LAST_NAME, ', ', doc.DOC_FIRST_NAME, ' ', COALESCE(doc.DOC_MIDDLE_INIT, '')) as doctor_name,
                        DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_app_date,
                        DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_app_time
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    LEFT JOIN {$this->table_appointment} a ON p.APPT_ID = a.APPT_ID
                    LEFT JOIN patient pat ON a.PAT_ID = pat.PAT_ID
                    LEFT JOIN doctor doc ON a.DOC_ID = doc.DOC_ID
                    WHERE p.PAYMT_ID = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':paymt_id' => (int)trim($paymt_id)]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching payment details: " . $e->getMessage());
            return [];
        }
    }

    // Get total count for pagination
    public function count() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table_payment}";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error counting payments: " . $e->getMessage());
            return 0;
        }
    }

    // Get payment IDs and related info for dropdown
    public function getAllForDropdown() {
        try {
            $sql = "SELECT 
                        p.PAYMT_ID as paymt_id,
                        CONCAT('Payment #', p.PAYMT_ID, ' - ', FORMAT(p.PAYMT_AMOUNT_PAID, 2), ' (', ps.PYMT_STAT_NAME, ')') as payment_info
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    ORDER BY p.PAYMT_ID DESC";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching dropdown payments: " . $e->getMessage());
            return [];
        }
    }

    // Search payments with related info
public function searchWithDetails($searchTerm) {
    try {
        $sql = "SELECT 
                    p.PAYMT_ID as paymt_id,
                    p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                    p.PAYMT_DATE as paymt_date,
                    pm.PYMT_METH_NAME as pymt_meth_name,
                    ps.PYMT_STAT_NAME as pymt_stat_name,
                    a.APPT_ID as app_id,
                    DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.PYMT_CREATED_AT, '%M %d, %Y %h:%i %p') as formatted_created_at
                FROM {$this->table_payment} p
                LEFT JOIN {$this->table_payment_method} pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                LEFT JOIN {$this->table_appointment} a ON p.APPT_ID = a.APPT_ID
                WHERE p.PAYMT_ID LIKE :search1
                   OR pm.PYMT_METH_NAME LIKE :search2
                   OR ps.PYMT_STAT_NAME LIKE :search3
                   OR CAST(p.PAYMT_AMOUNT_PAID AS CHAR) LIKE :search4
                ORDER BY p.PAYMT_ID DESC";

        $stmt = $this->conn->prepare($sql);
        $searchParam = '%' . trim($searchTerm) . '%';
        $stmt->execute([
            ':search1' => $searchParam,
            ':search2' => $searchParam,
            ':search3' => $searchParam,
            ':search4' => $searchParam
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error searching payments: " . $e->getMessage());
        return [];
    }
}

    // CREATE
    public function create($payment) {
        try {
            $sql = "INSERT INTO {$this->table_payment}
                    (PAYMT_AMOUNT_PAID, PAYMT_DATE, PYMT_METH_ID, PYMT_STAT_ID, APPT_ID, PYMT_CREATED_AT, PYMT_UPDATED_AT)
                    VALUES (:paymt_amount_paid, :paymt_date, :pymt_meth_id, :pymt_stat_id, :appt_id, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                ':paymt_amount_paid' => (float)$payment['paymt_amount_paid'],
                ':paymt_date'        => trim($payment['paymt_date']) . ' ' . date('H:i:s'),
                ':pymt_meth_id'      => (int)trim($payment['pymt_meth_id']),
                ':pymt_stat_id'      => (int)trim($payment['pymt_stat_id']),
                ':appt_id'           => trim($payment['appt_id'])
            ]);

            if ($success) {
                return (int)$this->conn->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE - FIXED VERSION
    public function update($payment) {
        try {
            $sql = "UPDATE {$this->table_payment}
                    SET PAYMT_AMOUNT_PAID = :paymt_amount_paid,
                        PAYMT_DATE = :paymt_date,
                        PYMT_METH_ID = :pymt_meth_id,
                        PYMT_STAT_ID = :pymt_stat_id,
                        APPT_ID = :appt_id,
                        PYMT_UPDATED_AT = NOW()
                    WHERE PAYMT_ID = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            
            // CONVERT DATE TO DATETIME - ADD TIME IF ONLY DATE PROVIDED
            $paymt_date_value = trim($payment['paymt_date']);
            if (!strpos($paymt_date_value, ':')) {
                // Only date provided (YYYY-MM-DD), add default time
                $paymt_date_value .= ' ' . date('H:i:s');
            }

            $params = [
                ':paymt_id'          => (int)trim($payment['paymt_id']),
                ':paymt_amount_paid' => (float)$payment['paymt_amount_paid'],
                ':paymt_date'        => $paymt_date_value,
                ':pymt_meth_id'      => (int)trim($payment['pymt_meth_id']),
                ':pymt_stat_id'      => (int)trim($payment['pymt_stat_id']),
                ':appt_id'           => trim($payment['appt_id'])
            ];

            error_log("Payment Update Debug - Params: " . json_encode($params));
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("Update failed. Error Info: " . json_encode($stmt->errorInfo()));
                return false;
            }
            
            error_log("Payment ID {$payment['paymt_id']} updated successfully");
            return true;
            
        } catch (PDOException $e) {
            error_log("Error updating payment: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($paymt_id) {
        try {
            $sql = "DELETE FROM {$this->table_payment} WHERE PAYMT_ID = :paymt_id";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':paymt_id' => (int)trim($paymt_id)]);
        } catch (PDOException $e) {
            error_log("Error deleting payment: " . $e->getMessage());
            return false;
        }
    }

    // ==================== NEW METHODS ====================

    public function allWithFilters($filters = []) {
        try {
            $sql = "SELECT 
                        p.PAYMT_ID,
                        p.APPT_ID,
                        p.PAYMT_AMOUNT_PAID,
                        p.PYMT_METH_ID,
                        p.PYMT_STAT_ID,
                        pm.PYMT_METH_NAME,
                        ps.PYMT_STAT_NAME,
                        s.SERV_NAME,
                        DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                        CONCAT(pat.PAT_FIRST_NAME, ' ', COALESCE(pat.PAT_MIDDLE_INIT, ''), '. ', pat.PAT_LAST_NAME) as patient_name
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN {$this->table_payment_status} ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    LEFT JOIN {$this->table_appointment} a ON p.APPT_ID = a.APPT_ID
                    LEFT JOIN patient pat ON a.PAT_ID = pat.PAT_ID
                    LEFT JOIN service s ON a.SERV_ID = s.SERV_ID
                    WHERE 1=1";

            $params = [];

            if (!empty($filters['appt_id'])) {
                $sql .= " AND p.APPT_ID = :appt_id";
                $params[':appt_id'] = $filters['appt_id'];
            }
            if (!empty($filters['paymt_id'])) {
                $sql .= " AND p.PAYMT_ID = :paymt_id";
                $params[':paymt_id'] = $filters['paymt_id'];
            }
            if (!empty($filters['pymt_stat_id'])) {
                $sql .= " AND p.PYMT_STAT_ID = :pymt_stat_id";
                $params[':pymt_stat_id'] = $filters['pymt_stat_id'];
            }
            if (!empty($filters['pymt_meth_id'])) {
                $sql .= " AND p.PYMT_METH_ID = :pymt_meth_id";
                $params[':pymt_meth_id'] = $filters['pymt_meth_id'];
            }
            if (!empty($filters['patient_name'])) {
                $sql .= " AND CONCAT(pat.PAT_FIRST_NAME, ' ', pat.PAT_LAST_NAME) LIKE :patient_name";
                $params[':patient_name'] = '%' . $filters['patient_name'] . '%';
            }
            if (!empty($filters['date_from'])) {
                $sql .= " AND p.PAYMT_DATE >= :date_from";
                $params[':date_from'] = $filters['date_from'] . ' 00:00:00';
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND p.PAYMT_DATE <= :date_to";
                $params[':date_to'] = $filters['date_to'] . ' 23:59:59';
            }

            $sql .= " ORDER BY p.PAYMT_ID DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in allWithFilters: " . $e->getMessage());
            return [];
        }
    }

    public function getAppointmentDetails($appt_id) {
        try {
            $sql = "SELECT 
                        a.APPT_ID as app_id,
                        a.APPT_DATE as app_date,
                        a.APPT_TIME as app_time,
                        s.SERV_NAME as serv_name,
                        s.SERV_PRICE as serv_price,
                        CONCAT(pat.PAT_FIRST_NAME, ' ', COALESCE(pat.PAT_MIDDLE_INIT, ''), ' ', pat.PAT_LAST_NAME) as patient_name,
                        DATE_FORMAT(a.APPT_DATE, '%M %d, %Y') as formatted_appt_date,
                        DATE_FORMAT(a.APPT_TIME, '%h:%i %p') as formatted_appt_time
                    FROM appointment a
                    LEFT JOIN patient pat ON a.PAT_ID = pat.PAT_ID
                    LEFT JOIN service s ON a.SERV_ID = s.SERV_ID
                    WHERE a.APPT_ID = :appt_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':appt_id' => $appt_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAppointmentDetails: " . $e->getMessage());
            return false;
        }
    }

    public function getPaymentsByAppointment($appt_id) {
        try {
            $sql = "SELECT 
                        p.PAYMT_ID as paymt_id,
                        p.PAYMT_AMOUNT_PAID as paymt_amount_paid,
                        pm.PYMT_METH_NAME as pymt_meth_name,
                        ps.PYMT_STAT_NAME as pymt_stat_name,
                        DATE_FORMAT(p.PAYMT_DATE, '%M %d, %Y %h:%i %p') as formatted_paymt_date
                    FROM payment p
                    LEFT JOIN payment_method pm ON p.PYMT_METH_ID = pm.PYMT_METH_ID
                    LEFT JOIN payment_status ps ON p.PYMT_STAT_ID = ps.PYMT_STAT_ID
                    WHERE p.APPT_ID = :appt_id
                    ORDER BY p.PAYMT_DATE DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':appt_id' => $appt_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPaymentsByAppointment: " . $e->getMessage());
            return [];
        }
    }

    public function searchAppointments($search = '') {
        try {
            $sql = "SELECT 
                        a.APPT_ID as id,
                        CONCAT(a.APPT_ID, ' - ', pat.PAT_FIRST_NAME, ' ', pat.PAT_LAST_NAME, ' (', DATE_FORMAT(a.APPT_DATE, '%m/%d'), ')') as text
                    FROM appointment a
                    LEFT JOIN patient pat ON a.PAT_ID = pat.PAT_ID
                    WHERE 1=1";

            $params = [];
            if (!empty($search)) {
                $sql .= " AND (a.APPT_ID LIKE :search OR pat.PAT_FIRST_NAME LIKE :search OR pat.PAT_LAST_NAME LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            $sql .= " ORDER BY a.APPT_DATE DESC LIMIT 20";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in searchAppointments: " . $e->getMessage());
            return [];
        }
    }

    public function getAllAppointmentsForDropdown() {
        return $this->searchAppointments();
    }
}
?>