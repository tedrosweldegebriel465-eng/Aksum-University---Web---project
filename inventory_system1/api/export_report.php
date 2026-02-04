<?php
/**
 * Export Report to CSV API - Fixed Version
 * Inventory Management System
 */
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$report_type = $_GET['type'] ?? 'inventory';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $report_type . '_report_' . date('Y-m-d') . '.csv"');

// Create file pointer
$output = fopen('php://output', 'w');

switch ($report_type) {
    case 'products':
        // Products Export
        fputcsv($output, ['Product Name', 'SKU', 'Category', 'Supplier', 'Description', 'Price', 'Quantity', 'Min Stock Level', 'Total Value', 'Status']);
        
        // Build query with filters
        $where_conditions = ["p.status = 'active'"];
        $params = [];
        $param_types = '';
        
        // Apply filters from URL
        $filter = $_GET['filter'] ?? 'all';
        $category_filter = $_GET['category'] ?? '';
        $supplier_filter = $_GET['supplier'] ?? '';
        $search = $_GET['search'] ?? '';
        
        if ($filter == 'low_stock') {
            $where_conditions[] = "p.quantity <= p.min_stock_level";
        } elseif ($filter == 'out_of_stock') {
            $where_conditions[] = "p.quantity = 0";
        } elseif ($filter == 'in_stock') {
            $where_conditions[] = "p.quantity > p.min_stock_level";
        }
        
        if ($category_filter) {
            $where_conditions[] = "p.category_id = ?";
            $params[] = $category_filter;
            $param_types .= 'i';
        }
        
        if ($supplier_filter) {
            $where_conditions[] = "p.supplier_id = ?";
            $params[] = $supplier_filter;
            $param_types .= 'i';
        }
        
        if ($search) {
            $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
            $param_types .= 'sss';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT p.name, p.sku, 
                   COALESCE(c.name, 'No Category') as category_name,
                   COALESCE(s.name, 'No Supplier') as supplier_name,
                   p.description, p.price, p.quantity, p.min_stock_level,
                   (p.price * p.quantity) as total_value,
                   CASE 
                       WHEN p.quantity = 0 THEN 'Out of Stock'
                       WHEN p.quantity <= p.min_stock_level THEN 'Low Stock'
                       ELSE 'In Stock'
                   END as status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE $where_clause
            ORDER BY p.name ASC
        ";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['name'],
                $row['sku'],
                $row['category_name'],
                $row['supplier_name'],
                $row['description'] ?? '',
                '$' . number_format($row['price'], 2),
                $row['quantity'],
                $row['min_stock_level'],
                '$' . number_format($row['total_value'], 2),
                $row['status']
            ]);
        }
        break;
        
    case 'activity_logs':
        // Activity Logs Export
        fputcsv($output, ['Date', 'User', 'Action', 'Table', 'Record ID', 'Details', 'IP Address']);
        
        // Build query with filters
        $where_conditions = ["1=1"];
        $params = [];
        $param_types = '';
        
        // Apply filters from URL
        $user_filter = $_GET['user_id'] ?? '';
        $action_filter = $_GET['action'] ?? '';
        
        if ($user_filter) {
            $where_conditions[] = "al.user_id = ?";
            $params[] = $user_filter;
            $param_types .= 'i';
        }
        
        if ($action_filter) {
            $where_conditions[] = "al.action LIKE ?";
            $params[] = "%$action_filter%";
            $param_types .= 's';
        }
        
        if ($start_date) {
            $where_conditions[] = "DATE(al.created_at) >= ?";
            $params[] = $start_date;
            $param_types .= 's';
        }
        
        if ($end_date) {
            $where_conditions[] = "DATE(al.created_at) <= ?";
            $params[] = $end_date;
            $param_types .= 's';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT al.created_at, u.username, al.action, al.table_name, 
                   al.record_id, al.details, al.ip_address
            FROM activity_logs al
            JOIN users u ON al.user_id = u.id
            WHERE $where_clause
            ORDER BY al.created_at DESC
            LIMIT 1000
        ";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                date('Y-m-d H:i:s', strtotime($row['created_at'])),
                $row['username'],
                $row['action'],
                $row['table_name'] ?? '',
                $row['record_id'] ?? '',
                $row['details'] ?? '',
                $row['ip_address'] ?? ''
            ]);
        }
        break;

    case 'inventory':
        // Current Inventory Report
        fputcsv($output, ['Product Name', 'SKU', 'Category', 'Supplier', 'Quantity', 'Unit Price', 'Total Value', 'Status']);
        
        $query = "
            SELECT p.name, p.sku, 
                   COALESCE(c.name, 'No Category') as category_name,
                   COALESCE(s.name, 'No Supplier') as supplier_name,
                   p.quantity, p.price, (p.price * p.quantity) as total_value,
                   CASE 
                       WHEN p.quantity = 0 THEN 'Out of Stock'
                       WHEN p.quantity <= p.min_stock_level THEN 'Low Stock'
                       ELSE 'In Stock'
                   END as status
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.status = 'active'
            ORDER BY p.name ASC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['name'],
                $row['sku'],
                $row['category_name'],
                $row['supplier_name'],
                $row['quantity'],
                '$' . number_format($row['price'], 2),
                '$' . number_format($row['total_value'], 2),
                $row['status']
            ]);
        }
        break;
        
    case 'low_stock':
        // Low Stock Report
        fputcsv($output, ['Product Name', 'SKU', 'Category', 'Current Stock', 'Min Level', 'Shortage', 'Urgency']);
        
        $query = "
            SELECT p.name, p.sku,
                   COALESCE(c.name, 'No Category') as category_name,
                   p.quantity, p.min_stock_level,
                   (p.min_stock_level - p.quantity) as shortage,
                   CASE 
                       WHEN p.quantity = 0 THEN 'Critical'
                       WHEN p.quantity <= (p.min_stock_level * 0.5) THEN 'Very Low'
                       ELSE 'Low'
                   END as urgency
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.quantity <= p.min_stock_level AND p.status = 'active'
            ORDER BY (p.quantity / NULLIF(p.min_stock_level, 0)) ASC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['name'],
                $row['sku'],
                $row['category_name'],
                $row['quantity'],
                $row['min_stock_level'],
                $row['shortage'],
                $row['urgency']
            ]);
        }
        break;
        
    case 'stock_movements':
    case 'stock_transactions':
        // Stock Movements Report
        fputcsv($output, ['Date', 'Product Name', 'SKU', 'Transaction Type', 'Quantity', 'Previous Stock', 'New Stock', 'User', 'Notes']);
        
        // Build query with filters
        $where_conditions = ["1=1"];
        $params = [];
        $param_types = '';
        
        // Apply filters from URL
        $product_filter = $_GET['product_id'] ?? '';
        $transaction_type_filter = $_GET['transaction_type'] ?? '';
        $user_filter = $_GET['user_id'] ?? '';
        
        if ($product_filter) {
            $where_conditions[] = "st.product_id = ?";
            $params[] = $product_filter;
            $param_types .= 'i';
        }
        
        if ($transaction_type_filter) {
            $where_conditions[] = "st.transaction_type = ?";
            $params[] = $transaction_type_filter;
            $param_types .= 's';
        }
        
        if ($user_filter) {
            $where_conditions[] = "st.user_id = ?";
            $params[] = $user_filter;
            $param_types .= 'i';
        }
        
        if ($start_date) {
            $where_conditions[] = "DATE(st.created_at) >= ?";
            $params[] = $start_date;
            $param_types .= 's';
        }
        
        if ($end_date) {
            $where_conditions[] = "DATE(st.created_at) <= ?";
            $params[] = $end_date;
            $param_types .= 's';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT st.created_at, p.name, p.sku, st.transaction_type, st.quantity,
                   st.previous_quantity, st.new_quantity, u.username, st.notes
            FROM stock_transactions st
            JOIN products p ON st.product_id = p.id
            JOIN users u ON st.user_id = u.id
            WHERE $where_clause
            ORDER BY st.created_at DESC
        ";
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $conn->query($query);
        }
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                date('Y-m-d H:i:s', strtotime($row['created_at'])),
                $row['name'],
                $row['sku'],
                ucfirst($row['transaction_type']),
                $row['quantity'],
                $row['previous_quantity'],
                $row['new_quantity'],
                $row['username'],
                $row['notes'] ?? ''
            ]);
        }
        break;
        
    case 'category':
        // Category Summary Report
        fputcsv($output, ['Category', 'Product Count', 'Total Quantity', 'Total Value', 'Low Stock Items']);
        
        $query = "
            SELECT 
                COALESCE(c.name, 'No Category') as category_name,
                COUNT(p.id) as product_count,
                SUM(p.quantity) as total_quantity,
                SUM(p.price * p.quantity) as total_value,
                SUM(CASE WHEN p.quantity <= p.min_stock_level THEN 1 ELSE 0 END) as low_stock_count
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
            GROUP BY p.category_id, c.name
            ORDER BY total_value DESC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['category_name'],
                $row['product_count'],
                $row['total_quantity'],
                '$' . number_format($row['total_value'], 2),
                $row['low_stock_count']
            ]);
        }
        break;
        
    case 'supplier':
        // Supplier Summary Report
        fputcsv($output, ['Supplier Name', 'Contact Person', 'Email', 'Phone', 'Product Count', 'Total Quantity', 'Total Value', 'Low Stock Items', 'Average Price']);
        
        $query = "
            SELECT 
                COALESCE(s.name, 'No Supplier') as supplier_name,
                s.contact_person,
                s.email,
                s.phone,
                COUNT(p.id) as product_count,
                SUM(p.quantity) as total_quantity,
                SUM(p.price * p.quantity) as total_value,
                SUM(CASE WHEN p.quantity <= p.min_stock_level THEN 1 ELSE 0 END) as low_stock_count,
                AVG(p.price) as avg_price
            FROM products p
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            WHERE p.status = 'active'
            GROUP BY p.supplier_id, s.name, s.contact_person, s.email, s.phone
            ORDER BY total_value DESC
        ";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['supplier_name'],
                $row['contact_person'] ?? '',
                $row['email'] ?? '',
                $row['phone'] ?? '',
                $row['product_count'],
                $row['total_quantity'],
                '$' . number_format($row['total_value'], 2),
                $row['low_stock_count'],
                '$' . number_format($row['avg_price'], 2)
            ]);
        }
        break;
        
    default:
        fputcsv($output, ['Error', 'Invalid report type']);
}

fclose($output);
exit();
?>