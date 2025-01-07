<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up - IDSFlow</title>
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
          <span class="logo-text">IDSFlow</span>
        </div>
      </nav>
    </header>

    <!-- Signup Form -->
    <div class="login-container" id="signup">
      <div class="login-box">
        <h2>Create Account</h2>
        <p class="login-subtitle">Join our community today</p>

        <form class="login-form" method="post" action="register.php">
          <div class="form-group">
            <label for="username">
              <i class="fas fa-user"></i>
              Username
            </label>
            <input
              type="text"
              id="username"
              name="username"
              placeholder="Choose a username"
              required
            />
          </div>

          <div class="form-group">
            <label for="email">
              <i class="fas fa-envelope"></i>
              Email
            </label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              required
            />
          </div>

          <div class="form-group">
            <label for="password">
              <i class="fas fa-lock"></i>
              Password
            </label>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Create a password"
              required
            />
          </div>

          <div class="form-group">
            <label for="confirm_password">
              <i class="fas fa-lock"></i>
              Confirm Password
            </label>
            <input
              type="password"
              id="confirm_password"
              name="confirm_password"
              placeholder="Confirm your password"
              required
            />
          </div>

          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" required />
              <span>I agree to the <a href="#">Terms of Service</a></span>
            </label>
          </div>

          <input type="submit" class="login-submit" value="SignUp" name="signUp">
          </input>
        </form>

        <div class="social-login">
          <p>Or sign up with</p>
          <div class="social-buttons">
            <button class="social-btn google">
              <i class="fab fa-google"></i>
            </button>
            <button class="social-btn github">
              <i class="fab fa-github"></i>
            </button>
            <button class="social-btn facebook">
              <i class="fab fa-facebook-f"></i>
            </button>
          </div>
        </div>

        <p class="signup-link">
          Already have an account?
          <a href="login.php">Log in</a>
        </p>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
      particlesJS.load("particles-js", "particles-config.json");
    </script>
  </body>
</html>
