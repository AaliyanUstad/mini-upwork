<?php
class Proposal {
    private $conn;
    private $table_name = "proposals";

    public $id;
    public $job_id;
    public $freelancer_id;
    public $cover_letter;
    public $bid_amount;
    public $estimated_hours;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET job_id=:job_id, freelancer_id=:freelancer_id, cover_letter=:cover_letter, 
                     bid_amount=:bid_amount, estimated_hours=:estimated_hours, 
                     status='pending', submitted_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $this->job_id = htmlspecialchars(strip_tags($this->job_id));
        $this->freelancer_id = htmlspecialchars(strip_tags($this->freelancer_id));
        $this->cover_letter = htmlspecialchars(strip_tags($this->cover_letter));
        $this->bid_amount = htmlspecialchars(strip_tags($this->bid_amount));
        $this->estimated_hours = htmlspecialchars(strip_tags($this->estimated_hours));
        
        $stmt->bindParam(":job_id", $this->job_id);
        $stmt->bindParam(":freelancer_id", $this->freelancer_id);
        $stmt->bindParam(":cover_letter", $this->cover_letter);
        $stmt->bindParam(":bid_amount", $this->bid_amount);
        $stmt->bindParam(":estimated_hours", $this->estimated_hours);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getJobProposals($job_id) {
        $query = "SELECT p.*, u.first_name, u.last_name, u.skills, u.hourly_rate 
                  FROM " . $this->table_name . " p 
                  JOIN users u ON p.freelancer_id = u.id 
                  WHERE p.job_id = ? 
                  ORDER BY p.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $job_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProposalById($id) {
        $query = "SELECT p.*, j.title, j.client_id, u.first_name, u.last_name 
                  FROM " . $this->table_name . " p 
                  JOIN jobs j ON p.job_id = j.id 
                  JOIN users u ON p.freelancer_id = u.id 
                  WHERE p.id = ? 
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
                 SET status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    public function createContract($proposal_id) {
        $proposal = $this->getProposalById($proposal_id);
        
        if(!$proposal) return false;

        $query = "INSERT INTO contracts 
                 SET proposal_id=:proposal_id, job_id=:job_id, freelancer_id=:freelancer_id, 
                     client_id=:client_id, contract_amount=:contract_amount, 
                     contract_status='active', start_date=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":proposal_id", $proposal_id);
        $stmt->bindParam(":job_id", $proposal['job_id']);
        $stmt->bindParam(":freelancer_id", $proposal['freelancer_id']);
        $stmt->bindParam(":client_id", $proposal['client_id']);
        $stmt->bindParam(":contract_amount", $proposal['bid_amount']);
        
        if($stmt->execute()) {
            $this->updateStatus($proposal_id, 'accepted');
            
            $job = new Job($this->conn);
            $job->updateStatus($proposal['job_id'], 'in_progress');
            
            return true;
        }
        return false;
    }
}
?>