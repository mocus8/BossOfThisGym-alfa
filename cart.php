<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title>Интернет-магазин "Boss Of This Gym"</title>
		<link rel="stylesheet" href="styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <img class="loader" src="img/loader.png" alt="Загрузка">
        </div>
        <div class="desktop">
            <?php
            require_once __DIR__ . '/header.php';
            require_once __DIR__ . '/src/getCartInfo.php';
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
                    Товары в корзине:
                </div>
                <div class="cart_products">
                    <?php
                    if ($cartCount == 0) {
                    ?>
                        <div class="cart_empty">
                            Корзина пуста
                        </div>
                    <?php
                    } else {
                        foreach ($cartItems as $item) {
                    ?>
                        <div class="cart_product" data-product-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                            <div class="product_click">
                                <a href="product/<?= $item['slug'] ?>">
                                    <img class="product_img_1" src="<?= $item['image_path'] ?>">
                                    <div class="cart_product_name">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </div>
                                </a>
                                <div class="cart_product_price">
                                    <?= $item['price'] * $item['amount'] ?> ₽
                                </div>
                                <div class="product_interaction">
                                    <div class="product_interaction_count">
                                        <button class="product_sign_button">
                                            <img class="product_interaction_sign" src="img/minus.png" data-subtract-cart data-product-id="<?= $item['id'] ?>">
                                        </button>
                                        <div class="product_interaction_amount" id="cart-counter-<?= $item['id'] ?>">
                                            <?= $item['amount'] ?>
                                        </div>
                                        <button class="product_sign_button">
                                            <img class="product_interaction_sign" src="img/plus.png" data-add-cart data-product-id="<?= $item['id'] ?>">
                                        </button>
                                    </div>
                                    <button class="product_sign_button">
                                        <img class="product_interaction_delete" src="img/trash.png" data-remove-cart data-product-id="<?= $item['id'] ?>">
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php
                        }
                    }
                    ?>
                </div>
                <div class="cart_right">
                    <img class="order_icon" src="img/cart.png">
                    <div class="order_inf">
                        <div class="order_inf_price_text">
                            Сумма заказа
                        </div>
                        <div class="order_inf_amount">
                            Количество товаров: <span id="order-cart-count"><?= $cartCount ?></span> шт.
                        </div>
                        <div class="order_inf_price_1" >
                            Стоимость всех товаров: <span data-total-counter><?= $cartTotalPrice ?></span> ₽
                        </div>
                        <div class="order_inf_price_2">
                            Итого: <span data-total-counter><?= $cartTotalPrice ?></span> ₽
                        </div>
                    </div>
                    <?php
                    if ($cartCount != 0) {
                        if ($idUser) {
                    ?>
                        <a href="order_making.php" class="order-button-link">
                            <div class="order_start">
                                <div class="order_start_text">
                                    Оформить заказ
                                </div>
                            </div>
                        </a>
                    <?php
                        } else {
                    ?>
                        <a class="order-button-link" id="open-registration-modal-from-cart">
                            <div class="order_start">
                                <div class="order_start_text">
                                    Оформить заказ
                                </div>
                            </div>
                        </a>
                    <?php
                        }
                    }
                    ?>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php'; ?>
        </div>
        <script src="js/loader.js"></script>
        <script defer type="module" src="js/cart.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>