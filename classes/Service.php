<?php
class Service {
    private $conn;
    private $table_name = "SERVICE";

    private $serv_id;
    private $serv_name;
    private $serv_description;
    private $serv_price;
    private $serv_created_at;
    private $serv_updated_at;

    public function __construct($db){
        $this->conn = $db;
    }

    // Setters
    public function setServId($id) { $this->serv_id = $id; }
    public function setServName($name) { $this->serv_name = $name; }
    public function setServDescription($desc) { $this->serv_description = $desc; }
    public function setServPrice($price) { $this->serv_price = $price; }
    public function setServCreatedAt($datetime) { $this->serv_created_at = $datetime; }
    public function setServUpdatedAt($datetime) { $this->serv_updated_at = $datetime; }

    // Getters
    public function getServId() { return $this->serv_id; }
    public function getServName() { return $this->serv_name; }
    public function getServDescription() { return $this->serv_description; }
    public function getServPrice() { return $this->serv_price; }
    public function getServCreatedAt() { return $this->serv_created_at; }
    public function getServUpdatedAt() { return $this->serv_updated_at; }

    // CRUD methods
    public function create(){
        $query = "INSERT INTO {$this->table_name} (SERV_NAME, SERV_DESCRIPTION, SERV_PRICE, SERV_CREATED_AT)
                  VALUES (:serv_name, :serv_description, :serv_price, :created_at)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_name', $this->serv_name);
        $stmt->bindParam(':serv_description', $this->serv_description);
        $stmt->bindParam(':serv_price', $this->serv_price);
        $stmt->bindParam(':created_at', $this->serv_created_at);

        if($stmt->execute()){
            $this->serv_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAll(){
        $query = "SELECT * FROM {$this->table_name} ORDER BY SERV_NAME ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne(){
        $query = "SELECT * FROM {$this->table_name} WHERE SERV_ID = :serv_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_id', $this->serv_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row){
            $this->serv_name = $row['SERV_NAME'];
            $this->serv_description = $row['SERV_DESCRIPTION'];
            $this->serv_price = $row['SERV_PRICE'];
            $this->serv_created_at = $row['SERV_CREATED_AT'];
            $this->serv_updated_at = $row['SERV_UPDATED_AT'];
            return true;
        }
        return false;
    }

    public function update(){
        $query = "UPDATE {$this->table_name}
                  SET SERV_NAME = :serv_name, SERV_DESCRIPTION = :serv_description, SERV_PRICE = :serv_price, SERV_UPDATED_AT = :updated_at
                  WHERE SERV_ID = :serv_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_name', $this->serv_name);
        $stmt->bindParam(':serv_description', $this->serv_description);
        $stmt->bindParam(':serv_price', $this->serv_price);
        $stmt->bindParam(':updated_at', $this->serv_updated_at);
        $stmt->bindParam(':serv_id', $this->serv_id);

        return $stmt->execute();
    }

    public function delete(){
        $query = "DELETE FROM {$this->table_name} WHERE SERV_ID = :serv_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_id', $this->serv_id);
        return $stmt->execute();
    }
}
?>
