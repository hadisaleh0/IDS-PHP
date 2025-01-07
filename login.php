<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - IDSFlow</title>
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

    <!-- Login Form -->
    <div class="login-container" id="login">
      <div class="login-box">
        <h2>Welcome Back</h2>
        <p class="login-subtitle">Enter your credentials to continue</p>

        <form class="login-form" method="post" action="register.php">
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
              placeholder="Enter your password"
              required
            />
          </div>

          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" />
              <span>Remember me</span>
            </label>
            <a href="#" class="forgot-password">Forgot password?</a>
          </div>

          <input type="submit" class="login-submit" value="Login" name="login">
          </input>
        </form>

        <div class="social-login">
          <p>Or continue with</p>
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
          Don't have an account?
          <a href="signup.html">Sign up</a>
        </p>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script>
      particlesJS.load("particles-js", "particles-config.json");
    </script>
  </body>
</html>
