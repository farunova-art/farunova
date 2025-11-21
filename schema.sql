-- FARUNOVA E-COMMERCE PLATFORM - MariaDB Schema (CLEANED)
-- Clothing Store (Shirts, Trousers, Hoodies)
-- Database: farunova-ecommerce
-- All duplicates removed, issues fixed

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS farunova_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farunova_ecommerce;

-- ==========================================
-- PHASE 1-2: CORE TABLES
-- ==========================================

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    postalCode VARCHAR(20),
    country VARCHAR(100),
    role ENUM('customer', 'admin') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profilePic VARCHAR(255) DEFAULT NULL,
    lastLoginAt DATETIME,
    totalNotificationsReceived INT DEFAULT 0,
    lastNotificationAt TIMESTAMP NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    category ENUM('Shirts', 'Trousers', 'Hoodies') NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    discountPrice DECIMAL(10, 2),
    discountPercentage INT DEFAULT 0,
    image VARCHAR(255),
    stock INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE,
    sizes VARCHAR(255) DEFAULT 'S,M,L,XL,XXL',
    colors VARCHAR(255) DEFAULT 'Black,White,Blue,Red,Gray',
    featured BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_price (price),
    INDEX idx_featured (featured),
    INDEX idx_stock (stock),
    INDEX idx_name (name),
    FULLTEXT idx_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CART ITEMS TABLE
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    productId INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    size VARCHAR(50),
    color VARCHAR(50),
    addedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (userId, productId, size, color),
    INDEX idx_userId (userId),
    INDEX idx_productId (productId),
    INDEX idx_user_product (userId, productId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ORDERS TABLE
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orderId VARCHAR(100) UNIQUE NOT NULL,
    userId INT NOT NULL,
    totalAmount DECIMAL(12, 2) NOT NULL,
    paymentMethod ENUM('mpesa_stkpush', 'qr_code', 'card', 'bank_transfer') DEFAULT 'mpesa_stkpush',
    transactionId VARCHAR(100),
    shippingAddress VARCHAR(255),
    shippingCity VARCHAR(100),
    shippingPostalCode VARCHAR(20),
    shippingCountry VARCHAR(100),
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    paymentStatus ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes LONGTEXT,
    trackingNumber VARCHAR(100),
    shippedAt DATETIME,
    deliveredAt DATETIME,
    invoiceGenerated BOOLEAN DEFAULT FALSE,
    invoiceGeneratedAt TIMESTAMP NULL,
    invoiceEmailSent BOOLEAN DEFAULT FALSE,
    invoiceEmailSentAt TIMESTAMP NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_userId (userId),
    INDEX idx_orderId (orderId),
    INDEX idx_status (status),
    INDEX idx_paymentStatus (paymentStatus),
    INDEX idx_transactionId (transactionId),
    INDEX idx_user_date (userId, createdAt),
    INDEX idx_status_date (status, createdAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ORDER ITEMS TABLE
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orderId INT NOT NULL,
    productId INT NOT NULL,
    quantity INT NOT NULL,
    size VARCHAR(50),
    color VARCHAR(50),
    priceAtTime DECIMAL(10, 2) NOT NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (orderId) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_orderId (orderId),
    INDEX idx_productId (productId),
    INDEX idx_order_product (orderId, productId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WISHLIST TABLE
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    productId INT NOT NULL,
    addedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (userId, productId),
    INDEX idx_userId (userId),
    INDEX idx_productId (productId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PRODUCT REVIEWS TABLE
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    productId INT NOT NULL,
    userId INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment LONGTEXT,
    isApproved BOOLEAN DEFAULT FALSE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (productId) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_productId (productId),
    INDEX idx_userId (userId),
    INDEX idx_isApproved (isApproved),
    INDEX idx_createdAt (createdAt),
    INDEX idx_product_approved (productId, isApproved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- SAMPLE DATA - FARUNOVA PRODUCTS
-- ==========================================
INSERT IGNORE INTO products (name, description, category, price, discountPrice, discountPercentage, sku, stock, featured) VALUES
('Classic White T-Shirt', 'Premium quality 100% cotton white t-shirt. Perfect for everyday wear. Comfortable and durable for daily use.', 'Shirts', 499.99, 399.99, 20, 'FAR-TS-001', 50, TRUE),
('Navy Blue Casual Shirt', 'Stylish casual shirt in navy blue. Great for office or casual outings. Premium fabric blend.', 'Shirts', 1299.99, 999.99, 23, 'FAR-CS-001', 35, TRUE),
('Black Slim Fit Trousers', 'Modern slim-fit trousers in pure black. Ideal for formal occasions. Perfect fit and finish.', 'Trousers', 1899.99, 1499.99, 21, 'FAR-TRS-001', 40, TRUE),
('Khaki Chino Pants', 'Comfortable khaki chino pants for casual styling. Versatile and durable for any occasion.', 'Trousers', 999.99, 799.99, 20, 'FAR-TRS-002', 45, TRUE),
('Red Polo Shirt', 'Classic red polo shirt with embroidered FARUNOVA logo. Premium fabric and excellent quality.', 'Shirts', 799.99, 599.99, 25, 'FAR-PS-001', 30, FALSE),
('Gray Business Trousers', 'Professional gray trousers for business wear. Comfortable and sleek design.', 'Trousers', 1599.99, 1199.99, 25, 'FAR-TRS-003', 25, FALSE),
('Striped Button-Up Shirt', 'Elegant striped button-up shirt in white and blue. Perfect for formal events.', 'Shirts', 1399.99, 1099.99, 21, 'FAR-FS-001', 20, TRUE),
('Cozy Black Hoodie', 'Warm and comfortable black hoodie made from premium cotton blend. Perfect for cold weather.', 'Hoodies', 1599.99, 1299.99, 19, 'FAR-HD-001', 30, TRUE),
('Gray Pullover Hoodie', 'Soft gray pullover hoodie with kangaroo pocket. Ideal for casual and sport activities.', 'Hoodies', 1299.99, 999.99, 23, 'FAR-HD-002', 25, TRUE),
('Navy Blue Zip Hoodie', 'Premium navy blue zip-up hoodie with drawstring. Great layering piece for any wardrobe.', 'Hoodies', 1499.99, 1199.99, 20, 'FAR-HD-003', 28, FALSE);

-- ==========================================
-- PHASE 3: OPTIMIZATION TABLES & INDEXES
-- ==========================================

-- Cache Statistics Table
CREATE TABLE IF NOT EXISTS cache_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cacheKey VARCHAR(100) UNIQUE,
    cacheValue LONGTEXT,
    expiresAt TIMESTAMP,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expiresAt (expiresAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Logs Table for Security and Debugging
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logLevel VARCHAR(20),
    category VARCHAR(50),
    message TEXT,
    context JSON,
    userId INT,
    ipAddress VARCHAR(45),
    userAgent VARCHAR(255),
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level_date (logLevel, createdAt),
    INDEX idx_category (category),
    INDEX idx_userId (userId),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- PHASE 5: M-PESA PAYMENT SYSTEM
-- ==========================================

-- Payment Methods Table
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    isActive TINYINT(1) DEFAULT 1,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_method (method),
    INDEX idx_method (method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default payment methods
INSERT IGNORE INTO payment_methods (method, name, isActive) VALUES 
('mpesa', 'M-Pesa', 1),
('card', 'Credit Card', 1),
('bank', 'Bank Transfer', 1);

-- Enhanced Payments Table - M-Pesa Transaction Tracking
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    orderId INT NOT NULL,
    userId INT NOT NULL,
    paymentMethod VARCHAR(50) DEFAULT 'mpesa',
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    
    -- M-Pesa specific fields
    checkoutRequestID VARCHAR(100) UNIQUE,
    mpesaReceiptCode VARCHAR(50),
    mpesaTransactionDate DATETIME,
    phoneNumber VARCHAR(20),
    
    -- Payment status
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    resultCode INT,
    resultDescription TEXT,
    
    -- Transaction metadata
    metadata JSON,
    failureReason VARCHAR(255),
    
    -- Refund tracking
    refundInitiated BOOLEAN DEFAULT FALSE,
    refundCompletedAt TIMESTAMP NULL,
    totalRefundedAmount DECIMAL(10, 2) DEFAULT 0,
    
    -- Timestamps
    initiatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completedAt TIMESTAMP NULL,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_orderId (orderId),
    INDEX idx_userId (userId),
    INDEX idx_status (status),
    INDEX idx_checkoutRequestID (checkoutRequestID),
    INDEX idx_createdDate (initiatedAt),
    INDEX idx_user_date (userId, initiatedAt),
    INDEX idx_status_date (status, initiatedAt),
    INDEX idx_phoneNumber (phoneNumber),
    FOREIGN KEY (orderId) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Transactions Log Table (audit trail)
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paymentId INT NOT NULL,
    transactionType ENUM('initiate', 'query', 'callback', 'refund') DEFAULT 'initiate',
    status VARCHAR(50),
    resultCode INT,
    resultDescription TEXT,
    apiResponse JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_paymentId (paymentId),
    INDEX idx_type (transactionType),
    INDEX idx_createdDate (timestamp),
    INDEX idx_payment_type_date (paymentId, transactionType, timestamp),
    FOREIGN KEY (paymentId) REFERENCES payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Reconciliation Table
CREATE TABLE IF NOT EXISTS payment_reconciliation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paymentId INT NOT NULL,
    mpesaAmount DECIMAL(10, 2),
    systemAmount DECIMAL(10, 2),
    amountDifference DECIMAL(10, 2),
    isMatched TINYINT(1) DEFAULT 0,
    notes TEXT,
    reconciliedAt TIMESTAMP NULL,
    reconciliedBy INT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_paymentId (paymentId),
    INDEX idx_matched (isMatched),
    INDEX idx_createdDate (createdAt),
    FOREIGN KEY (paymentId) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (reconciliedBy) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment Refunds Table
CREATE TABLE IF NOT EXISTS payment_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paymentId INT NOT NULL,
    orderId INT NOT NULL,
    refundAmount DECIMAL(10, 2) NOT NULL,
    reason VARCHAR(255),
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    mpesaReceiptCode VARCHAR(50),
    requestedBy INT,
    processedBy INT,
    notes TEXT,
    requestedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processedAt TIMESTAMP NULL,
    
    INDEX idx_paymentId (paymentId),
    INDEX idx_orderId (orderId),
    INDEX idx_status (status),
    INDEX idx_createdDate (requestedAt),
    FOREIGN KEY (paymentId) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (orderId) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (requestedBy) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (processedBy) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- PHASE 6: ADVANCED PAYMENT MANAGEMENT
-- ==========================================

-- Payment Notifications Table
CREATE TABLE IF NOT EXISTS payment_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paymentId INT NOT NULL,
    notificationType VARCHAR(50) NOT NULL COMMENT 'payment_confirmation, payment_failed, refund_notification, invoice_delivery, payment_receipt',
    recipientEmail VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL COMMENT 'sent, failed, bounced, opened',
    sentAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    openedAt TIMESTAMP NULL,
    retryCount INT DEFAULT 0,
    lastRetryAt TIMESTAMP NULL,
    notes TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (paymentId) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_paymentId (paymentId),
    INDEX idx_type (notificationType),
    INDEX idx_status (status),
    INDEX idx_sentAt (sentAt),
    INDEX idx_created_status (createdAt, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reconciliation Logs Table
CREATE TABLE IF NOT EXISTS reconciliation_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    paymentId INT NOT NULL,
    operationType VARCHAR(50) NOT NULL COMMENT 'auto_match, manual_match, discrepancy_flagged',
    systemAmount DECIMAL(10, 2),
    mpesaAmount DECIMAL(10, 2),
    mpesaReceiptCode VARCHAR(100),
    matchStatus VARCHAR(20) COMMENT 'matched, unmatched, partial',
    discrepancyAmount DECIMAL(10, 2) NULL COMMENT 'Difference if not matched',
    notes TEXT,
    reconciliedBy VARCHAR(100) NULL,
    reconciliedAt TIMESTAMP NULL,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (paymentId) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_paymentId (paymentId),
    INDEX idx_operationType (operationType),
    INDEX idx_matchStatus (matchStatus),
    INDEX idx_reconciliedAt (reconciliedAt),
    INDEX idx_createdAt (createdAt),
    INDEX idx_created_type (createdAt, operationType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Records Table
CREATE TABLE IF NOT EXISTS invoice_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    orderId INT NOT NULL,
    invoiceNumber VARCHAR(100) UNIQUE NOT NULL,
    filePath VARCHAR(255),
    pdfGenerated BOOLEAN DEFAULT FALSE,
    htmlGenerated BOOLEAN DEFAULT FALSE,
    emailSent BOOLEAN DEFAULT FALSE,
    emailSentAt TIMESTAMP NULL,
    downloadCount INT DEFAULT 0,
    lastDownloadedAt TIMESTAMP NULL,
    amount DECIMAL(10, 2) NOT NULL,
    mpesaReceiptCode VARCHAR(100) NULL,
    notes TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (orderId) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_invoiceNumber (invoiceNumber),
    INDEX idx_orderId (orderId),
    INDEX idx_emailSent (emailSent),
    INDEX idx_createdAt (createdAt),
    INDEX idx_email_sent (emailSent, emailSentAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Refund Queue Table
CREATE TABLE IF NOT EXISTS refund_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    refundId INT NOT NULL,
    paymentId INT NOT NULL,
    status VARCHAR(20) DEFAULT 'queued' COMMENT 'queued, processing, completed, failed',
    processAttempts INT DEFAULT 0,
    maxAttempts INT DEFAULT 3,
    lastErrorMessage TEXT,
    queuedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processedAt TIMESTAMP NULL,
    
    FOREIGN KEY (refundId) REFERENCES payment_refunds(id) ON DELETE CASCADE,
    FOREIGN KEY (paymentId) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_queuedAt (queuedAt),
    INDEX idx_processedAt (processedAt),
    INDEX idx_status_queued (status, queuedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification Preferences Table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    userId INT NOT NULL UNIQUE,
    emailPaymentConfirmation BOOLEAN DEFAULT TRUE,
    emailPaymentFailed BOOLEAN DEFAULT TRUE,
    emailRefundNotification BOOLEAN DEFAULT TRUE,
    emailInvoiceDelivery BOOLEAN DEFAULT TRUE,
    emailPaymentReceipt BOOLEAN DEFAULT TRUE,
    emailPromotions BOOLEAN DEFAULT FALSE,
    emailUpdates BOOLEAN DEFAULT TRUE,
    smsPaymentConfirmation BOOLEAN DEFAULT FALSE,
    smsPaymentFailed BOOLEAN DEFAULT FALSE,
    preferredNotificationMethod VARCHAR(50) DEFAULT 'email' COMMENT 'email, sms, both',
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_userId (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Invoice Templates Table
CREATE TABLE IF NOT EXISTS invoice_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'payment_confirmation, refund_notice, invoice, receipt',
    subject VARCHAR(255) NOT NULL,
    htmlContent LONGTEXT NOT NULL,
    isActive BOOLEAN DEFAULT TRUE,
    isDefault BOOLEAN DEFAULT FALSE,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_name (name),
    INDEX idx_type (type),
    INDEX idx_isActive (isActive)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Configuration Table
CREATE TABLE IF NOT EXISTS system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    configKey VARCHAR(100) UNIQUE NOT NULL,
    configValue TEXT,
    description TEXT,
    createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_key (configKey)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Email Templates
INSERT IGNORE INTO invoice_templates (name, type, subject, htmlContent, isDefault) VALUES
('payment_confirmation_default', 'payment_confirmation', 
 'Payment Confirmation - Order #{order_id}',
 '<p>Hi {username},</p><p>Your payment of KES {amount} has been confirmed!</p><p>Order ID: {order_id}<br>M-Pesa Code: {mpesa_code}</p><p>Thank you for shopping with FARUNOVA!</p>',
 TRUE),
('refund_notice_default', 'refund_notice',
 'Refund Notification - Order #{order_id}',
 '<p>Hi {username},</p><p>Your refund of KES {amount} has been processed.</p><p>Status: {status}<br>Expected delivery: 1-3 business days</p><p>Thank you for your patience.</p>',
 TRUE),
('invoice_default', 'invoice',
 'Your Invoice - Order #{order_id}',
 '<p>Hi {username},</p><p>Your invoice for Order #{order_id} is attached.</p><p>Thank you for your purchase!</p>',
 TRUE),
('receipt_default', 'receipt',
 'Payment Receipt',
 '<p>Hi {username},</p><p>Thank you for your payment of KES {amount}.</p><p>M-Pesa Code: {mpesa_code}<br>Date: {date}</p><p>Receipt saved for your records.</p>',
 TRUE);

-- Insert Initial System Configuration
INSERT IGNORE INTO system_config (configKey, configValue, description) VALUES
('email_from', 'support@farunova.com', 'Default from email address'),
('email_from_name', 'FARUNOVA Support', 'Sender name for emails'),
('smtp_enabled', 'false', 'Enable SMTP email sending'),
('smtp_host', '', 'SMTP server hostname'),
('smtp_port', '587', 'SMTP server port'),
('smtp_username', '', 'SMTP authentication username'),
('smtp_password', '', 'SMTP authentication password'),
('notification_retry_limit', '3', 'Maximum retry attempts for failed notifications'),
('reconciliation_tolerance', '0.01', 'Amount tolerance in KES for reconciliation'),
('invoice_directory', '/invoices', 'Directory for invoice storage'),
('enable_pdf_invoices', 'true', 'Generate PDF invoices when TCPDF available'),
('enable_html_fallback', 'true', 'Generate HTML invoices as fallback');

-- ==========================================
-- PHASE 3: OPTIMIZATION VIEWS
-- ==========================================

-- Order Summary with User and Item Count
CREATE OR REPLACE VIEW vw_order_summary AS
SELECT 
    o.id,
    o.userId,
    u.username,
    o.totalAmount as totalPrice,
    o.status,
    o.createdAt as orderDate,
    COUNT(oi.id) as itemCount
FROM orders o
JOIN users u ON o.userId = u.id
LEFT JOIN order_items oi ON o.id = oi.orderId
GROUP BY o.id, o.userId, u.username, o.totalAmount, o.status, o.createdAt;

-- Product Ratings and Review Statistics
CREATE OR REPLACE VIEW vw_product_ratings AS
SELECT 
    p.id,
    p.name,
    COUNT(r.id) as reviewCount,
    ROUND(AVG(r.rating), 2) as averageRating,
    SUM(CASE WHEN r.isApproved = 1 THEN 1 ELSE 0 END) as approvedReviews
FROM products p
LEFT JOIN reviews r ON p.id = r.productId
GROUP BY p.id, p.name;

-- Dashboard Statistics
CREATE OR REPLACE VIEW vw_dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM users) as totalUsers,
    (SELECT COUNT(*) FROM products) as totalProducts,
    (SELECT COUNT(*) FROM orders) as totalOrders,
    (SELECT SUM(totalAmount) FROM orders) as totalRevenue,
    (SELECT COUNT(*) FROM orders WHERE createdAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recentOrders,
    (SELECT COUNT(*) FROM reviews WHERE isApproved = 0) as pendingReviews;

-- ==========================================
-- PHASE 5: PAYMENT VIEWS
-- ==========================================

-- Payment Statistics
CREATE OR REPLACE VIEW vw_payment_statistics AS
SELECT 
    DATE(p.initiatedAt) as transaction_date,
    COUNT(*) as total_transactions,
    SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as successful_transactions,
    SUM(CASE WHEN p.status = 'failed' THEN 1 ELSE 0 END) as failed_transactions,
    SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END) as total_revenue,
    AVG(CASE WHEN p.status = 'completed' THEN p.amount ELSE NULL END) as avg_transaction_value
FROM payments p
GROUP BY DATE(p.initiatedAt)
ORDER BY transaction_date DESC;

-- Payment Summary
CREATE OR REPLACE VIEW vw_payment_summary AS
SELECT 
    p.status,
    COUNT(*) as count,
    SUM(p.amount) as total_amount,
    AVG(p.amount) as avg_amount,
    MAX(p.completedAt) as last_transaction
FROM payments p
GROUP BY p.status;

-- ==========================================
-- PHASE 6: REPORTING VIEWS
-- ==========================================

-- Payment with Notification Summary
CREATE OR REPLACE VIEW vw_payment_notification_summary AS
SELECT 
    p.id as paymentId,
    p.orderId,
    u.username,
    u.email,
    p.amount,
    p.mpesaReceiptCode,
    p.status as paymentStatus,
    COUNT(pn.id) as notificationCount,
    SUM(CASE WHEN pn.status = 'sent' THEN 1 ELSE 0 END) as sentCount,
    SUM(CASE WHEN pn.status = 'failed' THEN 1 ELSE 0 END) as failedCount,
    MAX(pn.sentAt) as lastNotificationSent,
    p.completedAt
FROM payments p
LEFT JOIN users u ON p.userId = u.id
LEFT JOIN payment_notifications pn ON p.id = pn.paymentId
GROUP BY p.id, p.orderId, u.username, u.email, p.amount, p.mpesaReceiptCode, p.status, p.completedAt;

-- Reconciliation Summary
CREATE OR REPLACE VIEW vw_reconciliation_summary AS
SELECT 
    DATE(rl.createdAt) as reconciliationDate,
    COUNT(rl.id) as totalReconciled,
    SUM(CASE WHEN rl.matchStatus = 'matched' THEN 1 ELSE 0 END) as matchedCount,
    SUM(CASE WHEN rl.matchStatus = 'unmatched' THEN 1 ELSE 0 END) as unmatchedCount,
    SUM(CASE WHEN rl.matchStatus = 'partial' THEN 1 ELSE 0 END) as partialCount,
    ROUND(SUM(CASE WHEN rl.matchStatus = 'matched' THEN 1 ELSE 0 END) / COUNT(rl.id) * 100, 2) as matchPercentage
FROM reconciliation_logs rl
GROUP BY DATE(rl.createdAt)
ORDER BY reconciliationDate DESC;

-- Refund Summary
CREATE OR REPLACE VIEW vw_refund_summary AS
SELECT 
    pr.status,
    COUNT(pr.id) as refundCount,
    SUM(pr.refundAmount) as totalRefundAmount,
    AVG(pr.refundAmount) as avgRefundAmount,
    MIN(pr.createdAt) as oldestRefund,
    MAX(pr.createdAt) as latestRefund
FROM payment_refunds pr
GROUP BY pr.status;

-- Invoice Status
CREATE OR REPLACE VIEW vw_invoice_status AS
SELECT 
    ir.invoiceNumber,
    ir.orderId,
    o.orderId as orderNumber,
    u.username,
    u.email,
    ir.amount,
    ir.pdfGenerated,
    ir.htmlGenerated,
    ir.emailSent,
    ir.downloadCount,
    ir.lastDownloadedAt,
    ir.createdAt
FROM invoice_records ir
LEFT JOIN orders o ON ir.orderId = o.id
LEFT JOIN users u ON o.userId = u.id
ORDER BY ir.createdAt DESC;

-- ==========================================
-- STORED PROCEDURES
-- ==========================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS UpdatePaymentStatus(
    IN p_orderId INT,
    IN p_status VARCHAR(50),
    IN p_transactionId VARCHAR(100)
)
BEGIN
    UPDATE orders 
    SET paymentStatus = p_status,
        transactionId = p_transactionId,
        updatedAt = NOW()
    WHERE id = p_orderId;
    
    UPDATE payments 
    SET status = p_status,
        completedAt = CASE WHEN p_status = 'completed' THEN NOW() ELSE NULL END,
        updatedAt = NOW()
    WHERE orderId = p_orderId;
END //

DELIMITER ;

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_notification_queue_stats()
BEGIN
    SELECT 
        'sent' as status,
        COUNT(*) as count,
        MAX(sentAt) as lastSent
    FROM payment_notifications
    WHERE status = 'sent'
    UNION ALL
    SELECT 
        'failed' as status,
        COUNT(*) as count,
        MAX(sentAt) as lastSent
    FROM payment_notifications
    WHERE status = 'failed';
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_reconciliation_health()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM reconciliation_logs WHERE matchStatus = 'matched') as matched,
        (SELECT COUNT(*) FROM reconciliation_logs WHERE matchStatus = 'unmatched') as unmatched,
        (SELECT COUNT(*) FROM reconciliation_logs WHERE matchStatus = 'partial') as partial,
        ROUND(
            (SELECT COUNT(*) FROM reconciliation_logs WHERE matchStatus = 'matched') / 
            NULLIF((SELECT COUNT(*) FROM reconciliation_logs), 0) * 100, 2
        ) as matchPercentage;
END$$
DELIMITER ;

DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_cleanup_old_logs(IN days INT)
BEGIN
    DELETE FROM payment_notifications 
    WHERE createdAt < DATE_SUB(NOW(), INTERVAL days DAY)
    AND status IN ('sent', 'bounced');
    
    DELETE FROM reconciliation_logs 
    WHERE createdAt < DATE_SUB(NOW(), INTERVAL days DAY)
    AND matchStatus = 'matched';
END$$
DELIMITER ;

-- ==========================================
-- CREATE USER FOR APPLICATION
-- ==========================================
-- IMPORTANT: Run these commands separately as root or with GRANT privileges
-- CREATE USER 'farunova_user'@'localhost' IDENTIFIED BY 'FarunovaPass@2025';
-- GRANT ALL PRIVILEGES ON GROUP1.* TO 'farunova_user'@'localhost';
-- GRANT ALL PRIVILEGES ON GROUP1.* TO 'farunova_user'@'%';
-- FLUSH PRIVILEGES;
