<?php
class Patient {
    private $conn;
    private $table = "patient";

    public function __construct($db) {
        $this->conn = $db;
    }

    // CREATE
    public function create($pat_first_name, $pat_middle_init, $pat_last_name, $pat_dob, $pat_gender, $pat_contact_num, $pat_email, $pat_address) {
        $sql = "INSERT INTO {$this->table} 
                (pat_first_name, pat_middle_init, pat_last_name, pat_dob, pat_gender, pat_contact_num, pat_email, pat_address, pat_created_at, pat_updated_at)
                VALUES (:pat_first_name, :pat_middle_init, :pat_last_name, :pat_dob, :pat_gender, :pat_contact_num, :pat_email, :pat_address, NOW(), NOW())";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':pat_first_name' => trim($pat_first_name),
            ':pat_middle_init' => trim($pat_middle_init),
            ':pat_last_name' => trim($pat_last_name),
            ':pat_dob' => $pat_dob,
            ':pat_gender' => trim($pat_gender),
            ':pat_contact_num' => trim($pat_contact_num),
            ':pat_email' => trim($pat_email),
            ':pat_address' => trim($pat_address)
        ]);
    }

    // READ ALL (with pagination)
    public function all($limit = 10, $offset = 0) {
        $sql = "SELECT p.pat_id,
                       p.pat_first_name,
                       p.pat_middle_init,
                       p.pat_last_name,
                       CONCAT(p.pat_first_name, ' ', 
                              CASE WHEN p.pat_middle_init IS NOT NULL AND p.pat_middle_init != '' 
                                   THEN CONCAT(p.pat_middle_init, '. ') 
                                   ELSE '' 
                              END, 
                              p.pat_last_name) AS full_name,
                       p.pat_dob,
                       p.pat_gender, 
                       p.pat_contact_num, 
                       p.pat_email, 
                       p.pat_address,
                       DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') AS created_at,
                       DATE_FORMAT(p.pat_updated_at, '%M %d, %Y %h:%i %p') AS updated_at
                FROM {$this->table} p
                ORDER BY p.pat_last_name ASC, p.pat_first_name ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // FIND BY ID (returns row + row number or 0 if not found)
    public function findById($pat_id) {
        $sql = "SELECT p.*, 
                       CONCAT(p.pat_first_name, ' ', 
                              CASE WHEN p.pat_middle_init IS NOT NULL AND p.pat_middle_init != '' 
                                   THEN CONCAT(p.pat_middle_init, '. ') 
                                   ELSE '' 
                              END, 
                              p.pat_last_name) AS full_name,
                       DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') AS created_at,
                       DATE_FORMAT(p.pat_updated_at, '%M %d, %Y %h:%i %p') AS updated_at
                FROM {$this->table} p
                WHERE p.pat_id = :pat_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':pat_id' => (int)$pat_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $indexSql = "SELECT COUNT(*) AS row_num 
                         FROM {$this->table} 
                         WHERE pat_id <= :pat_id";
            $indexStmt = $this->conn->prepare($indexSql);
            $indexStmt->execute([':pat_id' => (int)$pat_id]);
            $rowNumber = $indexStmt->fetch(PDO::FETCH_ASSOC)['row_num'] ?? 0;
            $row['row_number'] = $rowNumber;
            return $row;
        } else {
            return 0;
        }
    }

    // UPDATE
    public function update($pat_id, $pat_first_name, $pat_middle_init, $pat_last_name, $pat_dob, $pat_gender, $pat_contact_num, $pat_email, $pat_address) {
        $sql = "UPDATE {$this->table} 
                SET pat_first_name = :pat_first_name,
                    pat_middle_init = :pat_middle_init, 
                    pat_last_name = :pat_last_name,
                    pat_dob = :pat_dob,
                    pat_gender = :pat_gender, 
                    pat_contact_num = :pat_contact_num, 
                    pat_email = :pat_email,
                    pat_address = :pat_address,
                    pat_updated_at = NOW() 
                WHERE pat_id = :pat_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':pat_first_name' => trim($pat_first_name),
            ':pat_middle_init' => trim($pat_middle_init),
            ':pat_last_name' => trim($pat_last_name),
            ':pat_dob' => $pat_dob,
            ':pat_gender' => trim($pat_gender),
            ':pat_contact_num' => trim($pat_contact_num),
            ':pat_email' => trim($pat_email),
            ':pat_address' => trim($pat_address),
            ':pat_id' => (int)$pat_id
        ]);
    }

    // DELETE
    public function delete($pat_id) {
        $sql = "DELETE FROM {$this->table} WHERE pat_id = :pat_id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([':pat_id' => (int)$pat_id]);
    }

    // COUNT TOTAL RECORDS (for pagination)
    public function count() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // SEARCH PATIENTS
    public function search($keyword, $limit = 10, $offset = 0) {
        $sql = "SELECT p.pat_id,
                       p.pat_first_name,
                       p.pat_middle_init,
                       p.pat_last_name,
                       CONCAT(p.pat_first_name, ' ', 
                              CASE WHEN p.pat_middle_init IS NOT NULL AND p.pat_middle_init != '' 
                                   THEN CONCAT(p.pat_middle_init, '. ') 
                                   ELSE '' 
                              END, 
                              p.pat_last_name) AS full_name,
                       p.pat_dob,
                       p.pat_gender,
                       p.pat_contact_num, 
                       p.pat_email,
                       p.pat_address,
                       DATE_FORMAT(p.pat_created_at, '%M %d, %Y %h:%i %p') AS created_at,
                       DATE_FORMAT(p.pat_updated_at, '%M %d, %Y %h:%i %p') AS updated_at
                FROM {$this->table} p
                WHERE p.pat_first_name LIKE :keyword 
                   OR p.pat_last_name LIKE :keyword
                   OR p.pat_email LIKE :keyword
                   OR p.pat_contact_num LIKE :keyword
                ORDER BY p.pat_last_name ASC, p.pat_first_name ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':keyword', '%' . trim($keyword) . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>