<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>We've Received Your Message</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #2B547E 0%, #088F8F 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        .checkmark {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .section {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }

        .section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 18px;
            color: #2B547E;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .message-box {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #088F8F;
            margin: 15px 0;
        }

        .message-box label {
            display: block;
            color: #999;
            font-size: 12px;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .message-box value {
            display: block;
            color: #333;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .message-box value:last-child {
            margin-bottom: 0;
        }

        .highlight {
            background-color: #FFF5E1;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #FF9800;
        }

        .highlight strong {
            color: #E65100;
        }

        .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .timeline li {
            padding: 15px 0 15px 30px;
            position: relative;
            border-bottom: 1px solid #f0f0f0;
        }

        .timeline li:last-child {
            border-bottom: none;
        }

        .timeline li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #088F8F;
            font-weight: bold;
            font-size: 18px;
        }

        .timeline li strong {
            color: #2B547E;
        }

        .btn {
            display: inline-block;
            background-color: #2B547E;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #088F8F;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
        }

        .footer a {
            color: #2B547E;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="checkmark">✓</div>
            <h1>Thank You for Reaching Out!</h1>
            <p>We've received your message and appreciate you taking the time to contact us.</p>
        </div>

        <p>Hi <?php echo htmlspecialchars($name ?? 'there'); ?>,</p>

        <div class="section">
            <div class="section-title">We've Got Your Message</div>
            <div class="message-box">
                <label>Name</label>
                <value><?php echo htmlspecialchars($name ?? 'N/A'); ?></value>

                <label>Email</label>
                <value><?php echo htmlspecialchars($email ?? 'N/A'); ?></value>

                <label>Subject</label>
                <value><?php echo htmlspecialchars($subject ?? 'General Inquiry'); ?></value>

                <label>Message</label>
                <value><?php echo nl2br(htmlspecialchars($message ?? 'N/A')); ?></value>
            </div>
        </div>

        <div class="section">
            <div class="section-title">What Happens Next?</div>
            <ul class="timeline">
                <li><strong>Received:</strong> Your message has been logged in our system</li>
                <li><strong>Priority:</strong> Our team will review your inquiry within 24 hours</li>
                <li><strong>Response:</strong> We'll reply via email as soon as possible</li>
                <li><strong>Follow-up:</strong> Please check your inbox and spam folder for our response</li>
            </ul>
        </div>

        <div class="section">
            <div class="highlight">
                <strong>📧 Save This Email</strong><br>
                Keep this email for your records. It contains a summary of your inquiry and can help us provide better support.
            </div>
        </div>

        <div class="section">
            <div class="section-title">Need Immediate Assistance?</div>
            <p>If your matter is urgent, you can:</p>
            <ul style="margin: 10px 0; padding-left: 20px; list-style: disc;">
                <li>Call our customer support team during business hours</li>
                <li>Visit our <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>products.php" style="color: #088F8F;">product pages</a> for frequently asked questions</li>
                <li>Check our <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>" style="color: #088F8F;">homepage</a> for more information</li>
            </ul>
        </div>

        <div class="section">
            <p>Thank you for being part of the FARUNOVA community. We value your feedback and look forward to assisting you!</p>
            <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>" class="btn">Return to Homepage</a>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>This is an automated response. Our team will review your message shortly.</p>
            <p>&copy; 2025 FARUNOVA. All rights reserved.</p>
        </div>
    </div>
</body>

</html>