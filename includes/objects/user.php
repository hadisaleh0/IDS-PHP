<?php 

class User{

    private $conn;
    private $table_name = "Users";

    public $user_id;
    public $username;
    public $email;
    public $password;
    public $created_at;
    public $role;
    public $reputation_points;

    public function __construct($db) {
        if (!$db) {
            error_log("Database connection is null in User constructor");
            throw new Exception("Database connection failed");
        }
        $this->conn = $db;
        error_log("Database connection established in User constructor");
    }

    public function create(){
        try{
            $check_query = "SELECT id FROM " . $this->table_name . " WHERE email = ? LIMIT 1";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bind_param("s", $this->email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if($result->num_rows > 0) {
                throw new Exception("Email already exists");
            }
            
            $query = "INSERT INTO " . $this->table_name . "
                    (username, email, password)
                    VALUES (?, ?, ?)";
        
            $stmt = $this->conn->prepare($query);
            
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            
            $stmt->bind_param("sss", 
                $this->username,
                $this->email,
                $this->password
            );
            
            if($stmt->execute()) {
                return true;
            }
            
            throw new Exception($stmt->error);
        } catch(Exception $e) {
            error_log("User creation error: " . $e->getMessage());
            return false;
        }
    }


    // Read all users 
public function readAll() {
    $query = <<<SQL
        SELECT 
            user_id, 
            username, 
            email, 
            role, 
            reputation_points, 
            created_at, 
            last_login
        FROM {$this->table_name}
        ORDER BY created_at DESC
    SQL;

    try {
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Fetch all results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Failed to read users: " . $e->getMessage());
        return false;
    }
}


// Delete user
    public function delete() {
        try {
            $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("i", $this->user_id);
            
            if($stmt->execute()) {
                return true;
            }
            
            throw new Exception("Delete failed: " . $stmt->error);
        } catch(Exception $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }


    // Update password
    public function updatePassword($new_password) {
        try {
            $query = "UPDATE " . $this->table_name . "
                    SET password = :password
                    WHERE user_id = :user_id";

            $stmt = $this->conn->prepare($query);
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->bindParam(":user_id", $this->user_id);

            return $stmt->execute();
        } catch(Exception $e) {
            error_log("Password update error: " . $e->getMessage());
            return false;
        }
    }



}
