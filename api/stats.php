<?php
require_once __DIR__ . '/../config/db.php';

try {
    // 1. Total revenue (sum of order_items)
    $revenueStmt = $pdo->query("
        SELECT COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue
        FROM order_items oi
    ");
    $totalRevenue = (float)$revenueStmt->fetchColumn();

    // 2. Total orders
    $ordersCountStmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $totalOrders = (int)$ordersCountStmt->fetchColumn();

    // 3. Total customers
    $customersCountStmt = $pdo->query("SELECT COUNT(*) FROM customers");
    $totalCustomers = (int)$customersCountStmt->fetchColumn();

    // 4. Total products & low stock (< 30)
    $productsStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_products,
            COALESCE(SUM(CASE WHEN stock_quantity < 30 THEN 1 ELSE 0 END), 0) AS low_stock_count,
            COALESCE(SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END), 0) AS active_products
        FROM products
    ");
    $prodStats = $productsStmt->fetch();

    // 5. Total categories
    $categoriesStmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $totalCategories = (int)$categoriesStmt->fetchColumn();

    // 6. Average Order Value
    $aov = $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;

    // 7. Sales by Category
    $categorySalesStmt = $pdo->query("
        SELECT 
            c.category_id,
            c.category_name,
            COUNT(DISTINCT p.product_id) AS product_count,
            COALESCE(SUM(oi.quantity), 0) AS units_sold,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS category_revenue
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        GROUP BY c.category_id, c.category_name
        ORDER BY category_revenue DESC
    ");
    $categorySales = $categorySalesStmt->fetchAll();

    // 8. Orders by Status
    $statusStmt = $pdo->query("
        SELECT 
            status,
            COUNT(*) AS count
        FROM orders
        GROUP BY status
    ");
    $ordersByStatus = $statusStmt->fetchAll();

    // 9. Revenue timeline / trend (by order_date or month)
    $timelineStmt = $pdo->query("
        SELECT 
            DATE_FORMAT(o.order_date, '%b %d, %Y') as formatted_date,
            o.order_date,
            COUNT(DISTINCT o.order_id) as order_count,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) as daily_revenue
        FROM orders o
        JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_date
        ORDER BY o.order_date ASC
    ");
    $revenueTimeline = $timelineStmt->fetchAll();

    // 10. Top 5 Best Selling Products
    $topProductsStmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            c.category_name,
            p.price,
            p.stock_quantity,
            COALESCE(SUM(oi.quantity), 0) AS total_sold,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_sales
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        GROUP BY p.product_id, p.product_name, c.category_name, p.price, p.stock_quantity
        ORDER BY total_sales DESC
        LIMIT 5
    ");
    $topProducts = $topProductsStmt->fetchAll();

    // 11. Recent 5 Orders
    $recentOrdersStmt = $pdo->query("
        SELECT 
            o.order_id,
            o.order_date,
            o.status,
            CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
            c.email AS customer_email,
            COUNT(oi.order_item_id) AS items_count,
            COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_amount
        FROM orders o
        JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_id, o.order_date, o.status, customer_name, customer_email
        ORDER BY o.order_date DESC, o.order_id DESC
        LIMIT 5
    ");
    $recentOrders = $recentOrdersStmt->fetchAll();

    // 12. Low stock alert items (< 50)
    $lowStockStmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.stock_quantity,
            p.price,
            c.category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.stock_quantity <= 50
        ORDER BY p.stock_quantity ASC
        LIMIT 6
    ");
    $lowStockProducts = $lowStockStmt->fetchAll();

    sendJsonResponse([
        'success' => true,
        'data' => [
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_customers' => $totalCustomers,
                'total_products' => (int)$prodStats['total_products'],
                'active_products' => (int)$prodStats['active_products'],
                'low_stock_count' => (int)$prodStats['low_stock_count'],
                'total_categories' => $totalCategories,
                'average_order_value' => $aov
            ],
            'category_sales' => $categorySales,
            'orders_by_status' => $ordersByStatus,
            'revenue_timeline' => $revenueTimeline,
            'top_products' => $topProducts,
            'recent_orders' => $recentOrders,
            'low_stock_products' => $lowStockProducts,
            'db_name' => 'shop_db',
            'server_time' => date('Y-m-d H:i:s')
        ]
    ]);
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ], 500);
}
