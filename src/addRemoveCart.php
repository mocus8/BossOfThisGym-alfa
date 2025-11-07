<?php
session_start();
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

// ✅ ПРОВЕРКА
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Только POST запросы разрешены']);
    exit;
}

// Получаем данные из POST
$productId = (int)($_POST['product_id'] ?? 0);
$action = $_POST['action'] ?? '';

// Валидация
if (!$productId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указаны необходимые параметры']);
    exit;
}

// Подключаемся к базе данных
$connect = getDB();
$cartSessionId = getCartSessionId();
$userId = $_SESSION['user']['id'] ?? null;

// 1. ПРОВЕРЯЕМ СУЩЕСТВОВАНИЕ ТОВАРА
$stmt = $connect->prepare("SELECT product_id, price FROM products WHERE product_id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$productResult = $stmt->get_result();

if ($productResult->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

// 2. НАХОДИМ ИЛИ СОЗДАЕМ КОРЗИНУ
if ($userId) {
    $stmt = $connect->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart'");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $connect->prepare("SELECT order_id FROM orders WHERE session_id = ? AND status = 'cart'");
    $stmt->bind_param("s", $cartSessionId);
}

$stmt->execute();
$orderResult = $stmt->get_result();
$order = $orderResult->fetch_assoc();

if (!$order) {
    if ($userId) {
        $stmt = $connect->prepare("INSERT INTO orders (user_id, status, total_price) VALUES (?, 'cart', 0)");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $connect->prepare("INSERT INTO orders (session_id, status, total_price) VALUES (?, 'cart', 0)");
        $stmt->bind_param("s", $cartSessionId);
    }
    $stmt->execute();
    $orderId = $connect->insert_id;
} else {
    $orderId = $order['order_id'];
}

// 3. ОБРАБАТЫВАЕМ ДЕЙСТВИЯ
switch ($action) {
    case 'add_to_cart':
        $stmt = $connect->prepare("SELECT amount FROM product_order WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $orderId, $productId);
        $stmt->execute();
        $existingItem = $stmt->get_result()->fetch_assoc();

        if ($existingItem) {
            $newAmount = $existingItem['amount'] + 1;
            $stmt = $connect->prepare("UPDATE product_order SET amount = ? WHERE order_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $newAmount, $orderId, $productId);
        } else {
            $stmt = $connect->prepare("INSERT INTO product_order (order_id, product_id, amount) VALUES (?, ?, 1)");
            $stmt->bind_param("ii", $orderId, $productId);
        }
        $stmt->execute();
        break;

    case 'subtract_cart':
        $stmt = $connect->prepare("SELECT amount FROM product_order WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $orderId, $productId);
        $stmt->execute();
        $existingItem = $stmt->get_result()->fetch_assoc();

        if (!$existingItem) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Товар не найден в корзине']);
            exit;
        }

        if ($existingItem['amount'] > 1) {
            $newAmount = $existingItem['amount'] - 1;
            $stmt = $connect->prepare("UPDATE product_order SET amount = ? WHERE order_id = ? AND product_id = ?");
            $stmt->bind_param("iii", $newAmount, $orderId, $productId);
        } else {
            $stmt = $connect->prepare("DELETE FROM product_order WHERE order_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $orderId, $productId);
        }
        $stmt->execute();
        break;

    case 'remove_cart':
        $stmt = $connect->prepare("DELETE FROM product_order WHERE order_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $orderId, $productId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Товар не найден в корзине']);
            exit;
        }
        break;
}

// 4. ОБНОВЛЯЕМ ОБЩУЮ СУММУ ЗАКАЗА (ИСПРАВЛЕННАЯ ВЕРСИЯ)
$stmt = $connect->prepare("
    SELECT SUM(p.price * po.amount) as total 
    FROM product_order po 
    JOIN products p ON po.product_id = p.product_id 
    WHERE po.order_id = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$totalResult = $stmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$newTotalPrice = $totalRow['total'] ?? 0;

// Обновляем total_price в orders
$updateStmt = $connect->prepare("UPDATE orders SET total_price = ? WHERE order_id = ?");
$updateStmt->bind_param("di", $newTotalPrice, $orderId);
$updateStmt->execute();
$updateStmt->close();

// 5. ПОЛУЧАЕМ АКТУАЛЬНЫЕ ДАННЫЕ КОРЗИНЫ ДЛЯ ОТВЕТА
$cartItems = [];
$totalCount = 0;
$totalPrice = 0;

if ($userId) {
    $stmt = $connect->prepare("
        SELECT p.product_id, p.name, p.price, po.amount 
        FROM product_order po 
        JOIN products p ON po.product_id = p.product_id 
        JOIN orders o ON po.order_id = o.order_id
        WHERE o.user_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param("i", $userId);
} else {
    $stmt = $connect->prepare("
        SELECT p.product_id, p.name, p.price, po.amount 
        FROM product_order po 
        JOIN products p ON po.product_id = p.product_id 
        JOIN orders o ON po.order_id = o.order_id
        WHERE o.session_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param("s", $cartSessionId);
}

$stmt->execute();
$result = $stmt->get_result();

while ($item = $result->fetch_assoc()) {
    $cartItems[] = [
        'id' => $item['product_id'],
        'name' => $item['name'],
        'price' => $item['price'],
        'amount' => $item['amount'],
        'total' => $item['price'] * $item['amount']
    ];
    $totalCount += $item['amount'];
    $totalPrice += $item['price'] * $item['amount'];
}

// 6. ВОЗВРАЩАЕМ JSON С ДАННЫМИ
echo json_encode([
    'success' => true,
    'cart' => [
        'items' => $cartItems,
        'totalCount' => $totalCount,
        'totalPrice' => $totalPrice
    ]
]);

exit;
?>