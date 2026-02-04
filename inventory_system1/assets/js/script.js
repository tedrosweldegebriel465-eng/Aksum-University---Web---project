/**
 * Inventory Management System - JavaScript
 * University Project
 */

// Global variables
let confirmCallback = null;

// Logout confirmation function
function confirmLogout() {
    return confirm('Are you sure you want to logout?');
}

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeModals();
    initializeNotifications();
    initializeSearch();
    initializeCharts();
    initializeForms();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
});

// Sidebar functionality
function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    // Mobile menu toggle
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Sidebar toggle for desktop
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
}

// Modal functionality
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close');
    
    // Close modal when clicking close button
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
    
    // Confirmation modal
    const confirmModal = document.getElementById('confirmModal');
    const confirmAction = document.getElementById('confirmAction');
    const cancelAction = document.getElementById('cancelAction');
    
    if (confirmAction) {
        confirmAction.addEventListener('click', function() {
            if (confirmCallback) {
                confirmCallback();
                confirmCallback = null;
            }
            confirmModal.style.display = 'none';
        });
    }
    
    if (cancelAction) {
        cancelAction.addEventListener('click', function() {
            confirmCallback = null;
            confirmModal.style.display = 'none';
        });
    }
}

// Notifications
function initializeNotifications() {
    const notificationIcon = document.getElementById('notifications');
    const notificationModal = document.getElementById('notificationModal');
    
    if (notificationIcon) {
        notificationIcon.addEventListener('click', function() {
            loadNotifications();
            notificationModal.style.display = 'block';
        });
    }
    
    // Load notification count
    loadNotificationCount();
}

function loadNotificationCount() {
    fetch('../api/get_notifications.php?count_only=1')
        .then(response => response.json())
        .then(data => {
            const countElement = document.getElementById('notificationCount');
            if (countElement && data.count !== undefined) {
                countElement.textContent = data.count;
                countElement.style.display = data.count > 0 ? 'flex' : 'none';
            }
        })
        .catch(error => console.error('Error loading notification count:', error));
}

function loadNotifications() {
    fetch('../api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            const notificationList = document.getElementById('notificationList');
            if (notificationList && data.notifications) {
                if (data.notifications.length === 0) {
                    notificationList.innerHTML = '<p>No notifications</p>';
                } else {
                    notificationList.innerHTML = data.notifications.map(notification => `
                        <div class="notification-item ${notification.is_read ? '' : 'unread'}">
                            <div class="notification-header">
                                <strong>${notification.title}</strong>
                                <small>${formatDate(notification.created_at)}</small>
                            </div>
                            <p>${notification.message}</p>
                        </div>
                    `).join('');
                }
                
                // Mark notifications as read
                markNotificationsAsRead();
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function markNotificationsAsRead() {
    fetch('../api/mark_notifications_read.php', {
        method: 'POST'
    }).then(() => {
        loadNotificationCount();
    });
}

// Search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.table-container').querySelector('table tbody');
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
}

// Charts initialization
function initializeCharts() {
    // Simple bar chart for stock levels
    createStockChart();
    createCategoryChart();
}

function createStockChart() {
    const chartContainer = document.getElementById('stockChart');
    if (!chartContainer) return;
    
    // Fetch stock data and create simple bar chart
    fetch('../api/get_chart_data.php?type=stock')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                renderBarChart(chartContainer, data.data, 'Stock Levels');
            }
        })
        .catch(error => console.error('Error loading stock chart:', error));
}

function createCategoryChart() {
    const chartContainer = document.getElementById('categoryChart');
    if (!chartContainer) return;
    
    // Fetch category data and create pie chart
    fetch('../api/get_chart_data.php?type=category')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                renderPieChart(chartContainer, data.data, 'Products by Category');
            }
        })
        .catch(error => console.error('Error loading category chart:', error));
}

function renderBarChart(container, data, title) {
    console.log('renderBarChart called with data:', data);
    const maxValue = Math.max(...data.map(item => item.value));
    console.log('Max value:', maxValue);
    
    container.innerHTML = `
        <h4 style="font-family: 'Times New Roman', Times, serif; font-weight: 700; font-size: 1.4rem; color: #667eea; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-align: center; margin-bottom: 20px;">${title}</h4>
        <div class="chart-bars" style="position: relative; padding-top: 40px; height: 240px; display: flex; align-items: flex-end; justify-content: space-around; overflow: visible;">
            ${data.map((item, index) => {
                // Improved label handling - show more characters
                const truncatedLabel = item.label.length > 15 ? 
                    item.label.substring(0, 13) + '...' : 
                    item.label;
                
                const barHeight = Math.max((item.value / maxValue) * 180, 25);
                console.log(`Bar ${index}: ${item.label} = ${item.value}, height = ${barHeight}px`);
                
                return `
                <div class="chart-bar-container" title="${item.label}: ${item.value} units" style="position: relative; min-width: 60px; display: flex; flex-direction: column; align-items: center; margin: 0 2px;">
                    <div class="bar-value" style="position: absolute; top: -35px; left: 50%; transform: translateX(-50%); font-size: 0.9rem; font-weight: 700; color: #333; white-space: nowrap; z-index: 100; background: rgba(255, 255, 255, 0.95); padding: 3px 6px; border-radius: 4px; border: 1px solid #ccc; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-family: 'Times New Roman', Times, serif;">${item.value}</div>
                    <div class="chart-bar" style="height: ${barHeight}px; width: 40px; background: linear-gradient(135deg, #667eea, #764ba2); position: relative; border-radius: 4px 4px 0 0; min-height: 25px;">
                    </div>
                    <span class="bar-label" title="${item.label}" style="display: block; margin-top: 8px; font-size: 0.75rem; text-align: center; word-wrap: break-word; line-height: 1.2; color: #666; font-weight: 500; max-width: 60px; font-family: 'Times New Roman', Times, serif;">${truncatedLabel}</span>
                </div>
            `;
            }).join('')}
        </div>
    `;
    
    console.log('Chart rendered successfully');
}

function renderPieChart(container, data, title) {
    const total = data.reduce((sum, item) => sum + item.value, 0);
    let currentAngle = 0;
    
    const colors = ['#667eea', '#764ba2', '#56ab2f', '#f093fb', '#4facfe', '#43e97b', '#fa709a', '#ff9a9e'];
    
    container.innerHTML = `
        <h4 style="font-family: 'Times New Roman', Times, serif; font-weight: 700; font-size: 1.4rem; color: #667eea; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; text-align: center; margin-bottom: 20px;">${title}</h4>
        <div class="pie-chart-container" style="display: flex; flex-direction: row; align-items: center; justify-content: center; gap: 40px; padding: 20px; min-height: 300px;">
            <svg class="pie-chart" viewBox="0 0 200 200" style="width: 280px; height: 280px; flex-shrink: 0;">
                ${data.map((item, index) => {
                    const percentage = (item.value / total) * 100;
                    const angle = (item.value / total) * 360;
                    
                    // Skip very small slices to avoid display issues
                    if (percentage < 1) return '';
                    
                    const x1 = 100 + 80 * Math.cos((currentAngle - 90) * Math.PI / 180);
                    const y1 = 100 + 80 * Math.sin((currentAngle - 90) * Math.PI / 180);
                    const x2 = 100 + 80 * Math.cos((currentAngle + angle - 90) * Math.PI / 180);
                    const y2 = 100 + 80 * Math.sin((currentAngle + angle - 90) * Math.PI / 180);
                    const largeArc = angle > 180 ? 1 : 0;
                    
                    const path = `M 100 100 L ${x1} ${y1} A 80 80 0 ${largeArc} 1 ${x2} ${y2} Z`;
                    currentAngle += angle;
                    
                    return `<path d="${path}" fill="${colors[index % colors.length]}" stroke="white" stroke-width="2" title="${item.label}: ${item.value} (${percentage.toFixed(1)}%)"/>`;
                }).join('')}
            </svg>
            <div class="pie-legend" style="flex: 1; max-width: 400px; min-width: 250px;">
                ${data.map((item, index) => {
                    const percentage = (item.value / total) * 100;
                    // Show full text since we have more space horizontally
                    const displayLabel = item.label.length > 30 ? 
                        item.label.substring(0, 28) + '...' : 
                        item.label;
                    
                    return `
                    <div class="legend-item" title="${item.label}: ${item.value} items (${percentage.toFixed(1)}%)" style="display: flex; align-items: center; gap: 15px; margin-bottom: 12px; font-size: 1.1rem; font-weight: 500; color: #333; font-family: 'Times New Roman', Times, serif;">
                        <span class="legend-color" style="width: 20px; height: 20px; background: ${colors[index % colors.length]}; border-radius: 4px; flex-shrink: 0;"></span>
                        <span>${displayLabel}: ${item.value}</span>
                    </div>
                `;
                }).join('')}
            </div>
        </div>
    `;
}

// Form functionality
function initializeForms() {
    // Auto-generate SKU
    const productNameInput = document.getElementById('product_name');
    const skuInput = document.getElementById('sku');
    
    if (productNameInput && skuInput) {
        productNameInput.addEventListener('input', function() {
            if (!skuInput.value) {
                const sku = generateSKU(this.value);
                skuInput.value = sku;
            }
        });
    }
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

function generateSKU(productName) {
    const cleaned = productName.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
    const timestamp = Date.now().toString().slice(-4);
    return cleaned.substring(0, 6) + '-' + timestamp;
}

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// Utility functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    const content = document.querySelector('.content');
    content.insertBefore(alertDiv, content.firstChild);
    
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

function confirmAction(message, callback) {
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    
    confirmMessage.textContent = message;
    confirmCallback = callback;
    confirmModal.style.display = 'block';
}

// Delete functions
function deleteItem(id, type, name) {
    confirmAction(`Are you sure you want to delete "${name}"?`, function() {
        window.location.href = `delete_${type}.php?id=${id}`;
    });
}

// Stock update functions
function updateStock(productId, action) {
    const actionText = action === 'in' ? 'add to' : 'remove from';
    const quantity = prompt(`Enter quantity to ${actionText} stock:`);
    if (quantity && !isNaN(quantity) && quantity > 0) {
        // Create form dynamically
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'update_stock.php';
        form.style.display = 'none';
        
        // Add CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || 
                         document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        const fields = {
            product_id: productId,
            action: action,
            quantity: quantity,
            csrf_token: csrfToken,
            notes: `Stock ${actionText} via web interface`
        };
        
        Object.keys(fields).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Export functions - Fixed version
function exportData(type) {
    // Debug logging
    console.log('exportData called with type:', type);
    
    // Get current filters from URL
    const urlParams = new URLSearchParams(window.location.search);
    const params = new URLSearchParams();
    
    // Add report type
    params.set('type', type);
    
    // Add common filters if they exist
    if (urlParams.get('start_date')) params.set('start_date', urlParams.get('start_date'));
    if (urlParams.get('end_date')) params.set('end_date', urlParams.get('end_date'));
    
    // Add specific filters based on type
    if (type === 'stock_transactions' || type === 'stock_movements') {
        if (urlParams.get('product_id')) params.set('product_id', urlParams.get('product_id'));
        if (urlParams.get('transaction_type')) params.set('transaction_type', urlParams.get('transaction_type'));
        if (urlParams.get('user_id')) params.set('user_id', urlParams.get('user_id'));
    } else if (type === 'products') {
        if (urlParams.get('filter')) params.set('filter', urlParams.get('filter'));
        if (urlParams.get('category')) params.set('category', urlParams.get('category'));
        if (urlParams.get('supplier')) params.set('supplier', urlParams.get('supplier'));
        if (urlParams.get('search')) params.set('search', urlParams.get('search'));
    } else if (type === 'activity_logs') {
        if (urlParams.get('user_id')) params.set('user_id', urlParams.get('user_id'));
        if (urlParams.get('action')) params.set('action', urlParams.get('action'));
    }
    
    // Construct export URL
    const exportUrl = `../api/export_report.php?${params.toString()}`;
    console.log('Opening export URL:', exportUrl);
    
    // Create a temporary link and click it to trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = `${type}_report_${new Date().toISOString().split('T')[0]}.csv`;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    showNotification('Export started successfully!', 'success');
}

// Alternative export function for reports page
function exportReport() {
    const reportType = new URLSearchParams(window.location.search).get('type') || 'inventory';
    exportData(reportType);
}

// Show notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#d4edda' : '#d1ecf1'};
        color: ${type === 'success' ? '#155724' : '#0c5460'};
        padding: 15px 20px;
        border-radius: 8px;
        border: 1px solid ${type === 'success' ? '#c3e6cb' : '#bee5eb'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Print functions
function printReport() {
    window.print();
}

// Real-time updates (if needed)
function startRealTimeUpdates() {
    setInterval(() => {
        loadNotificationCount();
    }, 30000); // Check every 30 seconds
}

// Initialize real-time updates
startRealTimeUpdates();
// Filter update functions
function updateFilter(param, value) {
    const url = new URL(window.location);
    if (value) {
        url.searchParams.set(param, value);
    } else {
        url.searchParams.delete(param);
    }
    window.location = url;
}

function updateSearch(value) {
    const url = new URL(window.location);
    if (value.trim()) {
        url.searchParams.set('search', value.trim());
    } else {
        url.searchParams.delete('search');
    }
    window.location = url;
}

function clearFilters() {
    const url = new URL(window.location);
    // Keep only the base path and essential parameters
    const newUrl = url.pathname;
    window.location = newUrl;
}

// Enhanced chart initialization
function initializeCharts() {
    // Initialize dashboard charts if containers exist
    if (document.getElementById('stockChart')) {
        createStockChart();
    }
    
    if (document.getElementById('categoryChart')) {
        createCategoryChart();
    }
    
    // Initialize any test charts
    if (document.getElementById('testStockChart')) {
        const testData = [
            { label: 'Wireless Keyboard', value: 40 },
            { label: 'Wireless Headphones', value: 35 },
            { label: 'Printer Paper', value: 30 },
            { label: 'Laptop Dell', value: 25 },
            { label: 'Wireless Earbuds', value: 25 }
        ];
        renderBarChart(document.getElementById('testStockChart'), testData, 'Stock Levels Test');
    }
    
    if (document.getElementById('testCategoryChart')) {
        const testCategoryData = [
            { label: 'Electronics', value: 45 },
            { label: 'Office Supplies', value: 30 },
            { label: 'Mobile Devices', value: 25 }
        ];
        renderPieChart(document.getElementById('testCategoryChart'), testCategoryData, 'Categories Test');
    }
}

// Test export functionality
function testExport() {
    console.log('Testing export functionality...');
    
    // Test different export types
    const exportTypes = ['products', 'activity_logs', 'stock_transactions', 'inventory'];
    
    exportTypes.forEach(type => {
        console.log(`Testing export for: ${type}`);
        // Don't actually trigger download in test, just log
    });
    
    showNotification('Export test completed - check console for details', 'info');
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing inventory system...');
    
    // Initialize forms
    initializeForms();
    
    // Initialize charts
    initializeCharts();
    
    // Initialize search functionality
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                updateSearch(this.value);
            }
        });
    });
    
    console.log('Inventory system initialized successfully');
});