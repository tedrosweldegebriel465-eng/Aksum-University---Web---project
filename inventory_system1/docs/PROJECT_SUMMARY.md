# StockWise Pro - Inventory Management System

## 🎯 Project Overview
A complete web-based inventory management system built with PHP, MySQL, HTML, CSS, and JavaScript. Features a modern, colorful interface with comprehensive user management, product tracking, and reporting capabilities.

## ✨ Key Features Implemented
- **User Authentication & Management** - Login, registration, role-based access
- **Dashboard Analytics** - Real-time statistics with colorful stat cards
- **Product Management** - Add, edit, delete, track inventory levels
- **Supplier Management** - Manage supplier information and relationships
- **Category Management** - Organize products by categories
- **Stock Tracking** - Monitor stock levels, low stock alerts
- **Reporting System** - Generate reports for various metrics
- **Export Functionality** - CSV export for all data sections
- **Responsive Design** - Works on desktop and mobile devices
- **Modern UI** - Colorful gradients, Times New Roman typography

## 📁 Project Structure

### Core Application Files
```
inventory_system/
├── index.php                 # Main entry point
├── database.sql             # Database schema
├── .htaccess               # URL rewriting rules
├── 404.html                # Custom error page
│
├── admin/                  # Admin panel pages
│   ├── dashboard.php       # Main dashboard
│   ├── user_management.php # User management
│   ├── passcode_management.php
│   ├── reports/           # Report modules
│   │   ├── stock_movements_report.php
│   │   ├── supplier_report.php
│   │   └── category_report.php
│
├── auth/                   # Authentication
│   └── login.php          # Login page
│
├── api/                    # API endpoints
│   ├── export_report.php  # CSV export functionality
│   ├── generate_passcode.php
│   └── check_passcode.php
│
├── config/                 # Configuration
│   └── db.php             # Database connection
│
├── includes/               # Shared components
│   ├── header.php         # Common header
│   ├── footer.php         # Common footer
│   └── auth_check.php     # Authentication middleware
│
├── assets/                 # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── script.js      # JavaScript functionality
│
└── images/                 # Product images and assets
```

## 🎨 Design Features
- **Colorful Interface** - Modern gradient backgrounds and vibrant colors
- **Typography** - Times New Roman font throughout for professional look
- **Responsive Layout** - Grid-based design that adapts to screen sizes
- **Interactive Elements** - Hover effects, smooth transitions
- **Status Badges** - Color-coded status indicators
- **Chart Visualizations** - Bar charts and pie charts for data display

## 🔧 Technical Specifications
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6)
- **Styling**: Custom CSS with gradients and animations
- **Icons**: Font Awesome 6.0
- **Authentication**: Session-based with role management
- **Security**: Prepared statements, input sanitization, CSRF protection

## 🚀 Installation & Setup
1. Upload files to web server
2. Create MySQL database
3. Import `database.sql`
4. Configure database connection in `config/db.php`
5. Set proper file permissions
6. Access via web browser

## 👥 User Roles
- **Admin**: Full system access, user management, all reports
- **Staff**: Limited access, basic inventory operations

## 📊 Dashboard Features
- Total Products count
- Low Stock Items alert
- Active Suppliers count  
- Categories count
- Recent activity logs
- Quick action buttons

## 🔐 Security Features
- Password hashing (PHP password_hash)
- Session management with timeout
- SQL injection prevention
- CSRF token protection
- Role-based access control
- Input validation and sanitization

## 📈 Reporting & Analytics
- Stock movement reports
- Supplier performance reports
- Category analysis reports
- CSV export functionality
- Real-time dashboard metrics

## 🎯 Project Status: COMPLETE ✅
All features implemented and tested. Ready for production deployment.

---
**Developed by**: University 3rd Year Project Team
**Completion Date**: January 2026
**Version**: 1.0.0