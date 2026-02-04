<?php
/**
 * Category Summary Report
 * Inventory Management System
 */

// Get category summary data
$query = "
    SELECT 
        COALESCE(c.name, 'No Category') as category_name,
        COUNT(p.id) as product_count,
        SUM(p.quantity) as total_quantity,
        SUM(p.price * p.quantity) as total_value,
        SUM(CASE WHEN p.quantity <= p.min_stock_level THEN 1 ELSE 0 END) as low_stock_count,
        AVG(p.price) as avg_price
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.status = 'active'
    GROUP BY p.category_id, c.name
    ORDER BY total_value DESC
";

$result = $conn->query($query);
$categories = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_products = 0;
$total_quantity = 0;
$total_value = 0;
$total_low_stock = 0;

foreach ($categories as $category) {
    $total_products += $category['product_count'];
    $total_quantity += $category['total_quantity'];
    $total_value += $category['total_value'];
    $total_low_stock += $category['low_stock_count'];
}
?>

<div style="padding: 20px;">
    <h4 style="margin-bottom: 20px; color: #333;">Category Summary Report</h4>
    <p style="color: #666; margin-bottom: 20px;">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo count($categories); ?></h3>
            <p style="margin: 5px 0 0 0;">Total Categories</p>
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
    
    <?php if (empty($categories)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-tags" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
            <h3>No Categories Found</h3>
            <p>No product categories are available.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 15px; text-align: left; border-bottom: 2px solid #dee2e6;">Category</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Products</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Total Quantity</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Average Price</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Total Value</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">Low Stock Items</th>
                    <th style="padding: 15px; text-align: center; border-bottom: 2px solid #dee2e6;">% of Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 15px; font-weight: 500;">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php echo number_format($category['product_count']); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php echo number_format($category['total_quantity']); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            $<?php echo number_format($category['avg_price'], 2); ?>
                        </td>
                        <td style="padding: 15px; text-align: center; font-weight: 500;">
                            $<?php echo number_format($category['total_value'], 2); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php if ($category['low_stock_count'] > 0): ?>
                                <span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem;">
                                    <?php echo $category['low_stock_count']; ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #28a745;">0</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php 
                            $percentage = $total_value > 0 ? ($category['total_value'] / $total_value) * 100 : 0;
                            echo number_format($percentage, 1) . '%';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa; font-weight: bold;">
                    <td style="padding: 15px;">TOTAL</td>
                    <td style="padding: 15px; text-align: center;"><?php echo number_format($total_products); ?></td>
                    <td style="padding: 15px; text-align: center;"><?php echo number_format($total_quantity); ?></td>
                    <td style="padding: 15px; text-align: center;">-</td>
                    <td style="padding: 15px; text-align: center;">$<?php echo number_format($total_value, 2); ?></td>
                    <td style="padding: 15px; text-align: center;"><?php echo number_format($total_low_stock); ?></td>
                    <td style="padding: 15px; text-align: center;">100%</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>