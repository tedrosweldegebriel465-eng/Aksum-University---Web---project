# IMS - Inventory Management System
## University Project Documentation

### 🎯 **Project Overview**
A comprehensive web-based inventory management system designed to help businesses efficiently track products, manage suppliers, monitor stock levels, and generate detailed reports. Built with modern web technologies and professional UI/UX design.

### 🚀 **Key Features**
- **Professional Homepage** with responsive design and animations
- **Secure User Authentication** with passcode-based registration system
- **Role-Based Access Control** (Admin & Staff permissions)
- **Registration Passcode Management** for controlled user access
- **Product Management** with image upload and categorization
- **Supplier Management** with contact information tracking
- **Stock Transaction Tracking** with detailed history
- **Real-time Notifications** for low stock alerts
- **Comprehensive Reports** with CSV export functionality
- **User Profile Management** with photo upload
- **Activity Logging** for audit trails
- **Mobile-Responsive Design** for all devices

### 💻 **Technologies Used**
- **Backend**: PHP 8.0+, MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Frameworks**: Bootstrap Icons, Font Awesome
- **Database**: MySQL with normalized schema
- **Server**: Apache (XAMPP)
- **Security**: Password hashing, SQL injection prevention, CSRF protection

### 📋 **System Requirements**
- **Web Server**: Apache 2.4+
- **PHP**: Version 8.0 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)
- **Storage**: Minimum 100MB for application files

### 🏗️ **Project Structure**
```
inventory_system/
├── admin/                  # Admin panel pages
│   ├── dashboard.php      # Main dashboard
│   ├── products.php       # Product management
│   ├── add_product.php    # Add new products
│   ├── edit_product.php   # Edit existing products
│   ├── categories.php     # Category management
│   ├── suppliers.php      # Supplier management
│   ├── stock_transactions.php # Stock history
│   ├── users.php          # User management (Admin only)
│   ├── passcode_management.php # Registration passcode system (Admin only)
│   ├── role_management.php # Role-based access control
│   ├── reports.php        # Reports and analytics
│   ├── orders.php         # Order management
│   ├── sales.php          # Sales tracking
│   ├── contact_messages.php # Contact form messages
│   └── profile.php        # User profile management
├── api/                   # API endpoints
│   ├── export_report.php  # CSV export functionality
│   ├── get_chart_data.php # Dashboard charts data
│   ├── get_notifications.php # Notification system
│   └── check_passcode.php # Passcode validation API
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── script.js      # JavaScript functionality
│   └── images/            # Image storage
│       ├── homepage/      # Homepage images
│       ├── products/      # Product images
│       └── profiles/      # User profile photos
├── auth/                  # Authentication system
│   ├── login.php          # Animated login page
│   ├── register.php       # User registration
│   ├── logout.php         # Logout functionality
│   ├── forgot_password.php # Password reset request
│   ├── reset_password.php # Password reset form
│   └── verify_email.php   # Email verification
├── config/                # Configuration files
│   └── db.php             # Database connection
├── docs/                  # Project documentation
│   ├── README.md          # Documentation index
│   ├── USER_MANUAL.md     # User guide
│   ├── TECHNICAL_DOCUMENTATION.md # Technical specs
│   ├── PROJECT_SUMMARY.md # Project overview
│   ├── ROLE_BASED_ACCESS_CONTROL.md # Security docs
│   ├── MARKET_ANALYSIS.md # Market analysis
│   ├── PROJECT_BRANDING.md # Branding guidelines
│   └── PROJECT_PRESENTATION.md # Presentation materials
├── includes/              # Reusable components
│   ├── header.php         # Common header
│   ├── footer.php         # Common footer
│   ├── auth_check.php     # Authentication middleware
│   └── role_check.php     # Role-based access control
├── uploads/               # File uploads directory
├── index.php              # Homepage
├── contact_handler.php    # Contact form handler
├── database/               # Database files
│   ├── inventory_system.sql # Database schema and data
│   └── README.md          # Database documentation
├── .htaccess             # Apache configuration
├── 404.html              # Custom error page
└── README.md             # Main project documentation
```

### 🗄️ **Database Schema**
The system uses a normalized MySQL database with the following tables:
- **users**: User accounts and authentication
- **registration_passcodes**: Admin-generated passcodes for secure registration
- **categories**: Product categories
- **suppliers**: Supplier information
- **products**: Product inventory with images
- **stock_transactions**: Stock movement history
- **activity_logs**: System activity tracking
- **notifications**: User notifications

### 🔐 **Security Features**
- **Password Hashing**: bcrypt encryption for user passwords
- **Registration Passcode System**: Admin-controlled user registration
- **SQL Injection Prevention**: Prepared statements for all queries
- **CSRF Protection**: Token-based form validation
- **Session Management**: Secure session handling with timeouts
- **Input Sanitization**: All user inputs are sanitized
- **File Upload Security**: Restricted file types and size limits
- **Activity Logging**: Complete audit trail of user actions

### 👥 **User Roles**
1. **Administrator**
   - Full system access
   - User management
   - System configuration
   - All reports and analytics

2. **Staff Member**
   - Product management
   - Stock transactions
   - Basic reports
   - Profile management

### 📊 **Reports Available**
- **Inventory Report**: Current stock levels and values
- **Low Stock Report**: Products below minimum threshold
- **Stock Movement Report**: Transaction history
- **Category Report**: Inventory by category
- **Supplier Report**: Products by supplier

### 🚀 **Installation Guide**
1. **Download XAMPP** and install Apache + MySQL
2. **Copy project** to `C:\xampp\htdocs\inventory_system\`
3. **Start Apache and MySQL** in XAMPP Control Panel
4. **Import database** using phpMyAdmin:
   - Open `http://localhost/phpmyadmin`
   - Create database named `inventory_system`
   - Import `database/inventory_system.sql` file
5. **Run initial setup** by visiting `http://localhost/inventory_system/setup_initial_passcode.php`
6. **Save the generated passcodes** securely
7. **Delete setup file** for security: `setup_initial_passcode.php`
8. **Access application** at `http://localhost/inventory_system/`

### 🎯 **Default Login Credentials**
- **Admin**: username `admin`, password `admin123`
- **Staff**: username `staff`, password `admin123`

### 🔑 **Registration Passcode System**
- **New User Registration**: Requires a valid passcode from an administrator
- **Admin Registration**: Requires an admin-specific passcode
- **Staff Registration**: Requires a staff-specific passcode
- **Initial Setup**: Run `setup_initial_passcode.php` after database installation to generate first passcodes
- **Passcode Management**: Admins can generate new passcodes through the admin panel

### 🎨 **Design Features**
- **Modern UI/UX**: Professional interface design
- **Responsive Layout**: Works on desktop, tablet, and mobile
- **Smooth Animations**: CSS transitions and JavaScript effects
- **Color Scheme**: Professional blue gradient theme
- **Typography**: Times New Roman for consistency
- **Icons**: Font Awesome for visual elements

### 📱 **Mobile Compatibility**
- Responsive design adapts to all screen sizes
- Touch-friendly interface elements
- Optimized navigation for mobile devices
- Fast loading on mobile networks

### 🔧 **Future Enhancements**
- Email notifications for low stock
- Barcode scanning functionality
- Multi-language support
- Advanced analytics dashboard
- API for mobile app integration
- Automated backup system

### 👨‍💻 **Developer Information**
- **Project Type**: University Final Year Project
- **Development Time**: 3 months
- **Team Size**: Individual project
- **Academic Year**: 2024-2025

### 📞 **Support**
For technical support or questions about this project:
- Check the User Manual for detailed instructions
- Review the Technical Documentation for development details
- Contact the development team for assistance

---
**© 2026 IMS - Inventory Management System**