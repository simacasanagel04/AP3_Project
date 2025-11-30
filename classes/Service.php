<?php
// /classes/Service.php
class Service {
    private $conn;
    private $table_name = "service";

    // Make properties public for staff_service.php to access directly
    public $serv_id;
    public $serv_name;
    public $serv_description;
    public $serv_price;
    public $spec_id;
    public $serv_created_at;
    public $serv_updated_at;

    public function __construct($db){
        $this->conn = $db;
    }

    // Setters
    public function setServId($id) { $this->serv_id = $id; }
    public function setServName($name) { $this->serv_name = $name; }
    public function setServDescription($desc) { $this->serv_description = $desc; }
    public function setServPrice($price) { $this->serv_price = $price; }
    public function setSpecId($id) { $this->spec_id = $id; }
    public function setServCreatedAt($datetime) { $this->serv_created_at = $datetime; }
    public function setServUpdatedAt($datetime) { $this->serv_updated_at = $datetime; }

    // Getters
    public function getServId() { return $this->serv_id; }
    public function getServName() { return $this->serv_name; }
    public function getServDescription() { return $this->serv_description; }
    public function getServPrice() { return $this->serv_price; }
    public function getSpecId() { return $this->spec_id; }
    public function getServCreatedAt() { return $this->serv_created_at; }
    public function getServUpdatedAt() { return $this->serv_updated_at; }

    // Get services by specialization
    public function getBySpecialization($spec_id) {
        $query = "SELECT * FROM {$this->table_name} WHERE SPEC_ID = :spec_id ORDER BY SERV_NAME ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':spec_id', $spec_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // NEW: Alias for readAll() - needed by staff_service.php
    public function read(){
        return $this->readAll();
    }

    // NEW: Alias for readOne() - needed by staff_service.php
    public function readSingle(){
        return $this->readOne();
    }

    // CRUD methods
    public function create(){
        $query = "INSERT INTO {$this->table_name} 
                  (SERV_NAME, SERV_DESCRIPTION, SERV_PRICE, SPEC_ID, SERV_CREATED_AT)
                  VALUES (:serv_name, :serv_description, :serv_price, :spec_id, :created_at)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_name', $this->serv_name);
        $stmt->bindParam(':serv_description', $this->serv_description);
        $stmt->bindParam(':serv_price', $this->serv_price);
        $stmt->bindParam(':spec_id', $this->spec_id);
        $stmt->bindParam(':created_at', $this->serv_created_at);

        if($stmt->execute()){
            $this->serv_id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    /**
     * Read all services with specialization name
     */
    public function readAll(){
        $query = "SELECT 
                        s.SERV_ID,
                        s.SERV_NAME,
                        s.SERV_DESCRIPTION,
                        s.SERV_PRICE,
                        s.SPEC_ID,
                        s.SERV_CREATED_AT,
                        s.SERV_UPDATED_AT,
                        sp.SPEC_NAME 
                  FROM {$this->table_name} s 
                  LEFT JOIN specialization sp ON s.SPEC_ID = sp.SPEC_ID 
                  ORDER BY s.SERV_NAME ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
 * Searches for services based on keywords in name, description, or price.
 * @param string $keywords The search string.
 * @return array
 */
public function search($keyword) {
    $keyword = "%$keyword%";
    $query = "SELECT 
                    s.SERV_ID, 
                    s.SERV_NAME, 
                    s.SERV_DESCRIPTION, 
                    s.SERV_PRICE, 
                    s.SPEC_ID,
                    s.SERV_CREATED_AT, 
                    s.SERV_UPDATED_AT,
                    sp.SPEC_NAME
              FROM {$this->table_name} s
              LEFT JOIN specialization sp ON s.SPEC_ID = sp.SPEC_ID
              WHERE s.SERV_NAME LIKE :keyword1 
                 OR s.SERV_DESCRIPTION LIKE :keyword2 
                 OR CAST(s.SERV_PRICE AS CHAR) LIKE :keyword3
              ORDER BY s.SERV_ID DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute([
        ':keyword1' => $keyword,
        ':keyword2' => $keyword,
        ':keyword3' => $keyword
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    /**
     * Read one service by ID and populate object properties
     * Also returns full data including SPEC_ID
     */
    public function readOne(){
        $query = "SELECT 
                        SERV_ID, 
                        SERV_NAME, 
                        SERV_DESCRIPTION, 
                        SERV_PRICE, 
                        SPEC_ID,
                        SERV_CREATED_AT,
                        SERV_UPDATED_AT 
                  FROM {$this->table_name} 
                  WHERE SERV_ID = :serv_id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_id', $this->serv_id);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if($row){
                $this->serv_name = $row['SERV_NAME'];
                $this->serv_description = $row['SERV_DESCRIPTION'];
                $this->serv_price = $row['SERV_PRICE'];
                $this->spec_id = $row['SPEC_ID'];
                $this->serv_created_at = $row['SERV_CREATED_AT'];
                $this->serv_updated_at = $row['SERV_UPDATED_AT'];
                return $row; // Return full data including SPEC_ID
            }
        }
        return false;
    }

    public function update(){
        $query = "UPDATE {$this->table_name}
                  SET SERV_NAME = :serv_name, 
                      SERV_DESCRIPTION = :serv_description, 
                      SERV_PRICE = :serv_price, 
                      SPEC_ID = :spec_id,
                      SERV_UPDATED_AT = :updated_at
                  WHERE SERV_ID = :serv_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':serv_name', $this->serv_name);
        $stmt->bindParam(':serv_description', $this->serv_description);
        $stmt->bindParam(':serv_price', $this->serv_price);
        $stmt->bindParam(':spec_id', $this->spec_id);
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
