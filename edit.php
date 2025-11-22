<?php

include("connection.php");

if (!isset($_SESSION['username'])) {
    header("location: login.php");
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style1.css">
</head>

<body>

    <div class="container">
        <div class="form-box box">

            <?php

            if (isset($_POST['update'])) {
                // Verify CSRF token
                if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    echo "<div class='message'>
                <p>Security token validation failed. Please try again.</p>
                </div><br>";
                    echo "<a href='edit.php'><button class='btn'>Go Back</button></a>";
                } else {
                    $username = sanitizeInput($conn->real_escape_string($_POST['username']));
                    $email = sanitizeInput($conn->real_escape_string($_POST['email']));
                    $password = $_POST['password'];

                    // Validate inputs
                    if (!isValidUsername($username)) {
                        echo "<div class='message'>
                <p>Username must be 3-20 characters (alphanumeric and underscore only)</p>
                </div><br>";
                        echo "<a href='edit.php'><button class='btn'>Go Back</button></a>";
                    } elseif (!isValidEmail($email)) {
                        echo "<div class='message'>
                <p>Please enter a valid email address</p>
                </div><br>";
                        echo "<a href='edit.php'><button class='btn'>Go Back</button></a>";
                    } else {
                        $passwordValidation = validatePassword($password);
                        if (!$passwordValidation['valid']) {
                            echo "<div class='message'>
                <p>" . implode('<br>', $passwordValidation['errors']) . "</p>
                </div><br>";
                            echo "<a href='edit.php'><button class='btn'>Go Back</button></a>";
                        } else {
                            // Hash password securely with higher cost
                            $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                            $id = $_SESSION['id'];
                            $edit_query = "UPDATE users SET username=?, email=?, password=? WHERE id=?";

                            $stmt = $conn->prepare($edit_query);
                            $stmt->bind_param("sssi", $username, $email, $hashed_password, $id);

                            if ($stmt->execute()) {
                                logSecurityEvent('profile_update', 'User profile updated', $email);
                                echo "<div class='message'>
                <p>Profile Updated!</p>
                </div><br>";
                                echo "<a href='home.php'><button class='btn'>Go Home</button></a>";
                            } else {
                                logSecurityEvent('profile_update_failure', 'Profile update failed', $email);
                                echo "<div class='message'>
                <p>Error updating profile</p>
                </div><br>";
                                echo "<a href='edit.php'><button class='btn'>Go Back</button></a>";
                            }
                            $stmt->close();
                        }
                    }
                }
            } else {

                $id = $_SESSION['id'];
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $query = $stmt->get_result();

                $res_username = '';
                $res_email = '';

                while ($result = $query->fetch_assoc()) {
                    $res_username = $result['username'];
                    $res_email = $result['email'];
                    $res_password = $result['password'];
                    $res_id = $result['id'];
                }
                $stmt->close();

            ?>

                <header>Change Profile</header>
                <form action="#" method="POST" enctype="multipart/form-data">
                    <?php echo csrfTokenField(); ?>

                    <div class="form-box">

                        <div class="input-container">
                            <i class="fa fa-user icon"></i>
                            <input class="input-field" type="text" placeholder="Username" name="username"
                                value="<?php echo htmlspecialchars($res_username); ?>" required>
                        </div>

                        <div class="input-container">
                            <i class="fa fa-envelope icon"></i>
                            <input class="input-field" type="email" placeholder="Email Address" name="email"
                                value="<?php echo htmlspecialchars($res_email); ?>" required>
                        </div>

                        <div class="input-container">
                            <i class="fa fa-lock icon"></i>
                            <input class="input-field password" type="password" placeholder="New Password" name="password" required>
                            <i class="fa fa-eye toggle icon"></i>
                        </div>

                    </div>


                    <div class="field">
                        <input type="submit" name="update" id="submit" value="Update" class="btn">
                    </div>


                </form>
        </div>
    <?php
            }
    ?>
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