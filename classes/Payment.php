<?php
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
            $sqlRowNum = "SELECT COUNT(*) as row_num FROM {$this->table_payment} WHERE paymt_id <= :paymt_id ORDER BY paymt_id";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':paymt_id' => trim($paymt_id)]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get payment data
            $sql = "SELECT p.paymt_id, p.paymt_amount_paid, p.paymt_date,
                    pm.pymt_meth_name, ps.pymt_stat_name, a.app_id, a.app_date,
                    DATE_FORMAT(p.paymt_date, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.pymt_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(p.pymt_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                    DATE_FORMAT(a.app_date, '%M %d, %Y') as formatted_app_date
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.pymt_meth_id = pm.pymt_meth_id
                    LEFT JOIN {$this->table_payment_status} ps ON p.pymt_stat_id = ps.pymt_stat_id
                    LEFT JOIN {$this->table_appointment} a ON p.appt_id = a.app_id
                    WHERE p.paymt_id = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':paymt_id' => trim($paymt_id)]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                $payment['row_number'] = $rowData['row_num'];
                return $payment;
            }

            // Return 0 if payment does not exist
            return 0;
        } catch (PDOException $e) {
            error_log("Error finding payment: " . $e->getMessage());
            return 0;
        }
    }

    // Display all payments
    public function all() {
        try {
            $sql = "SELECT p.paymt_id, p.paymt_amount_paid, p.paymt_date,
                    pm.pymt_meth_name, ps.pymt_stat_name, a.app_id, a.app_date,
                    DATE_FORMAT(p.paymt_date, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.pymt_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(p.pymt_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                    DATE_FORMAT(a.app_date, '%M %d, %Y') as formatted_app_date
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.pymt_meth_id = pm.pymt_meth_id
                    LEFT JOIN {$this->table_payment_status} ps ON p.pymt_stat_id = ps.pymt_stat_id
                    LEFT JOIN {$this->table_appointment} a ON p.appt_id = a.app_id
                    ORDER BY p.paymt_id";

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
            $sql = "SELECT p.paymt_id, p.paymt_amount_paid, p.paymt_date,
                    pm.pymt_meth_name, ps.pymt_stat_name, a.app_id, a.app_date,
                    DATE_FORMAT(p.paymt_date, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.pymt_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(p.pymt_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at,
                    DATE_FORMAT(a.app_date, '%M %d, %Y') as formatted_app_date
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.pymt_meth_id = pm.pymt_meth_id
                    LEFT JOIN {$this->table_payment_status} ps ON p.pymt_stat_id = ps.pymt_stat_id
                    LEFT JOIN {$this->table_appointment} a ON p.appt_id = a.app_id
                    ORDER BY p.paymt_id
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
            $sql = "SELECT p.paymt_id, p.paymt_amount_paid, p.paymt_date,
                    pm.pymt_meth_name, ps.pymt_stat_name,
                    a.app_id, a.app_date, a.app_time, a.app_status, a.app_reason,
                    CONCAT(pat.pat_last_name, ', ', pat.pat_first_name, ' ', pat.pat_middle_init) as patient_name,
                    CONCAT(doc.doc_last_name, ', ', doc.doc_first_name, ' ', doc.doc_middle_init) as doctor_name,
                    DATE_FORMAT(p.paymt_date, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(a.app_date, '%M %d, %Y') as formatted_app_date,
                    DATE_FORMAT(a.app_time, '%h:%i %p') as formatted_app_time
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.pymt_meth_id = pm.pymt_meth_id
                    LEFT JOIN {$this->table_payment_status} ps ON p.pymt_stat_id = ps.pymt_stat_id
                    LEFT JOIN {$this->table_appointment} a ON p.appt_id = a.app_id
                    LEFT JOIN patient pat ON a.pat_id = pat.pat_id
                    LEFT JOIN doctor doc ON a.doc_id = doc.doc_id
                    WHERE p.paymt_id = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':paymt_id' => trim($paymt_id)]);
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
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting payments: " . $e->getMessage());
            return 0;
        }
    }

    // Get payment IDs and related info for dropdown
    public function getAllForDropdown() {
        try {
            $sql = "SELECT p.paymt_id,
                    CONCAT('Payment #', p.paymt_id, ' - ', FORMAT(p.paymt_amount_paid, 2), ' (', ps.pymt_stat_name, ')') as payment_info
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_status} ps ON p.pymt_stat_id = ps.pymt_stat_id
                    ORDER BY p.paymt_id";

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
            $sql = "SELECT p.paymt_id, p.paymt_amount_paid, p.paymt_date,
                    pm.pymt_meth_name, ps.pymt_stat_name, a.app_id,
                    DATE_FORMAT(p.paymt_date, '%M %d, %Y %h:%i %p') as formatted_paymt_date,
                    DATE_FORMAT(p.pymt_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at
                    FROM {$this->table_payment} p
                    LEFT JOIN {$this->table_payment_method} pm ON p.pymt_meth_id = pm.pymt_meth_id
                    LEFT JOIN {$this->table_payment_status} ps ON p.pymt_stat_id = ps.pymt_stat_id
                    LEFT JOIN {$this->table_appointment} a ON p.appt_id = a.app_id
                    WHERE p.paymt_id LIKE :search
                    OR pm.pymt_meth_name LIKE :search
                    OR ps.pymt_stat_name LIKE :search
                    OR CAST(p.paymt_amount_paid AS CHAR) LIKE :search
                    GROUP BY p.paymt_id
                    ORDER BY p.paymt_id";

            $stmt = $this->conn->prepare($sql);
            $searchParam = '%' . trim($searchTerm) . '%';
            $stmt->execute([':search' => $searchParam]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching payments: " . $e->getMessage());
            return [];
        }
    }

// CREATE
// removing paymt_id from the INSERT statement and using lastInsertId() to return the newly created ID
    public function create($payment) {
        try {
            $sql = "INSERT INTO {$this->table_payment}
                    (paymt_amount_paid, paymt_date, pymt_meth_id, pymt_stat_id, appt_id, pymt_created_at, pymt_updated_at)
                    VALUES (:paymt_amount_paid, :paymt_date, :pymt_meth_id, :pymt_stat_id, :appt_id, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                ':paymt_amount_paid' => $payment['paymt_amount_paid'],
                ':paymt_date'        => trim($payment['paymt_date']),
                ':pymt_meth_id'      => trim($payment['pymt_meth_id']),
                ':pymt_stat_id'      => trim($payment['pymt_stat_id']),
                ':appt_id'           => trim($payment['appt_id'])
            ]);

            if ($success) {
                $newPaymtId = $this->conn->lastInsertId();
                return $newPaymtId; // Return the auto-incremented PAYMT_ID
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating payment: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE
    public function update($payment) {
        try {
            $sql = "UPDATE {$this->table_payment}
                    SET paymt_amount_paid = :paymt_amount_paid,
                        paymt_date = :paymt_date,
                        pymt_meth_id = :pymt_meth_id,
                        pymt_stat_id = :pymt_stat_id,
                        appt_id = :appt_id,
                        pymt_updated_at = NOW()
                    WHERE paymt_id = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':paymt_id'          => trim($payment['paymt_id']),
                ':paymt_amount_paid' => $payment['paymt_amount_paid'],
                ':paymt_date'        => $payment['paymt_date'],
                ':pymt_meth_id'      => $payment['pymt_meth_id'],
                ':pymt_stat_id'      => $payment['pymt_stat_id'],
                ':appt_id'           => $payment['appt_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error updating payment: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($paymt_id) {
        try {
            $sql = "DELETE FROM {$this->table_payment} WHERE paymt_id = :paymt_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':paymt_id' => trim($paymt_id)]);
        } catch (PDOException $e) {
            error_log("Error deleting payment: " . $e->getMessage());
            return false;
        }
    }
}
?>