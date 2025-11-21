<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - FARUNOVA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #2B547E 0%, #088F8F 100%);
        }

        .error-container {
            text-align: center;
            color: white;
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
            opacity: 0.9;
        }

        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .btn-error {
            background-color: white;
            color: #2B547E;
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
            color: #2B547E;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <div class="error-code">404</div>
        <div class="error-message">Page Not Found</div>
        <div class="error-description">
            Sorry, the page you're looking for doesn't exist or has been moved.
        </div>
        <div>
            <a href="index.php" class="btn-error">
                <i class="bi bi-house"></i> Go Home
            </a>
            <a href="products.php" class="btn-error">
                <i class="bi bi-shop"></i> Shop Products
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>