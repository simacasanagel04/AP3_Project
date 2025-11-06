<?php
class Payment_Status {
    private $conn;
    private $table_payment_status = "payment_status";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Search by ID - Returns payment status data with row number
    public function findById($pymt_stat_id) {
        try {
            // Get row number
            $sqlRowNum = "SELECT COUNT(*) as row_num FROM {$this->table_payment_status} WHERE pymt_stat_id <= :pymt_stat_id ORDER BY pymt_stat_id";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':pymt_stat_id' => trim($pymt_stat_id)]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get payment status data
            $sql = "SELECT pymt_stat_id, pymt_stat_name,
                    DATE_FORMAT(pymt_stat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_stat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_status}
                    WHERE pymt_stat_id = :pymt_stat_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':pymt_stat_id' => trim($pymt_stat_id)]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($status) {
                $status['row_number'] = $rowData['row_num'];
                return $status;
            }

            // Return 0 if payment status does not exist
            return 0;
        } catch (PDOException $e) {
            error_log("Error finding payment status: " . $e->getMessage());
            return 0;
        }
    }

    // Display all payment statuses
    public function all() {
        try {
            $sql = "SELECT pymt_stat_id, pymt_stat_name,
                    DATE_FORMAT(pymt_stat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_stat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_status}
                    ORDER BY pymt_stat_id";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all payment statuses: " . $e->getMessage());
            return [];
        }
    }

    // Display all with pagination
    public function allPaginated($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT pymt_stat_id, pymt_stat_name,
                    DATE_FORMAT(pymt_stat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_stat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_status}
                    ORDER BY pymt_stat_id
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated payment statuses: " . $e->getMessage());
            return [];
        }
    }

    // Get total count for pagination
    public function count() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table_payment_status}";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting payment statuses: " . $e->getMessage());
            return 0;
        }
    }

    // Get payment status names for dropdown
    public function getAllForDropdown() {
        try {
            $sql = "SELECT pymt_stat_id, pymt_stat_name
                    FROM {$this->table_payment_status}
                    ORDER BY pymt_stat_name";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching dropdown payment statuses: " . $e->getMessage());
            return [];
        }
    }

    // Search payment statuses
    public function search($searchTerm) {
        try {
            $sql = "SELECT pymt_stat_id, pymt_stat_name,
                    DATE_FORMAT(pymt_stat_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_stat_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_status}
                    WHERE pymt_stat_name LIKE :search
                    ORDER BY pymt_stat_id";

            $stmt = $this->conn->prepare($sql);
            $searchParam = '%' . trim($searchTerm) . '%';
            $stmt->execute([':search' => $searchParam]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching payment statuses: " . $e->getMessage());
            return [];
        }
    }

    // CREATE
    public function create($payment_Status) {
        try {
            $sql = "INSERT INTO {$this->table_payment_status}
                    (pymt_stat_name, pymt_stat_created_at, pymt_stat_updated_at)
                    VALUES (:pymt_stat_name, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                ':pymt_stat_name' => trim($payment_Status['pymt_stat_name'])
            ]);

            if ($success) {
                $newPymtStatId = $this->conn->lastInsertId();
                return $newPymtStatId; // Return the auto-incremented PYMT_STAT_ID
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating payment status: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE
    public function update($payment_Status) {
        try {
            $sql = "UPDATE {$this->table_payment_status}
                    SET pymt_stat_name = :pymt_stat_name,
                        pymt_stat_updated_at = NOW()
                    WHERE pymt_stat_id = :pymt_stat_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':pymt_stat_id'    => trim($payment_Status['pymt_stat_id']),
                ':pymt_stat_name'  => trim($payment_Status['pymt_stat_name'])
            ]);
        } catch (PDOException $e) {
            error_log("Error updating payment status: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($pymt_stat_id) {
        try {
            $sql = "DELETE FROM {$this->table_payment_status} WHERE pymt_stat_id = :pymt_stat_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':pymt_stat_id' => trim($pymt_stat_id)]);
        } catch (PDOException $e) {
            error_log("Error deleting payment status: " . $e->getMessage());
            return false;
        }
    }
}
?>