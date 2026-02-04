<?php
/**
 * Enhanced Orders Management System - Dashboard Style
 * Modern glass morphism design matching dashboard
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
        if ($_POST['action'] == 'create_order') {
            $customer_name = trim($_POST['customer_name']);
            $customer_email = trim($_POST['customer_email']);
            $customer_phone = trim($_POST['customer_phone']);
            $customer_address = trim($_POST['customer_address']);
            $products = $_POST['products'] ?? [];
            $quantities = $_POST['quantities'] ?? [];
            $notes = trim($_POST['notes']);
            
            if (!empty($products)) {
                try {
                    $conn->begin_transaction();
                    
                    // Calculate totals
                    $subtotal = 0;
                    $order_items = [];
                    
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
                                
                                $order_items[] = [
                                    'product_id' => $product_id,
                                    'product_name' => $product['name'],
                                    'quantity' => $quantities[$index],
                                    'unit_price' => $product['price'],
                                    'total_price' => $item_total
                                ];
                            }
                        }
                    }
                    
                    if (!empty($order_items)) {
                        // Generate order number
                        $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                        
                        // Insert order record
                        $stmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_address, subtotal, total_amount, status, notes, order_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), ?)");
                        $stmt->bind_param("sssssddsi", $order_number, $customer_name, $customer_email, $customer_phone, $customer_address, $subtotal, $subtotal, $notes, $_SESSION['user_id']);
                        $stmt->execute();
                        
                        $order_id = $conn->insert_id;
                        
                        // Insert order items
                        foreach ($order_items as $item) {
                            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("iisidd", $order_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['total_price']);
                            $stmt->execute();
                        }
                        
                        $conn->commit();
                        $success_message = "Order created successfully! Order Number: $order_number";
                    } else {
                        throw new Exception("No valid products selected");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = "Error creating order: " . $e->getMessage();
                }
            } else {
                $error_message = "Please select at least one product";
            }
        }
        
        if ($_POST['action'] == 'update_status') {
            $order_id = $_POST['order_id'];
            $new_status = $_POST['new_status'];
            
            try {
                $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("si", $new_status, $order_id);
                $stmt->execute();
                
                $success_message = "Order status updated successfully";
            } catch (Exception $e) {
                $error_message = "Error updating order status: " . $e->getMessage();
            }
        }
        
        if ($_POST['action'] == 'cancel_order') {
            $order_id = $_POST['order_id'];
            
            try {
                $stmt = $conn->prepare("UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                
                $success_message = "Order cancelled successfully";
            } catch (Exception $e) {
                $error_message = "Error cancelling order: " . $e->getMessage();
            }
        }
    }
}

// Get orders with filters
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

$where_conditions = ["1=1"];
$params = [];
$param_types = "";

if ($filter_status) {
    $where_conditions[] = "status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

if ($filter_date_from) {
    $where_conditions[] = "DATE(order_date) >= ?";
    $params[] = $filter_date_from;
    $param_types .= "s";
}

if ($filter_date_to) {
    $where_conditions[] = "DATE(order_date) <= ?";
    $params[] = $filter_date_to;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

$orders = [];
try {
    $query = "SELECT o.*, u.username as created_by_name FROM orders o LEFT JOIN users u ON o.created_by = u.id WHERE $where_clause ORDER BY o.order_date DESC LIMIT 50";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($param_types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    if ($result) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $orders = [];
}

// Get products for order creation
$products = [];
try {
    $result = $conn->query("SELECT id, name, price, quantity FROM products WHERE status = 'active' AND quantity > 0 ORDER BY name ASC");
    if ($result) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $products = [];
}

// Get comprehensive statistics
$stats = [
    'total_orders' => 0,
    'pending_orders' => 0,
    'processing_orders' => 0,
    'shipped_orders' => 0,
    'delivered_orders' => 0,
    'cancelled_orders' => 0,
    'total_value' => 0,
    'today_orders' => 0,
    'monthly_orders' => 0,
    'avg_order_value' => 0
];

try {
    // Total orders
    $result = $conn->query("SELECT COUNT(*) as total FROM orders");
    if ($result) $stats['total_orders'] = $result->fetch_assoc()['total'];
    
    // Orders by status
    $result = $conn->query("SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'");
    if ($result) $stats['pending_orders'] = $result->fetch_assoc()['pending'];
    
    $result = $conn->query("SELECT COUNT(*) as processing FROM orders WHERE status = 'processing'");
    if ($result) $stats['processing_orders'] = $result->fetch_assoc()['processing'];
    
    $result = $conn->query("SELECT COUNT(*) as shipped FROM orders WHERE status = 'shipped'");
    if ($result) $stats['shipped_orders'] = $result->fetch_assoc()['shipped'];
    
    $result = $conn->query("SELECT COUNT(*) as delivered FROM orders WHERE status = 'delivered'");
    if ($result) $stats['delivered_orders'] = $result->fetch_assoc()['delivered'];
    
    $result = $conn->query("SELECT COUNT(*) as cancelled FROM orders WHERE status = 'cancelled'");
    if ($result) $stats['cancelled_orders'] = $result->fetch_assoc()['cancelled'];
    
    // Total value
    $result = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as value FROM orders WHERE status != 'cancelled'");
    if ($result) $stats['total_value'] = $result->fetch_assoc()['value'];
    
    // Today's orders
    $result = $conn->query("SELECT COUNT(*) as today FROM orders WHERE DATE(order_date) = CURDATE()");
    if ($result) $stats['today_orders'] = $result->fetch_assoc()['today'];
    
    // Monthly orders
    $result = $conn->query("SELECT COUNT(*) as monthly FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())");
    if ($result) $stats['monthly_orders'] = $result->fetch_assoc()['monthly'];
    
    // Average order value
    if ($stats['total_orders'] > 0) {
        $stats['avg_order_value'] = $stats['total_value'] / $stats['total_orders'];
    }
    
} catch (Exception $e) {
    // Keep default values
}

$page_title = "Orders Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - StockWise Pro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-weight: bold; }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-weight: bold;
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
            display: grid; grid-template-columns: repeat(auto-fit, minmax(115px, 1fr));
            gap: 9px; margin-bottom: 17px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 12px; padding: 17px; text-align: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative; overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.0rem; color: white; margin: 0 auto 12px;
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon.bg-primary { background: linear-gradient(135deg, #1e40af 0%, #0f766e 100%); }
        .stat-icon.bg-success { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-icon.bg-danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .stat-number {
            font-size: 1.27rem; font-weight: 900; margin-bottom: 6px;
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 0.69rem; font-weight: 700; color: #6c757d;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2); overflow: hidden;
            margin-bottom: 30px;
        }
        
        .section-header {
            background: linear-gradient(135deg, #1e40af 0%, #0f766e 50%, #059669 100%);
            color: white; padding: 25px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .section-header h3 {
            font-size: 1.3rem; font-weight: 600;
            display: flex; align-items: center; gap: 12px;
        }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: 12px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none; display: inline-flex;
            align-items: center; gap: 8px;
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
        
        .filter-section {
            padding: 25px 30px;
            background: rgba(248, 249, 250, 0.8);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .filter-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; align-items: end;
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
        
        .orders-table { width: 100%; border-collapse: collapse; }
        
        .orders-table th, .orders-table td {
            padding: 18px 20px; text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .orders-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600; color: #495057; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .orders-table tbody tr:hover {
            background: linear-gradient(135deg, rgba(30, 64, 175, 0.05) 0%, rgba(15, 118, 110, 0.05) 100%);
        }
        
        .status-badge {
            padding: 6px 12px; border-radius: 20px; font-size: 0.8rem;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }
        
        .status-processing {
            background: linear-gradient(135deg, #cce5ff 0%, #b3d9ff 100%);
            color: #004085;
        }
        
        .status-shipped {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
        }
        
        .status-delivered {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
        }
        
        .status-cancelled {
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
        
        /* Enhanced Create First Order Button */
        .btn-add-order {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            color: white !important;
            padding: 16px 32px !important;
            border-radius: 50px !important;
            font-size: 1.1rem !important;
            font-weight: 700 !important;
            border: none !important;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4) !important;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
            position: relative !important;
            overflow: hidden !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            margin-top: 20px !important;
        }

        .btn-add-order::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-add-order:hover::before {
            left: 100%;
        }

        .btn-add-order:hover {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%) !important;
            transform: translateY(-5px) scale(1.05) !important;
            box-shadow: 0 20px 40px rgba(0, 123, 255, 0.6) !important;
        }

        .btn-add-order i {
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
            color: #007bff !important;
            margin-bottom: 20px !important;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
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
                <i class="fas fa-boxes"></i> StockWise Pro
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
                <li class="nav-item active"><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li class="nav-item"><a href="sales.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li class="nav-item"><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="nav-item"><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="nav-item"><a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
                <li class="nav-item"><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <div class="main-content">

            <div class="page-header">
                <div>
                    <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
                    <p style="color: #6c757d; margin: 10px 0 0 0; font-size: 1.1rem;">Manage customer orders and fulfillment</p>
                </div>
                <div class="actions">
                    <button onclick="showCreateOrderModal()" class="btn btn-add-order">
                        <i class="fas fa-plus"></i> New Order
                    </button>
                    <button onclick="exportOrders()" class="btn btn-export">
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
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['total_orders']); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['pending_orders']); ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['processing_orders']); ?></div>
                    <div class="stat-label">Processing</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['shipped_orders']); ?></div>
                    <div class="stat-label">Shipped</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['delivered_orders']); ?></div>
                    <div class="stat-label">Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['total_value'], 2); ?></div>
                    <div class="stat-label">Total Value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($stats['today_orders']); ?></div>
                    <div class="stat-label">Today's Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number">$<?php echo number_format($stats['avg_order_value'], 2); ?></div>
                    <div class="stat-label">Avg Order Value</div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-list"></i> Orders History</h3>
                </div>
    
    <!-- Filters -->
    <div class="filter-section">
        <form method="GET" class="filter-grid">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $filter_status == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="shipped" <?php echo $filter_status == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="delivered" <?php echo $filter_status == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            <div class="form-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-filter" style="width: 100%;">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            <div class="form-group">
                <a href="orders.php" class="btn btn-secondary" style="width: 100%; text-align: center;">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="fas fa-shopping-cart"></i>
            <h3>No Orders Found</h3>
            <p>No orders placed yet. Create your first order to get started.</p>
            <button onclick="showCreateOrderModal()" class="btn btn-add-order">
                <i class="fas fa-plus"></i> Create First Order
            </button>
        </div>
    <?php else: ?>
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                        <td>
                            <div>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                <?php if ($order['customer_email']): ?>
                                    <br><small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                <?php endif; ?>
                                <?php if ($order['customer_phone']): ?>
                                    <br><small><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <button onclick="viewOrderItems(<?php echo $order['id']; ?>)" class="btn btn-sm btn-view">
                                <i class="fas fa-eye"></i> View Items
                            </button>
                        </td>
                        <td><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button onclick="updateOrderStatus(<?php echo $order['id']; ?>, '<?php echo addslashes($order['order_number']); ?>', '<?php echo $order['status']; ?>')" class="btn btn-sm btn-primary" title="Update Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="printOrderDetails(<?php echo $order['id']; ?>)" class="btn btn-sm btn-warning" title="Print Order">
                                    <i class="fas fa-print"></i>
                                </button>
                                <?php if ($order['status'] != 'cancelled' && $order['status'] != 'delivered'): ?>
                                    <button onclick="cancelOrder(<?php echo $order['id']; ?>, '<?php echo addslashes($order['order_number']); ?>')" class="btn btn-sm btn-danger" title="Cancel Order">
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

<!-- Create Order Modal -->
<div id="createOrderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Create New Order</h3>
            <span class="close" onclick="closeModal('createOrderModal')">&times;</span>
        </div>
        <form method="POST" action="" id="orderForm">
            <input type="hidden" name="action" value="create_order">
            
            <!-- Customer Information -->
            <h4 style="margin-bottom: 15px; color: #667eea;"><i class="fas fa-user"></i> Customer Information</h4>
            <div class="form-row">
                <div class="form-group">
                    <label for="customer_name">Customer Name *</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label for="customer_email">Email Address</label>
                    <input type="email" id="customer_email" name="customer_email">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="customer_phone">Phone Number</label>
                    <input type="tel" id="customer_phone" name="customer_phone">
                </div>
                <div class="form-group">
                    <label for="customer_address">Address</label>
                    <input type="text" id="customer_address" name="customer_address">
                </div>
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
            
            <!-- Order Total -->
            <h4 style="margin: 25px 0 15px 0; color: #667eea;"><i class="fas fa-calculator"></i> Order Total</h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Total Amount</label>
                    <input type="text" id="total_display" readonly style="font-weight: bold; background: #e8f5e8; font-size: 1.2rem;">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Additional notes or comments"></textarea>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="closeModal('createOrderModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Create Order
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateStatusModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Update Order Status</h3>
            <span class="close" onclick="closeModal('updateStatusModal')">&times;</span>
        </div>
        <form method="POST" action="" id="statusForm">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" id="status_order_id">
            
            <div class="form-group">
                <label>Order Number</label>
                <input type="text" id="status_order_number" readonly style="background: #f8f9fa;">
            </div>
            
            <div class="form-group">
                <label>Current Status</label>
                <input type="text" id="current_status" readonly style="background: #f8f9fa;">
            </div>
            
            <div class="form-group">
                <label for="new_status">New Status</label>
                <select id="new_status" name="new_status" required>
                    <option value="">Select Status</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" onclick="closeModal('updateStatusModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Order Items Modal -->
<div id="orderItemsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-list"></i> Order Items</h3>
            <span class="close" onclick="closeModal('orderItemsModal')">&times;</span>
        </div>
        <div id="orderItemsContent">
            <!-- Content will be loaded via AJAX -->
        </div>
    </div>
</div>

<script>
    let productRowCount = 1;
    
    function showCreateOrderModal() {
        document.getElementById('createOrderModal').style.display = 'block';
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
        let total = 0;
        
        document.querySelectorAll('.product-row').forEach(row => {
            const select = row.querySelector('.product-select');
            const quantity = parseFloat(row.querySelector('input[name="quantities[]"]').value) || 0;
            
            if (select.value && quantity > 0) {
                const option = select.options[select.selectedIndex];
                const price = parseFloat(option.dataset.price) || 0;
                total += price * quantity;
            }
        });
        
        document.getElementById('total_display').value = '$' + total.toFixed(2);
    }
    
    function updateOrderStatus(orderId, orderNumber, currentStatus) {
        document.getElementById('status_order_id').value = orderId;
        document.getElementById('status_order_number').value = orderNumber;
        document.getElementById('current_status').value = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
        document.getElementById('new_status').value = '';
        document.getElementById('updateStatusModal').style.display = 'block';
    }
    
    function cancelOrder(orderId, orderNumber) {
        if (confirm(`Are you sure you want to cancel order ${orderNumber}? This action cannot be undone.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            form.innerHTML = `
                <input type="hidden" name="action" value="cancel_order">
                <input type="hidden" name="order_id" value="${orderId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function viewOrderItems(orderId) {
        document.getElementById('orderItemsModal').style.display = 'block';
        document.getElementById('orderItemsContent').innerHTML = `
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                <p>Loading order items...</p>
            </div>
        `;
        
        // Simulate loading (in real implementation, use AJAX)
        setTimeout(() => {
            document.getElementById('orderItemsContent').innerHTML = `
                <p>Order items for Order ID: ${orderId}</p>
                <p><em>This feature would show detailed order items via AJAX in a full implementation.</em></p>
            `;
        }, 1000);
    }
    
    function printOrderDetails(orderId) {
        alert(`Printing order details for Order ID: ${orderId}\n\nThis feature would generate and print order details in a full implementation.`);
    }
    
    function exportOrders() {
        alert('Export functionality would generate CSV/PDF reports of orders data.');
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
    document.getElementById('orderForm').addEventListener('submit', function(e) {
        const products = document.querySelectorAll('select[name="products[]"]');
        let hasValidProduct = false;
        
        products.forEach(select => {
            if (select.value && select.value !== '') {
                hasValidProduct = true;
            }
        });
        
        if (!hasValidProduct) {
            e.preventDefault();
            alert('Please select at least one product.');
            return false;
        }
        
        const customerName = document.getElementById('customer_name').value.trim();
        if (!customerName) {
            e.preventDefault();
            alert('Please enter customer name.');
            return false;
        }
        
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
        console.log('Orders management page loaded successfully');
    });
</script>

</body>
</html>