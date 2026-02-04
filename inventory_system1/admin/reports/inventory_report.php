<?php
/**
 * Current Inventory Report
 * Inventory Management System
 */

// Get current inventory data
$query = "
    SELECT p.*, c.name as category_name, s.name as supplier_name,
           (p.price * p.quantity) as total_value
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.status = 'active'
    ORDER BY p.name ASC
";

$result = $conn->query($query);
$products = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_products = count($products);
$total_quantity = array_sum(array_column($products, 'quantity'));
$total_value = array_sum(array_column($products, 'total_value'));
$low_stock_count = count(array_filter($products, function($p) { return $p['quantity'] <= $p['min_stock_level']; }));
?>

<div style="padding: 25px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2>Current Inventory Report</h2>
        <p style="color: #666;">Generated on <?php echo date('F j, Y g:i A'); ?></p>
    </div>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #e3f2fd; padding: 20px; border-radius: 8px; text-align: center;">
            <h3 style="margin: 0; color: #1976d2;"><?php echo number_format($total_products); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Total Products</p>
        </div>
        
        <div style="background: #f3e5f5; padding: 20px; border-radius: 8px; text-align: center;">
            <h3 style="margin: 0; color: #7b1fa2;"><?php echo number_format($total_quantity); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Total Quantity</p>
        </div>
        
        <div style="background: #e8f5e8; padding: 20px; border-radius: 8px; text-align: center;">
            <h3 style="margin: 0; color: #388e3c;">$<?php echo number_format($total_value, 2); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Total Value</p>
        </div>
        
        <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center;">
            <h3 style="margin: 0; color: #f57c00;"><?php echo number_format($low_stock_count); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Low Stock Items</p>
        </div>
    </div>
    
    <?php if (empty($products)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
            <h3>No Products Found</h3>
            <p>No products available in inventory.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Product Name</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">SKU</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Category</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Supplier</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Quantity</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Unit Price</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Total Value</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #dee2e6;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                        </td>
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <code><?php echo htmlspecialchars($product['sku']); ?></code>
                        </td>
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                        </td>
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <?php echo htmlspecialchars($product['supplier_name'] ?? 'No Supplier'); ?>
                        </td>
                        <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                            <?php echo number_format($product['quantity']); ?>
                        </td>
                        <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                            $<?php echo number_format($product['price'], 2); ?>
                        </td>
                        <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                            $<?php echo number_format($product['total_value'], 2); ?>
                        </td>
                        <td style="padding: 12px; text-align: center; border: 1px solid #dee2e6;">
                            <?php
                            if ($product['quantity'] == 0) {
                                echo '<span style="background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">Out of Stock</span>';
                            } elseif ($product['quantity'] <= $product['min_stock_level']) {
                                echo '<span style="background: #fff3e0; color: #ef6c00; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">Low Stock</span>';
                            } else {
                                echo '<span style="background: #e8f5e8; color: #2e7d32; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">In Stock</span>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td colspan="4" style="padding: 12px; border: 1px solid #dee2e6; text-align: right;">
                        <strong>TOTALS:</strong>
                    </td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                        <?php echo number_format($total_quantity); ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #dee2e6;"></td>
                    <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                        $<?php echo number_format($total_value, 2); ?>
                    </td>
                    <td style="padding: 12px; border: 1px solid #dee2e6;"></td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>