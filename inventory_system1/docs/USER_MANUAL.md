# IMS - User Manual
## Inventory Management System

### 📋 **Table of Contents**
1. [Getting Started](#getting-started)
2. [Homepage Navigation](#homepage-navigation)
3. [User Authentication](#user-authentication)
4. [Dashboard Overview](#dashboard-overview)
5. [Product Management](#product-management)
6. [Category Management](#category-management)
7. [Supplier Management](#supplier-management)
8. [Stock Management](#stock-management)
9. [Reports & Analytics](#reports--analytics)
10. [User Profile](#user-profile)
11. [Admin Functions](#admin-functions)
12. [Troubleshooting](#troubleshooting)

---

## 🚀 **Getting Started**

### **System Access**
1. Open your web browser
2. Navigate to: `http://localhost/inventory_system/`
3. You'll see the professional homepage with navigation menu

### **First Time Setup**
1. **Homepage**: Explore the features and information
2. **Registration**: Click "Login" → Switch to "Create Account"
3. **Login**: Use existing credentials or demo accounts

---

## 🏠 **Homepage Navigation**

### **Navigation Menu**
- **Home**: Returns to homepage
- **Products**: View product showcase
- **About Us**: Company information and statistics
- **Features**: System capabilities overview
- **FAQ**: Frequently asked questions
- **Contact**: Contact form and business information

### **Main Actions**
- **Get Started**: Leads to login page (or dashboard if logged in)
- **Login**: Access the system
- **Learn More**: Scroll to features section

---

## 🔐 **User Authentication**

### **Login Process**
1. **Click "Login"** from homepage
2. **Enter credentials**:
   - Username or Email
   - Password
3. **Optional**: Check "Remember me"
4. **Click "Sign In"**

### **Registration Process**
1. **Click "Create Account"** on login page
2. **Fill required information**:
   - First Name & Last Name
   - Email Address
   - Username (minimum 3 characters)
   - Role (Staff or Admin)
   - **Registration Passcode** (required - obtain from administrator)
   - Password (minimum 6 characters)
   - Confirm Password
3. **Click "Create Account"**
4. **Success**: Automatically switches to login form

### **Registration Passcode System**
- **Security Feature**: All new registrations require a valid passcode
- **Staff Registration**: Requires a staff passcode from an administrator
- **Admin Registration**: Requires an admin passcode from an existing administrator
- **Passcode Validation**: Real-time validation shows if passcode is valid
- **Expiration**: Passcodes have expiration dates set by the administrator
- **One-Time Use**: Each passcode can only be used once

### **How to Get a Registration Passcode**
1. **Contact an Administrator**: Reach out to any existing admin user
2. **Request Role-Specific Passcode**: Specify whether you need staff or admin access
3. **Receive Passcode**: Administrator will generate and provide you with a unique passcode
4. **Use Within Expiration**: Use the passcode before it expires (typically 7-90 days)

### **Demo Accounts**
- **Administrator**: `admin` / `admin123`
- **Staff Member**: `staff` / `admin123`

### **Social Login Options**
- Facebook, Google, Twitter, LinkedIn icons available
- Currently for display (can be implemented later)

---

## 📊 **Dashboard Overview**

### **Main Dashboard Elements**
1. **Statistics Cards**:
   - Total Products
   - Low Stock Items
   - Total Categories
   - Total Suppliers

2. **Charts & Analytics**:
   - Inventory Value Chart
   - Stock Level Distribution
   - Recent Activity Timeline

3. **Quick Actions**:
   - Add New Product
   - Update Stock
   - Generate Reports
   - View Notifications

### **Navigation Sidebar**
- **Dashboard**: Main overview page
- **Products**: Product management
- **Categories**: Category organization
- **Suppliers**: Supplier information
- **Stock History**: Transaction records
- **Reports**: Analytics and exports
- **Users**: User management (Admin only)
- **Activity Logs**: System audit trail (Admin only)

---

## 📦 **Product Management**

### **Viewing Products**
1. **Click "Products"** in sidebar
2. **Product List** shows:
   - Product image
   - Name and SKU
   - Category and Supplier
   - Current stock level
   - Price and total value
   - Status (In Stock/Low Stock/Out of Stock)

### **Adding New Products**
1. **Click "Add Product"** button
2. **Fill product information**:
   - Product Name
   - SKU (Stock Keeping Unit)
   - Category (select from dropdown)
   - Supplier (select from dropdown)
   - Description
   - Price
   - Initial Quantity
   - Minimum Stock Level
   - Product Image (optional)
3. **Click "Save Product"**

### **Editing Products**
1. **Click "Edit"** button on product row
2. **Modify information** as needed
3. **Update image** if required
4. **Click "Update Product"**

### **Stock Updates**
1. **Click "Update Stock"** button
2. **Select transaction type**:
   - Stock In (receiving inventory)
   - Stock Out (selling/using inventory)
   - Adjustment (corrections)
3. **Enter quantity** and notes
4. **Click "Update Stock"**

---

## 🏷️ **Category Management**

### **Viewing Categories**
1. **Click "Categories"** in sidebar
2. **Category list** shows name, description, and product count

### **Adding Categories**
1. **Click "Add Category"** button
2. **Enter**:
   - Category Name
   - Description (optional)
3. **Click "Save Category"**

### **Managing Categories**
- **Edit**: Modify category information
- **Delete**: Remove category (only if no products assigned)
- **View Products**: See all products in category

---

## 🚚 **Supplier Management**

### **Viewing Suppliers**
1. **Click "Suppliers"** in sidebar
2. **Supplier list** shows contact information and status

### **Adding Suppliers**
1. **Click "Add Supplier"** button
2. **Enter supplier information**:
   - Company Name
   - Contact Person
   - Phone Number
   - Email Address
   - Physical Address
3. **Click "Save Supplier"**

### **Managing Suppliers**
- **Edit**: Update supplier information
- **Activate/Deactivate**: Change supplier status
- **View Products**: See products from this supplier

---

## 📈 **Stock Management**

### **Stock Transactions**
1. **Click "Stock History"** in sidebar
2. **View transaction log**:
   - Date and time
   - Product name
   - Transaction type
   - Quantity changed
   - Previous and new stock levels
   - User who made the change
   - Notes

### **Transaction Types**
- **Stock In**: Adding inventory (purchases, returns)
- **Stock Out**: Removing inventory (sales, damage)
- **Adjustment**: Corrections (count discrepancies)

### **Low Stock Alerts**
- **Automatic notifications** when products reach minimum levels
- **Dashboard indicators** for low stock items
- **Email alerts** (if configured)

---

## 📊 **Reports & Analytics**

### **Available Reports**
1. **Inventory Report**:
   - Current stock levels
   - Product values
   - Stock status

2. **Low Stock Report**:
   - Products below minimum levels
   - Urgency indicators
   - Reorder suggestions

3. **Stock Movement Report**:
   - Transaction history
   - Date range filtering
   - User activity

4. **Category Report**:
   - Inventory by category
   - Category performance
   - Value distribution

### **Generating Reports**
1. **Click "Reports"** in sidebar
2. **Select report type** from dropdown
3. **Choose date range** (if applicable)
4. **Click "Generate Report"**
5. **View results** in table format
6. **Export to CSV** using "Export CSV" button

### **Printing Reports**
1. **Click "Print"** button
2. **Browser print dialog** opens
3. **Select printer** and options
4. **Print report**

---

## 👤 **User Profile**

### **Accessing Profile**
1. **Click settings icon** (⚙️) next to logout button
2. **Profile page** opens with three sections

### **Profile Photo**
1. **Upload new photo**:
   - Click "Choose File"
   - Select image (JPG, PNG, GIF)
   - Maximum 5MB file size
   - Click "Upload Photo"

### **Profile Information**
1. **Update details**:
   - Username
   - Email address
   - View role and member since date
2. **Click "Update Profile"**

### **Change Password**
1. **Enter current password**
2. **Enter new password** (minimum 6 characters)
3. **Confirm new password**
4. **Click "Change Password"**

---

## 👨‍💼 **Admin Functions**

### **Registration Passcode Management** (Admin Only)
1. **Access Passcode Management**:
   - Click "Registration Passcodes" in sidebar
   - View statistics: Total generated, Active, Used, Expired

2. **Generate New Passcodes**:
   - Select role (Staff or Admin)
   - Choose expiration period (7-90 days)
   - Click "Generate Passcode"
   - Copy the generated passcode to share with new users

3. **Manage Existing Passcodes**:
   - View all generated passcodes with status
   - Copy passcodes to clipboard
   - Deactivate unused passcodes if needed
   - Track which user used each passcode

4. **Passcode Security Features**:
   - Unique 8-character alphanumeric codes
   - Role-specific (staff passcodes only work for staff registration)
   - Time-limited expiration
   - One-time use only
   - Activity logging for audit trail

### **User Management** (Admin Only)
1. **Click "Users"** in sidebar
2. **View all users** with roles and status
3. **Add new users**:
   - Click "Add User"
   - Fill user information
   - Assign role (Admin/Staff)
   - Set initial password
4. **Manage existing users**:
   - Edit user information
   - Change user roles
   - Activate/Deactivate accounts
   - Reset passwords

### **Activity Logs** (Admin Only)
1. **Click "Activity Logs"** in sidebar
2. **View system activities**:
   - User logins/logouts
   - Product changes
   - Stock transactions
   - User management actions
3. **Filter by**:
   - Date range
   - User
   - Action type

### **System Configuration**
- **Database management**
- **Security settings**
- **Notification preferences**
- **Backup and restore**

---

## 🔧 **Troubleshooting**

### **Common Issues**

#### **Login Problems**
- **Forgot Password**: Contact administrator for reset
- **Account Locked**: Check with administrator
- **Invalid Credentials**: Verify username and password

#### **Image Upload Issues**
- **File too large**: Maximum 5MB allowed
- **Wrong format**: Use JPG, PNG, or GIF only
- **Upload fails**: Check file permissions

#### **Report Generation Problems**
- **No data**: Check date range and filters
- **Export fails**: Ensure browser allows downloads
- **Slow loading**: Large datasets may take time

#### **Performance Issues**
- **Slow loading**: Clear browser cache
- **Timeout errors**: Refresh page and try again
- **Display problems**: Update browser or try different browser

### **Browser Compatibility**
- **Recommended**: Chrome, Firefox, Safari, Edge (latest versions)
- **Mobile**: Responsive design works on all mobile browsers
- **JavaScript**: Must be enabled for full functionality

### **Getting Help**
1. **Check FAQ** section on homepage
2. **Contact administrator** for technical issues
3. **Review user manual** for detailed instructions
4. **Check system requirements** for compatibility

---

## 📞 **Support Information**

### **Technical Support**
- **Email**: support@ims-system.com
- **Phone**: +1 (555) 123-4567
- **Hours**: Monday - Friday, 9:00 AM - 6:00 PM

### **Training Resources**
- **Video tutorials**: Available on request
- **User workshops**: Scheduled monthly
- **Documentation**: Always up-to-date online

### **System Updates**
- **Automatic updates**: Applied during maintenance windows
- **Feature requests**: Submit through admin panel
- **Bug reports**: Contact technical support

---

**© 2024 IMS - Inventory Management System | User Manual v1.0**