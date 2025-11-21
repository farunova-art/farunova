<?php

/**
 * FARUNOVA Setup Script
 * Creates necessary directories and initializes the application
 */

// Define the directories that need to be created
$directories = [
    'logs',
    'cache',
    'invoices',
    'images/products',
    'temp'
];

$errors = [];
$created = [];

// Create directories
foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;

    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            $created[] = $dir;
        } else {
            $errors[] = "Failed to create directory: $dir";
        }
    }
}

// Check if database is connected
session_start();
require_once 'connection.php';

$db_status = "✓ Database connected successfully";
if (!isset($conn) || $conn->connect_error) {
    $db_status = "✗ Database connection failed. Check connection.php credentials";
    $errors[] = $db_status;
}

// Check required files exist
$required_files = [
    'security.php',
    'lib/Logger.php',
    'lib/Cache.php',
    'lib/ErrorHandler.php',
    'lib/Validator.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $errors[] = "Missing required file: $file";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FARUNOVA - Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .setup-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        .setup-container h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }

        .status-item {
            padding: 10px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
            background: #f0f9f0;
        }

        .status-item.error {
            border-left-color: #dc3545;
            background: #fdf0f0;
            color: #dc3545;
        }

        .badge {
            margin-right: 10px;
        }

        .setup-complete {
            text-align: center;
            margin-top: 30px;
        }

        .btn-proceed {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div class="setup-container">
        <h1>🚀 FARUNOVA Setup</h1>

        <h3>Status Report:</h3>

        <?php if (empty($errors)): ?>
            <div class="alert alert-success">
                <strong>✓ All checks passed!</strong>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠ Issues found:</strong>
            </div>
        <?php endif; ?>

        <h5 class="mt-4">Directories:</h5>
        <?php foreach ($created as $dir): ?>
            <div class="status-item">
                <span class="badge bg-success">✓</span> Created: <strong><?php echo htmlspecialchars($dir); ?></strong>
            </div>
        <?php endforeach; ?>

        <?php if (!empty($errors)): ?>
            <h5 class="mt-4">Errors & Warnings:</h5>
            <?php foreach ($errors as $error): ?>
                <div class="status-item error">
                    <span class="badge bg-danger">✗</span> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <h5 class="mt-4">Database Status:</h5>
        <div class="status-item <?php echo strpos($db_status, '✗') !== false ? 'error' : ''; ?>">
            <?php echo $db_status; ?>
        </div>

        <div class="setup-complete">
            <?php if (empty($errors)): ?>
                <div class="alert alert-success mt-4">
                    <strong>✓ Setup complete! Your application is ready.</strong>
                </div>
                <a href="index.php" class="btn btn-primary btn-lg btn-proceed">
                    Go to Home Page →
                </a>
            <?php else: ?>
                <div class="alert alert-danger mt-4">
                    <strong>⚠ Please fix the issues above before proceeding.</strong>
                </div>
                <button class="btn btn-secondary btn-lg btn-proceed" onclick="location.reload()">
                    Retry →
                </button>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>