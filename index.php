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
          <input type="text" placeholder="Search..." />
          <i class="fas fa-search"></i>
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
            <a href="#"><i class="fas fa-home"></i> Home</a>
          </li>
          <li>
            <a href="#"><i class="fas fa-globe"></i> Questions</a>
          </li>
          <li>
            <a href="#"><i class="fas fa-tag"></i> Tags</a>
          </li>
          <li>
            <a href="#"><i class="fas fa-user"></i> Users</a>
          </li>
          <li>
            <a href="#"><i class="fas fa-trophy"></i> Badges</a>
          </li>
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
                              <span class="number"><?php echo $post['votes'] ?? 0; ?></span>
                              <span class="label">votes</span>
                          </div>
                          <div class="answers">
                              <span class="number">0</span>
                              <span class="label">answers</span>
                          </div>
                          <div class="views">
                              <span class="number"><?php echo $post['views'] ?? 0; ?></span>
                              <span class="label">views</span>
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
                                  <a href="#"><?php echo htmlspecialchars($post['username'] ?? 'Anonymous'); ?></a>
                              </div>
                          </div>
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
    </script>
  </body>
</html> 