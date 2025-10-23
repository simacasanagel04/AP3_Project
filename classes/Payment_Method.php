<?php
class Payment_Method {
    private $conn;
    private $table_payment_method = "payment_method";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Search by ID - Returns payment method data with row number
    public function findById($pymt_meth_id) {
        try {
            // Get row number
            $sqlRowNum = "SELECT COUNT(*) as row_num FROM {$this->table_payment_method} WHERE pymt_meth_id <= :pymt_meth_id ORDER BY pymt_meth_id";
            $stmtRowNum = $this->conn->prepare($sqlRowNum);
            $stmtRowNum->execute([':pymt_meth_id' => trim($pymt_meth_id)]);
            $rowData = $stmtRowNum->fetch(PDO::FETCH_ASSOC);

            // Get payment method data
            $sql = "SELECT pymt_meth_id, pymt_meth_name,
                    DATE_FORMAT(pymt_meth_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_meth_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_method}
                    WHERE pymt_meth_id = :pymt_meth_id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':pymt_meth_id' => trim($pymt_meth_id)]);
            $method = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($method) {
                $method['row_number'] = $rowData['row_num'];
                return $method;
            }

            // Return 0 if payment method does not exist
            return 0;
        } catch (PDOException $e) {
            error_log("Error finding payment method: " . $e->getMessage());
            return 0;
        }
    }

    // Display all payment methods
    public function all() {
        try {
            $sql = "SELECT pymt_meth_id, pymt_meth_name,
                    DATE_FORMAT(pymt_meth_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_meth_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_method}
                    ORDER BY pymt_meth_id";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all payment methods: " . $e->getMessage());
            return [];
        }
    }

    // Display all with pagination
    public function allPaginated($limit = 10, $offset = 0) {
        try {
            $sql = "SELECT pymt_meth_id, pymt_meth_name,
                    DATE_FORMAT(pymt_meth_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_meth_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_method}
                    ORDER BY pymt_meth_id
                    LIMIT :limit OFFSET :offset";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated payment methods: " . $e->getMessage());
            return [];
        }
    }

    // Get total count for pagination
    public function count() {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table_payment_method}";
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'];
        } catch (PDOException $e) {
            error_log("Error counting payment methods: " . $e->getMessage());
            return 0;
        }
    }

    // Get payment method names for dropdown
    public function getAllForDropdown() {
        try {
            $sql = "SELECT pymt_meth_id, pymt_meth_name
                    FROM {$this->table_payment_method}
                    ORDER BY pymt_meth_name";

            $stmt = $this->conn->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching dropdown payment methods: " . $e->getMessage());
            return [];
        }
    }

    // Search payment methods
    public function search($searchTerm) {
        try {
            $sql = "SELECT pymt_meth_id, pymt_meth_name,
                    DATE_FORMAT(pymt_meth_created_at, '%M %d, %Y %h:%i %p') as formatted_created_at,
                    DATE_FORMAT(pymt_meth_updated_at, '%M %d, %Y %h:%i %p') as formatted_updated_at
                    FROM {$this->table_payment_method}
                    WHERE pymt_meth_name LIKE :search
                    ORDER BY pymt_meth_id";

            $stmt = $this->conn->prepare($sql);
            $searchParam = '%' . trim($searchTerm) . '%';
            $stmt->execute([':search' => $searchParam]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error searching payment methods: " . $e->getMessage());
            return [];
        }
    }

    // CREATE
    public function create($payment_Method) {
        try {
            $sql = "INSERT INTO {$this->table_payment_method}
                    (pymt_meth_name, pymt_meth_created_at, pymt_meth_updated_at)
                    VALUES (:pymt_meth_name, NOW(), NOW())";

            $stmt = $this->conn->prepare($sql);
            $success = $stmt->execute([
                ':pymt_meth_name' => trim($payment_Method['pymt_meth_name'])
            ]);

            if ($success) {
                $newPymtMethId = $this->conn->lastInsertId();
                return $newPymtMethId; // Return the auto-incremented PYMT_METH_ID
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error creating payment method: " . $e->getMessage());
            return false;
        }
    }

    // UPDATE
    public function update($payment_Method) {
        try {
            $sql = "UPDATE {$this->table_payment_method}
                    SET pymt_meth_name = :pymt_meth_name,
                        pymt_meth_updated_at = NOW()
                    WHERE pymt_meth_id = :pymt_meth_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([
                ':pymt_meth_id'    => trim($payment_Method['pymt_meth_id']),
                ':pymt_meth_name'  => trim($payment_Method['pymt_meth_name'])
            ]);
        } catch (PDOException $e) {
            error_log("Error updating payment method: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function delete($pymt_meth_id) {
        try {
            $sql = "DELETE FROM {$this->table_payment_method} WHERE pymt_meth_id = :pymt_meth_id";

            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([':pymt_meth_id' => trim($pymt_meth_id)]);
        } catch (PDOException $e) {
            error_log("Error deleting payment method: " . $e->getMessage());
            return false;
        }
    }
}
?>