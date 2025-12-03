<?php
// тут не хватает многих проверок для продакшена, потом зарефакторить с остальными api
session_start();
require_once __DIR__ . '/helpers.php';
header('Content-Type: application/json');

$connect = getDB();
if (!$connect) {
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $delivery_type = $data['delivery_type'] ?? 'delivery';
    $user_id = $_SESSION['user']['id'] ?? null;

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Пользователь не авторизован']);
        exit;
    }

    // получаем данные заказа перед обновлением
    $stmt = $connect->prepare("SELECT total_price, delivery_cost FROM orders WHERE user_id = ? AND status = 'cart'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса']);
        exit;
    }

    $current_total_price = $order['total_price'];
    $current_delivery_cost = $order['delivery_cost'];
    $current_products_cost = $current_total_price - $current_delivery_cost;

    if ($delivery_type == 'delivery') {
        if ($current_products_cost < 5000) {
            $new_total_price = $current_products_cost + 750;
            $delivery_cost = 750;
        } else {
            $new_total_price = $current_products_cost;
            $delivery_cost = 0;
        }
    } else {
        $new_total_price = $current_products_cost;
        $delivery_cost = 0;
    }

    // меняем тип доставки и адрес на тот что был  
    $stmt = $connect->prepare("UPDATE orders SET total_price = ?, delivery_type = ?, delivery_cost = ?, delivery_address_id = NULL, store_id = NULL WHERE user_id = ? AND status = 'cart'");
    
    if ($stmt) {
        $stmt->bind_param("dsdi", $new_total_price, $delivery_type, $delivery_cost, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка выполнения запроса']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка подготовки запроса']);
    }
}

$connect->close();
?>