# IMS - Technical Documentation
## Inventory Management System

### 📋 **Table of Contents**
1. [System Architecture](#system-architecture)
2. [Database Design](#database-design)
3. [API Documentation](#api-documentation)
4. [Security Implementation](#security-implementation)
5. [File Structure](#file-structure)
6. [Configuration](#configuration)
7. [Development Guidelines](#development-guidelines)
8. [Deployment](#deployment)

---

## 🏗️ **System Architecture**

### **Technology Stack**
```
Frontend Layer:
├── HTML5 (Semantic markup)
├── CSS3 (Grid, Flexbox, Animations)
├── JavaScript ES6+ (Vanilla JS)
└── Font Awesome Icons

Backend Layer:
├── PHP 8.0+ (Object-oriented)
├── MySQL 8.0+ (Relational database)
└── Apache 2.4+ (Web server)

Development Tools:
├── XAMPP (Local development)
├── phpMyAdmin (Database management)
└── Git (Version control)
```

### **MVC Pattern Implementation**
```
Model (Data Layer):
├── config/db.php (Database connection)
├── Database tables (Data storage)
└── SQL queries (Data operations)

View (Presentation Layer):
├── HTML templates
├── CSS styling
└── JavaScript interactions

Controller (Logic Layer):
├── PHP scripts (Business logic)
├── Authentication (User management)
└── API endpoints (Data processing)
```

---

## 🗄️ **Database Design**

### **Entity Relationship Diagram**
```
Users ||--o{ Activity_Logs
Users ||--o{ Stock_Transactions
Users ||--o{ Notifications

Categories ||--o{ Products
Suppliers ||--o{ Products

Products ||--o{ Stock_Transactions
```

### **Table Structures**

#### **users**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

#### **categories**
```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### **suppliers**
```sql
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```

#### **products**
```sql
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    supplier_id INT,
    sku VARCHAR(50) UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);
```

#### **stock_transactions**
```sql
CREATE TABLE stock_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    transaction_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    previous_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **activity_logs**
```sql
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **notifications**
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('low_stock', 'system', 'info') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### **registration_passcodes**
```sql
CREATE TABLE registration_passcodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passcode VARCHAR(20) UNIQUE NOT NULL,
    role ENUM('admin', 'staff') NOT NULL,
    generated_by INT NOT NULL,
    used_by INT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Database Indexes**
```sql
-- Performance optimization indexes
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_supplier ON products(supplier_id);
CREATE INDEX idx_stock_transactions_product ON stock_transactions(product_id);
CREATE INDEX idx_activity_logs_user ON activity_logs(user_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_passcodes_generated_by ON registration_passcodes(generated_by);
CREATE INDEX idx_passcodes_used_by ON registration_passcodes(used_by);
```

---

## 🔌 **API Documentation**

### **Authentication Endpoints**

#### **POST /auth/login.php**
```php
// Login user
Request Body:
{
    "action": "login",
    "username": "string",
    "password": "string"
}

Response:
{
    "success": true,
    "redirect": "/admin/dashboard.php"
}
```

#### **POST /auth/register.php**
```php
// Register new user with passcode validation
Request Body:
{
    "action": "register",
    "firstName": "string",
    "lastName": "string",
    "email": "string",
    "username": "string",
    "role": "admin|staff",
    "passcode": "string",
    "password": "string",
    "confirmPassword": "string"
}

Response:
{
    "success": true,
    "message": "Registration successful"
}
```

#### **POST /api/check_passcode.php**
```php
// Validate registration passcode
Request Body:
{
    "passcode": "string",
    "role": "admin|staff"
}

Response:
{
    "valid": true,
    "message": "Valid passcode for staff registration (expires Mar 15, 2024 5:00 PM)"
}
```

### **Data Export Endpoints**

#### **GET /api/export_report.php**
```php
// Export reports to CSV
Parameters:
- type: "inventory|low_stock|stock_movements|category"
- start_date: "YYYY-MM-DD" (optional)
- end_date: "YYYY-MM-DD" (optional)

Response: CSV file download
```

#### **GET /api/get_chart_data.php**
```php
// Get dashboard chart data
Response:
{
    "inventory_value": [
        {"category": "Electronics", "value": 15000},
        {"category": "Office Supplies", "value": 5000}
    ],
    "stock_levels": [
        {"status": "In Stock", "count": 45},
        {"status": "Low Stock", "count": 8}
    ]
}
```

#### **GET /api/get_notifications.php**
```php
// Get user notifications
Response:
{
    "notifications": [
        {
            "id": 1,
            "type": "low_stock",
            "title": "Low Stock Alert",
            "message": "Product XYZ is running low",
            "is_read": false,
            "created_at": "2024-01-01 10:00:00"
        }
    ],
    "unread_count": 3
}
```

---

## 🔐 **Security Implementation**

### **Registration Passcode System**
```php
// Passcode generation
function generatePasscode() {
    return strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

// Passcode validation during registration
$stmt = $conn->prepare("SELECT id, role, expires_at FROM registration_passcodes WHERE passcode = ? AND is_used = FALSE AND expires_at > NOW()");
$stmt->bind_param("s", $passcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $passcode_data = $result->fetch_assoc();
    // Validate role match and proceed with registration
    
    // Mark passcode as used after successful registration
    $update_stmt = $conn->prepare("UPDATE registration_passcodes SET is_used = TRUE, used_by = ?, used_at = NOW() WHERE id = ?");
    $update_stmt->bind_param("ii", $new_user_id, $passcode_data['id']);
    $update_stmt->execute();
}
```

### **Authentication & Authorization**
```php
// Password hashing
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Password verification
if (password_verify($password, $stored_hash)) {
    // Login successful
}

// Session management
session_start();
$_SESSION['user_id'] = $user_id;
$_SESSION['user_role'] = $user_role;
$_SESSION['last_activity'] = time();
```

### **SQL Injection Prevention**
```php
// Prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();
```

### **Input Sanitization**
```php
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}
```

### **CSRF Protection**
```php
// Generate CSRF token
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
```

### **File Upload Security**
```php
// Allowed file types
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

// File size limit (5MB)
$max_size = 5000000;

// Validate file
if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
    // Process upload
}
```

---

## 📁 **File Structure**

### **Directory Organization**
```
inventory_system/
├── admin/                     # Admin panel pages
│   ├── dashboard.php         # Main dashboard
│   ├── products.php          # Product management
│   ├── add_product.php       # Add new product
│   ├── edit_product.php      # Edit existing product
│   ├── delete_product.php    # Delete product
│   ├── categories.php        # Category management
│   ├── suppliers.php         # Supplier management
│   ├── stock_transactions.php # Stock history
│   ├── update_stock.php      # Stock updates
│   ├── reports.php           # Reports dashboard
│   ├── users.php             # User management (Admin)
│   ├── activity_logs.php     # Activity logs (Admin)
│   ├── profile.php           # User profile
│   └── upload_image.php      # Image upload handler
├── api/                      # API endpoints
│   ├── export_report.php     # CSV export
│   ├── get_chart_data.php    # Dashboard data
│   ├── get_notifications.php # Notifications
│   └── mark_notifications_read.php # Mark as read
├── assets/                   # Static resources
│   ├── css/
│   │   └── style.css         # Main stylesheet
│   ├── js/
│   │   └── script.js         # JavaScript functions
│   └── images/
│       ├── products/         # Product images
│       ├── profiles/         # User profile photos
│       └── homepage/         # Homepage images
├── auth/                     # Authentication
│   ├── login.php             # Login page
│   ├── register.php          # Registration page
│   └── logout.php            # Logout handler
├── config/                   # Configuration
│   └── db.php                # Database connection
├── includes/                 # Reusable components
│   ├── header.php            # Common header
│   ├── footer.php            # Common footer
│   └── auth_check.php        # Authentication middleware
├── reports/                  # Report templates
│   ├── inventory_report.php  # Inventory report
│   └── low_stock_report.php  # Low stock report
├── index.php                 # Homepage
├── database.sql              # Database schema
├── .htaccess                 # Apache configuration
├── 404.html                  # Error page
├── README.md                 # Project documentation
├── USER_MANUAL.md            # User guide
└── TECHNICAL_DOCUMENTATION.md # Technical specs
```

### **Naming Conventions**
- **Files**: lowercase with underscores (snake_case)
- **Classes**: PascalCase
- **Functions**: camelCase
- **Variables**: camelCase
- **Constants**: UPPER_CASE
- **Database tables**: lowercase with underscores

---

## ⚙️ **Configuration**

### **Database Configuration** (`config/db.php`)
```php
<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");
?>
```

### **Apache Configuration** (`.htaccess`)
```apache
# Enable URL Rewriting
RewriteEngine On

# Security Headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Prevent access to sensitive files
<Files "*.sql">
    Order allow,deny
    Deny from all
</Files>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType text/javascript "access plus 1 month"
</IfModule>
```

### **PHP Configuration Requirements**
```ini
; Minimum PHP settings
memory_limit = 128M
upload_max_filesize = 5M
post_max_size = 8M
max_execution_time = 30
session.gc_maxlifetime = 1800
```

---

## 👨‍💻 **Development Guidelines**

### **Code Standards**
```php
<?php
/**
 * File description
 * Project: Inventory Management System
 */

class ProductManager {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Add new product to inventory
     * @param array $product_data Product information
     * @return bool Success status
     */
    public function addProduct($product_data) {
        // Implementation
    }
}
?>
```

### **Error Handling**
```php
try {
    // Database operations
    $stmt = $conn->prepare($sql);
    $stmt->execute();
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    return false;
}
```

### **Logging Standards**
```php
// Activity logging
function log_activity($action, $table_name = null, $record_id = null, $details = null) {
    global $conn;
    
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $details, $ip_address);
    $stmt->execute();
}
```

### **Frontend Standards**
```css
/* CSS Organization */
/* 1. Reset and base styles */
/* 2. Layout components */
/* 3. UI components */
/* 4. Utilities */
/* 5. Responsive design */

.component-name {
    /* Properties in alphabetical order */
    background: #ffffff;
    border: 1px solid #ddd;
    padding: 1rem;
}
```

```javascript
// JavaScript Standards
/**
 * Function description
 * @param {string} parameter - Parameter description
 * @returns {boolean} Return description
 */
function functionName(parameter) {
    // Implementation
    return true;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
});
```

---

## 🚀 **Deployment**

### **Production Deployment Steps**

#### **1. Server Requirements**
```
Web Server: Apache 2.4+ or Nginx 1.18+
PHP: 8.0+ with extensions:
  - mysqli
  - gd (for image processing)
  - session
  - json
Database: MySQL 8.0+ or MariaDB 10.4+
SSL Certificate: Required for production
```

#### **2. File Preparation**
```bash
# Remove development files
rm -f debug_*.php
rm -f test_*.php
rm -rf .git/

# Set proper permissions
chmod 755 admin/ api/ assets/ auth/ config/ includes/
chmod 644 *.php *.html *.css *.js
chmod 777 assets/images/products/ assets/images/profiles/
```

#### **3. Database Setup**
```sql
-- Create production database
CREATE DATABASE inventory_system_prod;

-- Import schema
mysql -u username -p inventory_system_prod < database.sql

-- Create database user
CREATE USER 'ims_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON inventory_system_prod.* TO 'ims_user'@'localhost';
FLUSH PRIVILEGES;
```

#### **4. Configuration Updates**
```php
// config/db.php - Production settings
define('DB_HOST', 'localhost');
define('DB_USER', 'ims_user');
define('DB_PASS', 'secure_password');
define('DB_NAME', 'inventory_system_prod');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
ini_set('display_errors', 0);
```

### **Security Checklist**
- [ ] Change default passwords
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall rules
- [ ] Set up regular backups
- [ ] Enable error logging
- [ ] Disable directory browsing
- [ ] Configure session security
- [ ] Set up monitoring

### **Performance Optimization**
```apache
# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript
</IfModule>

# Enable caching
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
</IfModule>
```

### **Backup Strategy**
```bash
#!/bin/bash
# Daily backup script
DATE=$(date +%Y%m%d_%H%M%S)

# Database backup
mysqldump -u username -p inventory_system_prod > backup_db_$DATE.sql

# File backup
tar -czf backup_files_$DATE.tar.gz /path/to/inventory_system/

# Keep only last 30 days
find /backup/path/ -name "backup_*" -mtime +30 -delete
```

---

## 📊 **Performance Metrics**

### **Database Performance**
- **Query execution time**: < 100ms average
- **Connection pooling**: Enabled
- **Index usage**: Optimized for common queries
- **Backup frequency**: Daily automated backups

### **Web Performance**
- **Page load time**: < 2 seconds
- **Image optimization**: Compressed and cached
- **CSS/JS minification**: Enabled in production
- **CDN usage**: Font Awesome from CDN

### **Security Metrics**
- **Password strength**: Minimum 8 characters
- **Session timeout**: 30 minutes inactivity
- **Failed login attempts**: Logged and monitored
- **File upload restrictions**: Type and size limits

---

**© 2024 IMS - Inventory Management System | Technical Documentation v1.0**