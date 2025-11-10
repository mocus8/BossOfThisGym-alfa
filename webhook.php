<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/helpers.php';

use YooKassa\Model\Notification\NotificationSucceeded;
use YooKassa\Model\Notification\NotificationWaitingForCapture;
use YooKassa\Model\Notification\NotificationCanceled;

try {
    $remoteIP = $_SERVER['REMOTE_ADDR'];
    $trustedRanges = [
        '185.71.76.0/27',
        '185.71.77.0/27',
        '77.75.153.0/25',
        '77.75.156.11', 
        '77.75.156.35',
        '77.75.154.128/25',
        '2a02:5180::/32'
    ];
    
    $ipTrusted = false;
    foreach ($trustedRanges as $range) {
        if (strpos($range, '/') !== false) {
            list($subnet, $bits) = explode('/', $range);
            $ip = ip2long($remoteIP);
            $subnet = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            if (($ip & $mask) == ($subnet & $mask)) {
                $ipTrusted = true;
                break;
            }
        } else {
            if ($remoteIP === $range) {
                $ipTrusted = true;
                break;
            }
        }
    }
    
    if (!$ipTrusted) {
        throw new Exception('Untrusted IP: ' . $remoteIP);
    }

    $source = file_get_contents('php://input');
    $requestBody = json_decode($source, true);

    switch ($requestBody['event']) {
        case 'payment.succeeded':
            $notification = new NotificationSucceeded($requestBody);
            break;
        case 'payment.waiting_for_capture':
            $notification = new NotificationWaitingForCapture($requestBody);
            break;
        case 'payment.canceled':
            $notification = new NotificationCanceled($requestBody);
            break;
        default:
            throw new Exception('Unsupported event: ' . $requestBody['event']);
    }

    $payment = $notification->getObject();

    if ($requestBody['event'] === 'payment.succeeded') {
        $metadata = $payment->getMetadata();
        $orderId = $metadata['orderId'] ?? null;
        
        if (!$orderId) {
            error_log("No orderId in metadata");
            http_response_code(200);
            exit('OK');
        }

        $connect = getDB();
        if (!$connect) {
            throw new Exception('Database connection failed');
        }

        // Обновляем статус заказа если прошли проверки
        $stmt = $connect->prepare("UPDATE orders SET paid_at = NOW(), status = 'paid' WHERE order_id = ?");
        $stmt->bind_param("i", $orderId);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
    }
    
} catch (Exception $e) {
    // ЛОГИРОВАНИЕ ОШИБОК (в реальном проекте использовать логирование в отдельный файл)
    error_log("Webhook error: " . $e->getMessage());
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($connect)) $connect->close();
}

http_response_code(200);
echo 'OK';
?>