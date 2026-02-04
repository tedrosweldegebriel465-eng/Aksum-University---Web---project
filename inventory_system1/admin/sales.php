<?php
/**
 * Enhanced Sales Management System
 * Complete sales functionality with modern features
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../config/db.php';

// Get user info for display
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT username, role, profile_photo FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc();
}

// Suppress PHP warnings for clean display
error_reporting(0);
ini_set('display_errors', 0);

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create_sale') {
            $customer_name = trim($_POST['customer_name']);
            $customer_phone = trim($_POST['customer_phone']);
            $customer_email = trim($_POST['customer_email']);
            $payment_method = $_POST['payment_method'];
            $discount_percentage = floatval($_POST['discount_percentage'] ?? 0);
            $tax_percentage = floatval($_POST['tax_percentage'] ?? 0);
            $notes = trim($_POST['notes']);
            
            $products = $_POST['products'] ?? [];
            $quantities = $_POST['quantities'] ?? [];
            
            if (!empty($products)) {
                try {
                    $conn->begin_transaction();
                    
                    // Calculate totals
                    $subtotal = 0;
                    $sale_items = [];
                    
                    foreach ($products as $index => $product_id) {
                        if ($product_id && isset($quantities[$index]) && $quantities[$index] > 0) {
                            // Get product details
                            $stmt = $conn->prepare("SELECT name, price, quantity FROM products WHERE id = ?");
                            $stmt->bind_param("i", $product_id);
                            $stmt->execute();
                            $product = $stmt->get_result()->fetch_assoc();
                            
                            if ($product && $product['quantity'] >= $quantities[$index]) {
                                $item_total = $product['price'] * $quantities[$index];
                                $subtotal += $item_total;
                                
                                $sale_items[] = [
                                    'product_id' => $product_id,
                                    'product_name' => $product['name'],
                                    'quantity' => $quantities[$index],
                                    'unit_price' => $product['price'],
                                    'total_price' => $item_total
                                ];
                            }
                        }
                    }
                    
                    if (!empty($sale_items)) {
                        // Calculate final amounts
                        $discount_amount = ($subtotal * $discount_percentage) / 100;
                        $taxable_amount = $subtotal - $discount_amount;
                        $tax_amount = ($taxable_amount * $tax_percentage) / 100;
                        $final_amount = $taxable_amount + $tax_amount;
                        
                        // Generate sale number
                        $sale_number = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                        
                        // Insert sale record
                        $stmt = $conn->prepare("INSERT INTO sales (sale_number, customer_name, customer_phone, customer_email, subtotal, discount_percentage, discount_amount, tax_percentage, tax_amount, final_amount, payment_method, notes, sale_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
                        $stmt->bind_param("ssssdddddsssi", $sale_number, $customer_name, $customer_phone, $customer_email, $subtotal, $discount_percentage, $discount_amount, $tax_percentage, $tax_amount, $final_amount, $payment_method, $notes, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $sale_id = $conn->insert_id;
                        
                        // Insert sale items and update inventory
                        foreach ($sale_items as $item) {
                            // Insert sale item
                            $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("iisidd", $sale_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['total_price']);
                            $stmt->execute();
                            
                            // Update product inventory
                            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                            $stmt->execute();
                        }
                        
                        $conn->commit();
                        $success_message = "Sale created successfully! Sale Number: $sale_number";
                    } else {
                        throw new Exception("No valid products selected");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Error creating sale: " . $e->getMessage();
                }
            } else {
                $error_message = "Please select at least one product";
            }
        }
        
        if ($_POST['action'] == 'void_sale') {
            $sale_id = $_POST['sale_id'];
            
            try {
                $conn->begin_transaction();
                
                // Get sale items to restore inventory
                $stmt = $conn->prepare("SELECT product_id, quantity FROM sale_items WHERE sale_id = ?");
                $stmt->bind_param("i", $sale_id);
                $stmt->execute();
                $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Restore inventory
                foreach ($items as $item) {
                    $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                    $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                    $stmt->execute();
                }
                
                // Mark sale as voided
                $stmt = $conn->prepare("UPDATE sales SET status = 'voided', voided_at = NOW(), voided_by = ? WHERE id = ?");
                $stmt->bind_param("ii", $_SESSION['user_id'], $sale_id);
                $stmt->execute();
                
                $conn->commit();
                $success_message = "Sale voided successfully and inventory restored";
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error voiding sale: " . $e->getMessage();
            }
        }
    }
}

// Get sales with filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_payment = $_GET['payment_method'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if ($filter_date_from) {
    $where_conditions[] = "DATE(sale_date) >= ?";
    $params[] = $filter_date_from;
    $param_types .= "s";
}

if ($filter_date_to) {
    $where_conditions[] = "DATE(sale_date) <= ?";
    $params[] = $filter_date_to;
    $param_types .= "s";
}

if ($filter_payment) {
    $where_conditions[] = "payment_method = ?";
    $params[] = $filter_payment;
    $param_types .= "s";
}

if ($filter_status) {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

$sales = [];
try {
    $query = "SELECT s.*, u.username as created_by_name FROM sales s LEFT JOIN users u ON s.created_by = u.id WHERE $where_clause ORDER BY s.sale_date DESC LIMIT 50";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    if ($result) {
        $sales = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $sales = [];
}

// Get products for sale creation
$products = [];
try {
    $result = $conn->query("SELECT id, name, price, quantity FROM products WHERE status = 'active' AND quantity > 0 ORDER BY name ASC");
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $products = [];
}

// Get statistics
$stats = [
    'total_sales' => 0,
    'today_sales' => 0,
    'total_revenue' => 0,
    'today_revenue' => 0,
    'avg_sale_value' => 0,
    'monthly_sales' => 0,
    'monthly_revenue' => 0
];

try {
    // Total sales
    $result = $conn->query("SELECT COUNT(*) as total FROM sales WHERE status != 'voided'");
    if ($result) $stats['total_sales'] = $result->fetch_assoc()['total'];
    
    // Today's sales
    $result = $conn->query("SELECT COUNT(*) as today FROM sales WHERE DATE(sale_date) = CURDATE() AND status != 'voided'");
    if ($result) $stats['today_sales'] = $result->fetch_assoc()['today'];
    
    // Total revenue
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE status != 'voided'");
    if ($result) $stats['total_revenue'] = $result->fetch_assoc()['revenue'];
    
    // Today's revenue
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE DATE(sale_date) = CURDATE() AND status != 'voided'");
    if ($result) $stats['today_revenue'] = $result->fetch_assoc()['revenue'];
    
    // Average sale value
    if ($stats['total_sales'] > 0) {
        $stats['avg_sale_value'] = $stats['total_revenue'] / $stats['total_sales'];
    }
    
    // Monthly sales
    $result = $conn->query("SELECT COUNT(*) as monthly FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) AND status != 'voided'");
    if ($result) $stats['monthly_sales'] = $result->fetch_assoc()['monthly'];
    
    // Monthly revenue
    $result = $conn->query("SELECT COALESCE(SUM(final_amount), 0) as revenue FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) AND status != 'voided'");
    if ($result) $stats['monthly_revenue'] = $result->fetch_assoc()['revenue'];
    
} catch (Exception $e) {
    // Keep default values
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            min-height: 100vh;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }
        
        @keyframes backgroundShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }
        
        .container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
        }
        
        .logo {
            text-align: center; padding: 30px 20px; font-size: 1.8rem; font-weight: 700;
            background: rgba(255, 255, 255, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; align-items: center; justify-content: center; gap: 12px;
        }
        
        .logo i {
            background: linear-gradient(135deg, #60a5fa 0%, #34d399 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }
        
        .nav-menu { list-style: none; padding: 20px 0; }
        
        .nav-item {
            margin: 5px 15px; border-radius: 12px; transition: all 0.3s ease;
        }
        
        .nav-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(8px);
        }
        
        .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #60a5fa;
        }
        
        .nav-item a {
            color: rgba(255, 255, 255, 0.9); text-decoration: none;
            display: flex; align-items: center; gap: 15px;
            padding: 16px 20px; font-weight: 500; transition: all 0.3s ease;
        }
        
        .nav-item:hover a { color: white; }
        .nav-item i { width: 20px; text-align: center; font-size: 1.1rem; }
        
        .main-content {
            flex: 1; padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px); border-radius: 20px; padding: 30px;
            margin-bottom: 30px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .page-header h1 {
            font-size: 2.5rem; font-weight: 700;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 15px;
        }
        
        .actions { display: flex; gap: 15px; }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 15px; padding: 20px; text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .stat-number {
            font-size: 2.5rem; font-weight: 800; margin-bottom: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 1rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; letter-spacing: 1px;
        }
        
        .sales-section {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
            margin-bottom: 25px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .section-header h3 {
            font-size: 1.5rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .filter-section {
            padding: 25px 30px;
            background: rgba(248, 249, 250, 0.8);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .filter-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; align-items: end;
        }
        
        .sales-table { width: 100%; border-collapse: collapse; }
        
        .sales-table th, .sales-table td {
            padding: 18px 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .sales-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .sales-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05) 0%, rgba(15, 118, 110, 0.05) 100%);
            transform: scale(1.01);
        }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px; position: relative; overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e);
            color: white; box-shadow: 0 8px 25px rgba(30, 64, 175, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white; box-shadow: 0 8px 25px rgba(79, 172, 254, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white; box-shadow: 0 8px 25px rgba(240, 147, 251, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white; box-shadow: 0 8px 25px rgba(250, 112, 154, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white; box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .btn-sm { padding: 8px 16px; font-size: 0.85rem; }
        
        .status-badge {
            padding: 6px 12px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-voided {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
        }
        
        .alert {
            padding: 20px 25px; border-radius: 15px; margin-bottom: 25px;
            display: flex; align-items: center; gap: 15px; font-weight: 500;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(212, 237, 218, 0.9) 0%, rgba(195, 230, 203, 0.9) 100%);
            color: #155724; border-left: 5px solid #28a745;
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(248, 215, 218, 0.9) 0%, rgba(245, 198, 203, 0.9) 100%);
            color: #721c24; border-left: 5px solid #dc3545;
        }
        
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            margin: 2% auto; padding: 30px; border-radius: 20px; width: 95%;
            max-width: 900px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.2); max-height: 90vh; overflow-y: auto;
        }
        
        .modal-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 25px; padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .modal-header h3 {
            font-size: 1.8rem; font-weight: 600;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            display: flex; align-items: center; gap: 10px;
        }
        
        .close {
            color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .close:hover { color: #000; }
        
        .form-row {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: #495057; font-size: 0.95rem;
        }
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px 16px; border: 2px solid #e9ecef;
            border-radius: 12px; font-size: 1rem; transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px);
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); background: white;
        }
        
        .product-row {
            display: grid; grid-template-columns: 2fr 1fr 1fr 80px;
            gap: 15px; align-items: end; margin-bottom: 15px;
            padding: 15px; background: rgba(248, 249, 250, 0.8);
            border-radius: 12px; border: 1px solid #e9ecef;
        }
        
        .empty-state {
            text-align: center; padding: 80px 30px; color: #6c757d;
            background: rgba(255, 255, 255, 0.5); border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .empty-state i {
            font-size: 5rem; color: #dee2e6; margin-bottom: 25px;
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .empty-state h3 {
            margin-bottom: 15px; color: #495057; font-size: 1.5rem; font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 20px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .filter-grid { grid-template-columns: 1fr; }
            .product-row { grid-template-columns: 1fr; }
        }
        
        /* Enhanced Create First Sale Button */
        .btn-add-sale {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            color: white !important;
            padding: 16px 32px !important;
            border-radius: 50px !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            border: none !important;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4) !important;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
            position: relative !important;
            overflow: hidden !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-top: 20px !important;
        }

        .btn-add-sale::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-add-sale:hover::before {
            left: 100%;
        }

        .btn-add-sale:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea085 100%) !important;
            transform: translateY(-5px) scale(1.05) !important;
            box-shadow: 0 20px 40px rgba(40, 167, 69, 0.6) !important;
        }

        .btn-add-sale i {
            margin-right: 10px !important;
            font-size: 1.2rem !important;
            animation: pulse 2s infinite !important;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Enhanced Empty State for Better Visual Appeal */
        .empty-state {
            text-align: center;
            padding: 60px 40px !important;
            color: #6c757d;
            background: rgba(255, 255, 255, 0.95) !important;
            border-radius: 20px !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
        }

        .empty-state i {
            font-size: 4rem !important;
            color: #28a745 !important;
            margin-bottom: 20px !important;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
        }

        .empty-state h3 {
            margin-bottom: 15px !important;
            color: #495057 !important;
            font-size: 1.8rem !important;
            font-weight: 700 !important;
        }

        .empty-state p {
            font-size: 1.1rem !important;
            margin-bottom: 25px !important;
            color: #6c757d !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-boxes"></i>
                StockWise Pro
            </div>
            
            <!-- User info under StockWise Pro -->
            <?php if ($user_info): ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(255, 255, 255, 0.1); border-radius: 12px; margin: 15px 20px; border: 1px solid rgba(255, 255, 255, 0.1);">
                <?php 
                // Check for profile photo using the same logic as dashboard
                $show_photo = false;
                $photo_src = '';
                
                if (!empty($user_info['profile_photo'])) {
                    // Try the path as stored in database
                    $photo_path = "../" . $user_info['profile_photo'];
                    if (file_exists($photo_path)) {
                        $show_photo = true;
                        $photo_src = $photo_path;
                    }
                }
                
                if ($show_photo): ?>
                    <img src="<?php echo htmlspecialchars($photo_src); ?>" 
                         alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255, 255, 255, 0.2);">
                <?php else: ?>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); display: flex; align-items: center; justify-content: center; color: white; border: 2px solid rgba(255, 255, 255, 0.2); font-weight: 600; font-size: 1.2rem;">
                        <?php echo strtoupper(substr($user_info['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: left;">
                    <span style="display: block; font-weight: 600; font-size: 0.9rem; color: white; margin-bottom: 2px;"><?php echo htmlspecialchars($user_info['username']); ?></span>
                    <span style="color: rgba(255,255,255,0.8); text-transform: capitalize; font-size: 0.75rem; background: rgba(79, 195, 247, 0.2); padding: 2px 8px; border-radius: 8px;"><?php echo ucfirst($user_info['role']); ?></span>
                </div>
            </div>
            <?php endif; ?>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li class="nav-item"><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
                <li class="nav-item"><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li class="nav-item"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item active"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-cash-register"></i> Sales Management</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Process sales and manage transactions</p>
                </div>
                <div class="actions">
                    <button onclick="showCreateSaleModal()" class="btn btn-add-sale">
                        <i class="fas fa-plus"></i> New Sale
                    </button>
                    <button onclick="exportSales()" class="btn btn-export">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_sales']); ?></div>
                    <div class="stat-label">Total Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['today_sales']); ?></div>
                    <div class="stat-label">Today's Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['today_revenue'], 2); ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($stats['avg_sale_value'], 2); ?></div>
                    <div class="stat-label">Avg Sale Value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['monthly_sales']); ?></div>
                    <div class="stat-label">Monthly Sales</div>
                </div>
            </div>
            
            <!-- Sales List -->
            <div class="sales-section">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Sales History</h3>
                </div>
                
                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="filter-grid">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                        <div class="form-group">
                            <label for="payment_method">Payment Method</label>
                            <select id="payment_method" name="payment_method">
                                <option value="">All Methods</option>
                                <option value="cash" <?php echo $filter_payment == 'cash' ? 'selected' : ''; ?>>Cash</option>
                                <option value="card" <?php echo $filter_payment == 'card' ? 'selected' : ''; ?>>Card</option>
                                <option value="bank_transfer" <?php echo $filter_payment == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="mobile_payment" <?php echo $filter_payment == 'mobile_payment' ? 'selected' : ''; ?>>Mobile Payment</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="voided" <?php echo $filter_status == 'voided' ? 'selected' : ''; ?>>Voided</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-filter" style="width: 100%;">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                        <div class="form-group">
                            <a href="sales.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($sales)): ?>
                    <div class="empty-state">
                        <i class="fas fa-cash-register"></i>
                        <h3>No Sales Found</h3>
                        <p>No sales recorded yet. Create your first sale to get started.</p>
                        <button onclick="showCreateSaleModal()" class="btn btn-add-sale">
                            <i class="fas fa-plus"></i> Create First Sale
                        </button>
                    </div>
                <?php else: ?>
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Sale #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($sale['sale_number']); ?></strong></td>
                                    <td>
                                        <?php if ($sale['customer_name']): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($sale['customer_name']); ?></strong>
                                                <?php if ($sale['customer_phone']): ?>
                                                    <br><small><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <em>Walk-in Customer</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="viewSaleItems(<?php echo $sale['id']; ?>)" class="btn btn-sm btn-view">
                                            <i class="fas fa-eye"></i> View Items
                                        </button>
                                    </td>
                                    <td>$<?php echo number_format($sale['subtotal'], 2); ?></td>
                                    <td>
                                        <?php if ($sale['discount_percentage'] > 0): ?>
                                            <?php echo $sale['discount_percentage']; ?>% (-$<?php echo number_format($sale['discount_amount'], 2); ?>)
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($sale['tax_percentage'] > 0): ?>
                                            <?php echo $sale['tax_percentage']; ?>% (+$<?php echo number_format($sale['tax_amount'], 2); ?>)
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><strong>$<?php echo number_format($sale['final_amount'], 2); ?></strong></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $sale['status'] ?? 'completed'; ?>">
                                            <?php echo ucfirst($sale['status'] ?? 'Completed'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <button onclick="printReceipt(<?php echo $sale['id']; ?>)" class="btn btn-sm btn-print" title="Print Receipt">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <?php if (($sale['status'] ?? 'completed') != 'voided'): ?>
                                                <button onclick="voidSale(<?php echo $sale['id']; ?>, '<?php echo addslashes($sale['sale_number']); ?>')" class="btn btn-sm btn-danger" title="Void Sale">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Create Sale Modal -->
    <div id="createSaleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Create New Sale</h3>
                <span class="close" onclick="closeModal('createSaleModal')">&times;</span>
            </div>
            <form method="POST" action="" id="saleForm">
                <input type="hidden" name="action" value="create_sale">
                
                <!-- Customer Information -->
                <h4 style="margin-bottom: 15px; color: #667eea;"><i class="fas fa-user"></i> Customer Information</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Phone Number</label>
                        <input type="tel" id="customer_phone" name="customer_phone" placeholder="Optional">
                    </div>
                </div>
                <div class="form-group">
                    <label for="customer_email">Email Address</label>
                    <input type="email" id="customer_email" name="customer_email" placeholder="Optional">
                </div>
                
                <!-- Products Section -->
                <h4 style="margin: 25px 0 15px 0; color: #667eea;"><i class="fas fa-shopping-cart"></i> Products</h4>
                <div id="productsContainer">
                    <div class="product-row">
                        <div class="form-group">
                            <label>Product</label>
                            <select name="products[]" class="product-select" onchange="updateProductPrice(this)">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['quantity']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantities[]" min="1" value="1" onchange="calculateTotal()">
                        </div>
                        <div class="form-group">
                            <label>Unit Price</label>
                            <input type="number" step="0.01" class="unit-price" readonly>
                        </div>
                        <div class="form-group">
                            <button type="button" onclick="removeProductRow(this)" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="addProductRow()" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                
                <!-- Totals Section -->
                <h4 style="margin: 25px 0 15px 0; color: #667eea;"><i class="fas fa-calculator"></i> Calculations</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="discount_percentage">Discount (%)</label>
                        <input type="number" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.01" value="0" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label for="tax_percentage">Tax (%)</label>
                        <input type="number" id="tax_percentage" name="tax_percentage" min="0" max="100" step="0.01" value="0" onchange="calculateTotal()">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Subtotal</label>
                        <input type="text" id="subtotal_display" readonly style="font-weight: bold; background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Final Total</label>
                        <input type="text" id="total_display" readonly style="font-weight: bold; background: #e8f5e8; font-size: 1.2rem;">
                    </div>
                </div>
                
                <!-- Payment Information -->
                <h4 style="margin: 25px 0 15px 0; color: #667eea;"><i class="fas fa-credit-card"></i> Payment</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="mobile_payment">Mobile Payment</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Status</label>
                        <input type="text" id="payment_status" value="Payment method not selected" readonly style="background: #fff3cd; color: #856404;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Additional notes or comments"></textarea>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                    <button type="button" onclick="closeModal('createSaleModal')" class="btn btn-secondary">Cancel</button>
                    <button type="button" onclick="debugForm()" class="btn btn-warning">
                        <i class="fas fa-bug"></i> Debug Form
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Create Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Sale Items Modal -->
    <div id="saleItemsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-list"></i> Sale Items</h3>
                <span class="close" onclick="closeModal('saleItemsModal')">&times;</span>
            </div>
            <div id="saleItemsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        let productRowCount = 1;
        
        function showCreateSaleModal() {
            document.getElementById('createSaleModal').style.display = 'block';
            calculateTotal();
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function addProductRow() {
            productRowCount++;
            const container = document.getElementById('productsContainer');
            const newRow = document.createElement('div');
            newRow.className = 'product-row';
            newRow.innerHTML = `
                <div class="form-group">
                    <label>Product</label>
                    <select name="products[]" class="product-select" onchange="updateProductPrice(this)">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['quantity']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantities[]" min="1" value="1" onchange="calculateTotal()">
                </div>
                <div class="form-group">
                    <label>Unit Price</label>
                    <input type="number" step="0.01" class="unit-price" readonly>
                </div>
                <div class="form-group">
                    <button type="button" onclick="removeProductRow(this)" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
        }
        
        function removeProductRow(button) {
            const row = button.closest('.product-row');
            row.remove();
            calculateTotal();
        }
        
        function updateProductPrice(select) {
            const row = select.closest('.product-row');
            const priceInput = row.querySelector('.unit-price');
            const quantityInput = row.querySelector('input[name="quantities[]"]');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                const price = parseFloat(option.dataset.price);
                const stock = parseInt(option.dataset.stock);
                
                priceInput.value = price.toFixed(2);
                quantityInput.max = stock;
                
                if (parseInt(quantityInput.value) > stock) {
                    quantityInput.value = stock;
                }
            } else {
                priceInput.value = '';
                quantityInput.max = '';
            }
            
            calculateTotal();
        }
        
        function calculateTotal() {
            let subtotal = 0;
            
            // Calculate subtotal from all product rows
            document.querySelectorAll('.product-row').forEach(row => {
                const select = row.querySelector('.product-select');
                const quantity = parseFloat(row.querySelector('input[name="quantities[]"]').value) || 0;
                
                if (select.value && quantity > 0) {
                    const option = select.options[select.selectedIndex];
                    const price = parseFloat(option.dataset.price) || 0;
                    subtotal += price * quantity;
                }
            });
            
            // Apply discount
            const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
            const discountAmount = (subtotal * discountPercentage) / 100;
            const afterDiscount = subtotal - discountAmount;
            
            // Apply tax
            const taxPercentage = parseFloat(document.getElementById('tax_percentage').value) || 0;
            const taxAmount = (afterDiscount * taxPercentage) / 100;
            const finalTotal = afterDiscount + taxAmount;
            
            // Update display
            document.getElementById('subtotal_display').value = '$' + subtotal.toFixed(2);
            document.getElementById('total_display').value = '$' + finalTotal.toFixed(2);
        }
        
        function voidSale(saleId, saleNumber) {
            if (confirm(`Are you sure you want to void sale ${saleNumber}? This will restore the inventory and cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                form.innerHTML = `
                    <input type="hidden" name="action" value="void_sale">
                    <input type="hidden" name="sale_id" value="${saleId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewSaleItems(saleId) {
            // This would typically load via AJAX
            document.getElementById('saleItemsModal').style.display = 'block';
            document.getElementById('saleItemsContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                    <p>Loading sale items...</p>
                </div>
            `;
            
            // Simulate loading (in real implementation, use AJAX)
            setTimeout(() => {
                document.getElementById('saleItemsContent').innerHTML = `
                    <p>Sale items for Sale ID: ${saleId}</p>
                    <p><em>This feature would show detailed sale items via AJAX in a full implementation.</em></p>
                `;
            }, 1000);
        }
        
        function printReceipt(saleId) {
            // This would open a receipt printing window
            alert(`Printing receipt for Sale ID: ${saleId}\n\nThis feature would generate and print a receipt in a full implementation.`);
        }
        
        function exportSales() {
            alert('Export functionality would generate CSV/PDF reports of sales data.');
        }
        
        function debugForm() {
            console.log('=== FORM DEBUG ===');
            
            // Check products
            const products = document.querySelectorAll('select[name="products[]"]');
            console.log('Products found:', products.length);
            products.forEach((select, index) => {
                console.log(`Product ${index + 1}:`, {
                    value: select.value,
                    text: select.options[select.selectedIndex]?.text || 'None'
                });
            });
            
            // Check quantities
            const quantities = document.querySelectorAll('input[name="quantities[]"]');
            console.log('Quantities found:', quantities.length);
            quantities.forEach((qty, index) => {
                console.log(`Quantity ${index + 1}:`, qty.value);
            });
            
            // Check payment method
            const paymentMethod = document.getElementById('payment_method');
            console.log('Payment method:', {
                element: paymentMethod ? 'Found' : 'Not found',
                value: paymentMethod?.value || 'No value',
                text: paymentMethod?.options[paymentMethod.selectedIndex]?.text || 'No selection'
            });
            
            // Check form data
            const formData = new FormData(document.getElementById('saleForm'));
            console.log('Form data:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}:`, value);
            }
            
            alert('Check the browser console (F12) for detailed form debug information.');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Form validation
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            console.log('Form submission started...');
            
            const products = document.querySelectorAll('select[name="products[]"]');
            let hasValidProduct = false;
            
            products.forEach((select, index) => {
                console.log(`Product ${index + 1}:`, select.value);
                if (select.value && select.value !== '') {
                    hasValidProduct = true;
                }
            });
            
            console.log('Has valid product:', hasValidProduct);
            
            if (!hasValidProduct) {
                e.preventDefault();
                alert('Please select at least one product.');
                return false;
            }
            
            const paymentMethod = document.getElementById('payment_method');
            console.log('Payment method element:', paymentMethod);
            console.log('Payment method value:', paymentMethod ? paymentMethod.value : 'null');
            
            if (!paymentMethod || !paymentMethod.value || paymentMethod.value === '') {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            // Additional validation for quantities
            const quantities = document.querySelectorAll('input[name="quantities[]"]');
            let hasValidQuantity = false;
            
            quantities.forEach((qty, index) => {
                const productSelect = products[index];
                console.log(`Quantity ${index + 1}:`, qty.value);
                if (productSelect && productSelect.value && qty.value && parseInt(qty.value) > 0) {
                    hasValidQuantity = true;
                }
            });
            
            console.log('Has valid quantity:', hasValidQuantity);
            
            if (!hasValidQuantity) {
                e.preventDefault();
                alert('Please enter valid quantities for selected products.');
                return false;
            }
            
            console.log('Form validation passed, submitting...');
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            }
        });
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
            
            // Add payment method change listener
            const paymentSelect = document.getElementById('payment_method');
            const paymentStatus = document.getElementById('payment_status');
            
            if (paymentSelect && paymentStatus) {
                paymentSelect.addEventListener('change', function() {
                    if (this.value) {
                        paymentStatus.value = `${this.options[this.selectedIndex].text} selected`;
                        paymentStatus.style.background = '#d4edda';
                        paymentStatus.style.color = '#155724';
                    } else {
                        paymentStatus.value = 'Payment method not selected';
                        paymentStatus.style.background = '#fff3cd';
                        paymentStatus.style.color = '#856404';
                    }
                });
            }
        });
    </script>
</body>
</html>