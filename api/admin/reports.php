<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

requireAdmin();

$action = $_GET['action'] ?? '';

try {
    $pdo = getDB();
    
    switch ($action) {
        case 'dashboard':
            getDashboardStats($pdo);
            break;
        case 'sales':
            getSalesReport($pdo);
            break;
        case 'products':
            getProductsReport($pdo);
            break;
        case 'customers':
            getCustomersReport($pdo);
            break;
        default:
            getDashboardStats($pdo);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

function getDashboardStats($pdo) {
    $stats = [];
    
    // Total sales
    $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total_sales FROM orders WHERE status != 'cancelled'");
    $stats['total_sales'] = $stmt->fetch()['total_sales'];
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $stats['total_orders'] = $stmt->fetch()['total_orders'];
    
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
    $stats['total_products'] = $stmt->fetch()['total_products'];
    
    // Total customers
    $stmt = $pdo->query("SELECT COUNT(*) as total_customers FROM users WHERE role = 'user'");
    $stats['total_customers'] = $stmt->fetch()['total_customers'];
    
    // Low stock products
    $stmt = $pdo->query("SELECT COUNT(*) as low_stock FROM products WHERE stock < 10");
    $stats['low_stock'] = $stmt->fetch()['low_stock'];
    
    // This month sales
    $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as month_sales FROM orders WHERE created_at >= date('now', 'start of month') AND status != 'cancelled'");
    $stats['month_sales'] = $stmt->fetch()['month_sales'];
    
    // This month orders
    $stmt = $pdo->query("SELECT COUNT(*) as month_orders FROM orders WHERE created_at >= date('now', 'start of month')");
    $stats['month_orders'] = $stmt->fetch()['month_orders'];
    
    // Recent orders
    $stmt = $pdo->query("
        SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stats['recent_orders'] = $stmt->fetchAll();
    
    // Top products
    $stmt = $pdo->query("
        SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'cancelled'
        GROUP BY p.id, p.name
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stats['top_products'] = $stmt->fetchAll();
    
    echo json_encode(['stats' => $stats]);
}

function getSalesReport($pdo) {
    $period = $_GET['period'] ?? 'month';
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');
    
    $data = [];
    
    if ($period === 'year') {
        // Monthly sales for the year
        $stmt = $pdo->prepare("
            SELECT 
                strftime('%m', created_at) as period,
                COALESCE(SUM(total), 0) as sales,
                COUNT(*) as orders
            FROM orders 
            WHERE strftime('%Y', created_at) = ? AND status != 'cancelled'
            GROUP BY strftime('%m', created_at)
            ORDER BY period
        ");
        $stmt->execute([$year]);
        
        $results = $stmt->fetchAll();
        
        // Fill in missing months
        for ($i = 1; $i <= 12; $i++) {
            $monthStr = sprintf('%02d', $i);
            $found = false;
            
            foreach ($results as $result) {
                if ($result['period'] === $monthStr) {
                    $data[] = [
                        'period' => $monthStr,
                        'period_name' => date('F', mktime(0, 0, 0, $i, 1)),
                        'sales' => $result['sales'],
                        'orders' => $result['orders']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $data[] = [
                    'period' => $monthStr,
                    'period_name' => date('F', mktime(0, 0, 0, $i, 1)),
                    'sales' => 0,
                    'orders' => 0
                ];
            }
        }
    } else {
        // Daily sales for the month
        $stmt = $pdo->prepare("
            SELECT 
                strftime('%d', created_at) as period,
                COALESCE(SUM(total), 0) as sales,
                COUNT(*) as orders
            FROM orders 
            WHERE strftime('%Y-%m', created_at) = ? AND status != 'cancelled'
            GROUP BY strftime('%d', created_at)
            ORDER BY period
        ");
        $stmt->execute([$year . '-' . $month]);
        
        $results = $stmt->fetchAll();
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // Fill in missing days
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $dayStr = sprintf('%02d', $i);
            $found = false;
            
            foreach ($results as $result) {
                if ($result['period'] === $dayStr) {
                    $data[] = [
                        'period' => $dayStr,
                        'period_name' => $dayStr,
                        'sales' => $result['sales'],
                        'orders' => $result['orders']
                    ];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $data[] = [
                    'period' => $dayStr,
                    'period_name' => $dayStr,
                    'sales' => 0,
                    'orders' => 0
                ];
            }
        }
    }
    
    echo json_encode(['data' => $data]);
}

function getProductsReport($pdo) {
    // Best selling products
    $stmt = $pdo->query("
        SELECT 
            p.name,
            p.price,
            p.stock,
            SUM(oi.quantity) as total_sold,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'cancelled' OR o.id IS NULL
        GROUP BY p.id, p.name, p.price, p.stock
        ORDER BY total_sold DESC
    ");
    
    $products = $stmt->fetchAll();
    
    // Category performance
    $stmt = $pdo->query("
        SELECT 
            c.name as category_name,
            COUNT(p.id) as product_count,
            COALESCE(SUM(oi.quantity), 0) as total_sold,
            COALESCE(SUM(oi.quantity * oi.price), 0) as total_revenue
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id
        WHERE o.status != 'cancelled' OR o.id IS NULL
        GROUP BY c.id, c.name
        ORDER BY total_revenue DESC
    ");
    
    $categories = $stmt->fetchAll();
    
    echo json_encode([
        'products' => $products,
        'categories' => $categories
    ]);
}

function getCustomersReport($pdo) {
    // Top customers by spending
    $stmt = $pdo->query("
        SELECT 
            u.username,
            COUNT(o.id) as total_orders,
            COALESCE(SUM(o.total), 0) as total_spent,
            MAX(o.created_at) as last_order
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'user' AND (o.status != 'cancelled' OR o.id IS NULL)
        GROUP BY u.id, u.username
        ORDER BY total_spent DESC
        LIMIT 20
    ");
    
    $customers = $stmt->fetchAll();
    
    // Customer registration trend
    $stmt = $pdo->query("
        SELECT 
            strftime('%Y-%m', created_at) as month,
            COUNT(*) as new_customers
        FROM users
        WHERE role = 'user'
        GROUP BY strftime('%Y-%m', created_at)
        ORDER BY month DESC
        LIMIT 12
    ");
    
    $registrations = $stmt->fetchAll();
    
    echo json_encode([
        'customers' => $customers,
        'registrations' => $registrations
    ]);
}
?>