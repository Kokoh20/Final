<?php
declare(strict_types=1);
require __DIR__ . '/_utils.php';
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->query('SELECT * FROM orders ORDER BY datetime(created_at) DESC');
    $orders = $stmt->fetchAll();
    // Load items for each order
    foreach ($orders as &$order) {
        $items = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$order['id']]);
        $orderItems = $items->fetchAll();
        foreach ($orderItems as &$it) {
            $xe = $pdo->prepare('SELECT * FROM order_item_extras WHERE order_item_id = ?');
            $xe->execute([$it['id']]);
            $it['extras'] = $xe->fetchAll();
        }
        $order['items'] = $orderItems;
        $order['totals'] = [
            'subtotal' => (float)$order['subtotal'],
            'discount' => (float)$order['discount'],
            'payable' => (float)$order['total']
        ];
    }
    send_json(['orders' => $orders]);
}

if ($method === 'POST') {
    $payload = get_json_input();

    // Very light validation
    $customer = $payload['customer'] ?? [];
    $items = $payload['items'] ?? [];
    $totals = $payload['totals'] ?? [];

    if (!$customer || !$items) {
        send_json([ 'error' => 'Missing customer or items' ], 422);
    }

    $code = substr(strtoupper(bin2hex(random_bytes(6))), 0, 12);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('INSERT INTO orders (code, status, order_type, customer_name, customer_phone, customer_address, subtotal, discount, total) VALUES (?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $code,
            'new',
            (string)($customer['type'] ?? 'pickup'),
            (string)($customer['name'] ?? ''),
            (string)($customer['phone'] ?? ''),
            (string)($customer['address'] ?? ''),
            (float)($totals['subtotal'] ?? 0),
            (float)($totals['discount'] ?? 0),
            (float)($totals['payable'] ?? 0)
        ]);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity, notes) VALUES (?,?,?,?,?,?)');
        $extraStmt = $pdo->prepare('INSERT INTO order_item_extras (order_item_id, extra_id, extra_name, unit_price, quantity) VALUES (?,?,?,?,?)');

        foreach ($items as $it) {
            $productId = null; // could resolve from slug if provided; keeping null for simplicity
            $itemStmt->execute([$orderId, $productId, (string)$it['name'], (float)$it['price'], (int)$it['qty'], (string)($it['notes'] ?? '')]);
            $orderItemId = (int)$pdo->lastInsertId();
            foreach (($it['extras'] ?? []) as $ex) {
                $extraStmt->execute([$orderItemId, null, (string)$ex['name'], (float)$ex['price'], (int)$ex['qty']]);
            }
        }

        $pdo->commit();

        send_json([ 'ok' => true, 'order' => [ 'id' => $orderId, 'code' => $code ] ]);
    } catch (Throwable $e) {
        $pdo->rollBack();
        send_json([ 'error' => 'Failed to save order', 'detail' => $e->getMessage() ], 500);
    }
}

send_json([ 'error' => 'Method not allowed' ], 405);

