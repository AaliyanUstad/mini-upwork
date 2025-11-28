<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $password;
    public $user_type;
    public $first_name;
    public $last_name;
    public $company_name;
    public $bio;
    public $skills;
    public $hourly_rate;
    public $portfolio_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET email=:email, password=:password, user_type=:user_type, 
                     first_name=:first_name, last_name=:last_name, 
                     company_name=:company_name, created_at=NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->company_name = htmlspecialchars(strip_tags($this->company_name));
        
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":user_type", $this->user_type);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":company_name", $this->company_name);
        
        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function emailExists() {
        $query = "SELECT id, password, user_type, first_name, last_name 
                  FROM " . $this->table_name . " 
                  WHERE email = ? 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->password = $row['password'];
            $this->user_type = $row['user_type'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            return true;
        }
        return false;
    }

    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                 SET first_name=:first_name, last_name=:last_name, 
                     company_name=:company_name, bio=:bio, skills=:skills, 
                     hourly_rate=:hourly_rate, portfolio_url=:portfolio_url 
                 WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->company_name = htmlspecialchars(strip_tags($this->company_name));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->skills = htmlspecialchars(strip_tags($this->skills));
        $this->hourly_rate = htmlspecialchars(strip_tags($this->hourly_rate));
        $this->portfolio_url = htmlspecialchars(strip_tags($this->portfolio_url));
        
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":company_name", $this->company_name);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":skills", $this->skills);
        $stmt->bindParam(":hourly_rate", $this->hourly_rate);
        $stmt->bindParam(":portfolio_url", $this->portfolio_url);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
}
?>