<?php


class Post{

    private $conn;
    private $table_name = "Posts";

    public $post_id;
    public $user_id;
    public $category_id;
    public $title;
    public $description;
    public $tags;
    public $author_name;
    public $comment_count;
    public $user_vote;
    public $category_name;
    public $created_at;
    public $updated_at;
    public $upvotes;
    public $downvotes;
    public $is_admin = false;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create post
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                (user_id, category_id, title, description) 
                VALUES (?, ?, ?, ?)";

        try {
            $stmt = mysqli_prepare($this->conn, $query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . mysqli_error($this->conn));
            }

            // Sanitize input
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));
            
            // Bind parameters
            mysqli_stmt_bind_param($stmt, "iiss", 
                $this->user_id,
                $this->category_id,
                $this->title,
                $this->description
            );
            
            // Execute the statement
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            
            mysqli_stmt_close($stmt);
            throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            
        } catch(Exception $e) {
            error_log("Post creation error: " . $e->getMessage());
            throw $e;
        }
    }



 public static function readOne($id) {
    global $conn;
    
    $query = "SELECT 
        p.id,
        p.title,
        p.description,
        p.created_at,
        p.upvotes,
        p.downvotes,
        p.category_id,
        u.username,
        c.name as category_name,
        GROUP_CONCAT(DISTINCT t.name) as tags
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN post_tags pt ON p.id = pt.post_id
    LEFT JOIN tags t ON pt.tag_id = t.id
    WHERE p.id = ?
    GROUP BY p.id, c.name";

    try {
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to execute statement: " . mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return $row;
        }

        mysqli_stmt_close($stmt);
        return null;

    } catch (Exception $e) {
        if (isset($stmt)) {
            mysqli_stmt_close($stmt);
        }
        throw new Exception("Error reading post: " . $e->getMessage());
    }
}

// Update post
public function update() {
    $query = "UPDATE " . $this->table_name . "
            SET
                title = :title,
                content = :content,
                category_id = :category_id
            WHERE 
                post_id = :post_id AND 
                user_id = :user_id";

    $stmt = $this->conn->prepare($query);

    // Sanitize input
    $this->title = htmlspecialchars(strip_tags($this->title));
    $this->description = htmlspecialchars(strip_tags($this->description));
    $this->category_id = htmlspecialchars(strip_tags($this->category_id));
    $this->post_id = htmlspecialchars(strip_tags($this->post_id));
    $this->user_id = htmlspecialchars(strip_tags($this->user_id));

    // Bind values
    $stmt->bindParam(":title", $this->title);
    $stmt->bindParam(":description", $this->description);
    $stmt->bindParam(":category_id", $this->category_id);
    $stmt->bindParam(":post_id", $this->post_id);
    $stmt->bindParam(":user_id", $this->user_id);

    if($stmt->execute()) {
        return true;
    }
    return false;
}


// Delete post
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE id = ? AND 
                (user_id = ? OR ? = TRUE)";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bind_param("iii", $this->post_id, $this->user_id, $this->is_admin);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }



// Format post data for response
    public function formatPostData() {
        return array(
            "post_id" => $this->post_id,
            "title" => $this->title,
            "content" => $this->description,
            "user_id" => $this->user_id,
            "author_name" => $this->author_name,
            "category_id" => $this->category_id,
            "category_name" => $this->category_name,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
            "comment_count" => $this->comment_count,
            "upvotes" => $this->upvotes,
            "downvotes" => $this->downvotes,
            "user_vote" => $this->user_vote ?? 0,
            "is_bookmarked" => $this->is_bookmarked ?? false
        );
    }


    // Search posts
    public function search($keywords, $page = 1, $per_page = 10) {
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT 
                    p.*, u.username as author_name,
                    (SELECT COUNT(*) FROM Comments WHERE post_id = p.post_id) as comment_count
                FROM " . $this->table_name . " p
                LEFT JOIN Users u ON p.user_id = u.user_id
                WHERE 
                    p.title LIKE :keywords OR 
                    p.content LIKE :keywords OR
                    u.username LIKE :keywords
                ORDER BY 
                    p.created_at DESC
                LIMIT :offset, :per_page";

        $stmt = $this->conn->prepare($query);

        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";

        // Bind values
        $stmt->bindParam(":keywords", $keywords);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":per_page", $per_page, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }


    // // Verify comment owner
    // public function verifyOwner() {
    //     $query = "SELECT 1 FROM " . $this->table_name . " 
    //             WHERE id = ? AND user_id = ?
    //             LIMIT 0,1";

    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bind_param("ii", $this->post_id, $this->user_id);
    //     $stmt->execute();
    //     $result = $stmt->get_result();
        
    //     return $result->num_rows > 0;
    // }




    // Vote on a post
    public function vote($user_id, $vote_type) {
        try {
            $this->conn->beginTransaction();

            // Check if user has already voted
            $check_query = "SELECT vote_type FROM PostVotes 
                        WHERE post_id = ? AND user_id = ?
                        LIMIT 0,1";
            
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(1, $this->post_id);
            $check_stmt->bindParam(2, $user_id);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
                if($row['vote_type'] == $vote_type) {
                    // Remove vote if same type (toggle)
                    $query = "DELETE FROM PostVotes 
                            WHERE post_id = ? AND user_id = ?";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(1, $this->post_id);
                    $stmt->bindParam(2, $user_id);
                } else {
                    // Update vote type
                    $query = "UPDATE PostVotes 
                            SET vote_type = :vote_type 
                            WHERE post_id = :post_id AND user_id = :user_id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(":vote_type", $vote_type);
                    $stmt->bindParam(":post_id", $this->post_id);
                    $stmt->bindParam(":user_id", $user_id);
                }
            } else {
                // Create new vote
                $query = "INSERT INTO PostVotes 
                        SET post_id = :post_id,
                            user_id = :user_id,
                            vote_type = :vote_type";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(":post_id", $this->post_id);
                $stmt->bindParam(":user_id", $user_id);
                $stmt->bindParam(":vote_type", $vote_type);
            }

            $stmt->execute();
            $this->conn->commit();
            return true;
        } catch(Exception $e) {
            $this->conn->rollBack();
            error_log("Error in vote: " . $e->getMessage());
            throw new Exception("Failed to process vote.");
        }
    }

// Get all users who voted on a post
public function getAllUsersVotedOnPost() {
    try {
        $query = "SELECT user_id, vote_type 
                FROM PostVotes 
                WHERE post_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->post_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        error_log("Error getting all votes on post: " . $e->getMessage());
        throw new Exception("Failed to retrieve votes.");
    }
}

// Get vote counts for a post
    public function getVoteCounts() {
        try {
            $query = "SELECT 
                        SUM(CASE WHEN vote_type = 1 THEN 1 ELSE 0 END) as upvotes,
                        SUM(CASE WHEN vote_type = -1 THEN 1 ELSE 0 END) as downvotes
                    FROM PostVotes 
                    WHERE post_id = ?";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->post_id);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return array(
                'upvotes' => (int)($row['upvotes'] ?? 0),
                'downvotes' => (int)($row['downvotes'] ?? 0)
            );
        } catch(Exception $e) {
            error_log("Error getting vote counts: " . $e->getMessage());
            throw new Exception("Failed to get vote counts.");
        }
    }

// Verify post owner
public static function verifyOwner($post_id, $user_id) {
    global $conn;
    
    try {
        $query = "SELECT 1 FROM posts WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement");
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $isOwner = mysqli_fetch_row($result) ? true : false;
        
        mysqli_stmt_close($stmt);
        return $isOwner;
        
    } catch (Exception $e) {
        error_log("Error verifying owner: " . $e->getMessage());
        return false;
    }
}

}