<?php
require_once __DIR__ . '/helpers.php';

function getOrderData($orderId) {
    // Инициализация переменных по умолчанию
    $orderItems = [];
    $orderCount = 0;
    $orderTotalPrice = 0;

    try {
        // Проверка входного orderId
        if (!$orderId || !is_numeric($orderId)) {
            throw new Exception('Unknown order');
        }

        $connect = getDB();
        if (!$connect || $connect->connect_error) {
            throw new Exception('Database connection failed');
        }
        
        $orderStmt = $connect->prepare("
                SELECT o.order_id, o.delivery_type, o.delivery_address_id, o.store_id,
                s.name as store_name, s.address as store_address, s.work_hours, s.phone,
                da.address_line as delivery_address
        FROM orders o
        LEFT JOIN stores s ON o.store_id = s.id
        LEFT JOIN delivery_addresses da ON o.delivery_address_id = da.id
        WHERE o.order_id = ?
        ");

        if (!$orderStmt) {
            throw new Exception('Database operations with order failed');
        }

        $orderStmt->bind_param("i", $orderId);
        $orderStmt->execute();
        $orderResult = $orderStmt->get_result();
        $orderDetails = $orderResult->fetch_assoc();
        
        if (!$orderDetails) {
            throw new Exception('Order not found');
        }

        $itemsStmt = $connect->prepare("
            SELECT p.product_id, p.slug, p.name, p.price, po.amount, 
            (SELECT pi.image_path 
            FROM product_images pi 
            WHERE pi.product_id = p.product_id 
            ORDER BY pi.image_id ASC 
            LIMIT 1) as image_path
        FROM product_order po 
        JOIN products p ON po.product_id = p.product_id 
        WHERE po.order_id = ?
        ");
        
        if (!$itemsStmt) {
            throw new Exception('Database operations with orderId failed');
        }

        $itemsStmt->bind_param("i", $orderId);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
                    
        while ($item = $itemsResult->fetch_assoc()) {
            // Формируем структурированный массив для каждого товара
            $orderItems[] = [
                'id' => $item['product_id'],
                'slug' => $item['slug'],
                'name' => $item['name'],
                'price' => $item['price'],
                'amount' => $item['amount'],
                'image_path' => !empty($item['image_path']) ? '/'.$item['image_path'] : '/img/default.png'
            ];

            $orderTotalPrice += $item['price'] * $item['amount'];
            $orderCount += $item['amount'];
        }

        $orderStmt->close();
        $itemsStmt->close();
        $connect->close();
        
    } catch (Exception $e) {
        // Логирование ошибок
        error_log("Order API Error: " . $e->getMessage());
        
        // Возвращаем пустой заказ при ошибке
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'items' => [],
            'total_price' => 0,
            'count' => 0,
            'order_details' => null
        ];
    }
    
    return [
        'success' => true,
        'order_id' => $orderId,
        'items' => $orderItems,
        'total_price' => $orderTotalPrice,
        'count' => $orderCount,
        'order_details' => $orderDetails
    ];
}

// Инициализация переменных с значениями по умолчанию
$orderItems = [];
$orderTotalPrice = 0;
$orderCount = 0;
$orderDetails = null;
$orderError = null;

// Получаем данные только если $orderId существует и валиден
if (!empty($orderId) && is_numeric($orderId)) {
    $orderData = getOrderData($orderId);
    
    if ($orderData['success']) {
        $orderItems = $orderData['items'];
        $orderTotalPrice = $orderData['total_price'];
        $orderCount = $orderData['count'];
        $orderDetails = $orderData['order_details'];
    } else {
        $orderError = $orderData['error'];
        // Можно залогировать ошибку или показать сообщение
        error_log("Order error: " . $orderError);
    }
} else {
    $orderError = "Invalid order ID";
}
?>