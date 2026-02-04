<?php
/**
 * Add New Order Page
 * Inventory Management System
 */
$page_title = 'Add New Order';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitize_input($_POST['customer_name']);
    $customer_email = sanitize_input($_POST['customer_email']);
    $customer_phone = sanitize_input($_POST['customer_phone']);
    $customer_address = sanitize_input($_POST['customer_address']);
    $notes = sanitize_input($_POST['notes']);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    if (empty($customer_name) || empty($products)) {
        $error_message = 'Please fill in customer name and select at least one product.';
    } else {
        // Generate order number
        $order_number = 'ORD' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if order number exists
        $check_stmt = $conn->prepare("SELECT id FROM orders WHERE order_number = ?");
        $check_stmt->bind_param("s", $order_number);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $order_number = 'ORD' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Calculate total amount
        $total_amount = 0;
        $valid_items = [];
        
        foreach ($products as $index => $product_id) {
            if (!empty($product_id) && !empty($quantities[$index]) && $quantities[$index] > 0) {
                // Get product price
                $price_stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                $price_stmt->bind_param("i", $product_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                
                if ($price_result->num_rows > 0) {
                    $product = $price_result->fetch_assoc();
                    $unit_price = $product['price'];
                    $quantity = (int)$quantities[$index];
                    $item_total = $unit_price * $quantity;
                    
                    $valid_items[] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'total_price' => $item_total
                    ];
                    
                    $total_amount += $item_total;
                }
            }
        }
        
        if (empty($valid_items)) {
            $error_message = 'Please select valid products with quantities.';
        } else {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert order
                $order_stmt = $conn->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, customer_address, total_amount, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $order_stmt->bind_param("sssssdsi", $order_number, $customer_name, $customer_email, $customer_phone, $customer_address, $total_amount, $notes, $_SESSION['user_id']);
                $order_stmt->execute();
                
                $order_id = $conn->insert_id;
                
                // Insert order items
                $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($valid_items as $item) {
                    $item_stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total_price']);
                    $item_stmt->execute();
                }
                
                // Log activity
                $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'Order Created', ?, ?)");
                $details = "Created order: $order_number for $customer_name";
                $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip_address);
                $log_stmt->execute();
                
                $conn->commit();
                
                header("Location: orders.php?created=1");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = 'Failed to create order. Please try again.';
            }
        }
    }
}

// Get all active products
$products_result = $conn->query("SELECT id, name, price, quantity FROM products WHERE status = 'active' ORDER BY name");

require_once '../includes/unified_header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-plus-circle"></i> Add New Order</h3>
        <div class="table-actions">
            <a href="orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" style="padding: 30px;">
        <!-- Customer Information -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-user"></i> Customer Information
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label for="customer_name">Customer Name *</label>
                    <input type="text" name="customer_name" id="customer_name" required>
                </div>
                
                <div class="form-group">
                    <label for="customer_email">Email</label>
                    <input type="email" name="customer_email" id="customer_email">
                </div>
                
                <div class="form-group">
                    <label for="customer_phone">Phone</label>
                    <input type="tel" name="customer_phone" id="customer_phone">
                </div>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label for="customer_address">Address</label>
                <textarea name="customer_address" id="customer_address" rows="3" style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: inherit;"></textarea>
            </div>
        </div>
        
        <!-- Order Items -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-shopping-cart"></i> Order Items
            </h4>
            
            <div id="orderItems">
                <div class="order-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end; margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px;">
                    <div class="form-group">
                        <label>Product</label>
                        <select name="products[]" required style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                            <option value="">Select Product</option>
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['quantity']; ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantities[]" min="1" value="1" required style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                    </div>
                    
                    <div class="form-group">
                        <label>Total</label>
                        <input type="text" class="item-total" readonly style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9f9f9;">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" onclick="removeOrderItem(this)" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="addOrderItem()" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Item
            </button>
            
            <div style="margin-top: 20px; text-align: right;">
                <h4 style="font-family: 'Times New Roman', serif;">
                    Total Amount: $<span id="grandTotal">0.00</span>
                </h4>
            </div>
        </div>
        
        <!-- Notes -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <div class="form-group">
                <label for="notes">Order Notes</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Any special instructions or notes..." style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: inherit;"></textarea>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div style="text-align: center;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Create Order
            </button>
        </div>
    </form>
</div>

<script>
// Product options HTML for new items
const productOptions = `
    <option value="">Select Product</option>
    <?php 
    $products_result->data_seek(0); // Reset result pointer
    while ($product = $products_result->fetch_assoc()): ?>
        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['quantity']; ?>)
        </option>
    <?php endwhile; ?>
`;

function addOrderItem() {
    const orderItems = document.getElementById('orderItems');
    const newItem = document.createElement('div');
    newItem.className = 'order-item';
    newItem.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end; margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px;';
    
    newItem.innerHTML = `
        <div class="form-group">
            <label>Product</label>
            <select name="products[]" required style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;" onchange="calculateItemTotal(this)">
                ${productOptions}
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity</label>
            <input type="number" name="quantities[]" min="1" value="1" required style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;" onchange="calculateItemTotal(this)">
        </div>
        
        <div class="form-group">
            <label>Total</label>
            <input type="text" class="item-total" readonly style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9f9f9;">
        </div>
        
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" onclick="removeOrderItem(this)" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    orderItems.appendChild(newItem);
}

function removeOrderItem(button) {
    const orderItems = document.getElementById('orderItems');
    if (orderItems.children.length > 1) {
        button.closest('.order-item').remove();
        calculateGrandTotal();
    }
}

function calculateItemTotal(element) {
    const orderItem = element.closest('.order-item');
    const productSelect = orderItem.querySelector('select[name="products[]"]');
    const quantityInput = orderItem.querySelector('input[name="quantities[]"]');
    const totalInput = orderItem.querySelector('.item-total');
    
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    const total = price * quantity;
    
    totalInput.value = '$' + total.toFixed(2);
    calculateGrandTotal();
}

function calculateGrandTotal() {
    const totalInputs = document.querySelectorAll('.item-total');
    let grandTotal = 0;
    
    totalInputs.forEach(input => {
        const value = input.value.replace('$', '');
        grandTotal += parseFloat(value) || 0;
    });
    
    document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
}

// Add event listeners to existing items
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name="products[]"]');
    const quantities = document.querySelectorAll('input[name="quantities[]"]');
    
    selects.forEach(select => {
        select.addEventListener('change', function() {
            calculateItemTotal(this);
        });
    });
    
    quantities.forEach(input => {
        input.addEventListener('change', function() {
            calculateItemTotal(this);
        });
    });
});
</script>

<?php require_once '../includes/unified_footer.php'; ?>