<?php
/**
 * Low Stock Report
 * Inventory Management System
 */

// Get low stock products
$query = "
    SELECT p.*, c.name as category_name, s.name as supplier_name,
           (p.min_stock_level - p.quantity) as shortage_quantity,
           CASE 
               WHEN p.quantity = 0 THEN 'Critical'
               WHEN p.quantity <= (p.min_stock_level * 0.5) THEN 'Very Low'
               ELSE 'Low'
           END as urgency_level
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.quantity <= p.min_stock_level AND p.status = 'active'
    ORDER BY (p.quantity / NULLIF(p.min_stock_level, 0)) ASC, p.name ASC
";

$result = $conn->query($query);
$low_stock_products = $result->fetch_all(MYSQLI_ASSOC);

// Calculate summary
$critical_count = count(array_filter($low_stock_products, function($p) { return $p['quantity'] == 0; }));
$very_low_count = count(array_filter($low_stock_products, function($p) { return $p['urgency_level'] == 'Very Low'; }));
$low_count = count(array_filter($low_stock_products, function($p) { return $p['urgency_level'] == 'Low'; }));
?>

<div style="padding: 25px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h2>Low Stock Alert Report</h2>
        <p style="color: #666;">Generated on <?php echo date('F j, Y g:i A'); ?></p>
    </div>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: #ffebee; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #f44336;">
            <h3 style="margin: 0; color: #c62828;"><?php echo number_format($critical_count); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Critical (Out of Stock)</p>
        </div>
        
        <div style="background: #fff3e0; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #ff9800;">
            <h3 style="margin: 0; color: #ef6c00;"><?php echo number_format($very_low_count); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Very Low Stock</p>
        </div>
        
        <div style="background: #fff8e1; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #ffc107;">
            <h3 style="margin: 0; color: #f57c00;"><?php echo number_format($low_count); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Low Stock</p>
        </div>
        
        <div style="background: #f3e5f5; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #9c27b0;">
            <h3 style="margin: 0; color: #7b1fa2;"><?php echo number_format(count($low_stock_products)); ?></h3>
            <p style="margin: 5px 0 0 0; color: #666;">Total Items</p>
        </div>
    </div>
    
    <?php if (empty($low_stock_products)): ?>
        <div style="text-align: center; padding: 40px; color: #666; background: #e8f5e8; border-radius: 8px;">
            <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 20px; color: #4caf50;"></i>
            <h3 style="color: #2e7d32;">Excellent Stock Levels!</h3>
            <p>All products are well stocked above minimum levels.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Product Name</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Category</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dee2e6;">Supplier</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Current Stock</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Min Level</th>
                    <th style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">Shortage</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #dee2e6;">Urgency</th>
                    <th style="padding: 12px; text-align: center; border: 1px solid #dee2e6;">Action Required</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $product): ?>
                    <tr style="<?php echo $product['quantity'] == 0 ? 'background: #ffebee;' : ''; ?>">
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($product['sku']); ?></small>
                        </td>
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?>
                        </td>
                        <td style="padding: 12px; border: 1px solid #dee2e6;">
                            <?php echo htmlspecialchars($product['supplier_name'] ?? 'No Supplier'); ?>
                        </td>
                        <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                            <strong style="color: <?php echo $product['quantity'] == 0 ? '#c62828' : '#ef6c00'; ?>;">
                                <?php echo number_format($product['quantity']); ?>
                            </strong>
                        </td>
                        <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                            <?php echo number_format($product['min_stock_level']); ?>
                        </td>
                        <td style="padding: 12px; text-align: right; border: 1px solid #dee2e6;">
                            <span style="color: #c62828; font-weight: bold;">
                                <?php echo number_format($product['shortage_quantity']); ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center; border: 1px solid #dee2e6;">
                            <?php
                            $urgency_colors = [
                                'Critical' => 'background: #ffcdd2; color: #c62828;',
                                'Very Low' => 'background: #ffe0b2; color: #ef6c00;',
                                'Low' => 'background: #fff9c4; color: #f57c00;'
                            ];
                            $style = $urgency_colors[$product['urgency_level']];
                            ?>
                            <span style="<?php echo $style; ?> padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                <?php echo $product['urgency_level']; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center; border: 1px solid #dee2e6;">
                            <?php if ($product['quantity'] == 0): ?>
                                <span style="background: #f44336; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                    RESTOCK IMMEDIATELY
                                </span>
                            <?php else: ?>
                                <span style="background: #ff9800; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                    ORDER SOON
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Recommendations -->
        <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
            <h4 style="margin-top: 0; color: #1976d2;"><i class="fas fa-lightbulb"></i> Recommendations</h4>
            <ul style="margin: 10px 0; padding-left: 20px; color: #666;">
                <?php if ($critical_count > 0): ?>
                    <li><strong>Immediate Action Required:</strong> <?php echo $critical_count; ?> products are completely out of stock and need immediate restocking.</li>
                <?php endif; ?>
                <?php if ($very_low_count > 0): ?>
                    <li><strong>Priority Restocking:</strong> <?php echo $very_low_count; ?> products have very low stock levels and should be reordered within 24-48 hours.</li>
                <?php endif; ?>
                <?php if ($low_count > 0): ?>
                    <li><strong>Plan Restocking:</strong> <?php echo $low_count; ?> products are below minimum levels and should be included in the next order cycle.</li>
                <?php endif; ?>
                <li><strong>Review Minimum Levels:</strong> Consider adjusting minimum stock levels based on demand patterns and lead times.</li>
                <li><strong>Supplier Communication:</strong> Contact suppliers for products with critical or very low stock levels.</li>
            </ul>
        </div>
    <?php endif; ?>
</div>