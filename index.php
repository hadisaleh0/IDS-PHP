<?php
session_start();
include_once 'includes/config/dbcon.php';

?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>IDS OverFlow</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap"
      rel="stylesheet"
    />
  </head>
  <body>
    <div id="particles-js"></div>

    <!-- Header -->
    <header>
      <nav class="navbar">
        <div class="logo">
          <img src="img/IDS.png" alt="IDSFlow Logo" class="logo-image" />
          <span class="logo-text"><?php
           if(isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
            $query = mysqli_query($conn, "SELECT users.* FROM users WHERE users.username = '$username'");
            while($row = mysqli_fetch_array($query)) {
              echo $row['username'];
            }
           }
           else {
            echo "IDS Overflow";
           }
          ?>
          </span>
        </div>

        <div class="search-box">
          <input type="text" id="searchInput" placeholder="Search..." />
          <i class="fas fa-search" id="searchButton"></i>
        </div>
        <div class="nav-buttons">
          <?php if(isset($_SESSION['username'])): ?>
              <span class="user-welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
              <button class="signup-btn" onclick="window.location.href='signup.php'">Sign up</button>
              <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
          <?php else: ?>
              <button class="login-btn" onclick="window.location.href='login.php'">Log in</button>
              <button class="signup-btn" onclick="window.location.href='signup.php'">Sign up</button>
          <?php endif; ?>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <div class="container">
      <div class="sidebar">
        <ul>
          <li class="active">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
          </li>
          <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'Admin'): ?>
              <li>
                  <a href="users.php"><i class="fas fa-user"></i> Users</a>
              </li>
          <?php endif; ?>
          <?php if(isset($_SESSION['user_id'])): ?>
              <li>
                  <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
              </li>
          <?php endif; ?>
        </ul>
      </div>

      <main class="content">
        <div class="content-header">
          <h1>Top Questions</h1>
          <?php if(isset($_SESSION['username'])): ?>
              <button class="ask-question-btn" onclick="window.location.href='createPost.php'">Ask Question</button>
          <?php else: ?>
              <button class="ask-question-btn" onclick="window.location.href='login.php'">Ask Question</button>
          <?php endif; ?>
        </div>

        <!-- Question List -->
        <div class="question-list">
          <?php
          require_once 'includes/posts/function.php';
          
          $postsJson = getPosts();
          $posts = json_decode($postsJson, true);
          
          if (isset($posts['status']) && ($posts['status'] == 404 || $posts['status'] == 500)) {
              echo '<div class="error-message">' . htmlspecialchars($posts['message']) . '</div>';
          } else {
              foreach ($posts as $post) {
                  ?>
                  <div class="question-item" onclick="window.location.href='postReading.php?id=<?php echo htmlspecialchars($post['id']); ?>'">
                      <div class="stats">
                          <div class="votes">
                              <span class="number positive">
                                  <?php echo $post['upvotes'] ?? 0; ?>
                              </span>
                              <span class="label">upvotes</span>
                          </div>
                          <div class="votes">
                              <span class="number negative">
                                  <?php echo $post['downvotes'] ?? 0; ?>
                              </span>
                              <span class="label">downvotes</span>
                          </div>
                          <div class="comments">
                              <span class="number"><?php echo $post['comments_count'] ?? 0; ?></span>
                              <span class="label">comments</span>
                          </div>
                      </div>
                      <div class="question-summary">
                          <h3>
                              <a href="post.php?id=<?php echo htmlspecialchars($post['id']); ?>">
                                  <?php echo htmlspecialchars($post['title']); ?>
                              </a>
                          </h3>
                          <div class="question-excerpt">
                              <?php 
                              $content = $post['description'];
                              $maxLength = 127; 
                              if (strlen($content) > $maxLength) {
                                  $spacePosition = strrpos(substr($content, 0, $maxLength), ' ');
                                  $content = substr($content, 0, $spacePosition) . '...';
                              }
                              echo htmlspecialchars($content);
                              ?>
                          </div>
                          <div class="question-meta">
                              <div class="category-tag">
                                  <i class="fas fa-folder"></i>
                                  <?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?>
                              </div>
                              <div class="tags">
                                  <?php 
                                  if (!empty($post['tags'])) {
                                      $tags = explode(',', $post['tags']);
                                      foreach ($tags as $tag) {
                                          echo '<a href="#" class="tag">' . htmlspecialchars(trim($tag)) . '</a>';
                                      }
                                  }
                                  ?>
                              </div>
                              <div class="user-info">
                                  asked <?php echo getTimeAgo($post['created_at']); ?> by 
                                  <a href="#"><?php echo htmlspecialchars($post['author_name'] ?? 'Anonymous'); ?></a>
                              </div>
                          </div>
                      </div>
                      <div class="post-actions">
                          <?php 
                          $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
                          if(isset($_SESSION['user_id']) && ($post['user_id'] == $_SESSION['user_id'] || $isAdmin)): 
                          ?>
                              <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                  <input type="hidden" name="delete_post_id" value="<?php echo $post['id']; ?>">
                                  <button type="submit" name="delete_post" class="delete-btn">
                                      <i class="fas fa-trash"></i> Delete
                                  </button>
                              </form>
                          <?php endif; ?>
                      </div>
                  </div>
                  <?php
              }
          }
          ?>
        </div>
      </main>

      <div class="right-sidebar">
        <div class="widget">
          <h4>Network Questions</h4>
          <ul>
            <li><a href="#">Hot Network Questions</a></li>
            <li><a href="#">Featured Questions</a></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer>
      <div class="footer-content">
        <div class="footer-col">
          <h5>Stack Overflow</h5>
          <ul>
            <li><a href="#">Questions</a></li>
            <li><a href="#">Help</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h5>Company</h5>
          <ul>
            <li><a href="#">About</a></li>
            <li><a href="#">Contact</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2024 IDS Overflow. All rights reserved.</p>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
      particlesJS.load("particles-js", "particles-config.json");

      // Search functionality
      document.getElementById('searchInput').addEventListener('keypress', function(e) {
          if (e.key === 'Enter') {
              performSearch();
          }
      });

      document.getElementById('searchButton').addEventListener('click', performSearch);

      function performSearch() {
          const searchQuery = document.getElementById('searchInput').value.trim();
          if (searchQuery === '') return;

          fetch(`search.php?query=${encodeURIComponent(searchQuery)}`)
              .then(response => response.json())
              .then(posts => {
                  const questionList = document.querySelector('.question-list');
                  questionList.innerHTML = '';

                  if (posts.length === 0) {
                      questionList.innerHTML = '<div class="no-results">No posts found matching your search.</div>';
                      return;
                  }

                  posts.forEach(post => {
                      const content = post.description;
                      const maxLength = 127;
                      const truncatedContent = content.length > maxLength 
                          ? content.substr(0, content.substr(0, maxLength).lastIndexOf(' ')) + '...'
                          : content;

                      const postHtml = `
                          <div class="question-item" onclick="window.location.href='postReading.php?id=${post.id}'">
                              <div class="stats">
                                  <div class="votes">
                                      <span class="number positive">${post.upvotes || 0}</span>
                                      <span class="label">upvotes</span>
                                  </div>
                                  <div class="votes">
                                      <span class="number negative">${post.downvotes || 0}</span>
                                      <span class="label">downvotes</span>
                                  </div>
                                  <div class="comments">
                                      <span class="number">${post.comments_count || 0}</span>
                                      <span class="label">comments</span>
                                  </div>
                              </div>
                              <div class="question-summary">
                                  <h3>
                                      <a href="postReading.php?id=${post.id}">
                                          ${post.title}
                                      </a>
                                  </h3>
                                  <div class="question-excerpt">
                                      ${truncatedContent}
                                  </div>
                                  <div class="question-meta">
                                      <div class="category-tag">
                                          <i class="fas fa-folder"></i>
                                          ${post.category_name || 'Uncategorized'}
                                      </div>
                                      <div class="tags">
                                          ${post.tags ? post.tags.split(',').map(tag => 
                                              `<a href="#" class="tag">${tag.trim()}</a>`
                                          ).join('') : ''}
                                      </div>
                                      <div class="user-info">
                                          asked ${getTimeAgo(post.created_at)} by 
                                          <a href="#">${post.author_name || 'Anonymous'}</a>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      `;
                      questionList.innerHTML += postHtml;
                  });
              })
              .catch(error => {
                  console.error('Error:', error);
                  const questionList = document.querySelector('.question-list');
                  questionList.innerHTML = '<div class="error-message">An error occurred while searching. Please try again.</div>';
              });
      }

      // Time ago function
      function getTimeAgo(timestamp) {
          const now = new Date();
          const past = new Date(timestamp);
          const diffInSeconds = Math.floor((now - past) / 1000);

          if (diffInSeconds < 60) return 'just now';
          if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
          if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
          if (diffInSeconds < 2592000) return Math.floor(diffInSeconds / 86400) + ' days ago';
          if (diffInSeconds < 31536000) return Math.floor(diffInSeconds / 2592000) + ' months ago';
          return Math.floor(diffInSeconds / 31536000) + ' years ago';
      }
    </script>

    <style>
      .no-results {
          text-align: center;
          padding: 20px;
          color: var(--dark-text);
          background: rgba(0, 0, 0, 0.2);
          border-radius: 8px;
          margin: 20px 0;
      }

      .error-message {
          text-align: center;
          padding: 20px;
          color: var(--danger-color);
          background: rgba(255, 0, 0, 0.1);
          border-radius: 8px;
          margin: 20px 0;
      }

      #searchInput {
          padding-right: 40px;
      }

      .search-box {
          position: relative;
      }

      .search-box i {
          position: absolute;
          right: 15px;
          top: 50%;
          transform: translateY(-50%);
          cursor: pointer;
          color: var(--primary-color);
          transition: color 0.3s ease;
      }

      .search-box i:hover {
          color: var(--secondary-color);
      }

      .category-tag {
          display: inline-flex;
          align-items: center;
          background: rgba(255, 0, 255, 0.1);
          padding: 4px 8px;
          border-radius: 4px;
          margin-right: 10px;
          color: var(--primary-color);
          font-size: 0.9em;
      }

      .category-tag i {
          margin-right: 5px;
          font-size: 0.9em;
      }

      .question-meta {
          display: flex;
          flex-wrap: wrap;
          align-items: center;
          gap: 10px;
      }
    </style>

    <?php
    if (isset($_POST['delete_post']) && isset($_SESSION['user_id'])) {
        $post_id = $_POST['delete_post_id'];
        
        // Check if user is admin or post owner
        $check_query = "SELECT user_id FROM posts WHERE id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $post_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $post = mysqli_fetch_assoc($result);
        
        $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
        if ($post && ($post['user_id'] == $_SESSION['user_id'] || $isAdmin)) {
            $delete_query = "DELETE FROM posts WHERE id = ?";
            $stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($stmt, "i", $post_id);
            if (mysqli_stmt_execute($stmt)) {
                header('Location: index.php?success=Post deleted');
                exit();
            }
        }
    }
    ?>
  </body>
</html> 