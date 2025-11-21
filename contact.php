<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Form</title>
    <link rel="stylesheet" href="css/style1.css">
</head>

<body>
    <div class="container">
        <div class="form-box box">

            <?php

            include "connection.php";

            $showForm = true;

            if (isset($_POST['submit'])) {
                // Verify CSRF token
                if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                    echo "<div class='message'>
                    <p>Security token validation failed. Please try again.</p>
                    </div><br>";
                    echo "<a href='index.php'><button class='btn'>Go Back</button></a>";
                    $showForm = false;
                } else {
                    $name = sanitizeInput($conn->real_escape_string($_POST['name']));
                    $email = sanitizeInput($conn->real_escape_string($_POST['email']));
                    $subject = sanitizeInput($conn->real_escape_string($_POST['subject']));
                    $message = sanitizeInput($conn->real_escape_string($_POST['message']));

                    // Validate inputs
                    if (!isValidEmail($email) || !isValidLength($name, 2, 100) || !isValidLength($message, 10, 1000)) {
                        echo "<div class='message'>
                        <p>Please provide valid input for all fields</p>
                        </div><br>";
                        echo "<a href='contact.php'><button class='btn'>Go Back</button></a>";
                        $showForm = false;
                    } else {
                        // Use prepared statement to prevent SQL injection
                        $stmt = $conn->prepare("INSERT INTO contact(name, email, subject, message) VALUES(?, ?, ?, ?)");
                        $stmt->bind_param("ssss", $name, $email, $subject, $message);

                        if ($stmt->execute()) {
                            logSecurityEvent('contact_form_submission', 'Contact form submitted successfully', $email);

                            // Send confirmation email to customer
                            include_once 'email_config.php';
                            sendContactReplyEmail($name, $email, $message);

                            // Send notification email to admin
                            $adminData = [
                                'name' => $name,
                                'email' => $email,
                                'subject' => $subject,
                                'message' => $message,
                                'submissionDate' => date('Y-m-d H:i:s'),
                                'adminUrl' => BASE_URL . 'admin_dashboard.php'
                            ];
                            sendAdminNotificationEmail('contact', $adminData);

                            echo "<div class='message'>
                            <p>Message sent successfully! ✨ Check your email for our confirmation.</p>
                            </div><br>";
                            echo "<a href='index.php'><button class='btn'>Go Back</button></a>";
                            $showForm = false;
                        } else {
                            logSecurityEvent('contact_form_failure', 'Failed to submit contact form', $email);
                            echo "<div class='message'>
                            <p>Message sending fail 😔</p>
                            </div><br>";
                            echo "<a href='index.php'><button class='btn'>Go Back</button></a>";
                            $showForm = false;
                        }
                        $stmt->close();
                    }
                }
            }

            if ($showForm) {
            ?>
                <header>Contact Us</header>
                <hr>
                <form action="#" method="POST">
                    <?php echo csrfTokenField(); ?>

                    <div class="input-container">
                        <i class="fa fa-user icon"></i>
                        <input class="input-field" type="text" placeholder="Your Name" name="name" required>
                    </div>

                    <div class="input-container">
                        <i class="fa fa-envelope icon"></i>
                        <input class="input-field" type="email" placeholder="Email Address" name="email" required>
                    </div>

                    <div class="input-container">
                        <i class="fa fa-pencil icon"></i>
                        <input class="input-field" type="text" placeholder="Subject" name="subject" required>
                    </div>

                    <div class="input-container">
                        <i class="fa fa-comments icon"></i>
                        <textarea class="input-field" placeholder="Your Message" name="message" rows="5" required></textarea>
                    </div>

                    <input type="submit" name="submit" id="submit" value="Send Message" class="btn">

                </form>
            <?php
            }
            ?>

        </div>
    </div>
</body>

</html>