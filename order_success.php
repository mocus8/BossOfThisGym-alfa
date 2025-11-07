<?php
require_once __DIR__ . '/src/helpers.php';

session_start();

$userId = $_SESSION['user']['id'] ?? null;
$orderId = $_GET['orderId'] ?? '';

if (!$userId) {
    header('Location: index.php');
    exit();
}

if (!$orderId) {
    $_SESSION['flash_payment_error'] = 'ORDER_NOT_FOUND';
    header('Location: order_making.php');
    exit();
}

try {
    $connect = getDB();
    if (!$connect || $connect->connect_error) {
        throw new Exception('DATABASE_CONNECT_FAILED');
    }

    // получаем из бд данные о заказах (id заказа и статус)
    $stmt = $connect->prepare("SELECT order_id, status FROM orders WHERE order_id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception('DATABASE_OPERATIONS_FAILED');
    }

    // Привязываем параметры
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    // Заказ вообще не существует
    if (!$order) {
        throw new Exception('ORDER_NOT_FOUND');
    }

    // Заказ есть, но не оплачен
    if ($order['status'] !== 'paid') {
        throw new Exception('PAYMENT_NOT_FOUND');
    }

} catch (Exception $e) {
    $_SESSION['flash_payment_error'] = $e->getMessage();
    header('Location: order_making.php');
    exit();
} finally {
    // Закрываем все соединения в finally (выполнится в любом случае)
    if (isset($stmt)) $stmt->close();
    if (isset($connect)) $connect->close();
}

require_once __DIR__ . '/src/getOrderData.php';
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>
            Интернет-магазин "Boss Of This Gym"
		</title>
		<link rel="stylesheet" href="styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <!-- <div class="loading-text">Загрузка...</div> -->
        </div>
        <div class="desktop">
            <?php require_once __DIR__ . '/header.php'; ?>
            <main class="main">
                <div class="button_return_position">
                    <a href="index.php">
                        <div class="button_return">
                            <div class="button_return_text">
                                На главную
                            </div>
                            <img class="button_return_img" src="img/arrow_back.png">
                        </div>
                    </a>
                </div>
                <div class="order_success_back">
                    <div class="order_success_title">
                        Заказ успешно оформлен!
                    </div>
                    <div class="order_success_row">
                        Номер вашего заказа: <?= '#' . str_pad($orderId, 6, '0', STR_PAD_LEFT) ?>
                    </div>
                    <div class="order_right_row_gap"></div>
                    <?php
                    foreach ($orderItems as $item) { 
                    ?>   
                        <div class="order_right_products_row">
                            <?= htmlspecialchars($item['name']) ?>, (<?= $item['amount'] ?> шт.) - <?= $item['price'] * $item['amount'] ?> ₽
                        </div>
                    <?php
                        }
                    ?>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_row">
                        Итоговая стоимость: <?= $orderTotalPrice ?> ₽
                    </div>
                    <div class="order_right_row_gap"></div>
                    <!-- выбранный способ и адрес доставки -->
                    <div class="order_success_row">
                        Способ доставки: <?= $orderDetails['delivery_type'] === 'pickup' ? 'самовывоз' : 'доставка' ?>
                    </div>
                    <?php if ($orderDetails['delivery_type'] === 'pickup' && $orderDetails['store_name']) { ?>  
                    <div class="order_success_row">
                        Пункт выдачи: <?= htmlspecialchars($orderDetails['store_name']) ?>, <?= htmlspecialchars($orderDetails['store_address']) ?>
                    </div>
                    <?php } else if ($orderDetails['delivery_type'] === 'delivery' && $orderDetails['delivery_address']) { ?>
                    <div class="order_success_row">
                        Адрес доставки: <?= htmlspecialchars($orderDetails['delivery_address']) ?>
                    </div>
                    <div class="order_success_row">
                        Курьер свяжеться с вами по телефону
                    </div>
                    <?php } ?>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_row">
                        Письмо с чеком будет отправлено на вашу почту
                    </div>
                    <div class="order_right_row_gap"></div>
                    <div class="order_success_title">
                        Спасибо за ваш заказ!
                    </div>
                    <div class="order_success_actions">
                        <a href="order_history.php" class="order_success_actions_button">История заказов</a>
                        <a href="index.php" class="order_success_actions_button">Продолжить покупки</a>
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>