<?php
/**
 * Delete Product Script
 * Inventory Management System
 */
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Check if user has admin privileges for deletion
if (!is_admin()) {
    $_SESSION['error_message'] = 'Access denied. Admin privileges required for deletion.';
    header('Location: products.php');
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: products.php');
    exit();
}

// Get product details before deletion
$stmt = $conn->prepare("SELECT name FROM products WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = 'Product not found or already deleted.';
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();
$product_name = $product['name'];

// Start transaction
$conn->begin_transaction();

try {
    // Soft delete - update status to inactive instead of actual deletion
    // This preserves data integrity and transaction history
    $delete_stmt = $conn->prepare("UPDATE products SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $delete_stmt->bind_param("i", $product_id);
    $delete_stmt->execute();
    
    // Log the deletion activity
    log_activity('Product Deleted', 'products', $product_id, "Deleted product: $product_name");
    
    $conn->commit();
    
    $_SESSION['success_message'] = "Product '$product_name' has been deleted successfully.";
    
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Failed to delete product. Please try again.';
}

header('Location: products.php');
exit();
?>