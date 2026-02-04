<?php
/**
 * Get Chart Data API
 * Inventory Management System
 */
header('Content-Type: application/json');
require_once '../includes/auth_check.php';
require_once '../config/db.php';

$response = ['success' => false, 'data' => []];

try {
    $type = $_GET['type'] ?? 'stock';
    
    switch ($type) {
        case 'stock':
            // Get top 10 products by quantity for stock chart
            $stmt = $conn->prepare("
                SELECT name as label, quantity as value 
                FROM products 
                WHERE status = 'active' 
                ORDER BY quantity DESC 
                LIMIT 10
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Convert to chart format
            $chart_data = array_map(function($item) {
                return [
                    'label' => substr($item['label'], 0, 15) . (strlen($item['label']) > 15 ? '...' : ''),
                    'value' => (int)$item['value']
                ];
            }, $data);
            
            $response['success'] = true;
            $response['data'] = $chart_data;
            break;
            
        case 'category':
            // Get products count by category for pie chart
            $stmt = $conn->prepare("
                SELECT 
                    COALESCE(c.name, 'No Category') as label, 
                    COUNT(p.id) as value 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'active' 
                GROUP BY p.category_id, c.name 
                ORDER BY value DESC
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Convert to chart format
            $chart_data = array_map(function($item) {
                return [
                    'label' => $item['label'],
                    'value' => (int)$item['value']
                ];
            }, $data);
            
            $response['success'] = true;
            $response['data'] = $chart_data;
            break;
            
        case 'stock_status':
            // Get stock status distribution
            $stmt = $conn->prepare("
                SELECT 
                    CASE 
                        WHEN quantity = 0 THEN 'Out of Stock'
                        WHEN quantity <= min_stock_level THEN 'Low Stock'
                        ELSE 'In Stock'
                    END as label,
                    COUNT(*) as value
                FROM products 
                WHERE status = 'active'
                GROUP BY 
                    CASE 
                        WHEN quantity = 0 THEN 'Out of Stock'
                        WHEN quantity <= min_stock_level THEN 'Low Stock'
                        ELSE 'In Stock'
                    END
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            $chart_data = array_map(function($item) {
                return [
                    'label' => $item['label'],
                    'value' => (int)$item['value']
                ];
            }, $data);
            
            $response['success'] = true;
            $response['data'] = $chart_data;
            break;
            
        case 'monthly_movements':
            // Get monthly stock movements for the last 6 months
            $stmt = $conn->prepare("
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as label,
                    SUM(CASE WHEN transaction_type = 'in' THEN quantity ELSE 0 END) as stock_in,
                    SUM(CASE WHEN transaction_type = 'out' THEN quantity ELSE 0 END) as stock_out
                FROM stock_transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY label DESC
                LIMIT 6
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            
            // Format for chart
            $chart_data = [
                'labels' => array_reverse(array_column($data, 'label')),
                'stock_in' => array_reverse(array_column($data, 'stock_in')),
                'stock_out' => array_reverse(array_column($data, 'stock_out'))
            ];
            
            $response['success'] = true;
            $response['data'] = $chart_data;
            break;
            
        default:
            $response['error'] = 'Invalid chart type';
    }
    
} catch (Exception $e) {
    $response['error'] = 'Failed to fetch chart data: ' . $e->getMessage();
}

echo json_encode($response);
?>