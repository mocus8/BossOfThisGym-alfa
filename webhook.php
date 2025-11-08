<?php

//с логами от сика
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';

// Включаем логирование
file_put_contents(__DIR__ . '/webhook.log', date('Y-m-d H:i:s') . " - Webhook called\n", FILE_APPEND);

// Получаем JSON из тела запроса
$input = file_get_contents('php://input');
file_put_contents(__DIR__ . '/webhook.log', "Raw input: " . $input . "\n", FILE_APPEND);

if (empty($input)) {
    file_put_contents(__DIR__ . '/webhook.log', "ERROR: Empty input\n", FILE_APPEND);
    http_response_code(400);
    exit('Empty request');
}

$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    file_put_contents(__DIR__ . '/webhook.log', "ERROR: Invalid JSON\n", FILE_APPEND);
    http_response_code(400);
    exit('Invalid JSON');
}

// Проверяем тип события
if (isset($data['event']) && $data['event'] === 'payment.succeeded') {
    $payment = $data['object'];
    $orderId = $payment['metadata']['orderId'] ?? null;
    $paymentId = $payment['id'] ?? 'unknown';
    
    file_put_contents(__DIR__ . '/webhook.log', "Processing payment: payment_id=$paymentId, order_id=" . ($orderId ?? 'NULL') . "\n", FILE_APPEND);
    
    if (!$orderId) {
        file_put_contents(__DIR__ . '/webhook.log', "ERROR: No orderId in metadata\n", FILE_APPEND);
        http_response_code(200); // Все равно возвращаем 200 для ЮКассы
        exit('OK');
    }
    
    try {
        $connect = getDB();
        if (!$connect) {
            throw new Exception('Database connection failed');
        }
        
        // ОБНОВЛЯЕМ СТАТУС ЗАКАЗА - исправленный запрос
        $stmt = $connect->prepare("UPDATE orders SET paid_at = NOW(), status = 'paid' WHERE order_id = ?");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $connect->error);
        }
        
        $stmt->bind_param("i", $orderId);
        $result = $stmt->execute();
        
        if ($result) {
            file_put_contents(__DIR__ . '/webhook.log', "SUCCESS: Order $orderId updated to paid\n", FILE_APPEND);
        } else {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        $connect->close();
        
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/webhook.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    }
} else {
    file_put_contents(__DIR__ . '/webhook.log', "Ignored event: " . ($data['event'] ?? 'unknown') . "\n", FILE_APPEND);
}

// Всегда возвращаем 200 OK для ЮКассы
http_response_code(200);
echo 'OK';














//нормальная версия без логов 
// require_once __DIR__ . '/helpers.php';

// $input = file_get_contents('php://input');
// $data = json_decode($input, true);

// if ($data['event'] === 'payment.succeeded') {
//     $payment = $data['object'];
//     $orderId = $payment['metadata']['orderId'];
    
//     // Обновляем статус заказа
//     $connect = getDB();
//     $stmt = $connect->prepare("UPDATE orders SET paid_at = NOW(), status = 'paid' WHERE order_id = ?");
//     $stmt->bind_param("i", $orderId);
//     $stmt->execute();
// }

// http_response_code(200);
?>