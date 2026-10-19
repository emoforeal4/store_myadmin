<?php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'presets') {
    // Return handy preset queries
    $presets = [
        [
            'title' => 'Top 5 Best Selling Products by Revenue',
            'category' => 'Analytics',
            'sql' => "SELECT p.product_id, p.product_name, c.category_name, p.price, SUM(oi.quantity) AS total_units_sold, ROUND(SUM(oi.quantity * oi.unit_price), 2) AS total_revenue\nFROM products p\nJOIN categories c ON p.category_id = c.category_id\nJOIN order_items oi ON p.product_id = oi.product_id\nGROUP BY p.product_id\nORDER BY total_revenue DESC\nLIMIT 5;"
        ],
        [
            'title' => 'Customer Lifetime Value & Total Orders',
            'category' => 'Customers',
            'sql' => "SELECT c.customer_id, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, c.email, c.city, c.country, COUNT(DISTINCT o.order_id) AS total_orders, ROUND(COALESCE(SUM(oi.quantity * oi.unit_price), 0), 2) AS lifetime_spent\nFROM customers c\nLEFT JOIN orders o ON c.customer_id = o.customer_id\nLEFT JOIN order_items oi ON o.order_id = oi.order_id\nGROUP BY c.customer_id\nORDER BY lifetime_spent DESC;"
        ],
        [
            'title' => 'Category Sales & Inventory Health',
            'category' => 'Inventory',
            'sql' => "SELECT c.category_name, COUNT(DISTINCT p.product_id) AS product_count, SUM(p.stock_quantity) AS stock_on_hand, ROUND(SUM(p.stock_quantity * p.price), 2) AS inventory_value, ROUND(COALESCE(SUM(oi.quantity * oi.unit_price), 0), 2) AS revenue_generated\nFROM categories c\nLEFT JOIN products p ON c.category_id = p.category_id\nLEFT JOIN order_items oi ON p.product_id = oi.product_id\nGROUP BY c.category_id, c.category_name\nORDER BY revenue_generated DESC;"
        ],
        [
            'title' => 'Recent Orders with Full Customer Details',
            'category' => 'Orders',
            'sql' => "SELECT o.order_id, o.order_date, o.status, CONCAT(c.first_name, ' ', c.last_name) AS customer_name, c.email, c.city, COUNT(oi.order_item_id) AS items_count, ROUND(SUM(oi.quantity * oi.unit_price), 2) AS order_total\nFROM orders o\nJOIN customers c ON o.customer_id = c.customer_id\nLEFT JOIN order_items oi ON o.order_id = oi.order_id\nGROUP BY o.order_id\nORDER BY o.order_date DESC, o.order_id DESC\nLIMIT 10;"
        ],
        [
            'title' => 'Low Stock Alert (< 50 items in stock)',
            'category' => 'Inventory',
            'sql' => "SELECT p.product_id, p.product_name, c.category_name, p.price, p.stock_quantity, CASE WHEN p.stock_quantity = 0 THEN 'OUT OF STOCK' ELSE 'LOW STOCK' END AS stock_status\nFROM products p\nJOIN categories c ON p.category_id = c.category_id\nWHERE p.stock_quantity <= 50\nORDER BY p.stock_quantity ASC;"
        ],
        [
            'title' => 'Database Tables & Row Counts (shop_db)',
            'category' => 'System',
            'sql' => "SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, CREATE_TIME\nFROM information_schema.TABLES\nWHERE TABLE_SCHEMA = 'shop_db'\nORDER BY TABLE_NAME ASC;"
        ]
    ];
    sendJsonResponse(['success' => true, 'presets' => $presets]);
}

if ($method !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'POST method required'], 405);
}

$input = getJsonInput();
$sql = trim($input['query'] ?? '');

if ($sql === '') {
    sendJsonResponse(['success' => false, 'message' => 'SQL query cannot be empty'], 400);
}

// Disallow dangerous multi-query drops if needed, but allow SELECT, SHOW, DESCRIBE, EXPLAIN, INSERT, UPDATE
$firstWord = strtoupper(strtok($sql, " \t\n\r"));
$isSelectType = in_array($firstWord, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN']);

$startTime = microtime(true);

try {
    $stmt = $pdo->query($sql);
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);

    if ($isSelectType) {
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowCount = count($rows);
        $columns = [];
        if ($rowCount > 0) {
            $columns = array_keys($rows[0]);
        } elseif ($stmt->columnCount() > 0) {
            for ($i = 0; $i < $stmt->columnCount(); $i++) {
                $colMeta = $stmt->getColumnMeta($i);
                $columns[] = $colMeta['name'];
            }
        }

        sendJsonResponse([
            'success' => true,
            'is_select' => true,
            'execution_time_ms' => $executionTime,
            'row_count' => $rowCount,
            'columns' => $columns,
            'data' => $rows
        ]);
    } else {
        $affectedRows = $stmt->rowCount();
        sendJsonResponse([
            'success' => true,
            'is_select' => false,
            'execution_time_ms' => $executionTime,
            'affected_rows' => $affectedRows,
            'message' => "Query executed successfully ($affectedRows row(s) affected in {$executionTime}ms)"
        ]);
    }
} catch (PDOException $e) {
    $executionTime = round((microtime(true) - $startTime) * 1000, 2);
    sendJsonResponse([
        'success' => false,
        'execution_time_ms' => $executionTime,
        'message' => 'SQL Execution Error: ' . $e->getMessage()
    ], 400);
}
