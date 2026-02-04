<?php
/**
 * Stock Movements Report
 * Inventory Management System
 */

// Get stock movements data for the specified date range
$query = "
    SELECT 
        st.created_at,
        p.name as product_name,
        p.sku,
        c.name as category_name,
        st.transaction_type,
        st.quantity,
        st.previous_quantity,
        st.new_quantity,
        u.username,
        st.notes
    FROM stock_transactions st
    JOIN products p ON st.product_id = p.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE DATE(st.created_at) BETWEEN ? AND ?
    ORDER BY st.created_at DESC
    LIMIT 500
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$movements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate summary statistics
$total_movements = count($movements);
$stock_in_count = 0;
$stock_out_count = 0;
$adjustment_count = 0;
$total_in_quantity = 0;
$total_out_quantity = 0;

foreach ($movements as $movement) {
    switch ($movement['transaction_type']) {
        case 'in':
            $stock_in_count++;
            $total_in_quantity += $movement['quantity'];
            break;
        case 'out':
            $stock_out_count++;
            $total_out_quantity += $movement['quantity'];
            break;
        case 'adjustment':
            $adjustment_count++;
            break;
    }
}

$net_movement = $total_in_quantity - $total_out_quantity;
?>

<div style="padding: 20px;">
    <h4 style="margin-bottom: 20px; color: #333;">Stock Movements Report</h4>
    <p style="color: #666; margin-bottom: 20px;">
        Period: <?php echo date('F j, Y', strtotime($start_date)); ?> to <?php echo date('F j, Y', strtotime($end_date)); ?>
        <br>Generated on <?php echo date('F j, Y \a\t g:i A'); ?>
    </p>
    
    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($total_movements); ?></h3>
            <p style="margin: 5px 0 0 0;">Total Movements</p>
        </div>
        <div style="background: linear-gradient(135deg, #43e97b, #38f9d7); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($stock_in_count); ?></h3>
            <p style="margin: 5px 0 0 0;">Stock In</p>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb, #f5576c); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($stock_out_count); ?></h3>
            <p style="margin: 5px 0 0 0;">Stock Out</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 2rem;"><?php echo number_format($adjustment_count); ?></h3>
            <p style="margin: 5px 0 0 0;">Adjustments</p>
        </div>
        <div style="background: linear-gradient(135deg, #fa709a, #fee140); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 1.5rem;">+<?php echo number_format($total_in_quantity); ?></h3>
            <p style="margin: 5px 0 0 0;">Total In Quantity</p>
        </div>
        <div style="background: linear-gradient(135deg, #ff9a9e, #fecfef); color: white; padding: 20px; border-radius: 10px; text-align: center;">
            <h3 style="margin: 0; font-size: 1.5rem;">-<?php echo number_format($total_out_quantity); ?></h3>
            <p style="margin: 5px 0 0 0;">Total Out Quantity</p>
        </div>
    </div>
    
    <!-- Net Movement Summary -->
    <div style="background: <?php echo $net_movement >= 0 ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $net_movement >= 0 ? '#155724' : '#721c24'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
        <h4 style="margin: 0;">
            Net Movement: <?php echo $net_movement >= 0 ? '+' : ''; ?><?php echo number_format($net_movement); ?> items
        </h4>
        <p style="margin: 5px 0 0 0;">
            <?php echo $net_movement > 0 ? 'Inventory increased' : ($net_movement < 0 ? 'Inventory decreased' : 'No net change'); ?>
        </p>
    </div>
    
    <?php if (empty($movements)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <i class="fas fa-exchange-alt" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
            <h3>No Stock Movements Found</h3>
            <p>No stock movements occurred during the selected period.</p>
        </div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Date & Time</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Product</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Type</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Quantity</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Stock Change</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">User</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movements as $movement): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;">
                            <strong><?php echo date('M j, Y', strtotime($movement['created_at'])); ?></strong>
                            <br><small style="color: #666;"><?php echo date('g:i A', strtotime($movement['created_at'])); ?></small>
                        </td>
                        <td style="padding: 12px;">
                            <strong><?php echo htmlspecialchars($movement['product_name']); ?></strong>
                            <br><small style="color: #666;">
                                <?php echo htmlspecialchars($movement['sku']); ?>
                                <?php if ($movement['category_name']): ?>
                                    | <?php echo htmlspecialchars($movement['category_name']); ?>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php
                            $type_colors = [
                                'in' => 'background: #d4edda; color: #155724;',
                                'out' => 'background: #f8d7da; color: #721c24;',
                                'adjustment' => 'background: #d1ecf1; color: #0c5460;'
                            ];
                            $type_icons = [
                                'in' => 'fas fa-plus',
                                'out' => 'fas fa-minus',
                                'adjustment' => 'fas fa-edit'
                            ];
                            $type_labels = [
                                'in' => 'Stock In',
                                'out' => 'Stock Out',
                                'adjustment' => 'Adjustment'
                            ];
                            ?>
                            <span style="<?php echo $type_colors[$movement['transaction_type']]; ?> padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                <i class="<?php echo $type_icons[$movement['transaction_type']]; ?>"></i>
                                <?php echo $type_labels[$movement['transaction_type']]; ?>
                            </span>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <strong style="color: <?php echo $movement['transaction_type'] == 'in' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $movement['transaction_type'] == 'in' ? '+' : '-'; ?><?php echo number_format($movement['quantity']); ?>
                            </strong>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <small style="color: #666;">
                                <?php echo number_format($movement['previous_quantity']); ?> → 
                                <strong><?php echo number_format($movement['new_quantity']); ?></strong>
                            </small>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php echo htmlspecialchars($movement['username']); ?>
                        </td>
                        <td style="padding: 12px;">
                            <?php if ($movement['notes']): ?>
                                <small><?php echo htmlspecialchars($movement['notes']); ?></small>
                            <?php else: ?>
                                <small style="color: #999;">No notes</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (count($movements) >= 500): ?>
            <div style="padding: 15px; text-align: center; background: #fff3cd; color: #856404; margin-top: 20px; border-radius: 8px;">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> Showing the most recent 500 movements. Use a smaller date range for complete results.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>