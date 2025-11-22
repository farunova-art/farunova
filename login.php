<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link rel="stylesheet" href="css/style1.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
  <div class="container">
    <div class="form-box box">
      <?php
      include "connection.php";

      if (isset($_POST['login'])) {
        $email = sanitizeInput($_POST['email']);
        $pass  = $_POST['password'];

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
          echo "<div class='message'><p>Security token validation failed. Please try again.</p></div><br>";
          echo "<a href='login.php'><button class='btn'>Go Back</button></a>";
        } else {
          // Check rate limiting
          $rateCheck = recordFailedLogin($email);
          if (!$rateCheck['allowed']) {
            echo "<div class='message'><p>Too many login attempts. Please try again in 15 minutes.</p></div><br>";
            echo "<a href='index.php'><button class='btn'>Go Home</button></a>";
          } else {
            // Use prepared statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
              $row = $result->fetch_assoc();

              // Verify hashed password
              if (password_verify($pass, $row['password'])) {
                // Clear rate limit on successful login
                clearRateLimit('login_' . $email);

                $_SESSION['id'] = $row['id'];
                $_SESSION['username'] = $row['username'];

                // Regenerate session after successful login
                session_regenerate_id(true);

                // Log successful login
                logSecurityEvent('login_success', 'User logged in successfully', $email);

                header("Location: home.php");
                exit();
              } else {
                logSecurityEvent('login_failure', 'Invalid password attempt', $email);
                echo "<div class='message'><p>Wrong Password</p></div><br>";
                echo "<a href='login.php'><button class='btn'>Go Back</button></a>";
              }
            } else {
              logSecurityEvent('login_failure', 'User not found', $email);
              echo "<div class='message'><p>Wrong Email or Password</p></div><br>";
              echo "<a href='login.php'><button class='btn'>Go Back</button></a>";
            }
            $stmt->close();
          }
        }
      ?>
        <header>Login</header>
        <hr>
        <form action="#" method="POST">
          <?php echo csrfTokenField(); ?>
          <div class="input-container">
            <i class="fa fa-envelope icon"></i>
            <input class="input-field" type="email" placeholder="Email Address" name="email" required>
          </div>

          <div class="input-container">
            <i class="fa fa-lock icon"></i>
            <input class="input-field password" type="password" placeholder="Password" name="password" required>
            <i class="fa fa-eye toggle icon"></i>
          </div>

          <div class="remember">
            <input type="checkbox" class="check" name="remember_me">
            <label for="remember">Remember me</label>
            <span><a href="forgot.php">Forgot password</a></span>
          </div>

          <input type="submit" name="login" id="submit" value="Login" class="button">

          <div class="links">
            Don't have an account? <a href="signup.php">Signup Now</a>
          </div>
        </form>
      <?php
      }
      ?>
    </div>
  </div>

  <script>
    // Wait for DOM to load before accessing elements
    document.addEventListener("DOMContentLoaded", function() {
      const toggle = document.querySelector(".toggle");
      const input = document.querySelector(".password");

      // Only add listener if elements exist
      if (toggle && input) {
        toggle.addEventListener("click", () => {
          if (input.type === "password") {
            input.type = "text";
            toggle.classList.replace("fa-eye-slash", "fa-eye");
          } else {
            input.type = "password";
            toggle.classList.replace("fa-eye", "fa-eye-slash");
          }
        });
      }
    });
  </script>
</body>

</html>