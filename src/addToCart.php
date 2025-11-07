<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Получаем ID товара из POST запроса
$productId = $_POST['product_id'] ?? null;

// Проверяем что ID товара передан
if (!$productId) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Не указан id товара'
    ]);
    exit;
}

// Преобразуем в число для безопасности
$productId = (int)$productId;

// Подключаемся к базе данных
$connect = getDB();
$cartSessionId = getCartSessionId(); // Получаем ID корзины из куков
$userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;

// 1. ПРОВЕРЯЕМ СУЩЕСТВОВАНИЕ ТОВАРА
$stmt = $connect->prepare("SELECT id, price, name FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$productResult = $stmt->get_result();

if ($productResult->num_rows === 0) {
    http_response_code(404); // Not Found
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'товар не найден'
    ]);
    exit;
}

$product = $productResult->fetch_assoc();

// 2. НАХОДИМ ИЛИ СОЗДАЕМ КОРЗИНУ (ЗАКАЗ СО СТАТУСОМ 'cart')
if ($userId) {
    // Для авторизованного пользователя ищем по user_id
    $stmt = $connect->prepare("SELECT id FROM orders WHERE user_id = ? AND status = 'cart'");
    $stmt->bind_param("i", $userId);
} else {
    // Для гостя ищем по session_id
    $stmt = $connect->prepare("SELECT id FROM orders WHERE session_id = ? AND status = 'cart'");
    $stmt->bind_param("s", $cartSessionId);
}

$stmt->execute();
$orderResult = $stmt->get_result();
$order = $orderResult->fetch_assoc();

// Если корзина не найдена - создаем новую
if (!$order) {
    if ($userId) {
        $stmt = $connect->prepare("INSERT INTO orders (user_id, session_id, status, total_price) VALUES (?, NULL, 'cart', 0)");
        $stmt->bind_param("i", $userId);
    } else {
        $stmt = $connect->prepare("INSERT INTO orders (user_id, session_id, status, total_price) VALUES (NULL, ?, 'cart', 0)");
        $stmt->bind_param("s", $cartSessionId);
    }
    $stmt->execute();
    $orderId = $stmt->insert_id;
} else {
    $orderId = $order['id'];
}

// 3. ДОБАВЛЯЕМ ТОВАР В КОРЗИНУ
// Сначала проверяем, нет ли уже этого товара в корзине
$stmt = $connect->prepare("SELECT id, quantity FROM product_order WHERE order_id = ? AND product_id = ?");
$stmt->bind_param("ii", $orderId, $productId);
$stmt->execute();
$existingItem = $stmt->get_result()->fetch_assoc();

if ($existingItem) {
    // Если товар уже есть - увеличиваем количество
    $newQuantity = $existingItem['quantity'] + 1;
    $stmt = $connect->prepare("UPDATE product_order SET quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $newQuantity, $existingItem['id']);
} else {
    // Если товара нет - добавляем новый
    $stmt = $connect->prepare("INSERT INTO product_order (order_id, product_id, quantity, price_at_time) VALUES (?, ?, 1, ?)");
    $stmt->bind_param("iid", $orderId, $productId, $product['price']);
}

$stmt->execute();

// 4. ОБНОВЛЯЕМ ОБЩУЮ СУММУ ЗАКАЗА
$stmt = $connect->prepare("UPDATE orders SET total_price = (SELECT SUM(quantity * price_at_time) FROM product_order WHERE order_id = ?) WHERE id = ?");
$stmt->bind_param("ii", $orderId, $orderId);
$stmt->execute();

// Успешный ответ
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Товар "' . $product['name'] . '" добавлен в корзину!',
    'product_id' => $productId
]);
?>