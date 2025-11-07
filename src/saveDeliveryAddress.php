<?php
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

    // Получаем текущий заказ
    $stmt = $connect->prepare("SELECT order_id FROM orders WHERE user_id = ? AND status = 'cart'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if ($order) {
        $order_id = $order['order_id'];
        
        if ($delivery_type === 'delivery') {
        // ДОСТАВКА - сохраняем адрес пользователя
            $address = $data['address'] ?? '';
            $postalCode = $data['postalCode'] ?? '';
            $stmt = $connect->prepare("SELECT id FROM delivery_addresses WHERE user_id = ? AND address_line = ?");
            $stmt->bind_param("is", $user_id, $address);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_address = $result->fetch_assoc();

            if ($existing_address) {
                $address_id = $existing_address['id'];
            } else {
                //ВОТ ЗДЕСЬ НУЖНО БУДЕТ ДОБАВИТЬ postal_code
                $stmt = $connect->prepare("INSERT INTO delivery_addresses (user_id, address_line, postal_code) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $user_id, $address, $postalCode);
                $stmt->execute();
                $address_id = $connect->insert_id;
            }
            
            // Обновляем заказ: активируем доставку, деактивируем самовывоз
            $stmt = $connect->prepare("UPDATE orders SET delivery_type = 'delivery', delivery_address_id = ?, store_id = NULL WHERE order_id = ?");
            $stmt->bind_param("ii", $address_id, $order_id);
            
        } else {
        // САМОВЫВОЗ
            $store_id = $data['store_id'] ?? 0;

            if (!$store_id) {
                echo json_encode(['success' => false, 'message' => 'Магазин не выбран']);
                exit;
            }

            // Проверяем что магазин существует
            $stmt = $connect->prepare("SELECT id FROM stores WHERE id = ?");
            $stmt->bind_param("i", $store_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $store = $result->fetch_assoc();
            
            if (!$store) {
                echo json_encode(['success' => false, 'message' => 'Магазин не найден']);
                exit;
            }

            // Обновляем заказ: активируем самовывоз, деактивируем доставку
            $stmt = $connect->prepare("UPDATE orders SET delivery_type = 'pickup', delivery_address_id = NULL, store_id = ? WHERE order_id = ?");
            $stmt->bind_param("ii", $store_id, $order_id);
        }
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка сохранения адреса']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Заказ не найден']);
    }
}

$connect->close();
?>