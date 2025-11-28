<?php
class Review {
    private $conn;
    private $table_name = "reviews";

    public $id;
    public $contract_id;
    public $reviewer_id;
    public $reviewee_id;
    public $rating;
    public $comment;
    public $type;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET contract_id=:contract_id, reviewer_id=:reviewer_id, 
                     reviewee_id=:reviewee_id, rating=:rating, comment=:comment, 
                     type=:type, created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $this->contract_id = htmlspecialchars(strip_tags($this->contract_id));
        $this->reviewer_id = htmlspecialchars(strip_tags($this->reviewer_id));
        $this->reviewee_id = htmlspecialchars(strip_tags($this->reviewee_id));
        $this->rating = htmlspecialchars(strip_tags($this->rating));
        $this->comment = htmlspecialchars(strip_tags($this->comment));
        $this->type = htmlspecialchars(strip_tags($this->type));
        
        $stmt->bindParam(":contract_id", $this->contract_id);
        $stmt->bindParam(":reviewer_id", $this->reviewer_id);
        $stmt->bindParam(":reviewee_id", $this->reviewee_id);
        $stmt->bindParam(":rating", $this->rating);
        $stmt->bindParam(":comment", $this->comment);
        $stmt->bindParam(":type", $this->type);
        
        if($stmt->execute()) {
            $this->updateUserRating($this->reviewee_id);
            return true;
        }
        return false;
    }

    private function updateUserRating($user_id) {
        $query = "SELECT AVG(rating) as avg_rating 
                  FROM " . $this->table_name . " 
                  WHERE reviewee_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $avg_rating = $result['avg_rating'] ? round($result['avg_rating'], 1) : 0;
        
        $update_query = "UPDATE users SET rating = ? WHERE id = ?";
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->execute([$avg_rating, $user_id]);
    }

    public function getUserReviews($user_id) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.company_name,
                         c.contract_amount, j.title as job_title
                  FROM " . $this->table_name . " r 
                  JOIN users u ON r.reviewer_id = u.id 
                  JOIN contracts c ON r.contract_id = c.id 
                  JOIN jobs j ON c.job_id = j.id 
                  WHERE r.reviewee_id = ? 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hasReviewedContract($contract_id, $reviewer_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE contract_id = ? AND reviewer_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$contract_id, $reviewer_id]);
        
        return $stmt->rowCount() > 0;
    }

    public function getContractReviews($contract_id) {
        $query = "SELECT r.*, u.first_name, u.last_name, u.company_name 
                  FROM " . $this->table_name . " r 
                  JOIN users u ON r.reviewer_id = u.id 
                  WHERE r.contract_id = ? 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$contract_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>