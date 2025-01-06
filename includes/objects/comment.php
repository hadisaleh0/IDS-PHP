<?php
class Comment {
    // Database connection and table name
    private $conn;
    public $table_name = "Comments";

    // Object properties
    public $comment_id;
    public $post_id;
    public $user_id;
    public $parent_id;
    public $content;
    public $created_at;
    public $updated_at;
    public $author_name;
    public $upvotes;
    public $downvotes;
    public $user_vote;
    public $is_admin = false;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }


    // Create comment
    public function create() {
        try {
            // Validate all data
            $this->validateData();

            // Validate parent comment if exists
            if(!$this->validateParentComment()) {
                throw new Exception("Invalid parent comment or parent comment belongs to different post.");
            }

            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        post_id = :post_id,
                        user_id = :user_id,
                        parent_id = :parent_id,
                        content = :content";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->post_id = htmlspecialchars(strip_tags($this->post_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));
            $this->content = htmlspecialchars(strip_tags($this->content));
            $this->parent_id = $this->parent_id ? htmlspecialchars(strip_tags($this->parent_id)) : null;

            // Bind values
            $stmt->bindParam(":post_id", $this->post_id);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":parent_id", $this->parent_id);
            $stmt->bindParam(":content", $this->content);

            if($stmt->execute()) {
                $this->comment_id = $this->conn->lastInsertId();
                return true;
            }
            throw new Exception("Failed to create comment.");
        } catch(Exception $e) {
            error_log("Comment creation error: " . $e->getMessage());
            throw $e;
        }
    }



    // Read single comment
    public function readOne($current_user_id = null) {
        $query = "SELECT 
                    c.*, u.username as author_name
                FROM " . $this->table_name . " c
                LEFT JOIN Users u ON c.user_id = u.id
                WHERE c.id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if($row) {
            $this->post_id = $row['post_id'];
            $this->user_id = $row['user_id'];
            $this->content = $row['content'];
            $this->created_at = $row['created_at'];
            $this->author_name = $row['author_name'];
            // Set default values for optional properties
            $this->parent_id = null;
            $this->updated_at = null;
            $this->upvotes = 0;
            $this->downvotes = 0;
            $this->user_vote = 0;
            return true;
        }
        return false;
    }



    // Update comment
    public function update() {
        try {
            // Validate content
            if(empty($this->content)) {
                throw new Exception("Content is required.");
            }
            if(strlen($this->content) < 1 || strlen($this->content) > 1000) {
                throw new Exception("Comment content must be between 1 and 1000 characters.");
            }

            // Check if comment exists and get original data
            if(!$this->readOne()) {
                throw new Exception("Comment not found.");
            }

            $query = "UPDATE " . $this->table_name . "
                    SET
                        content = :content,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE 
                        comment_id = :comment_id AND 
                        (user_id = :user_id OR :is_admin = TRUE)";

            $stmt = $this->conn->prepare($query);

            // Sanitize input
            $this->content = htmlspecialchars(strip_tags($this->content));
            $this->comment_id = htmlspecialchars(strip_tags($this->comment_id));
            $this->user_id = htmlspecialchars(strip_tags($this->user_id));

            // Bind values
            $stmt->bindParam(":content", $this->content);
            $stmt->bindParam(":comment_id", $this->comment_id);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":is_admin", $this->is_admin, PDO::PARAM_BOOL);

            if($stmt->execute() && $stmt->rowCount() > 0) {
                return true;
            }
            throw new Exception("Failed to update comment. You may not have permission.");
        } catch(Exception $e) {
            error_log("Comment update error: " . $e->getMessage());
            throw $e;
        }
    }


    // Delete comment
    public function delete() {
        try {
            // First check if comment exists
            if(!$this->readOne()) {
                throw new Exception("Comment not found.");
            }

            $query = "DELETE FROM " . $this->table_name . "
                    WHERE id = ? AND 
                    (user_id = ? OR ? = TRUE)";

            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iii", $this->comment_id, $this->user_id, $this->is_admin);

            if($stmt->execute()) {
                return true;
            }
            throw new Exception("Failed to delete comment.");
        } catch(Exception $e) {
            error_log("Comment deletion error: " . $e->getMessage());
            throw $e;
        }
    }



    // Validate comment data
    private function validateData() {
        // Check required fields
        if(empty($this->post_id)) {
            throw new Exception("Post ID is required.");
        }
        if(empty($this->user_id)) {
            throw new Exception("User ID is required.");
        }
        if(empty($this->content)) {
            throw new Exception("Content is required.");
        }

        // Validate content length
        if(strlen($this->content) < 1 || strlen($this->content) > 1000) {
            throw new Exception("Comment content must be between 1 and 1000 characters.");
        }

        // Validate post exists
        $query = "SELECT 1 FROM Posts WHERE post_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->post_id);
        $stmt->execute();
        if($stmt->rowCount() === 0) {
            throw new Exception("Post does not exist.");
        }

        // Validate user exists
        $query = "SELECT 1 FROM Users WHERE user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();
        if($stmt->rowCount() === 0) {
            throw new Exception("User does not exist.");
        }

        return true;
    }




    // Verify parent comment exists and belongs to the same post
    private function validateParentComment() {
        if(!$this->parent_id) {
            return true; // No parent comment is valid
        }

        $query = "SELECT post_id FROM " . $this->table_name . " 
                WHERE comment_id = ? AND post_id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->parent_id);
        $stmt->bindParam(2, $this->post_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }


    // Get all comments for a post
    public function getPostComments($post_id, $current_user_id = null) {
        $query = "SELECT 
                    c.*, 
                    u.username as author_name
                FROM " . $this->table_name . " c
                LEFT JOIN Users u ON c.user_id = u.user_id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $post_id);

        $stmt->execute();

        return $stmt;
    }



    // Get total comments count for a post
    public function getPostCommentsCount($post_id) {
        $query = "SELECT COUNT(*) as total 
                FROM " . $this->table_name . "
                WHERE post_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $post_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }



    // Get total comments count by post
    public function getTotalCountByPost($post_id) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . "
                WHERE post_id = ? AND parent_id IS NULL";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $post_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }


    // Verify comment owner
    public function verifyOwner() {
        $query = "SELECT 1 FROM " . $this->table_name . " 
                WHERE id = ? AND user_id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->comment_id, $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }




}