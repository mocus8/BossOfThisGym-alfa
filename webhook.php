<?php
require_once __DIR__ . '/helpers.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data['event'] === 'payment.succeeded') {
    $payment = $data['object'];
    $orderId = $payment['metadata']['orderId'];
    
    // Обновляем статус заказа
    $connect = getDB();
    $stmt = $connect->prepare("UPDATE orders SET paid_at = NOW(), SET status = 'paid' WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
}

http_response_code(200);
?>