<?php
/**
 * View Order Details Page
 * Inventory Management System
 */
$page_title = 'Order Details';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$order_id = (int)($_GET['id'] ?? 0);

if (!$order_id) {
    header("Location: orders.php");
    exit();
}

// Get order details
$order_stmt = $conn->prepare("
    SELECT o.*, u.username as created_by_name
    FROM orders o
    LEFT JOIN users u ON o.created_by = u.id
    WHERE o.id = ?
");
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows == 0) {
    header("Location: orders.php");
    exit();
}

$order = $order_result->fetch_assoc();

// Get order items
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.sku, c.name as category_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE oi.order_id = ?
    ORDER BY p.name
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

require_once '../includes/unified_header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-eye"></i> Order Details - <?php echo htmlspecialchars($order['order_number']); ?></h3>
        <div class="table-actions">
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <a href="edit_order.php?id=<?php echo $order['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Order
            </a>
            <button onclick="printOrder()" class="btn btn-info">
                <i class="fas fa-print"></i> Print Order
            </button>
        </div>
    </div>
    
    <div style="padding: 30px;">
        <!-- Order Information -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Customer Information -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                    <i class="fas fa-user"></i> Customer Information
                </h4>
                
                <div style="margin-bottom: 15px;">
                    <strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?>
                </div>
                
                <?php if ($order['customer_email']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($order['customer_phone']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($order['customer_address']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Address:</strong><br>
                    <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Information -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                    <i class="fas fa-info-circle"></i> Order Information
                </h4>
                
                <div style="margin-bottom: 15px;">
                    <strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Order Date:</strong> <?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $order['status']; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Created By:</strong> <?php echo htmlspecialchars($order['created_by_name'] ?? 'System'); ?>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Total Amount:</strong> 
                    <span style="font-size: 1.2rem; color: #28a745; font-weight: bold;">
                        $<?php echo number_format($order['total_amount'], 2); ?>
                    </span>
                </div>
                
                <?php if ($order['updated_at'] != $order['order_date']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($order['updated_at'])); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-shopping-cart"></i> Order Items
            </h4>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        while ($item = $items_result->fetch_assoc()): 
                            $grand_total += $item['total_price'];
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'No Category'); ?></td>
                                <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                                <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><strong>$<?php echo number_format($item['total_price'], 2); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #e9ecef; font-weight: bold;">
                            <td colspan="5" style="text-align: right; padding: 15px;">Grand Total:</td>
                            <td style="font-size: 1.2rem; color: #28a745;">$<?php echo number_format($grand_total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Order Notes -->
        <?php if ($order['notes']): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h4 style="margin-bottom: 15px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-sticky-note"></i> Order Notes
            </h4>
            <p style="margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function printOrder() {
    window.print();
}

// Print styles
const printStyles = `
    <style media="print">
        .sidebar, .top-header, .table-actions, .btn { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .table-container { box-shadow: none !important; border: 1px solid #000 !important; }
        body { background: white !important; }
        .table-header { background: #f8f9fa !important; color: #000 !important; }
    </style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>

<?php require_once '../includes/unified_footer.php'; ?>