
# FARUNOVA - E-Commerce Clothing Store Platform

![FARUNOVA Logo](readme/index.jpg)

A comprehensive, production-ready e-commerce platform for selling clothing items (Shirts, Trousers, Hoodies) built with PHP, MySQL, and Bootstrap 5. Features robust payment integration with M-Pesa, admin dashboard, order management, reviews, wishlists, and advanced analytics.

---

## 📋 Table of Contents

- [Project Overview](#project-overview)
- [Architecture](#architecture)
- [Features](#features)
- [Technologies & Stack](#technologies--stack)
- [Installation & Setup](#installation--setup)
- [Database Schema](#database-schema)
- [File Structure](#file-structure)
- [API Documentation](#api-documentation)
- [Security Features](#security-features)
- [Admin Features](#admin-features)
- [Improvements & Enhancements](#improvements--enhancements)
- [Known Issues & Recommendations](#known-issues--recommendations)
- [Contributing](#contributing)
- [License](#license)

---

## 🎯 Project Overview

**FARUNOVA** is a full-stack e-commerce platform designed specifically for clothing retailers. It provides:
- **Customer-facing storefront** with product browsing, search, filtering, and reviews
- **Secure payment processing** via M-Pesa STK Push with QR code support
- **Order management** with real-time tracking and status updates
- **Admin dashboard** for inventory, orders, payments, customers, and analytics
- **Advanced features** including wishlists, reviews moderation, invoicing, and refund management
- **Payment reconciliation** and comprehensive logging for audit trails

**Database:** GROUP1 (MariaDB)  
**Current Phase:** 6 (Advanced Payment Management & Reconciliation)

---

## 🏗️ Architecture

### Application Layers

```
┌─────────────────────────────────────────────────┐
│          Frontend (HTML/CSS/JS/Bootstrap5)       │
│  (index.php, products.php, dashboard.php, etc)   │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│   API Layer & Business Logic (api/*.php)         │
│  (payments.php, orders.php, cart.php, etc)       │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│      Core Libraries & Services (lib/*.php)       │
│  (Logger, Validator, Database, MpesaPayment,    │
│   RefundManager, InvoiceGenerator, etc)          │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│    Security & Session Management                 │
│  (security.php, connection.php)                  │
└──────────────────┬──────────────────────────────┘
                   │
┌──────────────────▼──────────────────────────────┐
│        Database (MariaDB - GROUP1)               │
│  (14 core tables + 8 payment tables + views)     │
└─────────────────────────────────────────────────┘
```

### Key Components

| Component | Files | Purpose |
|-----------|-------|---------|
| **Authentication** | login.php, signup.php, logout.php, edit.php | User registration and session management |
| **Product Catalog** | products.php, product_detail.php, search.php | Browse and search products |
| **Shopping Cart** | cart.php, api/cart.php, js/cart.js | Cart management and checkout |
| **Orders** | checkout.php, order_confirmation.php, order_tracking.php | Order placement and tracking |
| **Payments** | api/payments.php, lib/MpesaPayment.php, lib/MpesaAuth.php | M-Pesa payment processing |
| **Customer Features** | reviews.php, wishlist.php, dashboard.php | Reviews, wishlists, user dashboard |
| **Admin Panel** | admin_*.php | Dashboard, orders, products, customers, analytics, payments |
| **Libraries** | lib/*.php | Utilities for logging, validation, errors, invoicing, refunds |

---

## ✨ Features

### Customer Features
- ✅ **User Registration & Authentication** - Secure signup/login with email validation
- ✅ **Product Browsing** - View products by category with sorting and filtering
- ✅ **Advanced Search** - Full-text search with category and price filters
- ✅ **Product Details** - Detailed product pages with images, reviews, sizes, colors
- ✅ **Shopping Cart** - Add/remove items, update quantities, persistent storage
- ✅ **Wishlist Management** - Save favorite products for later
- ✅ **Secure Checkout** - Address entry, order summary, payment method selection
- ✅ **M-Pesa Payments** - STK Push payment initiation with real-time status
- ✅ **Order Tracking** - Real-time order status with delivery tracking
- ✅ **Order History** - View past orders and re-purchase items
- ✅ **Product Reviews** - Rate and review products (moderated)
- ✅ **Email Notifications** - Order confirmations, status updates, receipts
- ✅ **Profile Management** - Update personal information and shipping address
- ✅ **Invoice Management** - Download purchase invoices

### Admin Features
- ✅ **Dashboard Analytics** - Overview of sales, orders, products, customers
- ✅ **Product Management** - Add, edit, delete products with stock management
- ✅ **Order Management** - View orders, update status, manage shipping
- ✅ **Customer Management** - View customer list with purchase history
- ✅ **Payment Tracking** - Monitor M-Pesa transactions and payment status
- ✅ **Refund Management** - Process and track customer refunds
- ✅ **Review Moderation** - Approve/reject customer reviews
- ✅ **Advanced Analytics** - Monthly sales, category trends, customer insights
- ✅ **Reconciliation** - Verify payment discrepancies, audit trails

---

## 💻 Technologies & Stack

### Backend
- **PHP 7.4+** - Server-side logic
- **MariaDB/MySQL** - Database with InnoDB engine
- **M-Pesa API** - Payment gateway integration
- **SMTP** - Email delivery (configurable)

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Responsive styling
- **JavaScript (ES6)** - Client-side interactions
- **Bootstrap 5** - UI Framework
- **Bootstrap Icons** - Icon library

### Architecture Patterns
- **MVC-inspired** - Separation of concerns
- **Prepared Statements** - SQL injection prevention
- **Session Management** - Secure user authentication
- **Error Handling** - Centralized error management
- **Logging** - Comprehensive audit trails
- **Caching** - Performance optimization

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MariaDB 10.3+ or MySQL 5.7+
- Apache/Nginx web server
- Composer (optional, for dependencies)
- M-Pesa Business Account (for payments)

### Step 1: Database Setup

```sql
-- Import the schema
mysql -u root -p < schema.sql

-- Or manually create and import:
CREATE DATABASE GROUP1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- Then import schema.sql content
```

### Step 2: Application Configuration

**Edit `connection.php`:**
```php
$server   = "localhost";
$username = "root";           // Change to your DB user
$password = "";               // Set your DB password
$db       = "GROUP1";
```

**Edit `lib/MpesaConfig.php`:**
```php
define('MPESA_CONSUMER_KEY', 'Your_Consumer_Key');
define('MPESA_CONSUMER_SECRET', 'Your_Consumer_Secret');
define('MPESA_SHORTCODE', '174379');        // Your paybill number
define('MPESA_PASSKEY', 'Your_Pass_Key');
define('MPESA_INITIATOR_USERNAME', 'apiop');
define('MPESA_INITIATOR_PASSWORD', 'Your_Initiator_Password');
```

**Edit `email_config.php`:**
```php
define('SMTP_HOST', 'smtp.mailtrap.io');     // Your SMTP server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_username');
define('SMTP_PASSWORD', 'your_password');
define('SMTP_ENABLED', true);
```

### Step 3: File Permissions

```bash
# Linux/Mac - Make sure directories are writable
chmod 755 logs/
chmod 755 uploads/
chmod 755 invoices/
```

### Step 4: Folder Structure

```
farunova/
├── admin_*.php              # Admin pages
├── api/                     # API endpoints
├── css/                     # Stylesheets
├── email_templates/         # Email templates
├── errors/                  # Error pages
├── images/                  # Product images
├── js/                      # JavaScript files
├── lib/                     # Core libraries
├── logs/                    # Application logs
├── invoices/                # Generated invoices
├── readme/                  # Documentation assets
├── schema.sql               # Database schema
├── connection.php           # DB configuration
├── security.php             # Security functions
├── email_config.php         # Email configuration
└── ... (other PHP files)
```

### Step 5: Start Using

1. Navigate to `http://localhost/farunova/`
2. Create a customer account via signup
3. Browse products and make a purchase
4. Access admin panel at `http://localhost/farunova/admin_dashboard.php` (requires admin role)

---

## 📊 Database Schema

### Core Tables (14 main tables)

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| **users** | User accounts | id, username, email, password, role (customer/admin) |
| **products** | Product catalog | id, name, category, price, stock, sizes, colors |
| **cart_items** | Shopping cart | userId, productId, quantity, size, color |
| **orders** | Customer orders | id, orderId, userId, totalAmount, status, paymentStatus |
| **order_items** | Order line items | orderId, productId, quantity, priceAtTime |
| **wishlist** | Saved products | userId, productId |
| **reviews** | Product reviews | productId, userId, rating, comment, isApproved |
| **payments** | Payment records | orderId, amount, status, checkoutRequestID, mpesaReceiptCode |
| **payment_transactions** | Transaction audit | paymentId, transactionType, status, apiResponse |
| **payment_refunds** | Refund tracking | paymentId, refundAmount, status, reason |
| **invoice_records** | Invoice storage | orderId, invoiceNumber, filePath, emailSent |
| **system_logs** | Application logs | logLevel, category, message, userId, ipAddress |
| **cache_stats** | Cache management | cacheKey, cacheValue, expiresAt |
| **payment_methods** | Payment options | method, name, isActive |

### Payment Management Tables (8 additional)
- `payment_reconciliation` - Payment discrepancy tracking
- `payment_notifications` - Email/SMS notification logs
- `reconciliation_logs` - Reconciliation audit trail
- `refund_queue` - Refund processing queue
- `notification_preferences` - User notification settings
- `invoice_templates` - Customizable email templates
- `system_config` - System configuration values

### Database Views (7 views)
- `vw_order_summary` - Order with user and item count
- `vw_product_ratings` - Product ratings and review stats
- `vw_dashboard_stats` - Dashboard statistics
- `vw_payment_statistics` - Payment analytics
- `vw_payment_summary` - Payment status summary
- `vw_payment_notification_summary` - Payment notification tracking
- `vw_reconciliation_summary` - Reconciliation metrics

---

## 📂 File Structure

### Pages

**Public Pages:**
- `index.php` - Landing page
- `products.php` - Product catalog
- `product_detail.php` - Product detail view
- `search.php` - Search results
- `contact.php` - Contact form

**Authentication:**
- `login.php` - Login form
- `signup.php` - Registration form
- `logout.php` - Session termination
- `edit.php` - Profile update

**Customer Pages:**
- `dashboard.php` - User dashboard with order history
- `cart.php` - Shopping cart
- `checkout.php` - Checkout process
- `order_confirmation.php` - Order confirmation
- `order_tracking.php` - Track orders
- `reviews.php` - View/submit product reviews
- `wishlist.php` - Saved products
- `home.php` - Authenticated homepage

**Admin Pages:**
- `admin_dashboard.php` - Admin overview
- `admin_products.php` - Product management
- `admin_orders.php` - Order management
- `admin_order_detail.php` - Order details
- `admin_customers.php` - Customer management
- `admin_payments.php` - Payment tracking
- `admin_refunds.php` - Refund management
- `admin_review_moderation.php` - Review approval
- `admin_analytics.php` - Analytics dashboard

### API Endpoints (`/api`)

- `cart.php` - Cart operations (add, remove, update)
- `products.php` - Product data API
- `payments.php` - Payment initiation and status
- `reviews.php` - Review submission
- `wishlist.php` - Wishlist operations
- `invoices.php` - Invoice generation
- `refunds.php` - Refund processing
- `reconciliation.php` - Payment reconciliation

### Libraries (`/lib`)

- `Logger.php` - Logging system with rotation
- `Database.php` - Database abstraction
- `Validator.php` - Input validation
- `ErrorHandler.php` - Error management
- `Helpers.php` - Utility functions
- `Cache.php` - Caching mechanism
- `MpesaAuth.php` - M-Pesa authentication
- `MpesaConfig.php` - M-Pesa configuration
- `MpesaPayment.php` - M-Pesa API integration
- `PaymentNotifications.php` - Email/SMS notifications
- `PaymentReconciliation.php` - Payment matching
- `RefundManager.php` - Refund processing
- `InvoiceGenerator.php` - PDF/HTML invoice generation

---

## 🔌 API Documentation

### Payment API

#### Initiate Payment
```http
POST /api/payments.php
Content-Type: application/json

{
  "action": "initiate",
  "order_id": 123,
  "amount": 5000,
  "phone": "254712345678",
  "description": "Order #ORD-123"
}
```

**Response:**
```json
{
  "success": true,
  "checkoutRequestID": "ws_CO_291120242114200000",
  "message": "Payment initiated. Please enter M-Pesa PIN"
}
```

#### Query Payment Status
```http
POST /api/payments.php
Content-Type: application/json

{
  "action": "query",
  "checkoutRequestID": "ws_CO_291120242114200000"
}
```

#### Refund Processing
```http
POST /api/refunds.php
Content-Type: application/json

{
  "action": "process",
  "refund_id": 456,
  "reason": "Customer requested refund"
}
```

### Cart API

#### Add to Cart
```http
POST /api/cart.php
Content-Type: application/json

{
  "action": "add",
  "product_id": 1,
  "quantity": 2,
  "size": "M",
  "color": "Black"
}
```

#### Get Cart Items
```http
GET /api/cart.php?action=get
```

### Review API

#### Submit Review
```http
POST /api/reviews.php
Content-Type: application/json

{
  "action": "submit",
  "product_id": 1,
  "rating": 5,
  "comment": "Great product!"
}
```

---

## 🔒 Security Features

### Authentication & Authorization
- ✅ **Password Hashing** - bcrypt with salt
- ✅ **CSRF Tokens** - On all forms (generateCSRFToken, verifyCSRFToken)
- ✅ **Session Security** - HttpOnly, Secure, SameSite cookies
- ✅ **Rate Limiting** - Failed login attempts (max 5 in 15 mins)
- ✅ **Admin Verification** - Role-based access control
- ✅ **Session Regeneration** - Periodic session ID refresh

### Input Security
- ✅ **Prepared Statements** - Prevent SQL injection
- ✅ **Input Sanitization** - sanitizeInput() function
- ✅ **Email Validation** - isValidEmail()
- ✅ **Phone Validation** - Format verification
- ✅ **Password Requirements** - validatePassword() with complexity rules
- ✅ **XSS Prevention** - htmlspecialchars() on output
- ✅ **HTMLPURIFIER** - Content sanitization for reviews

### API Security
- ✅ **HTTPS Enforcement** - In production
- ✅ **M-Pesa SSL Verification** - Secure API calls
- ✅ **API Response Validation** - Verify callback signatures
- ✅ **Transaction Logging** - All API calls logged
- ✅ **Timeout Protection** - 30-second CURL timeout

### Database Security
- ✅ **Least Privilege** - Separate DB user with minimal permissions
- ✅ **Indexing** - Optimized queries
- ✅ **Data Encryption** - Passwords hashed, sensitive data protected
- ✅ **Backup Strategy** - Regular database backups recommended

---

## 👨‍💼 Admin Features

### Dashboard
- Total orders, revenue, products, customers at a glance
- Recent orders list
- Low stock alerts
- Favorite product categories

### Product Management
- Create, edit, delete products
- Stock management
- Price and discount configuration
- Category organization (Shirts, Trousers, Hoodies)
- Size and color management
- Featured product marking

### Order Management
- View all orders with filters
- Update order status (pending → processing → shipped → delivered)
- Order detail view with line items
- Customer information
- Payment status tracking
- Tracking number assignment

### Customer Management
- View all registered customers
- Customer profile details
- Purchase history per customer
- Account status (active/suspended)

### Payment Management
- Payment transaction history
- Filter by status and date range
- Daily revenue reports
- M-Pesa receipt tracking
- Transaction reconciliation

### Refund Management
- Process customer refunds
- Track refund status
- Bulk refund operations
- Refund audit trail
- Queue management

### Review Moderation
- Pending reviews queue
- Approve/reject reviews
- Review statistics
- Spam detection

### Analytics
- Monthly sales trends
- Category performance
- Customer acquisition
- Payment method breakdown
- Revenue reports

---

## 🔧 Improvements & Enhancements

### Security Improvements (Priority: CRITICAL)

1. **SQL Injection Prevention** ⚠️
   - **Issue**: `products.php` line 18-19, `search.php` uses `real_escape_string` (deprecated)
   - **Fix**: Replace all `real_escape_string` with prepared statements
   - **Files**: products.php, search.php, checkout.php, order_confirmation.php
   - **Impact**: Prevents database compromise

2. **Admin Access Control** ⚠️
   - **Issue**: Some admin pages check `role !== 'admin'` inconsistently
   - **Fix**: Centralize admin middleware function
   - **Implementation**: Create `lib/AdminAuth.php` with role verification
   ```php
   function requireAdmin() {
       if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
           logSecurityEvent('unauthorized_access_attempt', 'Admin page accessed without permission');
           header("location: login.php");
           exit();
       }
   }
   ```

3. **API Endpoint Protection** ⚠️
   - **Issue**: API endpoints not validating authentication
   - **Fix**: Add token-based auth or session verification
   - **Implementation**: Add to each API file:
   ```php
   if (!isset($_SESSION['username'])) {
       http_response_code(401);
       exit(json_encode(['error' => 'Unauthorized']));
   }
   ```

4. **Rate Limiting on APIs** ⚠️
   - **Issue**: Payment API can be called repeatedly without throttling
   - **Fix**: Implement per-user/per-IP rate limiting
   - **Implementation**: Use checkRateLimit() on sensitive endpoints

5. **Password Complexity Enforcement** ⚠️
   - **Issue**: validatePassword() rules not enforced on signup
   - **Fix**: Ensure all validations run before insertion
   - **Files**: signup.php, edit.php

### Code Quality Improvements (Priority: HIGH)

6. **Inconsistent Error Handling**
   - **Issue**: Mix of die(), exit(), and exceptions
   - **Fix**: Use ErrorHandler class consistently
   - **Implementation**: `$logger->error()` instead of die()
   - **Files**: All page files

7. **Missing Transaction Support**
   - **Issue**: Multi-step operations (order + payment) not atomic
   - **Fix**: Wrap in `BEGIN TRANSACTION` / `COMMIT`
   - **Files**: checkout.php, api/payments.php
   ```php
   $conn->begin_transaction();
   try {
       // Create order
       // Create payment record
       // Deduct from inventory
       $conn->commit();
   } catch (Exception $e) {
       $conn->rollback();
       throw $e;
   }
   ```

8. **Missing Order Validation**
   - **Issue**: Checkout doesn't verify sufficient stock
   - **Fix**: Validate stock before order creation
   - **Impact**: Prevents overselling

9. **Inconsistent Pagination**
   - **Issue**: Pagination logic duplicated in products.php, search.php, admin_orders.php
   - **Fix**: Create reusable `Pagination` class
   ```php
   class Pagination {
       private $itemsPerPage;
       private $currentPage;
       
       public function getOffset() { return ($this->currentPage - 1) * $this->itemsPerPage; }
       public function getLimit() { return $this->itemsPerPage; }
       public function getTotalPages($total) { return ceil($total / $this->itemsPerPage); }
   }
   ```

10. **Missing API Documentation**
    - **Issue**: API endpoints not documented with request/response formats
    - **Fix**: Add OpenAPI/Swagger documentation
    - **File**: Create `api/swagger.php` or `docs/api.md`

11. **Incomplete Input Validation**
    - **Issue**: Phone number validation in checkout not consistent with M-Pesa format
    - **Fix**: Use validatePhone() from security.php everywhere
    - **Files**: checkout.php, api/payments.php

### Functionality Improvements (Priority: MEDIUM)

12. **Invoice PDF Generation**
    - **Issue**: InvoiceGenerator.php exists but may not generate PDFs
    - **Improvement**: Integrate TCPDF library for PDF invoicing
    - **Files**: lib/InvoiceGenerator.php, checkout.php
    - **Expected**: Auto-generate and email invoices after payment

13. **Email Template System**
    - **Issue**: Hardcoded email content in sendEmail functions
    - **Fix**: Use template system with variables (already in schema as `invoice_templates` table)
    - **Implementation**: Query templates table instead of hardcoding

14. **Cart Persistence**
    - **Issue**: Cart stored in DB but not synchronized with session
    - **Fix**: Add cache layer for cart (Redis recommended)
    - **Implementation**: Update CartAPI to use $cache before DB query

15. **Product Image Upload**
    - **Issue**: Image field in products table but no upload functionality
    - **Fix**: Add image upload to admin_products.php
    ```php
    if (isset($_FILES['image'])) {
        $uploadDir = 'images/products/';
        $filename = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename);
    }
    ```

16. **Payment Reconciliation Automation**
    - **Issue**: Manual reconciliation required
    - **Fix**: Implement auto-reconciliation cron job
    - **Implementation**: Create `cron/reconcile_payments.php`

17. **Search Optimization**
    - **Issue**: Full-text search may return irrelevant results
    - **Fix**: Add search ranking/relevance scoring
    - **Implementation**: ORDER BY MATCH(...) AGAINST(...) relevance DESC

18. **Mobile App API**
    - **Issue**: No dedicated mobile API endpoints
    - **Fix**: Create `/api/mobile/` endpoints with JSON responses
    - **Files**: api/mobile/auth.php, api/mobile/products.php, etc.

19. **Order Status Notifications**
    - **Issue**: No automatic SMS notifications for status changes
    - **Fix**: Integrate Twilio or similar SMS provider
    - **Files**: lib/PaymentNotifications.php

20. **Inventory Management Dashboard**
    - **Issue**: No stock alerts or low inventory view
    - **Fix**: Add inventory dashboard showing critical stock levels
    - **Files**: Create `admin_inventory.php`

---

## 🚨 Known Issues & Recommendations

### Current Issues

| Issue | Severity | Status | Workaround |
|-------|----------|--------|-----------|
| SQL injection in products.php | HIGH | Not Fixed | Use prepared statements |
| Missing admin checks on some pages | HIGH | Not Fixed | Add requireAdmin() function |
| Pagination offset vulnerability | MEDIUM | Not Fixed | Validate page parameter |
| No transaction support | MEDIUM | Not Fixed | Use explicit transactions |
| Email config hardcoded | MEDIUM | Not Fixed | Use system_config table |
| Invoice generation untested | MEDIUM | Untested | Test TCPDF integration |
| Rate limiting incomplete | MEDIUM | Partial | Add API endpoint limits |

### Recommendations Before Production

- [ ] Run security audit with OWASP Top 10
- [ ] Implement Web Application Firewall (WAF)
- [ ] Set up automated backups
- [ ] Configure monitoring and alerting
- [ ] Load test with >1000 concurrent users
- [ ] Conduct penetration testing
- [ ] Set up CDN for static assets
- [ ] Configure Redis for caching
- [ ] Implement API rate limiting
- [ ] Add phone number verification
- [ ] Set up error tracking (Sentry, Rollbar)
- [ ] Configure CORS for APIs
- [ ] Add two-factor authentication
- [ ] Implement audit logging for all admin actions
- [ ] Set up automated vulnerability scanning

### Performance Optimizations

- Add query caching for product listings
- Implement pagination on all tables
- Add database connection pooling
- Use CDN for images and static files
- Implement lazy loading on product pages
- Minify CSS and JavaScript
- Enable GZIP compression
- Add database query analysis
- Implement Redis for sessions
- Use async email sending

---

## 📝 File Reference

### Key PHP Functions

**Security Functions** (`security.php`):
```php
generateCSRFToken()              // Generate CSRF token
verifyCSRFToken($token)          // Verify CSRF token
sanitizeInput($input)             // Sanitize user input
isValidEmail($email)              // Validate email format
validatePassword($password)       // Check password strength
checkRateLimit($identifier)       // Check rate limiting
logSecurityEvent($event, $details) // Log security events
addSecurityHeaders()              // Add HTTP security headers
```

**Logger Methods** (`Logger.php`):
```php
$logger->error($message, $context)
$logger->warning($message, $context)
$logger->info($message, $context)
$logger->debug($message, $context)
$logger->query($query, $params, $time)
$logger->api($method, $endpoint, $status, $time)
$logger->userAction($action, $description, $userId)
$logger->security($event, $description, $details)
```

**Validator Methods** (`Validator.php`):
```php
$validator->required($field, $value)
$validator->email($field, $value)
$validator->minLength($field, $value, $min)
$validator->maxLength($field, $value, $max)
$validator->numeric($field, $value)
$validator->phone($field, $value)
$validator->date($field, $value, $format)
$validator->unique($field, $value, $table, $column, $conn)
$validator->getErrors()
$validator->isValid()
```

---

## 📧 Email Templates

Located in `/email_templates/`:
- `welcome.php` - New user welcome
- `order_confirmation.php` - Order receipt
- `order_status_update.php` - Shipping updates
- `admin_new_order.php` - Admin notification
- `admin_low_stock.php` - Inventory alert
- `contact_reply.php` - Contact form response
- `admin_contact.php` - New contact submission

---

## 🧪 Testing

### Unit Tests
```bash
# Test payment processing
php tests/PaymentTest.php

# Test validation
php tests/ValidatorTest.php
```

### Manual Testing Checklist
- [ ] User registration and login
- [ ] Product search and filtering
- [ ] Add/remove from cart
- [ ] Checkout process
- [ ] M-Pesa payment initiation
- [ ] Order tracking
- [ ] Review submission
- [ ] Admin order management
- [ ] Admin product management
- [ ] Payment reconciliation

---

## 🚀 Deployment

### Production Checklist
1. **Environment Setup**
   - [ ] Set environment variables for sensitive data
   - [ ] Configure HTTPS/SSL certificates
   - [ ] Set up database backups
   - [ ] Configure log rotation

2. **Security Hardening**
   - [ ] Disable PHP error display in production
   - [ ] Set strong database passwords
   - [ ] Configure M-Pesa production credentials
   - [ ] Set up firewall rules

3. **Performance**
   - [ ] Enable query caching
   - [ ] Set up Redis for sessions
   - [ ] Configure CDN for static files
   - [ ] Enable GZIP compression

4. **Monitoring**
   - [ ] Set up error tracking
   - [ ] Configure performance monitoring
   - [ ] Set up alerting for critical issues
   - [ ] Enable application logging

---

## 🤝 Contributing

To contribute to FARUNOVA:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please ensure all code follows the existing style and includes proper security checks.

---

## 📚 Additional Resources

- [M-Pesa API Documentation](https://developer.safaricom.co.ke/)
- [PHP Security Best Practices](https://owasp.org/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.0/)
- [MariaDB Documentation](https://mariadb.com/kb/en/)
- [TCPDF Library](https://tcpdf.org/)

---

## 📄 License

This project is open source and available under the MIT License. See LICENSE file for details.

---

## 👥 Support & Contact

For issues, suggestions, or support:
- **Email**: support@farunova.com
- **GitHub Issues**: [Report an issue](https://github.com/farunova-art/farunova/issues)
- **Documentation**: Visit `/readme` folder

---

**Last Updated**: November 22, 2025  
**Current Version**: 1.0.0 (Phase 6 - Advanced Payment Management)  
**Maintained By**: FARUNOVA Development Team

This software is under an MIT License. Which allows full use to edit, distribute, or sell this code.
See the "LICENSE" file for more information!
