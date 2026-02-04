<?php
/**
 * Add New Sale Page (Point of Sale)
 * Inventory Management System
 */
$page_title = 'Add New Sale';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_name = sanitize_input($_POST['customer_name']);
    $customer_email = sanitize_input($_POST['customer_email']);
    $customer_phone = sanitize_input($_POST['customer_phone']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $discount = (float)($_POST['discount'] ?? 0);
    $tax = (float)($_POST['tax'] ?? 0);
    $notes = sanitize_input($_POST['notes']);
    $products = $_POST['products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    
    if (empty($products)) {
        $error_message = 'Please select at least one product.';
    } else {
        // Generate sale number
        $sale_number = 'SAL' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if sale number exists
        $check_stmt = $conn->prepare("SELECT id FROM sales WHERE sale_number = ?");
        $check_stmt->bind_param("s", $sale_number);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            $sale_number = 'SAL' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }
        
        // Calculate total amount
        $total_amount = 0;
        $valid_items = [];
        
        foreach ($products as $index => $product_id) {
            if (!empty($product_id) && !empty($quantities[$index]) && $quantities[$index] > 0) {
                // Get product price and check stock
                $price_stmt = $conn->prepare("SELECT price, quantity FROM products WHERE id = ?");
                $price_stmt->bind_param("i", $product_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                
                if ($price_result->num_rows > 0) {
                    $product = $price_result->fetch_assoc();
                    $unit_price = $product['price'];
                    $quantity = (int)$quantities[$index];
                    
                    // Check if enough stock available
                    if ($quantity > $product['quantity']) {
                        $error_message = "Insufficient stock for product ID $product_id. Available: {$product['quantity']}, Requested: $quantity";
                        break;
                    }
                    
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
        
        if (empty($error_message)) {
            if (empty($valid_items)) {
                $error_message = 'Please select valid products with quantities.';
            } else {
                // Calculate final amount
                $final_amount = $total_amount - $discount + $tax;
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert sale
                    $sale_stmt = $conn->prepare("INSERT INTO sales (sale_number, customer_name, customer_email, customer_phone, payment_method, total_amount, discount, tax, final_amount, notes, sold_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $sale_stmt->bind_param("sssssddddsi", $sale_number, $customer_name, $customer_email, $customer_phone, $payment_method, $total_amount, $discount, $tax, $final_amount, $notes, $_SESSION['user_id']);
                    $sale_stmt->execute();
                    
                    $sale_id = $conn->insert_id;
                    
                    // Insert sale items and update inventory
                    $item_stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
                    $stock_stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                    $transaction_stmt = $conn->prepare("INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, created_by) VALUES (?, 'out', ?, ?, ?)");
                    
                    foreach ($valid_items as $item) {
                        // Insert sale item
                        $item_stmt->bind_param("iiidd", $sale_id, $item['product_id'], $item['quantity'], $item['unit_price'], $item['total_price']);
                        $item_stmt->execute();
                        
                        // Update product stock
                        $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                        $stock_stmt->execute();
                        
                        // Record stock transaction
                        $transaction_notes = "Sale: $sale_number";
                        $transaction_stmt->bind_param("iisi", $item['product_id'], $item['quantity'], $transaction_notes, $_SESSION['user_id']);
                        $transaction_stmt->execute();
                    }
                    
                    // Log activity
                    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'Sale Recorded', ?, ?)");
                    $details = "Recorded sale: $sale_number for $final_amount";
                    $log_stmt->bind_param("iss", $_SESSION['user_id'], $details, $ip_address);
                    $log_stmt->execute();
                    
                    $conn->commit();
                    
                    header("Location: sales.php?created=1");
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    $error_message = 'Failed to record sale. Please try again.';
                }
            }
        }
    }
}

// Get all active products with stock
$products_result = $conn->query("SELECT id, name, price, quantity FROM products WHERE status = 'active' AND quantity > 0 ORDER BY name");

require_once '../includes/unified_header.php';
?>

<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-cash-register"></i> Point of Sale - New Sale</h3>
        <div class="table-actions">
            <a href="sales.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Sales
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
                <i class="fas fa-user"></i> Customer Information (Optional)
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" name="customer_name" id="customer_name" placeholder="Walk-in customer">
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
        </div>
        
        <!-- Sale Items -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-shopping-cart"></i> Sale Items
            </h4>
            
            <div id="saleItems">
                <div class="sale-item" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 15px; align-items: end; margin-bottom: 15px; padding: 15px; background: white; border-radius: 8px;">
                    <div class="form-group">
                        <label>Product</label>
                        <select name="products[]" required style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                            <option value="">Select Product</option>
                            <?php while ($product = $products_result->fetch_assoc()): ?>
                                <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
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
                        <button type="button" onclick="removeSaleItem(this)" class="btn btn-danger btn-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <button type="button" onclick="addSaleItem()" class="btn btn-success">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>
        
        <!-- Payment & Totals -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <h4 style="margin-bottom: 20px; color: #333; font-family: 'Times New Roman', serif;">
                <i class="fas fa-credit-card"></i> Payment & Totals
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label for="payment_method">Payment Method *</label>
                    <select name="payment_method" id="payment_method" required style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="check">Check</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="discount">Discount ($)</label>
                    <input type="number" name="discount" id="discount" min="0" step="0.01" value="0" style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                </div>
                
                <div class="form-group">
                    <label for="tax">Tax ($)</label>
                    <input type="number" name="tax" id="tax" min="0" step="0.01" value="0" style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px;">
                </div>
            </div>
            
            <div style="text-align: right; font-family: 'Times New Roman', serif;">
                <div style="margin-bottom: 10px;">
                    <strong>Subtotal: $<span id="subtotal">0.00</span></strong>
                </div>
                <div style="margin-bottom: 10px; color: #dc3545;">
                    <strong>Discount: -$<span id="discountAmount">0.00</span></strong>
                </div>
                <div style="margin-bottom: 10px; color: #28a745;">
                    <strong>Tax: +$<span id="taxAmount">0.00</span></strong>
                </div>
                <div style="font-size: 1.5rem; color: #333; border-top: 2px solid #333; padding-top: 10px;">
                    <strong>Total: $<span id="finalTotal">0.00</span></strong>
                </div>
            </div>
        </div>
        
        <!-- Notes -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
            <div class="form-group">
                <label for="notes">Sale Notes</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Any special notes about this sale..." style="width: 100%; padding: 10px; border: 2px solid #e5e7eb; border-radius: 8px; font-family: inherit;"></textarea>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div style="text-align: center;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-cash-register"></i> Complete Sale
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
        <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>" data-stock="<?php echo $product['quantity']; ?>">
            <?php echo htmlspecialchars($product['name']); ?> - $<?php echo number_format($product['price'], 2); ?> (Stock: <?php echo $product['quantity']; ?>)
        </option>
    <?php endwhile; ?>
`;

function addSaleItem() {
    const saleItems = document.getElementById('saleItems');
    const newItem = document.createElement('div');
    newItem.className = 'sale-item';
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
            <button type="button" onclick="removeSaleItem(this)" class="btn btn-danger btn-sm">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    saleItems.appendChild(newItem);
}

function removeSaleItem(button) {
    const saleItems = document.getElementById('saleItems');
    if (saleItems.children.length > 1) {
        button.closest('.sale-item').remove();
        calculateTotals();
    }
}

function calculateItemTotal(element) {
    const saleItem = element.closest('.sale-item');
    const productSelect = saleItem.querySelector('select[name="products[]"]');
    const quantityInput = saleItem.querySelector('input[name="quantities[]"]');
    const totalInput = saleItem.querySelector('.item-total');
    
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
    const stock = parseInt(selectedOption.getAttribute('data-stock')) || 0;
    const quantity = parseInt(quantityInput.value) || 0;
    
    // Check stock availability
    if (quantity > stock && stock > 0) {
        alert(`Only ${stock} items available in stock!`);
        quantityInput.value = stock;
        quantity = stock;
    }
    
    const total = price * quantity;
    totalInput.value = '$' + total.toFixed(2);
    calculateTotals();
}

function calculateTotals() {
    const totalInputs = document.querySelectorAll('.item-total');
    let subtotal = 0;
    
    totalInputs.forEach(input => {
        const value = input.value.replace('$', '');
        subtotal += parseFloat(value) || 0;
    });
    
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const tax = parseFloat(document.getElementById('tax').value) || 0;
    const finalTotal = subtotal - discount + tax;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('discountAmount').textContent = discount.toFixed(2);
    document.getElementById('taxAmount').textContent = tax.toFixed(2);
    document.getElementById('finalTotal').textContent = finalTotal.toFixed(2);
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select[name="products[]"]');
    const quantities = document.querySelectorAll('input[name="quantities[]"]');
    const discount = document.getElementById('discount');
    const tax = document.getElementById('tax');
    
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
    
    discount.addEventListener('input', calculateTotals);
    tax.addEventListener('input', calculateTotals);
});
</script>

<?php require_once '../includes/unified_footer.php'; ?>