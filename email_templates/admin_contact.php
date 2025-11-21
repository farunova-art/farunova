<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Form Submission</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 700px;
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

        .alert {
            background-color: #E3F2FD;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #1565C0;
            font-weight: 500;
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

        .info-box {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #999;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            color: #2B547E;
            font-weight: 600;
            word-break: break-word;
        }

        .message-content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #088F8F;
            line-height: 1.6;
            color: #333;
            margin: 15px 0;
        }

        .category-badge {
            display: inline-block;
            background-color: #2B547E;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 150px;
            display: inline-block;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #2B547E;
            color: white;
        }

        .btn-primary:hover {
            background-color: #088F8F;
        }

        .btn-secondary {
            background-color: #088F8F;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #2B547E;
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

        .priority-high {
            background-color: #FFEBEE;
            color: #c62828;
        }

        .priority-medium {
            background-color: #FFF3E0;
            color: #E65100;
        }

        .priority-low {
            background-color: #E8F5E9;
            color: #2E7D32;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📧 New Contact Form Submission</h1>
            <p>A customer has submitted a contact form</p>
        </div>

        <div class="alert">
            Please review and respond to this inquiry as soon as possible to maintain customer satisfaction.
        </div>

        <div class="section">
            <div class="section-title">Sender Information</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($name); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
                </div>
                <?php if (!empty($phone)): ?>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($phone); ?></span>
                    </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Submission Date:</span>
                    <span class="info-value"><?php echo htmlspecialchars($submissionDate); ?></span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Message Details</div>
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Subject:</span>
                    <span class="info-value"><?php echo htmlspecialchars($subject); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Category:</span>
                    <span class="info-value"><span class="category-badge"><?php echo htmlspecialchars($category ?? 'General'); ?></span></span>
                </div>
            </div>

            <div class="section-title">Message</div>
            <div class="message-content">
                <?php echo nl2br(htmlspecialchars($message)); ?>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Response Actions</div>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li><strong>Read the message</strong> - Understand the customer's inquiry or concern</li>
                <li><strong>Categorize it</strong> - Mark if it's sales, support, feedback, or complaint</li>
                <li><strong>Assign ownership</strong> - Assign to the appropriate team member</li>
                <li><strong>Draft response</strong> - Compose a professional and helpful reply</li>
                <li><strong>Send reply</strong> - Send the response to the customer's email</li>
                <li><strong>Follow up</strong> - Track the issue until resolution if needed</li>
            </ul>
        </div>

        <div class="section">
            <div class="section-title">Quick Links</div>
            <div class="action-buttons">
                <a href="<?php echo htmlspecialchars($adminUrl ?? 'admin_dashboard.php'); ?>" class="btn btn-primary">Admin Dashboard</a>
                <a href="mailto:<?php echo htmlspecialchars($email); ?>?subject=Re:%20<?php echo urlencode($subject); ?>" class="btn btn-secondary">Reply to Customer</a>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Customer Service Guidelines</div>
            <ul style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">
                <li>Respond within 24 hours whenever possible</li>
                <li>Be professional and courteous</li>
                <li>Address all points mentioned in the inquiry</li>
                <li>Provide clear next steps if applicable</li>
                <li>Offer additional support contact options</li>
                <li>Document the interaction for future reference</li>
            </ul>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>&copy; 2025 FARUNOVA. All rights reserved.</p>
        </div>
    </div>
</body>

</html>