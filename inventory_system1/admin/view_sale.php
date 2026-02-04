<?php
/**
 * View Sale Details Page
 * Inventory Management System
 */
$page_title = 'Sale Details';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$sale_id = (int)($_GET['id'] ?? 0);

if (!$sale_id) {
    header("Location: sales.php");
    exit();
}

// Get sale details
$sale_stmt = $conn->prepare("
    SELECT s.*, u.username as sold_by_name
    FROM sales s
    LEFT JOIN users u ON s.sold_by = u.id
    WHERE s.id = ?
");
$sale_stmt->bind_param("i", $sale_id);
$sale_stmt->execute();
$sale_result = $sale_stmt->get_result();

if ($sale_result->num_rows == 0) {
    header("Location: sales.php");
    exit();
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items_stmt = $conn->prepare("
    SELECT si.*, p.name as product_name, p.sku, c.name as category_name
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE si.sale_id = ?
    ORDER BY p.name
");
$items_stmt->bind_param("i", $sale_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

require_once '../includes/unified_header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-receipt"></i> Sale Receipt - <?php echo htmlspecialchars($sale['sale_number']); ?></h3>
        <div class="table-actions">
            <a href="sales.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sales
            </a>
            <a href="edit_sale.php?id=<?php echo $sale['id']; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit Sale
            </a>
            <button onclick="printReceipt()" class="btn btn-info">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
    
    <div style="padding: 30px;">
        <!-- Sale Information -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
            <!-- Customer Information -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                    <i class="fas fa-user"></i> Customer Information
                </h4>
                
                <?php if ($sale['customer_name']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Name:</strong> <?php echo htmlspecialchars($sale['customer_name']); ?>
                </div>
                <?php else: ?>
                <div style="margin-bottom: 15px; color: #666; font-style: italic;">
                    Walk-in Customer
                </div>
                <?php endif; ?>
                
                <?php if ($sale['customer_email']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Email:</strong> <?php echo htmlspecialchars($sale['customer_email']); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($sale['customer_phone']): ?>
                <div style="margin-bottom: 15px;">
                    <strong>Phone:</strong> <?php echo htmlspecialchars($sale['customer_phone']); ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sale Information -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
                <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                    <i class="fas fa-info-circle"></i> Sale Information
                </h4>
                
                <div style="margin-bottom: 15px;">
                    <strong>Sale Number:</strong> <?php echo htmlspecialchars($sale['sale_number']); ?>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Sale Date:</strong> <?php echo date('M j, Y g:i A', strtotime($sale['sale_date'])); ?>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Payment Method:</strong> 
                    <span class="payment-badge payment-<?php echo $sale['payment_method']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>
                    </span>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <strong>Sold By:</strong> <?php echo htmlspecialchars($sale['sold_by_name'] ?? 'System'); ?>
                </div>
            </div>
        </div>
        
        <!-- Sale Items -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-shopping-cart"></i> Items Sold
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
                        <?php while ($item = $items_result->fetch_assoc()): ?>
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
                </table>
            </div>
        </div>
        
        <!-- Payment Summary -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-calculator"></i> Payment Summary
            </h4>
            
            <div style="max-width: 400px; margin-left: auto;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px 0; border-bottom: 1px solid #dee2e6;">
                    <span><strong>Subtotal:</strong></span>
                    <span>$<?php echo number_format($sale['total_amount'], 2); ?></span>
                </div>
                
                <?php if ($sale['discount'] > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px 0; border-bottom: 1px solid #dee2e6; color: #dc3545;">
                    <span><strong>Discount:</strong></span>
                    <span>-$<?php echo number_format($sale['discount'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($sale['tax'] > 0): ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px 0; border-bottom: 1px solid #dee2e6; color: #28a745;">
                    <span><strong>Tax:</strong></span>
                    <span>+$<?php echo number_format($sale['tax'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; padding: 15px 0; border-top: 2px solid #333; font-size: 1.3rem; font-weight: bold; color: #333;">
                    <span>Total Paid:</span>
                    <span>$<?php echo number_format($sale['final_amount'], 2); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Sale Notes -->
        <?php if ($sale['notes']): ?>
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px;">
            <h4 style="margin-bottom: 15px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-sticky-note"></i> Sale Notes
            </h4>
            <p style="margin: 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($sale['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function printReceipt() {
    window.print();
}

// Print styles for receipt
const printStyles = `
    <style media="print">
        .sidebar, .top-header, .table-actions, .btn { display: none !important; }
        .main-content { margin-left: 0 !important; }
        .table-container { box-shadow: none !important; border: 1px solid #000 !important; }
        body { background: white !important; }
        .table-header { background: #f8f9fa !important; color: #000 !important; }
        .table-header h3 { text-align: center; }
        
        /* Receipt-specific styles */
        @page { margin: 0.5in; }
        .content { font-size: 12px; }
        h4 { font-size: 14px; }
        .payment-badge { background: #f0f0f0 !important; color: #000 !important; }
    </style>
`;
document.head.insertAdjacentHTML('beforeend', printStyles);
</script>

<?php require_once '../includes/unified_footer.php'; ?>