<?php
/**
 * Edit Product Page
 * Inventory Management System
 */
$page_title = 'Edit Product';
require_once '../includes/auth_check.php';
require_once '../config/db.php';

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    header('Location: products.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, s.name as supplier_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN suppliers s ON p.supplier_id = s.id
    WHERE p.id = ? AND p.status = 'active'
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error_message'] = 'Product not found.';
    header('Location: products.php');
    exit();
}

$product = $result->fetch_assoc();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $name = sanitize_input($_POST['name']);
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $sku = sanitize_input($_POST['sku']);
        $description = sanitize_input($_POST['description']);
        $price = (float)$_POST['price'];
        $min_stock_level = (int)$_POST['min_stock_level'];
        $image = sanitize_input($_POST['image_filename'] ?? $product['image']);
        
        // Validation
        if (empty($name) || empty($sku) || $price <= 0) {
            $error_message = 'Please fill in all required fields with valid values.';
        } else {
            // Check if SKU already exists for other products
            $check_stmt = $conn->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
            $check_stmt->bind_param("si", $sku, $product_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = 'SKU already exists for another product. Please use a different SKU.';
            } else {
                // Update product
                $stmt = $conn->prepare("
                    UPDATE products 
                    SET name = ?, category_id = ?, supplier_id = ?, sku = ?, description = ?, price = ?, min_stock_level = ?, image = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->bind_param("siissdiisi", $name, $category_id, $supplier_id, $sku, $description, $price, $min_stock_level, $image, $product_id);
                
                if ($stmt->execute()) {
                    // Log activity
                    log_activity('Product Updated', 'products', $product_id, "Updated product: $name");
                    
                    $_SESSION['success_message'] = 'Product updated successfully!';
                    header('Location: products.php');
                    exit();
                } else {
                    $error_message = 'Failed to update product. Please try again.';
                }
            }
        }
    }
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Get suppliers
$suppliers = $conn->query("SELECT * FROM suppliers WHERE status = 'active' ORDER BY name")->fetch_all(MYSQLI_ASSOC);

require_once '../includes/unified_header.php';
?>

<div class="form-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2><i class="fas fa-edit"></i> Edit Product</h2>
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
    
    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        
        <div class="form-row">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?php echo htmlspecialchars($product['name']); ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="sku">SKU *</label>
                <input type="text" id="sku" name="sku" class="form-control" 
                       value="<?php echo htmlspecialchars($product['sku']); ?>" 
                       required>
                <small style="color: #666;">Unique product identifier</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id" class="form-control">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id" class="form-control">
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?php echo $supplier['id']; ?>" 
                                <?php echo ($product['supplier_id'] == $supplier['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($supplier['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        
        <!-- Product Image Upload -->
        <div class="form-group">
            <label for="product_image">Product Image</label>
            <div style="display: flex; gap: 20px; align-items: start;">
                <div style="flex: 1;">
                    <input type="file" id="product_image" accept="image/*" class="form-control" onchange="uploadProductImage(this)">
                    <input type="hidden" name="image_filename" id="image_filename" value="<?php echo htmlspecialchars($product['image']); ?>">
                    <small style="color: #666;">Supported formats: JPG, PNG, GIF (Max: 5MB)</small>
                </div>
                <div id="image_preview" style="width: 100px; height: 100px; border: 2px dashed #ddd; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; overflow: hidden;">
                    <?php if ($product['image'] && file_exists("../assets/images/products/" . $product['image'])): ?>
                        <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover;" 
                             alt="Current product image">
                    <?php else: ?>
                        <i class="fas fa-image" style="color: #ccc; font-size: 2rem;"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div id="upload_progress" style="display: none; margin-top: 10px;">
                <div style="background: #e9ecef; border-radius: 4px; overflow: hidden;">
                    <div id="progress_bar" style="background: #007bff; height: 4px; width: 0%; transition: width 0.3s;"></div>
                </div>
                <small id="upload_status" style="color: #666;">Uploading...</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="price">Price ($) *</label>
                <input type="number" id="price" name="price" class="form-control" 
                       step="0.01" min="0" 
                       value="<?php echo $product['price']; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="current_quantity">Current Quantity</label>
                <input type="number" id="current_quantity" class="form-control" 
                       value="<?php echo $product['quantity']; ?>" 
                       readonly style="background: #f8f9fa;">
                <small style="color: #666;">Use stock management buttons to change quantity</small>
            </div>
        </div>
        
        <div class="form-group">
            <label for="min_stock_level">Minimum Stock Level</label>
            <input type="number" id="min_stock_level" name="min_stock_level" class="form-control" 
                   min="0" 
                   value="<?php echo $product['min_stock_level']; ?>">
            <small style="color: #666;">Alert when stock falls below this level</small>
        </div>
        
        <!-- Stock Management Section -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <h4><i class="fas fa-boxes"></i> Stock Management</h4>
            <p style="color: #666; margin-bottom: 15px;">Current Stock: <strong><?php echo $product['quantity']; ?> units</strong></p>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="button" onclick="updateStock(<?php echo $product['id']; ?>, 'in')" 
                        class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Stock
                </button>
                <button type="button" onclick="updateStock(<?php echo $product['id']; ?>, 'out')" 
                        class="btn btn-warning">
                    <i class="fas fa-minus"></i> Remove Stock
                </button>
                <a href="stock_transactions.php?product_id=<?php echo $product['id']; ?>" 
                   class="btn btn-info">
                    <i class="fas fa-history"></i> View History
                </a>
            </div>
        </div>
        
        <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
            <a href="products.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Product
            </button>
        </div>
    </form>
</div>

<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const sku = document.getElementById('sku').value.trim();
    const price = parseFloat(document.getElementById('price').value);
    
    if (!name || !sku || !price || price <= 0) {
        e.preventDefault();
        showAlert('Please fill in all required fields with valid values.', 'error');
    }
});

// Image upload functionality
function uploadProductImage(input) {
    const file = input.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        showAlert('Please select a valid image file (JPG, PNG, or GIF).', 'error');
        input.value = '';
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('Image file is too large. Maximum size is 5MB.', 'error');
        input.value = '';
        return;
    }
    
    // Show upload progress
    document.getElementById('upload_progress').style.display = 'block';
    document.getElementById('progress_bar').style.width = '0%';
    document.getElementById('upload_status').textContent = 'Uploading...';
    
    // Create FormData
    const formData = new FormData();
    formData.append('image', file);
    
    // Upload image
    fetch('upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('upload_progress').style.display = 'none';
        
        if (data.success) {
            // Set filename in hidden field
            document.getElementById('image_filename').value = data.filename;
            
            // Show preview
            const preview = document.getElementById('image_preview');
            preview.innerHTML = `<img src="../assets/images/products/${data.filename}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">`;
            
            showAlert('Image uploaded successfully!', 'success');
        } else {
            showAlert(data.message || 'Failed to upload image.', 'error');
            input.value = '';
        }
    })
    .catch(error => {
        document.getElementById('upload_progress').style.display = 'none';
        showAlert('Upload failed. Please try again.', 'error');
        input.value = '';
        console.error('Upload error:', error);
    });
}
</script>

<?php require_once '../includes/unified_footer.php'; ?>