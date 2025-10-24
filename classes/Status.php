<?php
class Status {
    private $conn;
    private $table_name = "STATUS";

    private $stat_id;
    private $status_name;
    private $status_created_at;
    private $status_updated_at;

    public function __construct($db){
        $this->conn = $db;
    }

    // Setters
    public function setStatId($id) { $this->stat_id = $id; }
    public function setStatusName($name) { $this->status_name = $name; }
    public function setStatusCreatedAt($datetime) { $this->status_created_at = $datetime; }
    public function setStatusUpdatedAt($datetime) { $this->status_updated_at = $datetime; }

    // Getters
    public function getStatId() { return $this->stat_id; }
    public function getStatusName() { return $this->status_name; }
    public function getStatusCreatedAt() { return $this->status_created_at; }
    public function getStatusUpdatedAt() { return $this->status_updated_at; }

    // CRUD methods
    public function create(){
        $query = "INSERT INTO {$this->table_name} (STATUS_NAME, STATUS_CREATED_AT)
                  VALUES (:status_name, :created_at)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_name', $this->status_name);
        $stmt->bindParam(':created_at', $this->status_created_at);

        if($stmt->execute()){
            $this->stat_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll(){
        $query = "SELECT * FROM {$this->table_name} ORDER BY STATUS_NAME ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne(){
        $query = "SELECT * FROM {$this->table_name} WHERE STAT_ID = :stat_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':stat_id', $this->stat_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row){
            $this->status_name = $row['STATUS_NAME'];
            $this->status_created_at = $row['STATUS_CREATED_AT'];
            $this->status_updated_at = $row['STATUS_UPDATED_AT'];
            return true;
        }
        return false;
    }

    public function update(){
        $query = "UPDATE {$this->table_name}
                  SET STATUS_NAME = :status_name, STATUS_UPDATED_AT = :updated_at
                  WHERE STAT_ID = :stat_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status_name', $this->status_name);
        $stmt->bindParam(':updated_at', $this->status_updated_at);
        $stmt->bindParam(':stat_id', $this->stat_id);

        return $stmt->execute();
    }

    public function delete(){
        $query = "DELETE FROM {$this->table_name} WHERE STAT_ID = :stat_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':stat_id', $this->stat_id);
        return $stmt->execute();
    }
}
?>
