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
                SET
                    user_id = :user_id,
                    category_id =:category_id,
                    title = :title,
                    content = :content";

        $stmt = $this->conn->prepare($query);

        // Sanitize input
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));

        // Bind values
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }


    // Read single post
// Read single post
public function readOne($current_user_id = null) {
    $query = "SELECT 
                p.*, 
                u.username as author_name, 
                c.category_name, 
                (SELECT COUNT(*) FROM Comments WHERE post_id = p.post_id) as comment_count, 
                (SELECT COUNT(*) FROM PostVotes WHERE post_id = p.post_id AND vote_type = 1) as upvotes, 
                (SELECT COUNT(*) FROM PostVotes WHERE post_id = p.post_id AND vote_type = -1) as downvotes";
    
    if ($current_user_id) {
        $query .= ", (SELECT vote_type FROM PostVotes WHERE post_id = p.post_id AND user_id = :current_user_id) as user_vote";
    }
    
    $query .= " FROM " . $this->table_name . " p
            LEFT JOIN Users u ON p.user_id = u.user_id
            LEFT JOIN Categories c ON p.category_id = c.category_id
            WHERE p.post_id = :post_id
            LIMIT 0,1";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":post_id", $this->post_id);
    if ($current_user_id) {
        $stmt->bindParam(":current_user_id", $current_user_id);
    }
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $this->post_id = $row['post_id'];
        $this->user_id = $row['user_id'];
        $this->title = $row['title'];
        $this->description = $row['content'];
        $this->created_at = $row['created_at'];
        $this->updated_at = $row['updated_at'];
        $this->author_name = $row['author_name'];
        $this->category_name = $row['category_name']; // Fetch category name
        $this->comment_count = $row['comment_count'];
        $this->upvotes = (int)$row['upvotes'];
        $this->downvotes = (int)$row['downvotes'];
        $this->user_vote = isset($row['user_vote']) ? (int)$row['user_vote'] : 0;
        return true;
    }
    return false;
}


public function readAllPostsByCategory($page = 1, $per_page = 10, $current_user_id = null, $sort_by = 'created_at', $sort_order = 'DESC') {
    try {
        // Calculate offset
        $offset = ($page - 1) * $per_page;

        // Validate sort parameters
        $allowed_sort_fields = ['created_at', 'title', 'updated_at'];
        $allowed_sort_orders = ['ASC', 'DESC'];
        
        $sort_by = in_array($sort_by, $allowed_sort_fields) ? $sort_by : 'created_at';
        $sort_order = in_array(strtoupper($sort_order), $allowed_sort_orders) ? strtoupper($sort_order) : 'DESC';

        // Base query
        $query = "SELECT 
                    c.category_name, 
                    p.*, 
                    u.username as author_name,
                    (SELECT COUNT(*) FROM Comments WHERE post_id = p.post_id) as comment_count";


        $query .= " FROM " . $this->table_name . " p
                LEFT JOIN Users u ON p.user_id = u.user_id
                LEFT JOIN Categories c ON p.category_id = c.category_id
                ORDER BY c.category_name, p.{$sort_by} {$sort_order}
                LIMIT :offset, :per_page";

        $stmt = $this->conn->prepare($query);

        // Bind values
        if ($current_user_id) {
            $stmt->bindParam(":current_user_id", $current_user_id);
        }
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->bindParam(":per_page", $per_page, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    } catch (Exception $e) {
        error_log("Error in readAllByCategory: " . $e->getMessage());
        throw new Exception("Failed to retrieve posts by category.");
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
                WHERE post_id = :post_id AND 
                (user_id = :user_id OR :is_admin = TRUE)";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":post_id", $this->post_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":is_admin", $this->is_admin, PDO::PARAM_BOOL);

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


    // Verify post owner
    public function verifyOwner() {
        $query = "SELECT 1 FROM " . $this->table_name . " 
                WHERE post_id = ? AND user_id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->post_id);
        $stmt->bindParam(2, $this->user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }




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



}