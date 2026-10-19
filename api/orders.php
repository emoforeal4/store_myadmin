<?php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            // Single order detail
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $orderId = (int)$_GET['id'];
                
                $orderStmt = $pdo->prepare("
                    SELECT 
                        o.order_id,
                        o.customer_id,
                        o.order_date,
                        o.status,
                        CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                        c.email AS customer_email,
                        c.phone AS customer_phone,
                        c.city AS customer_city,
                        c.country AS customer_country
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.customer_id
                    WHERE o.order_id = ?
                ");
                $orderStmt->execute([$orderId]);
                $order = $orderStmt->fetch();

                if (!$order) {
                    sendJsonResponse(['success' => false, 'message' => 'Order not found'], 404);
                }

                $itemsStmt = $pdo->prepare("
                    SELECT 
                        oi.order_item_id,
                        oi.order_id,
                        oi.product_id,
                        p.product_name,
                        c.category_name,
                        oi.quantity,
                        oi.unit_price,
                        (oi.quantity * oi.unit_price) AS line_total,
                        p.stock_quantity AS current_stock
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.product_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE oi.order_id = ?
                ");
                $itemsStmt->execute([$orderId]);
                $items = $itemsStmt->fetchAll();

                $totalAmount = 0;
                $totalUnits = 0;
                foreach ($items as $item) {
                    $totalAmount += (float)$item['line_total'];
                    $totalUnits += (int)$item['quantity'];
                }

                $order['total_amount'] = $totalAmount;
                $order['total_units'] = $totalUnits;
                $order['items'] = $items;

                sendJsonResponse(['success' => true, 'data' => $order]);
            }

            // List orders
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $customerId = isset($_GET['customer_id']) && is_numeric($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

            $sql = "
                SELECT 
                    o.order_id,
                    o.customer_id,
                    o.order_date,
                    o.status,
                    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                    c.email AS customer_email,
                    c.city AS customer_city,
                    COUNT(oi.order_item_id) AS items_count,
                    COALESCE(SUM(oi.quantity), 0) AS total_units,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_amount
                FROM orders o
                JOIN customers c ON o.customer_id = c.customer_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE 1=1
            ";
            $params = [];

            if ($status !== '' && $status !== 'All') {
                $sql .= " AND o.status = ?";
                $params[] = $status;
            }

            if ($customerId !== null && $customerId > 0) {
                $sql .= " AND o.customer_id = ?";
                $params[] = $customerId;
            }

            if ($search !== '') {
                $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR o.order_id = ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = is_numeric($search) ? (int)$search : -1;
            }

            $sql .= " GROUP BY o.order_id, o.customer_id, o.order_date, o.status, customer_name, customer_email, customer_city ORDER BY o.order_date DESC, o.order_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            sendJsonResponse([
                'success' => true,
                'count' => count($orders),
                'data' => $orders
            ]);
            break;

        case 'POST':
            $input = getJsonInput();
            $customerId = (int)($input['customer_id'] ?? 0);
            $orderDate = !empty($input['order_date']) ? $input['order_date'] : date('Y-m-d');
            $status = !empty($input['status']) ? trim($input['status']) : 'Pending';
            $items = $input['items'] ?? [];

            if ($customerId <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid customer ID is required'], 400);
            }
            if (empty($items) || !is_array($items)) {
                sendJsonResponse(['success' => false, 'message' => 'At least one order item is required'], 400);
            }

            // Begin transaction for order + order_items + stock update
            $pdo->beginTransaction();

            // Verify customer
            $custCheck = $pdo->prepare("SELECT customer_id FROM customers WHERE customer_id = ?");
            $custCheck->execute([$customerId]);
            if (!$custCheck->fetch()) {
                $pdo->rollBack();
                sendJsonResponse(['success' => false, 'message' => 'Customer does not exist'], 400);
            }

            // Insert order
            $orderStmt = $pdo->prepare("INSERT INTO orders (customer_id, order_date, status) VALUES (?, ?, ?)");
            $orderStmt->execute([$customerId, $orderDate, $status]);
            $orderId = (int)$pdo->lastInsertId();

            // Insert items and decrease stock
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                VALUES (?, ?, ?, ?)
            ");
            $stockStmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = GREATEST(0, stock_quantity - ?)
                WHERE product_id = ?
            ");

            foreach ($items as $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 1);
                $unitPrice = (float)($item['unit_price'] ?? 0);

                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                // If unit price not provided or 0, fetch current product price
                if ($unitPrice <= 0) {
                    $prodPriceStmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");
                    $prodPriceStmt->execute([$productId]);
                    $unitPrice = (float)$prodPriceStmt->fetchColumn();
                }

                $itemStmt->execute([$orderId, $productId, $quantity, $unitPrice]);
                $stockStmt->execute([$quantity, $productId]);
            }

            $pdo->commit();

            sendJsonResponse([
                'success' => true,
                'message' => 'Order created successfully',
                'order_id' => $orderId
            ], 201);
            break;

        case 'PUT':
            $input = getJsonInput();
            $orderId = (int)($input['order_id'] ?? 0);
            $newStatus = trim($input['status'] ?? '');

            if ($orderId <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid order ID required'], 400);
            }

            if ($newStatus === '') {
                sendJsonResponse(['success' => false, 'message' => 'New status required'], 400);
            }

            $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
            if (!in_array($newStatus, $validStatuses)) {
                sendJsonResponse(['success' => false, 'message' => 'Invalid status value'], 400);
            }

            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$newStatus, $orderId]);

            sendJsonResponse([
                'success' => true,
                'message' => "Order #$orderId status updated to $newStatus"
            ]);
            break;

        case 'DELETE':
            $input = getJsonInput();
            $orderId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['order_id'] ?? 0);

            if ($orderId <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid order ID required'], 400);
            }

            $pdo->beginTransaction();
            // Delete order items first
            $delItems = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $delItems->execute([$orderId]);

            // Delete order
            $delOrder = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
            $delOrder->execute([$orderId]);

            $pdo->commit();

            sendJsonResponse([
                'success' => true,
                'message' => "Order #$orderId and its items were deleted successfully"
            ]);
            break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
