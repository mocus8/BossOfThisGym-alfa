<?php
session_start();
require_once __DIR__ . '/src/envLoader.php';

// ПРОВЕРКА АВТОРИЗАЦИИ - ЕСЛИ НЕ АВТОРИЗОВАН, ПЕРЕНАПРАВЛЯЕМ НА ГЛАВНУЮ
if (!isset($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit;
}
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
            <?php 
            require_once __DIR__ . '/header.php'; 

            // тут в нормальной версии нужно обрабатывать ошибку при подключении и далее,
            // и логировать ее как положено вместе с остальными
            try {
                $connect = getDB();
                if (!$connect || $connect->connect_error) {
                    throw new Exception('Database connection failed');
                }
                
                $stmt = $connect->prepare("
                    SELECT 
                        o.order_id, 
                        o.status, 
                        o.total_price, 
                        o.created_at, 
                        o.paid_at,
                        o.delivery_type,
                        s.name as store_name,
                        s.address as store_address, 
                        da.address_line as delivery_address
                    FROM orders o 
                    LEFT JOIN stores s ON o.store_id = s.id
                    LEFT JOIN delivery_addresses da ON o.delivery_address_id = da.id
                    WHERE o.user_id = ? AND o.status != 'cart'
                    ORDER BY o.created_at DESC
                ");
                
                if (!$stmt) {
                    throw new Exception('Failed to prepare statement');
                }
                
                $stmt->bind_param("i", $idUser);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $ordersInfo = [];
                while ($order = $result->fetch_assoc()) {
                    $ordersInfo[] = $order;
                }
            } catch (Exception $e) {
                error_log("My orders error: " . $e->getMessage());
                $ordersInfo = [];
            } finally {
                if (isset($stmt)) $stmt->close();
                if (isset($connect)) $connect->close();
            }
            ?>
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
                <div class="cart_in_cart_text">
                    Ваши заказы:
                </div>
                <div class="orders_list">
                    <?php
                    if (empty($ordersInfo)) {
                    ?>
                        <div class="cart_empty">
                            У вас еще нет заказов
                        </div>
                    <?php
                    } else {
                        foreach ($ordersInfo as $order) {
                    ?>
                            <div class="order">
                                <div class="order_number">
                                    Заказ <?= '#' . str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?>
                                </div>
                                <div class="order_data">
                                    Стоимость: <?= $order['total_price'] ?> ₽
                                </div>
                                <div class="order_data">
                                    Способ получения: <br>
                                    <?= $order['delivery_type'] === 'pickup' ? 'самовывоз' : 'доставка' ?>
                                </div>
                                <?php
                                if ($order['delivery_type'] === 'pickup' && $order['store_address']) { 
                                ?>  
                                    <div class="order_data_address">
                                        Пункт выдачи: <?= $order['store_address'] ?>
                                    </div>
                                <?php
                                } else if ($order['delivery_type'] === 'delivery' && $order['delivery_address']) { 
                                ?>
                                    <div class="order_data_address">
                                        Адрес доставки: <?= $order['delivery_address'] ?>
                                    </div>
                                <?php
                                } 
                                ?>
                                <div class="order_data">
                                    Статус заказа: <br>
                                    <?= match($order['status']) {
                                        'cart' => 'корзина',
                                        'paid' => 'оплачен', 
                                        'pending_payment' => 'ожидает оплаты',
                                        'cancelled' => 'отменён',
                                        'refund' => 'возврат',
                                        default => $order['status']
                                    } ?> 
                                </div>
                                <?php
                                if ($order['status'] === 'paid') {
                                ?>
                                     <a href="order_success.php?orderId=<?= $order['order_id'] ?>">
                                        <div class="order_button">
                                            детали заказа
                                        </div>
                                    </a>
                                <?php
                                } else if ($order['status'] === 'pending_payment') {
                                ?>
                                    <div class="order_button" data-action="pay" data-order-id="<?= $order['order_id'] ?>">
                                        Оплатить
                                    </div>
                                    <div class="order_button" data-action="cancel" data-order-id="<?= $order['order_id'] ?>">
                                        Отменить
                                    </div>
                                <?php
                                }
                                ?>
                                <?php 
                                // Проверяем наличие КОНКРЕТНОГО кода ошибки оплаты
                                if (isset($_SESSION['flash_payment_error']) && isset($_SESSION['flash_payment_error'][$order['order_id']])) { 
                                    // Преобразуем код в текст прямо на месте
                                    $paymentErrorText = match($_SESSION['flash_payment_error'][$order['order_id']]) {
                                        'PAYMENT_CANCELED' => 'Оплата отменена. Попробуйте еще раз',
                                        'PAYMENT_FAILED' => 'Оплата не прошла. Попробуйте еще раз или выберите другой способ',
                                        'ORDER_NOT_FOUND' => 'Заказ не найден. Попробуйте создать заказ заново',
                                        'PAYMENT_PENDING' => 'Оплата обрабатывается. Подождите несколько минут',
                                        'EMPTY_USER_PHONE' => 'Заказ не найден. Попробуйте создать заказ заново',
                                        'PAYMENT_STATUS_UNKNOWN' => 'Статус оплаты неизвестен. Подождите или проверьте позже',
                                        'DATABASE_CONNECT_FAILED' => 'Временные технические неполадки. Попробуйте позже',
                                        'DATABASE_OPERATIONS_FAILED' => 'Ошибка обработки заказа. Попробуйте позже',
                                        default => 'Произошла ошибка при оплате. Пожалуйста, попробуйте оплатить еще раз.'
                                    };
                                ?>
                                    <div class="order_error open" id="flash-payment-error">
                                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                                        <?= htmlspecialchars($paymentErrorText) ?>
                                    </div>
                                <?php 
                                    // Удаляем ошибку после показа
                                    unset($_SESSION['flash_payment_error'][$order['order_id']]);
                                }
                                
                                if (isset($_SESSION['flash_cancel_error']) && isset($_SESSION['flash_cancel_error'][$order['order_id']])) {
                                    // Преобразуем код в текст прямо на месте
                                    $cancelErrorText = match($_SESSION['flash_payment_error'][$order['order_id']]) {
                                        'DATABASE_OPERATIONS_FAILED' => 'Ошибка отмены заказа. Попробуйте позже',
                                        'DATABASE_CONNECT_FAILED' => 'Временные технические неполадки. Попробуйте позже',
                                        'ORDER_CANNOT_BE_CANCELLED' => 'Заказ не найден. Попробуйте позже или обновите страницу',
                                        default => 'Произошла ошибка при отмене заказа. Пожалуйста, попробуйте отменить еще раз.'
                                    };
                                ?>
                                    <div class="order_error open" id="flash-payment-error">
                                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                                        <?= htmlspecialchars($cancelErrorText) ?>
                                    </div>
                                <?php 
                                    // Удаляем ошибку после показа
                                    unset($_SESSION['flash_cancel_error'][$order['order_id']]);
                                }

                                if (isset($_SESSION['flash_cancel_success']) && isset($_SESSION['flash_cancel_success'][$order['order_id']])) {
                                ?>
                                    <div class="order_error open" id="flash-payment-error">
                                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                                        Заказ успешно отменен
                                    </div>
                                <?php 
                                    // Удаляем ошибку после показа
                                    unset($_SESSION['flash_cancel_success'][$order['order_id']]);
                                }
                                ?>
                            </div>
                    <?php
                        }
                        if (isset($_SESSION['flash_payment_error']) && empty($_SESSION['flash_payment_error'])) {
                            unset($_SESSION['flash_payment_error']);
                        }
                        if (isset($_SESSION['flash_cancel_error']) && empty($_SESSION['flash_cancel_error'])) {
                            unset($_SESSION['flash_cancel_error']);
                        }
                        if (isset($_SESSION['flash_cancel_success']) && empty($_SESSION['flash_cancel_success'])) {
                            unset($_SESSION['flash_cancel_success']);
                        }
                    ?>
                    <div class="order_refund_info">
                        Для возврата заказа свяжитесь с менеджером (<a href='tel: +70000000000' class="colour_href">+7 000 000 00 00</a>). Вернуть получится только заказы с момента получения которых прошло менее 14 дней
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
        <div class="registration_modal_blur" id="order-cancel-modal">
            <div class="account_delete_modal">
                <div class="account_delete_modal_entry_text">
                    Вы уверены что хотите отменить заказ?
                </div>
                <div class="registration_modal_form">
                    <div class="registration_modal_buttons">
                        <button class="registration_modal_button" type="button" id="close-order-cancel-modal">
                            Вернуться
                        </button>
                        <form method="POST" action="/src/cancelOrder.php" id="cancel-order-form" class="registration_modal_button">
                            <input type="hidden" name="order_id" id="cancel-order-id">
                            <button type="submit">
                                Отменить заказ
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script defer src="js/my_orders.js"></script>
	</body>
</html>