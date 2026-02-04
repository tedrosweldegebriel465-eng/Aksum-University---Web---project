<?php
/**
 * Supplier Summary Report
 * Inventory Management System
 */

// Get supplier summary data
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
        AVG(p.price) as avg_price,
        MIN(p.created_at) as first_product_date,
        MAX(p.updated_at) as last_update
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.status = 'active'
    GROUP BY p.supplier_id, s.name, s.contact_person, s.email, s.phone
    ORDER BY total_value DESC
";

$result = $conn->query($query);
$suppliers = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_suppliers = count($suppliers);
$total_products = 0;
$total_quantity = 0;
$total_value = 0;
$total_low_stock = 0;

foreach ($suppliers as $supplier) {
    $total_products += $supplier['product_count'];
    $total_quantity += $supplier['total_quantity'];
    $total_value += $supplier['total_value'];
    $total_low_stock += $supplier['low_stock_count'];
}
?>

<div style="padding: 20px;">
    <h4 style="margin-bottom: 20px; color: #333;">Supplier Summary Report</h4>
    <p style="color: #666; margin-bottom: 20px;">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo $total_suppliers; ?></h3>
            <p style="margin: 5px 0 0 0;">Active Suppliers</p>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($total_products); ?></h3>
            <p style="margin: 5px 0 0 0;">Total Products</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($total_quantity); ?></h3>
            <p style="margin: 5px 0 0 0;">Total Items</p>
        </div>
        <div style="background: linear-gradient(135deg, #43e97b, #38f9d7); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;">$<?php echo number_format($total_value, 2); ?></h3>
            <p style="margin: 5px 0 0 0;">Total Value</p>
        </div>
    </div>
    
    <?php if (empty($suppliers)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-truck" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
            <h3>No Suppliers Found</h3>
            <p>No suppliers with active products are available.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Supplier Details</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Products</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Total Quantity</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Average Price</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Total Value</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Low Stock</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Last Update</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 15px;">
                            <div>
                                <strong style="color: #333;"><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                <?php if ($supplier['contact_person']): ?>
                                    <br><small style="color: #666;">Contact: <?php echo htmlspecialchars($supplier['contact_person']); ?></small>
                                <?php endif; ?>
                                <?php if ($supplier['email']): ?>
                                    <br><small style="color: #666;">Email: <?php echo htmlspecialchars($supplier['email']); ?></small>
                                <?php endif; ?>
                                <?php if ($supplier['phone']): ?>
                                    <br><small style="color: #666;">Phone: <?php echo htmlspecialchars($supplier['phone']); ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php echo number_format($supplier['product_count']); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php echo number_format($supplier['total_quantity']); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            $<?php echo number_format($supplier['avg_price'], 2); ?>
                        </td>
                        <td style="padding: 15px; text-align: center; font-weight: 500;">
                            $<?php echo number_format($supplier['total_value'], 2); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php if ($supplier['low_stock_count'] > 0): ?>
                                <span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                                    <?php echo $supplier['low_stock_count']; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #28a745;">0</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <small style="color: #666;">
                                <?php echo $supplier['last_update'] ? date('M j, Y', strtotime($supplier['last_update'])) : 'N/A'; ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td style="padding: 15px;">TOTAL (<?php echo $total_suppliers; ?> suppliers)</td>
                    <td style="padding: 15px; text-align: center;"><?php echo number_format($total_products); ?></td>
                    <td style="padding: 15px; text-align: center;"><?php echo number_format($total_quantity); ?></td>
                    <td style="padding: 15px; text-align: center;">-</td>
                    <td style="padding: 15px; text-align: center;">$<?php echo number_format($total_value, 2); ?></td>
                    <td style="padding: 15px; text-align: center;"><?php echo number_format($total_low_stock); ?></td>
                    <td style="padding: 15px; text-align: center;">-</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>