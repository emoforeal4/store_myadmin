<?php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query("
                SELECT 
                    c.category_id,
                    c.category_name,
                    c.description,
                    COUNT(p.product_id) AS product_count,
                    COALESCE(SUM(p.stock_quantity), 0) AS total_inventory,
                    COALESCE(SUM(p.price * p.stock_quantity), 0) AS total_inventory_value,
                    COALESCE(SUM(oi.quantity), 0) AS total_units_sold,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_revenue
                FROM categories c
                LEFT JOIN products p ON c.category_id = p.category_id
                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                GROUP BY c.category_id, c.category_name, c.description
                ORDER BY c.category_name ASC
            ");
            $categories = $stmt->fetchAll();

            sendJsonResponse([
                'success' => true,
                'count' => count($categories),
                'data' => $categories
            ]);
            break;

        case 'POST':
            $input = getJsonInput();
            $name = trim($input['category_name'] ?? '');
            $desc = trim($input['description'] ?? '');

            if ($name === '') {
                sendJsonResponse(['success' => false, 'message' => 'Category name is required'], 400);
            }

            // Check unique name
            $chk = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                sendJsonResponse(['success' => false, 'message' => 'Category already exists'], 409);
            }

            $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Category created successfully',
                'category_id' => (int)$pdo->lastInsertId()
            ], 201);
            break;

        case 'PUT':
            $input = getJsonInput();
            $id = (int)($input['category_id'] ?? 0);
            $name = trim($input['category_name'] ?? '');
            $desc = trim($input['description'] ?? '');

            if ($id <= 0 || $name === '') {
                sendJsonResponse(['success' => false, 'message' => 'Valid ID and name required'], 400);
            }

            $chk = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
            $chk->execute([$name, $id]);
            if ($chk->fetch()) {
                sendJsonResponse(['success' => false, 'message' => 'Another category already has this name'], 409);
            }

            $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
            $stmt->execute([$name, $desc, $id]);

            sendJsonResponse(['success' => true, 'message' => 'Category updated successfully']);
            break;

        case 'DELETE':
            $input = getJsonInput();
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['category_id'] ?? 0);

            if ($id <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid category ID required'], 400);
            }

            // Check if products exist in category
            $chk = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $chk->execute([$id]);
            $count = (int)$chk->fetchColumn();

            if ($count > 0) {
                sendJsonResponse([
                    'success' => false,
                    'message' => "Cannot delete category because $count product(s) are assigned to it. Reassign or delete those products first."
                ], 409);
            }

            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$id]);

            sendJsonResponse(['success' => true, 'message' => 'Category deleted successfully']);
            break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
