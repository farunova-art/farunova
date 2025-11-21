<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to FARUNOVA</title>
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
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
        }

        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.95;
        }

        .greeting {
            color: #2B547E;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
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

        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .feature-list li {
            padding: 12px 0 12px 30px;
            position: relative;
            color: #333;
            border-bottom: 1px solid #f0f0f0;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #088F8F;
            font-weight: bold;
            font-size: 18px;
        }

        .cta-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 150px;
            display: inline-block;
            padding: 14px 28px;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s ease;
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

        .promo-box {
            background: linear-gradient(135deg, #FFF5E1 0%, #FFE8D6 100%);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #FF9800;
            margin: 20px 0;
        }

        .promo-box h4 {
            margin: 0 0 10px 0;
            color: #E65100;
            font-size: 16px;
        }

        .promo-code {
            background-color: #ffffff;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
            color: #2B547E;
            display: inline-block;
            margin-top: 10px;
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

        .social-links {
            margin-top: 15px;
        }

        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: #088F8F;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to FARUNOVA!</h1>
            <p>Your Gateway to Authentic Clothing</p>
        </div>

        <div class="greeting">
            Hi <?php echo htmlspecialchars($firstName ?? 'there'); ?>,
        </div>

        <p>Welcome to FARUNOVA – we're thrilled to have you join our community of authentic clothing enthusiasts!</p>

        <div class="section">
            <div class="section-title">What You Can Do Now</div>
            <ul class="feature-list">
                <li><strong>Browse Collections</strong> - Explore our curated selection of authentic clothing</li>
                <li><strong>Manage Your Profile</strong> - Update your personal information and preferences</li>
                <li><strong>Track Orders</strong> - Keep tabs on your purchases in real-time</li>
                <li><strong>Save Favorites</strong> - Build your wishlist of items you love</li>
                <li><strong>Leave Reviews</strong> - Share your thoughts on products you've purchased</li>
                <li><strong>Enjoy Exclusive Offers</strong> - Get special promotions just for members</li>
            </ul>
        </div>

        <div class="section">
            <div class="promo-box">
                <h4>🎉 Welcome Offer - Get 10% Off Your First Purchase!</h4>
                <p>We want to celebrate your arrival with a special discount on your first order.</p>
                <div class="promo-code">WELCOME10</div>
                <p style="margin: 10px 0 0 0; font-size: 12px; color: #E65100;">Use this code at checkout. Valid for new customers only.</p>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Get Started</div>
            <p>Start shopping now and discover the perfect pieces for your wardrobe:</p>
            <div class="cta-buttons">
                <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>products.php" class="btn btn-primary">Shop Now</a>
                <a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>home.php" class="btn btn-secondary">View Your Profile</a>
            </div>
        </div>

        <div class="section">
            <div class="section-title">We're Here to Help</div>
            <p>Have questions? Our customer support team is ready to assist you:</p>
            <ul class="feature-list">
                <li><a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>contact.php" style="color: #088F8F; text-decoration: none;">Contact Us</a> - Reach out anytime</li>
                <li><a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>products.php" style="color: #088F8F; text-decoration: none;">Browse Products</a> - Check out our latest items</li>
                <li><a href="<?php echo htmlspecialchars($baseUrl ?? BASE_URL); ?>" style="color: #088F8F; text-decoration: none;">Visit Homepage</a> - Learn more about FARUNOVA</li>
            </ul>
        </div>

        <div class="section">
            <div class="section-title">Stay Connected</div>
            <p>Follow us for the latest updates, promotions, and new arrivals:</p>
            <div class="social-links">
                <a href="#">Facebook</a> •
                <a href="#">Instagram</a> •
                <a href="#">Twitter</a>
            </div>
        </div>

        <div class="footer">
            <p><strong>FARUNOVA - Authentic Clothing Store</strong></p>
            <p>Email: <?php echo htmlspecialchars($userEmail ?? 'support@farunova.com'); ?></p>
            <p>&copy; 2025 FARUNOVA. All rights reserved. | <a href="#">Privacy Policy</a> | <a href="#">Terms of Service</a></p>
        </div>
    </div>
</body>

</html>