<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';

$input = json_decode(file_get_contents('php://input'), true);
$orderId = $input['order_id'] ?? '';
$userId = $_SESSION['user']['id'] ?? null;

if (!$userId || !$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'INVALID_REQUEST']);
    exit();
}

try {
    $connect = getDB();
    if (!$connect) {
        throw new Exception('DATABASE_CONNECT_FAILED');
    }

    $stmt = $connect->prepare("
        SELECT o.order_id, o.total_price, o.status 
        FROM orders o 
        WHERE o.order_id = ? AND o.user_id = ? AND o.status IN ('cart', 'pending_payment')
        AND EXISTS (SELECT 1 FROM product_order po WHERE po.order_id = o.order_id)
    ");
    if (!$stmt) {
        throw new Exception('DATABASE_OPERATIONS_FAILED');
    }
    
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    
    if (!$order) {
        throw new Exception('ORDER_NOT_FOUND');
    }

    if ($order['status'] === 'cart') {
        $updateStmt = $connect->prepare("UPDATE orders SET status = 'pending_payment' WHERE order_id = ?");
        $updateStmt->bind_param("i", $orderId);
        $updateStmt->execute();
        $updateStmt->close();
    }

    $yookassa = new \YooKassa\Client();
    //тут мб тоже менять постоянно придётся
    $yookassa->setAuth('1201088', 'test_rpA9Mys4bSs-45HSfQqF_zzu_8ytngW71Veg_yf9ISA');
    
    $payment = $yookassa->createPayment([
        'amount' => [
            'value' => $order['total_price'],
            'currency' => 'RUB'
        ],
        'confirmation' => [
            'type' => 'redirect',
            //тут менять пока постоянно первую часть
            'return_url' => 'bossofthisgym/404.php?orderId=' . $orderId
        ],
        'capture' => true,
        'description' => 'Заказ №' . $orderId,
        'metadata' => ['orderId' => $orderId]
    ], uniqid('', true));

    echo json_encode([
        'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($connect)) $connect->close();
}
?>