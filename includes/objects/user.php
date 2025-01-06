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

    public function __construct($db){
        $this->conn = $db;
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


  // Check if email exists
public function emailExists() {
    $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE email = :email";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':email', $this->email);
    $stmt->execute();
    
    // Fetch the count directly
    $count = $stmt->fetchColumn();
    return $count > 0;
}


    // Check if username exists
public function usernameExists() {
    $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE username = :username";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':username', $this->username);
    $stmt->execute();

    // Fetch the count directly
    $count = $stmt->fetchColumn();
    return $count > 0;
}




// Verify password
    public function verifyPassword($password) {
        try {
            $query = "SELECT password FROM " . $this->table_name . " 
                     WHERE user_id = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->user_id);
            $stmt->execute();
            
            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return password_verify($password, $row['password']);
            }
            return false;
        } catch(Exception $e) {
            error_log("Password verification error: " . $e->getMessage());
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


    // Login user
    public function login($email, $password) {
        try {
            $query = "SELECT 
                        user_id,
                        username,
                        password,
                        role,
                        reputation_points
                    FROM " . $this->table_name . "
                    WHERE email = ?
                    LIMIT 1";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $email);
            $stmt->execute();

            if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if(password_verify($password, $row['password'])) {
                    return [
                        'user_id' => $row['user_id'],
                        'username' => $row['username'],
                        'role' => $row['role'],
                        'reputation_points' => $row['reputation_points']
                    ];
                }
            }
            return false;
        } catch(Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    // Logout function
public function logout() {
    // Start the session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Unset all session variables
    $_SESSION = [];

    // Destroy the session
    session_destroy();

    // Redirect to login page or home page
    header("Location: /login.php");
    exit();
}



}
