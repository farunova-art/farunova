<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link rel="stylesheet" href="css/style1.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
  <div class="container">
    <div class="form-box box">
      <header>Sign Up</header>
      <hr>

      <?php
      include "connection.php";

      if (isset($_POST['register'])) {
        // Verify CSRF token first
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
          echo "<div class='message'><p>Security token validation failed. Please try again.</p></div><br>";
          echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
        } else {
          $name  = sanitizeInput(trim($_POST['username']));
          $email = sanitizeInput(trim($_POST['email']));
          $pass  = $_POST['password'];
          $cpass = $_POST['cpass'];

          // Validate inputs
          if (!isValidUsername($name)) {
            echo "<div class='message'><p>Username must be 3-20 characters (alphanumeric and underscore only).</p></div><br>";
            echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
          } elseif (!isValidEmail($email)) {
            echo "<div class='message'><p>Please enter a valid email address.</p></div><br>";
            echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
          } else {
            $passwordValidation = validatePassword($pass);
            if (!$passwordValidation['valid']) {
              echo "<div class='message'><p>" . implode('<br>', $passwordValidation['errors']) . "</p></div><br>";
              echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
            } elseif ($pass !== $cpass) {
              echo "<div class='message'><p>Passwords do not match.</p></div><br>";
              echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
            } else {
              // Check rate limiting
              $rateCheck = checkRateLimit('signup_' . $email, 3, 3600); // 3 signup attempts per hour
              if (!$rateCheck['allowed']) {
                echo "<div class='message'><p>Too many signup attempts. Please try again in 1 hour.</p></div><br>";
                echo "<a href='index.php'><button class='btn'>Go Home</button></a>";
              } else {
                // Check if email already exists using prepared statement
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                  logSecurityEvent('signup_failure', 'Email already registered', $email);
                  echo "<div class='message'><p>This email is already registered. Try another one.</p></div><br>";
                  echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
                } else {
                  // Hash password securely with higher cost
                  $passwd = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

                  // Insert new user
                  $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                  $insert_stmt->bind_param("sss", $name, $email, $passwd);

                  if ($insert_stmt->execute()) {
                    logSecurityEvent('signup_success', 'New user registered', $email);

                    // Send welcome email
                    include_once 'email_config.php';
                    sendWelcomeEmail($email, $name);

                    echo "<div class='message'><p>You are registered successfully! Check your email for a welcome message.</p></div><br>";
                    echo "<a href='login.php'><button class='btn'>Login Now</button></a>";
                  } else {
                    logSecurityEvent('signup_failure', 'Database insertion error', $email);
                    echo "<div class='message'><p>Registration failed. Please try again.</p></div><br>";
                    echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
                  }
                  $insert_stmt->close();
                }
                $stmt->close();
              }
            }
          }
        }
      } else {
      ?>
        <form action="#" method="POST">
          <?php echo csrfTokenField(); ?>
          <div class="input-container">
            <i class="fa fa-user icon"></i>
            <input class="input-field" type="text" placeholder="Username" name="username" required>
          </div>

          <div class="input-container">
            <i class="fa fa-envelope icon"></i>
            <input class="input-field" type="email" placeholder="Email Address" name="email" required>
          </div>

          <div class="input-container">
            <i class="fa fa-lock icon"></i>
            <input class="input-field password" type="password" placeholder="Password" name="password" required>
            <i class="fa fa-eye icon toggle"></i>
          </div>

          <div class="input-container">
            <i class="fa fa-lock icon"></i>
            <input class="input-field" type="password" placeholder="Confirm Password" name="cpass" required>
            <i class="fa fa-eye icon"></i>
          </div>

          <center><input type="submit" name="register" id="submit" value="Signup" class="btn"></center>

          <div class="links">
            Already have an account? <a href="login.php">Signin Now</a>
          </div>
        </form>
      <?php
      }
      ?>
    </div>
  </div>

  <script>
    const toggle = document.querySelector(".toggle"),
      input = document.querySelector(".password");
    toggle.addEventListener("click", () => {
      if (input.type === "password") {
        input.type = "text";
        toggle.classList.replace("fa-eye-slash", "fa-eye");
      } else {
        input.type = "password";
      }
    })
  </script>
</body>

</html>