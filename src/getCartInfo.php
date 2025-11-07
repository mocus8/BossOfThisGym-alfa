<?php
require_once __DIR__ . '/helpers.php';

// ПОДКЛЮЧАЕМСЯ К БД И ПОЛУЧАЕМ ДАННЫЕ КОРЗИНЫ
function getCartData() {

    // ИНИЦИАЛИЗИРУЕМ ПЕРЕМЕННЫЕ ПО УМОЛЧАНИЮ
    $cartItems = [];
    $cartTotalPrice = 0;
    $cartCount = 0;
    $cartSessionId = getCartSessionId();
    $userId = $_SESSION['user']['id'] ?? null;
    $cartOrderId = null;

    try {
        $connect = getDB();
        // ПОДКЛЮЧАЕМСЯ К БД С ОБРАБОТКОЙ ОШИБОК
        if (!$connect || $connect->connect_error) {
            throw new Exception('Database connection failed');
        }

        if (!$userId && !$cartSessionId) {
            throw new Exception('Ukwown user');
        }

        if ($userId) {
            $stmt = $connect->prepare("
            SELECT o.order_id, p.product_id, p.slug, p.name, p.price, po.amount, 
                (SELECT pi.image_path 
                    FROM product_images pi 
                    WHERE pi.product_id = p.product_id 
                    ORDER BY pi.image_id ASC 
                    LIMIT 1) as image_path
            FROM product_order po 
            JOIN products p ON po.product_id = p.product_id 
            JOIN orders o ON po.order_id = o.order_id
            WHERE o.user_id = ? AND o.status = 'cart'
            ");
            if ($stmt) {
                $stmt->bind_param("i", $userId);
            } else {
                throw new Exception('Database operations with userId failed');
            }
        } else {
            $stmt = $connect->prepare("
            SELECT o.order_id, p.product_id, p.slug, p.name, p.price, po.amount,
                (SELECT pi.image_path 
                    FROM product_images pi 
                    WHERE pi.product_id = p.product_id 
                    ORDER BY pi.image_id ASC 
                    LIMIT 1) as image_path
            FROM product_order po 
            JOIN products p ON po.product_id = p.product_id 
            JOIN orders o ON po.order_id = o.order_id
            WHERE o.session_id = ? AND o.status = 'cart'
            ");
            if ($stmt) {
                $stmt->bind_param("s", $cartSessionId);
            } else {
                throw new Exception('Database operations with sessionCartId failed');
            }
        }

        $stmt->execute();
        $result = $stmt->get_result();
                    
        if ($result) {
            while ($item = mysqli_fetch_assoc($result)) {
                // Сохраняем order_id из первой записи
                if ($cartOrderId === null) {
                    $cartOrderId = $item['order_id'];
                }

                // Формируем структурированный массив для каждого товара
                $cartItems[] = [
                    'id' => $item['product_id'],
                    'slug' => $item['slug'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'amount' => $item['amount'],
                    'image_path' => !empty($item['image_path']) ? '/'.$item['image_path'] : '/img/default.png'
                ];

                $cartTotalPrice += $item['price'] * $item['amount'];
                $cartCount += $item['amount'];
            }
        }
        $stmt->close();
        $connect->close();
    } catch (Exception $e) {
        // ЛОГИРОВАНИЕ ОШИБОК (в реальном проекте использовать логирование в отдельный файл)
        error_log("Cart API Error: " . $e->getMessage());
        
        // Возвращаем пустую корзину при ошибке, но сайт не падает
        return [
            'success'=> false,
            'cart_id' => null,
            'items' => [],
            'total_price' => 0,
            'count' => 0,
        ];
    }
    
    return [
        'success'=> true,
        'cart_id' => $cartOrderId,
        'items' => $cartItems,
        'total_price' => $cartTotalPrice,
        'count' => $cartCount
    ];
}

// Получаем данные с проверкой на ошибки
$cartData = getCartData();
$cartOrderId = $cartData['cart_id'];
$cartItems = $cartData['items'];
$cartTotalPrice = $cartData['total_price'];
$cartCount = $cartData['count'];
?>