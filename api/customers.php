<?php
require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $customerId = (int)$_GET['id'];
                $stmt = $pdo->prepare("
                    SELECT 
                        c.*,
                        COUNT(DISTINCT o.order_id) AS total_orders,
                        COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_spent,
                        MAX(o.order_date) AS last_order_date
                    FROM customers c
                    LEFT JOIN orders o ON c.customer_id = o.customer_id
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE c.customer_id = ?
                    GROUP BY c.customer_id
                ");
                $stmt->execute([$customerId]);
                $customer = $stmt->fetch();
                if (!$customer) {
                    sendJsonResponse(['success' => false, 'message' => 'Customer not found'], 404);
                }

                // Also fetch their order history
                $histStmt = $pdo->prepare("
                    SELECT 
                        o.order_id,
                        o.order_date,
                        o.status,
                        COUNT(oi.order_item_id) as items_count,
                        COALESCE(SUM(oi.quantity * oi.unit_price), 0) as total_amount
                    FROM orders o
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id
                    WHERE o.customer_id = ?
                    GROUP BY o.order_id, o.order_date, o.status
                    ORDER BY o.order_date DESC
                ");
                $histStmt->execute([$customerId]);
                $customer['orders_history'] = $histStmt->fetchAll();

                sendJsonResponse(['success' => true, 'data' => $customer]);
            }

            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $city = isset($_GET['city']) ? trim($_GET['city']) : '';

            $sql = "
                SELECT 
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    CONCAT(c.first_name, ' ', c.last_name) AS full_name,
                    c.email,
                    c.phone,
                    c.city,
                    c.country,
                    c.created_at,
                    COUNT(DISTINCT o.order_id) AS total_orders,
                    COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS total_spent,
                    MAX(o.order_date) AS last_order_date
                FROM customers c
                LEFT JOIN orders o ON c.customer_id = o.customer_id
                LEFT JOIN order_items oi ON o.order_id = oi.order_id
                WHERE 1=1
            ";
            $params = [];

            if ($search !== '') {
                $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }

            if ($city !== '') {
                $sql .= " AND c.city = ?";
                $params[] = $city;
            }

            $sql .= " GROUP BY c.customer_id ORDER BY total_spent DESC, c.customer_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $customers = $stmt->fetchAll();

            sendJsonResponse([
                'success' => true,
                'count' => count($customers),
                'data' => $customers
            ]);
            break;

        case 'POST':
            $input = getJsonInput();
            $firstName = trim($input['first_name'] ?? '');
            $lastName = trim($input['last_name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $city = trim($input['city'] ?? '');
            $country = !empty($input['country']) ? trim($input['country']) : 'Malaysia';

            if ($firstName === '' || $lastName === '') {
                sendJsonResponse(['success' => false, 'message' => 'First and last name are required'], 400);
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendJsonResponse(['success' => false, 'message' => 'A valid email address is required'], 400);
            }

            // Check email uniqueness
            $chk = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                sendJsonResponse(['success' => false, 'message' => 'A customer with this email already exists'], 409);
            }

            $stmt = $pdo->prepare("
                INSERT INTO customers (first_name, last_name, email, phone, city, country, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$firstName, $lastName, $email, $phone, $city, $country]);
            $newId = (int)$pdo->lastInsertId();

            sendJsonResponse([
                'success' => true,
                'message' => 'Customer created successfully',
                'customer_id' => $newId
            ], 201);
            break;

        case 'PUT':
            $input = getJsonInput();
            $id = (int)($input['customer_id'] ?? 0);
            if ($id <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid customer ID required'], 400);
            }

            $firstName = trim($input['first_name'] ?? '');
            $lastName = trim($input['last_name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $city = trim($input['city'] ?? '');
            $country = !empty($input['country']) ? trim($input['country']) : 'Malaysia';

            if ($firstName === '' || $lastName === '') {
                sendJsonResponse(['success' => false, 'message' => 'First and last name are required'], 400);
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendJsonResponse(['success' => false, 'message' => 'A valid email address is required'], 400);
            }

            // Check email uniqueness for other customers
            $chk = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ? AND customer_id != ?");
            $chk->execute([$email, $id]);
            if ($chk->fetch()) {
                sendJsonResponse(['success' => false, 'message' => 'Another customer already has this email'], 409);
            }

            $stmt = $pdo->prepare("
                UPDATE customers 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, city = ?, country = ?
                WHERE customer_id = ?
            ");
            $stmt->execute([$firstName, $lastName, $email, $phone, $city, $country, $id]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Customer details updated successfully'
            ]);
            break;

        case 'DELETE':
            $input = getJsonInput();
            $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($input['customer_id'] ?? 0);

            if ($id <= 0) {
                sendJsonResponse(['success' => false, 'message' => 'Valid customer ID required'], 400);
            }

            // Check if customer has orders
            $chk = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
            $chk->execute([$id]);
            $orderCount = (int)$chk->fetchColumn();

            if ($orderCount > 0) {
                sendJsonResponse([
                    'success' => false,
                    'message' => "Cannot delete customer because they have $orderCount linked order(s). Delete their orders first or keep customer history intact."
                ], 409);
            }

            $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
            $stmt->execute([$id]);

            sendJsonResponse([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
            break;

        default:
            sendJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
