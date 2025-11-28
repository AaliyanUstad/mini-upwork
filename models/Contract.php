<?php
class Contract {
    private $conn;
    private $table_name = "contracts";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getContractById($id) {
        $query = "SELECT c.*, j.title as job_title, j.description,
                         client.first_name as client_first_name, 
                         client.last_name as client_last_name,
                         client.company_name as client_company_name,
                         freelancer.first_name as freelancer_first_name,
                         freelancer.last_name as freelancer_last_name
                  FROM " . $this->table_name . " c 
                  JOIN jobs j ON c.job_id = j.id 
                  JOIN users client ON c.client_id = client.id 
                  JOIN users freelancer ON c.freelancer_id = freelancer.id 
                  WHERE c.id = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $contract = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $contract['client_name'] = $contract['client_company_name'] ?: 
                                      $contract['client_first_name'] . ' ' . $contract['client_last_name'];
            $contract['freelancer_name'] = $contract['freelancer_first_name'] . ' ' . $contract['freelancer_last_name'];
            
            return $contract;
        }
        return false;
    }

    public function updatePaymentStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET payment_status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    public function getClientContracts($client_id) {
    $query = "SELECT c.*, j.title as job_title, 
                     freelancer.first_name as f_first_name, 
                     freelancer.last_name as f_last_name
              FROM " . $this->table_name . " c 
              JOIN jobs j ON c.job_id = j.id 
              JOIN users freelancer ON c.freelancer_id = freelancer.id 
              WHERE c.client_id = ? 
              ORDER BY c.created_at DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->execute([$client_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}
?>