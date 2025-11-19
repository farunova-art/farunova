<?php
session_start();
?>
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
        $name  = trim($_POST['username']);
        $email = trim($_POST['email']);
        $pass  = $_POST['password'];
        $cpass = $_POST['cpass'];

        // Check if email already exists using prepared statement
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
          echo "<div class='message'><p>This email is already registered. Try another one.</p></div><br>";
          echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
        } else {
          if ($pass === $cpass) {
            // Hash password securely
            $passwd = password_hash($pass, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $passwd);

            if ($stmt->execute()) {
              echo "<div class='message'><p>You are registered successfully!</p></div><br>";
              echo "<a href='login.php'><button class='btn'>Login Now</button></a>";
            } else {
              echo "<div class='message'><p>Registration failed. Please try again.</p></div><br>";
              echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
            }
          } else {
            echo "<div class='message'><p>Passwords do not match.</p></div><br>";
            echo "<a href='signup.php'><button class='btn'>Go Back</button></a>";
          }
        }
      } else {
      ?>
        <form action="#" method="POST">
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

