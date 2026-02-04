# Database Files

This folder contains all database-related files for the IMS - Inventory Management System.

## Files

- **`inventory_system.sql`** - Complete database schema and structure
- **`README.md`** - This documentation file

## Database Information

- **Database Name**: `inventory_system`
- **Type**: MySQL Database
- **Charset**: UTF-8
- **Engine**: InnoDB (recommended)

## Import Instructions

### Using phpMyAdmin:
1. Open phpMyAdmin in your browser
2. Create a new database named `inventory_system`
3. Select the database
4. Click "Import" tab
5. Choose `inventory_system.sql` file
6. Click "Go" to import

### Using MySQL Command Line:
```bash
mysql -u root -p
CREATE DATABASE inventory_system;
USE inventory_system;
SOURCE /path/to/database/inventory_system.sql;
```

### Using XAMPP:
1. Start Apache and MySQL in XAMPP Control Panel
2. Open http://localhost/phpmyadmin
3. Follow phpMyAdmin instructions above

## Database Tables

The database contains the following main tables:
- `users` - User accounts and authentication
- `products` - Product inventory management
- `categories` - Product categorization
- `suppliers` - Supplier information
- `stock_transactions` - Stock movement tracking
- `activity_logs` - System activity logging
- `notifications` - User notifications
- `registration_passcodes` - Admin passcode system

## Configuration

After importing the database, ensure your `config/db.php` file has the correct settings:
```php
define('DB_NAME', 'inventory_system');
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
```

---
**© 2026 IMS - Inventory Management System**