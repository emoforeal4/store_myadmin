<?php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            // Check if single product requested
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $stmt = $pdo->prepare("
                    SELECT 
                        p.*,
                        c.category_name,
                        COALESCE(SUM(oi.quantity), 0) AS units_sold,
                        COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    LEFT JOIN order_items oi ON p.product_id = oi.product_id
                    WHERE p.product_id = ?
                    GROUP BY p.product_id
                ");
                $stmt->execute([(int)$_GET['id']]);
                $product = $stmt->fetch();
                if (!$product) {
                    sendJsonResponse(['success' => false, 'message' => 'Product not found'], 404);
                }
                sendJsonResponse(['success' => true, 'data' => $product]);
            }

            // List products with optional search and filters
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;
            $stockFilter = $_GET['stock'] ?? 'all';
            $statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;

            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category_id,
                    c.category_name,
                    p.price,
                    p.stock_quantity,
                    p.is_active,
                    p.description,
                    COALESCE(SUM(oi.quantity), 0) AS units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                WHERE 1=1
            ";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if ($categoryId !== null && $categoryId > 0) {
                $sql .= " AND p.category_id = ?";
                $params[] = $categoryId;
            }

            if ($stockFilter === 'out') {
                $sql .= " AND p.stock_quantity = 0";
            } elseif ($stockFilter === 'low') {
                $sql .= " AND p.stock_quantity > 0 AND p.stock_quantity <= 30";
            } elseif ($stockFilter === 'in') {
                $sql .= " AND p.stock_quantity > 30";
            }

            if ($statusFilter !== null) {
                $sql .= " AND p.is_active = ?";
                $params[] = $statusFilter;
            }

            $sql .= " GROUP BY p.product_id ORDER BY p.product_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll();

            sendJsonResponse([
                'success' => true,
                'count' => count($products),
                'data' => $products
            ]);
            break;

        case 'POST':
            $input = getJsonInput();
            $name = trim($input['product_name'] ?? '');
            $categoryId = (int)($input['category_id'] ?? 0);
            $price = (float)($input['price'] ?? 0);
            $stock = (int)($input['stock_quantity'] ?? 0);
            $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
            $description = trim($input['description'] ?? '');

            if ($name === '') {
                sendJsonResponse(['success' => false, 'message' => 'Product name is required'], 400);
            }
            if ($categoryId <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid category must be selected'], 400);
            }
            if ($price < 0) {
                sendJsonResponse(['success' => false, 'message' => 'Price cannot be negative'], 400);
            }

            // Verify category exists
            $catCheck = $pdo->prepare("SELECT category_id FROM categories WHERE category_id = ?");
            $catCheck->execute([$categoryId]);
            if (!$catCheck->fetch()) {
                sendJsonResponse(['success' => false, 'message' => 'Category does not exist'], 400);
            }

            $stmt = $pdo->prepare("
                INSERT INTO products (product_name, category_id, price, stock_quantity, is_active, description)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $categoryId, $price, $stock, $isActive, $description]);
            $newId = (int)$pdo->lastInsertId();

            sendJsonResponse([
                'success' => true,
                'message' => 'Product created successfully',
                'product_id' => $newId
            ], 201);
            break;

        case 'PUT':
            $input = getJsonInput();
            $id = (int)($input['product_id'] ?? 0);
            if ($id <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid product ID required'], 400);
            }

            $name = trim($input['product_name'] ?? '');
            $categoryId = (int)($input['category_id'] ?? 0);
            $price = (float)($input['price'] ?? 0);
            $stock = (int)($input['stock_quantity'] ?? 0);
            $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
            $description = trim($input['description'] ?? '');

            if ($name === '') {
                sendJsonResponse(['success' => false, 'message' => 'Product name cannot be empty'], 400);
            }

            $stmt = $pdo->prepare("
                UPDATE products 
                SET product_name = ?, category_id = ?, price = ?, stock_quantity = ?, is_active = ?, description = ?
                WHERE product_id = ?
            ");
            $stmt->execute([$name, $categoryId, $price, $stock, $isActive, $description, $id]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Product updated successfully'
            ]);
            break;

        case 'DELETE':
            $input = getJsonInput();
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['product_id'] ?? 0);

            if ($id <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid product ID required'], 400);
            }

            // Check if product is in order_items
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $checkStmt->execute([$id]);
            $inOrders = (int)$checkStmt->fetchColumn();

            if ($inOrders > 0) {
                // Cannot delete directly due to foreign key constraint
                // Suggest setting inactive or provide friendly warning
                sendJsonResponse([
                    'success' => false,
                    'message' => "Cannot delete product because it is associated with $inOrders order item(s). You can mark it inactive instead."
                ], 409);
            }

            $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$id]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
            break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
