<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .error-container {
            text-align: center;
            color: white;
            max-width: 600px;
        }

        .error-code {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .error-message {
            font-size: 32px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .error-description {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.95;
        }

        .btn-error {
            background-color: white;
            color: #dc3545;
            padding: 12px 30px;
            font-weight: 600;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            transition: all 0.3s;
        }

        .btn-error:hover {
            background-color: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: #dc3545;
        }

        .error-details {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid white;
            padding: 20px;
            border-radius: 5px;
            text-align: left;
            margin-top: 30px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-server"></i>
        </div>
        <div class="error-code">500</div>
        <div class="error-message">Server Error</div>
        <div class="error-description">
            We're sorry! Something went wrong on our end. Please try again later.
        </div>
        <div>
            <a href="index.php" class="btn-error">
                <i class="bi bi-house"></i> Go Home
            </a>
            <a href="contact.php" class="btn-error">
                <i class="bi bi-envelope"></i> Contact Support
            </a>
        </div>

        <div class="error-details">
            <p><strong>What happened?</strong></p>
            <p>An unexpected error occurred while processing your request. Our team has been notified and is working to fix the issue.</p>
            <p style="margin-bottom: 0;"><strong>Reference ID:</strong> <code><?php echo uniqid('ERR_'); ?></code></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>