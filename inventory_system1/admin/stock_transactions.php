<?php
/**
 * Stock Transactions History Page
 * Inventory Management System
 */
$page_title = 'Stock Transaction History';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Get filters
$product_filter = $_GET['product_id'] ?? '';
$transaction_type = $_GET['transaction_type'] ?? '';
$user_filter = $_GET['user_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Build query
$where_conditions = ["1=1"];
$params = [];
$param_types = '';

if ($product_filter) {
    $where_conditions[] = "st.product_id = ?";
    $params[] = $product_filter;
    $param_types .= 'i';
}

if ($transaction_type) {
    $where_conditions[] = "st.transaction_type = ?";
    $params[] = $transaction_type;
    $param_types .= 's';
}

if ($user_filter) {
    $where_conditions[] = "st.user_id = ?";
    $params[] = $user_filter;
    $param_types .= 'i';
}

if ($start_date) {
    $where_conditions[] = "DATE(st.created_at) >= ?";
    $params[] = $start_date;
    $param_types .= 's';
}

if ($end_date) {
    $where_conditions[] = "DATE(st.created_at) <= ?";
    $params[] = $end_date;
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

// Get transactions
$query = "
    SELECT st.*, p.name as product_name, p.sku, u.username,
           c.name as category_name
    FROM stock_transactions st
    JOIN products p ON st.product_id = p.id
    JOIN users u ON st.user_id = u.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE $where_clause
    ORDER BY st.created_at DESC
    LIMIT 100
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get products for filter
$products = $conn->query("SELECT id, name FROM products WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get users for filter
$users = $conn->query("SELECT id, username FROM users WHERE status = 'active' ORDER BY username")->fetch_all(MYSQLI_ASSOC);

// Calculate summary
$total_in = 0;
$total_out = 0;
foreach ($transactions as $transaction) {
    if ($transaction['transaction_type'] == 'in') {
        $total_in += $transaction['quantity'];
    } else {
        $total_out += $transaction['quantity'];
    }
}

require_once '../includes/unified_header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-exchange-alt"></i> Stock Transaction History</h3>
        <div class="table-actions">
            <button onclick="exportData('stock_transactions')" class="btn btn-success">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>
    
    <!-- Filters -->
    <div style="padding: 20px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Product:</label>
                <select onchange="updateFilter('product_id', this.value)" style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">All Products</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo $product_filter == $product['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">Transaction Type:</label>
                <select onchange="updateFilter('transaction_type', this.value)" style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">All Types</option>
                    <option value="in" <?php echo $transaction_type == 'in' ? 'selected' : ''; ?>>Stock In</option>
                    <option value="out" <?php echo $transaction_type == 'out' ? 'selected' : ''; ?>>Stock Out</option>
                    <option value="adjustment" <?php echo $transaction_type == 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">User:</label>
                <select onchange="updateFilter('user_id', this.value)" style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="">All Users</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">From Date:</label>
                <input type="date" value="<?php echo $start_date; ?>" onchange="updateFilter('start_date', this.value)" 
                       style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 500;">To Date:</label>
                <input type="date" value="<?php echo $end_date; ?>" onchange="updateFilter('end_date', this.value)" 
                       style="width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            
            <div>
                <button onclick="clearFilters()" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-times"></i> Clear Filters
                </button>
            </div>
        </div>
    </div>
    
    <!-- Summary -->
    <?php if (!empty($transactions)): ?>
    <div style="padding: 20px; background: #fff; border-bottom: 1px solid #dee2e6;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px;">
            <div style="text-align: center;">
                <h4 style="margin: 0; color: #28a745;"><?php echo number_format($total_in); ?></h4>
                <p style="margin: 5px 0 0 0; color: #666;">Total Stock In</p>
            </div>
            <div style="text-align: center;">
                <h4 style="margin: 0; color: #dc3545;"><?php echo number_format($total_out); ?></h4>
                <p style="margin: 5px 0 0 0; color: #666;">Total Stock Out</p>
            </div>
            <div style="text-align: center;">
                <h4 style="margin: 0; color: #17a2b8;"><?php echo number_format($total_in - $total_out); ?></h4>
                <p style="margin: 5px 0 0 0; color: #666;">Net Movement</p>
            </div>
            <div style="text-align: center;">
                <h4 style="margin: 0; color: #6c757d;"><?php echo number_format(count($transactions)); ?></h4>
                <p style="margin: 5px 0 0 0; color: #666;">Total Transactions</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (empty($transactions)): ?>
        <div style="padding: 40px; text-align: center; color: #666;">
            <i class="fas fa-exchange-alt" style="font-size: 3rem; margin-bottom: 20px; color: #ddd;"></i>
            <h3>No Transactions Found</h3>
            <p>No stock transactions match your current filters.</p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Product</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Stock Change</th>
                    <th>User</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td>
                            <strong><?php echo date('M j, Y', strtotime($transaction['created_at'])); ?></strong>
                            <br><small style="color: #666;"><?php echo date('g:i A', strtotime($transaction['created_at'])); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($transaction['product_name']); ?></strong>
                            <br><small style="color: #666;">
                                <?php echo htmlspecialchars($transaction['sku']); ?>
                                <?php if ($transaction['category_name']): ?>
                                    | <?php echo htmlspecialchars($transaction['category_name']); ?>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td>
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
                            <span style="<?php echo $type_colors[$transaction['transaction_type']]; ?> padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold;">
                                <i class="<?php echo $type_icons[$transaction['transaction_type']]; ?>"></i>
                                <?php echo $type_labels[$transaction['transaction_type']]; ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color: <?php echo $transaction['transaction_type'] == 'in' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $transaction['transaction_type'] == 'in' ? '+' : '-'; ?><?php echo number_format($transaction['quantity']); ?>
                            </strong>
                        </td>
                        <td>
                            <small style="color: #666;">
                                <?php echo number_format($transaction['previous_quantity']); ?> → 
                                <strong><?php echo number_format($transaction['new_quantity']); ?></strong>
                            </small>
                        </td>
                        <td><?php echo htmlspecialchars($transaction['username']); ?></td>
                        <td>
                            <?php if ($transaction['notes']): ?>
                                <small><?php echo htmlspecialchars($transaction['notes']); ?></small>
                            <?php else: ?>
                                <small style="color: #999;">No notes</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function updateFilter(param, value) {
    const url = new URL(window.location);
    if (value) {
        url.searchParams.set(param, value);
    } else {
        url.searchParams.delete(param);
    }
    window.location = url;
}

function clearFilters() {
    window.location = 'stock_transactions.php';
}
</script>

<?php require_once '../includes/unified_footer.php'; ?>