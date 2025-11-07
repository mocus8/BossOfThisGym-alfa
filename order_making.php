<?php
session_start();
require_once __DIR__ . '/config/config.php';

// ПРОВЕРКА АВТОРИЗАЦИИ - ЕСЛИ НЕ АВТОРИЗОВАН, ПЕРЕНАПРАВЛЯЕМ НА ГЛАВНУЮ
if (!isset($_SESSION['user']['id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/src/getCartInfo.php';
if ($cartCount === 0) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Интернет-магазин "Boss Of This Gym"</title>
    <link rel="stylesheet" href="styles.css">
    <script defer src="https://cdn.jsdelivr.net/npm/@dadata/suggestions@25.4.1/dist/suggestions.min.js"></script>
    <script src="https://api-maps.yandex.ru/2.1/?apikey=<?= YANDEX_MAPS_KEY ?>&lang=ru_RU&load=package.full"></script>
</head>
<body class="body">
    <div class="loader-overlay" id="loader">
        <img class="loader" src="img/loader.png" alt="Загрузка">
    </div>
    <div class="desktop">
        <?php require_once __DIR__ . '/header.php' ?>
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
                Получение:
            </div>
            <div class="order_types">
                <div class="order_type chosen" id="order-type-delivery">
                    Доставка
                </div>
                <div class="order_type" id="order-type-pickup">
                    Самовывоз
                </div>
            </div>
            <div class="modal_order_type open" id="modal-order-type-delivery">
                <div class="order_left">
                    <div class="map_search_form">
                       <input type="text"
                        id="delivery-address"
                        name="delivery_address"
                        autocomplete="street-address"
                        autocorrect="off"
                        autocapitalize="off"
                        spellcheck="false"
                        class="map_search_input" 
                        placeholder="Введите адрес доставки"
                        readonly
                        onfocus="this.removeAttribute('readonly')">
                        <button type="button" id="delivery-search-btn" class="map_search_btn">
                            Найти
                        </button>
                    </div>
                    <div class="map_container">
                        <div class="order_map_loader">
                            <img class="loader" src="img/loader.png" alt="Загрузка карты">
                        </div>
                        <div id="delivery-map"></div>
                    </div>
                    <div class="error_delivery_map">
                        Карта временно недоступна :(
                    </div>
                    <div class="error_address_not_found" id="modal-error-address-not-found">
                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                        Адрес не найден
                    </div>
                    <div class="error_address_not_found" id="modal-error-address-empty">
                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                        Введите адрес
                    </div>
                    <div class="error_address_not_found" id="modal-error-address-timeout">
                        <img class="error_modal_icon" src="img/error_modal_icon.png">
                        Проблемы с соединением. Попробуйте еще раз
                    </div>
                </div>
                <div class="order_right">
                    <div class="order_info">
                        <div class="order_right_order">Ваш заказ</div>
                        <?php
                        foreach ($cartItems as $item) { 
                        ?>   
                            <div class="order_right_products_row">
                                <?= htmlspecialchars($item['name']) ?>, (<?= $item['amount'] ?> шт.) - <?= $item['price'] * $item['amount'] ?> ₽
                            </div>
                        <?php
                        }
                        ?>
                        <div class="order_right_row_gap"></div>
                        <div class="order_right_row">Количество товаров: <?= $cartCount ?></div>
                        <div class="order_right_row">Стоимость всех товаров: <?= $cartTotalPrice ?></div>
                        <?php
                        if ($cartTotalPrice < 5000) {
                            $cartTotalPrice += 750
                        ?> 
                            <div class="order_right_row">Стоимость доставки: 750 ₽ (бесплатно при заказе от 5000 ₽)</div>
                            <div class="order_right_row">Итого: <?= $cartTotalPrice?></div>
                        <?php
                        } else {
                        ?>
                            <div class="order_right_row">Стоимость доставки: 0 ₽</div>
                            <div class="order_right_row">Итого: <?= $cartTotalPrice?></div>
                        <?php
                        }
                        ?>
                        <div class="order_right_row">Адрес доставки: <span id="order-right-delivery-address">не указан</span></div>
                    </div>
                    <button class="order_right_pay_button" data-order-id="<?= $cartOrderId ?>">
                        Оплатить
                    </button>
                    <div class="payment_errors">
                        <div class="error_pay_no_address" id="error-pay-delivery-no-address">
                            <img class="error_modal_icon" src="img/error_modal_icon.png">
                            Укажите адрес доставки
                        </div>
                        <?php 
                        // Проверяем наличие КОНКРЕТНОГО кода ошибки оплаты
                        if (isset($_SESSION['flash_payment_error'])) { 
                            // Преобразуем код в текст прямо на месте
                            $errorText = match($_SESSION['flash_payment_error']) {
                                'ORDER_NOT_FOUND' => 'Заказ не найден. Пожалуйста, попробуйте еще раз.',
                                'DATABASE_CONNECT_FAILED' => 'Ошибка базы данных. Пожалуйста, попробуйте еще раз.',
                                'DATABASE_OPERATIONS_FAILED' => 'Ошибка базы данных. Пожалуйста, попробуйте еще раз.',
                                'PAYMENT_NOT_FOUND' => 'Оплата не найдена в базе данных. Пожалуйста, попробуйте еще раз.',
                                'PAYMENT_FAILED' => 'Оплата не прошла. Попробуйте другой способ оплаты или проверьте данные карты.',
                                'PAYMENT_CANCELED' => 'Оплата отменена. Вы можете попробовать снова.',
                                default => 'Произошла ошибка при оплате. Пожалуйста, попробуйте еще раз.'
                            };
                        ?>
                            <div class="error_pay_no_address open" id="flash-payment-error">
                                <img class="error_modal_icon" src="img/error_modal_icon.png">
                                <?= htmlspecialchars($errorText) ?>
                            </div>
                        <?php 
                            // Удаляем ошибку после показа
                            // unset($_SESSION['flash_payment_error']);
                        } 
                        ?>
                    </div>
                </div>
            </div>
            <div class="modal_order_type" id="modal-order-type-pickup">
                <div class="order_left">
                    <div class="pickup_text">
                        Выберите магазин для самовывоза:
                    </div>
                    <div class="map_container">
                        <div class="order_map_loader">
                            <img class="loader" src="img/loader.png" alt="Загрузка карты">
                        </div>
                        <div id="pickup-map"></div>
                    </div>
                    <div class="error_pickup_map">
                        Карта временно недоступна :(
                    </div>
                </div>
                <div class="order_right">
                    <div class="order_info">
                        <div class="order_right_order">Ваш заказ</div>
                        <?php
                        foreach ($cartItems as $item) { 
                        ?>   
                            <div class="order_right_products_row">
                                <?= htmlspecialchars($item['name']) ?>, (<?= $item['amount'] ?> шт.) - <?= $item['price'] * $item['amount'] ?> ₽
                            </div>
                        <?php
                            }
                        ?>
                        <div class="order_right_row_gap"></div>
                        <div class="order_right_row">Количество товаров: <?= $cartCount ?></div>
                        <div class="order_right_row">Стоимость всех товаров: <?= $cartTotalPrice ?></div>
                        <div class="order_right_row">Итого: <?= $cartTotalPrice ?></div>
                        <div class="order_right_row">Адрес магазина для самовывоза:<br><span id="order-right-pickup-address">не указан</span></div>
                    </div>
                    <button class="order_right_pay_button" data-order-id="<?= $cartOrderId ?>">
                        Оплатить
                    </button>
                    <div class="payment_errors">
                        <div class="error_pay_no_address" id="error-pay-pickup-no-address">
                            <img class="error_modal_icon" src="img/error_modal_icon.png">
                            Укажите магазин для самовывоза
                        </div>
                        <?php 
                        // Проверяем наличие КОНКРЕТНОГО кода ошибки оплаты
                        if (isset($_SESSION['flash_payment_error'])) { 
                            // Преобразуем код в текст прямо на месте
                            $errorText = match($_SESSION['flash_payment_error']) {
                                'ORDER_NOT_FOUND' => 'Заказ не найден. Пожалуйста, попробуйте еще раз.',
                                'DATABASE_CONNECT_FAILED' => 'Ошибка базы данных. Пожалуйста, попробуйте еще раз.',
                                'DATABASE_OPERATIONS_FAILED' => 'Ошибка базы данных. Пожалуйста, попробуйте еще раз.',
                                'PAYMENT_NOT_FOUND' => 'Оплата не найдена в базе данных. Пожалуйста, попробуйте еще раз.',
                                'PAYMENT_FAILED' => 'Оплата не прошла. Попробуйте другой способ оплаты или проверьте данные карты.',
                                'PAYMENT_CANCELED' => 'Оплата отменена. Вы можете попробовать снова.',
                                default => 'Произошла ошибка при оплате. Пожалуйста, попробуйте еще раз.'
                            };
                        ?>
                            <div class="error_pay_no_address open" id="flash-payment-error">
                                <img class="error_modal_icon" src="img/error_modal_icon.png">
                                <?= htmlspecialchars($errorText) ?>
                            </div>
                        <?php 
                            // Удаляем ошибку после показа
                            unset($_SESSION['flash_payment_error']);
                        } 
                        ?>
                    </div>
                </div>
            </div>
        </main>
        <?php require_once __DIR__ . '/footer.php'; ?>
    </div>
    <script src="js/loader.js"></script>
    <script defer src="js/modals.js"></script>
    <script defer src="js/maps.js"></script>
    <script defer src="js/order.js"></script>
</body>
</html>