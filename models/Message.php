<?php
class Message {
    private $conn;
    private $table_name = "messages";

    public $id;
    public $sender_id;
    public $receiver_id;
    public $contract_id;
    public $message_text;
    public $is_read;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET sender_id=:sender_id, receiver_id=:receiver_id, 
                     contract_id=:contract_id, message_text=:message_text, 
                     sent_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $this->sender_id = htmlspecialchars(strip_tags($this->sender_id));
        $this->receiver_id = htmlspecialchars(strip_tags($this->receiver_id));
        $this->contract_id = htmlspecialchars(strip_tags($this->contract_id));
        $this->message_text = htmlspecialchars(strip_tags($this->message_text));
        
        $stmt->bindParam(":sender_id", $this->sender_id);
        $stmt->bindParam(":receiver_id", $this->receiver_id);
        $stmt->bindParam(":contract_id", $this->contract_id);
        $stmt->bindParam(":message_text", $this->message_text);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getContractMessages($contract_id, $user_id) {
        $query = "SELECT m.*, u.first_name, u.last_name 
                  FROM " . $this->table_name . " m 
                  JOIN users u ON m.sender_id = u.id 
                  WHERE m.contract_id = ? 
                  ORDER BY m.sent_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$contract_id]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->markMessagesAsRead($contract_id, $user_id);
        
        return $messages;
    }

    private function markMessagesAsRead($contract_id, $user_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_read = 1 
                 WHERE contract_id = ? AND receiver_id = ? AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$contract_id, $user_id]);
    }

    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as unread_count 
                  FROM " . $this->table_name . " 
                  WHERE receiver_id = ? AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$user_id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
    }
}
?>