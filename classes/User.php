<?php
class User {
    private $conn;
    private $table = "USERS";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function emailExists($email) {
        $sql = "SELECT USER_ID FROM {$this->table} WHERE USER_NAME = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }

        public function findByUsername($username) {
            $sql = "SELECT * FROM {$this->table} WHERE USER_NAME = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

    public function create($data) {
    $sql = "INSERT INTO {$this->table} 
            (USER_NAME, PASSWORD, PAT_ID, USER_CREATED_AT, USER_UPDATED_AT, USER_IS_SUPERADMIN)
            VALUES (:user_name, :password, :pat_id, NOW(), NOW(), 0)";

    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        ':user_name' => $data['user_name'],
        ':password'  => $data['password'],
        ':pat_id'    => $data['pat_id']
    ]);
}

}