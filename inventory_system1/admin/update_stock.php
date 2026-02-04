<?php
/**
 * Update Stock Script
 * Inventory Management System
 */
require_once '../includes/auth_check.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: products.php');
        exit();
    }
    
    $product_id = (int)$_POST['product_id'];
    $action = sanitize_input($_POST['action']); // 'in' or 'out'
    $quantity = (int)$_POST['quantity'];
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    if ($product_id <= 0 || $quantity <= 0 || !in_array($action, ['in', 'out'])) {
        $_SESSION['error_message'] = 'Invalid stock update data.';
        header('Location: products.php');
        exit();
    }
    
    // Get current product info
    $stmt = $conn->prepare("SELECT name, quantity FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $_SESSION['error_message'] = 'Product not found.';
        header('Location: products.php');
        exit();
    }
    
    $product = $result->fetch_assoc();
    $previous_quantity = $product['quantity'];
    
    // Calculate new quantity
    if ($action == 'in') {
        $new_quantity = $previous_quantity + $quantity;
    } else {
        $new_quantity = $previous_quantity - $quantity;
        if ($new_quantity < 0) {
            $_SESSION['error_message'] = 'Cannot remove more stock than available. Current stock: ' . $previous_quantity;
            header('Location: products.php');
            exit();
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update product quantity
        $update_stmt = $conn->prepare("UPDATE products SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $product_id);
        $update_stmt->execute();
        
        // Log stock transaction
        $log_stmt = $conn->prepare("
            INSERT INTO stock_transactions (product_id, user_id, transaction_type, quantity, previous_quantity, new_quantity, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $log_stmt->bind_param("iisiiss", $product_id, $_SESSION['user_id'], $action, $quantity, $previous_quantity, $new_quantity, $notes);
        $log_stmt->execute();
        
        // Log activity
        $action_text = $action == 'in' ? 'Stock Added' : 'Stock Removed';
        $details = "$action_text: {$quantity} units for {$product['name']}";
        log_activity($action_text, 'products', $product_id, $details);
        
        $conn->commit();
        
        $action_word = $action == 'in' ? 'added to' : 'removed from';
        $_SESSION['success_message'] = "Successfully $action_word {$product['name']}. New stock: $new_quantity";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = 'Failed to update stock. Please try again.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header('Location: products.php');
exit();
?>