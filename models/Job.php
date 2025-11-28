<?php
class Job {
    private $conn;
    private $table_name = "jobs";

    public $id;
    public $client_id;
    public $title;
    public $description;
    public $budget;
    public $budget_type;
    public $skills_required;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET client_id=:client_id, title=:title, description=:description, 
                     budget=:budget, budget_type=:budget_type, skills_required=:skills_required, 
                     status='open', created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $this->client_id = htmlspecialchars(strip_tags($this->client_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->budget = htmlspecialchars(strip_tags($this->budget));
        $this->budget_type = htmlspecialchars(strip_tags($this->budget_type));
        $this->skills_required = htmlspecialchars(strip_tags($this->skills_required));
        
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":budget", $this->budget);
        $stmt->bindParam(":budget_type", $this->budget_type);
        $stmt->bindParam(":skills_required", $this->skills_required);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getClientJobs($client_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE client_id = ? 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $client_id);
        $stmt->execute();
        
        return $stmt;
    }

    public function getJobById($id) {
        $query = "SELECT j.*, u.first_name, u.last_name, u.company_name 
                  FROM " . $this->table_name . " j 
                  JOIN users u ON j.client_id = u.id 
                  WHERE j.id = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status, updated_at = NOW() 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }
}
?>