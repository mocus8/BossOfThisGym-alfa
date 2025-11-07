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

    // меняем тип доставки и адрес на тот что был  
    $stmt = $connect->prepare("UPDATE orders SET delivery_address_id = NULL, store_id = NULL, delivery_type = ? WHERE user_id = ? AND status = 'cart'");
    
    if ($stmt) {
        $stmt->bind_param("si", $delivery_type, $user_id);
        
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