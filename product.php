<?php
require_once __DIR__ . '/src/helpers.php';
session_start();

// ОБЪЯВЛЯЕМ ПЕРЕМЕННЫЕ ДО ИХ ИСПОЛЬЗОВАНИЯ
$cartSessionId = getCartSessionId();
$idUser = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : '';

// Получаем slug товара из URL через .htaccess
$productSlug = $_GET['url'] ?? '';

// ПОДКЛЮЧАЕМСЯ К БД
$connect = getDB();

// ПОЛУЧАЕМ ДАННЫЕ ТОВАРА
$stmt = $connect->prepare("SELECT * FROM products WHERE slug = ?");
$stmt->bind_param("s", $productSlug);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header("HTTP/1.0 404 Not Found");
    include '404.php';
    exit;
}

$productId = $product['product_id'];
$productName = $product['name'];
$productPrice = $product['price'];
$productDescription = $product['description'] ?? 'Описание отсутствует';

// ПОЛУЧАЕМ ИЗОБРАЖЕНИЯ ТОВАРА ИЗ ТАБЛИЦЫ product_images
$stmt_images = $connect->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY image_id ASC");
$stmt_images->bind_param("i", $productId);
$stmt_images->execute();
$images_result = $stmt_images->get_result();
$productImages = [];

if ($images_result && $images_result->num_rows > 0) {
    while ($image = $images_result->fetch_assoc()) {
        $productImages[] = $image['image_path'];
    }
}

// Первое изображение считается главным
if ($productImages) {
    $mainImage = $productImages[0];
} else {
    $mainImage = '/img/default.png';
}

// 2. ПОЛУЧАЕМ ДАННЫЕ КОРЗИНЫ
if ($idUser) {
    $stmt = $connect->prepare("
    SELECT p.product_id, p.name, p.price, po.amount 
    FROM product_order po 
    JOIN products p ON po.product_id = p.product_id 
    JOIN orders o ON po.order_id = o.order_id
    WHERE o.user_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param("i", $idUser);
} else {
    $stmt = $connect->prepare("
    SELECT p.product_id, p.name, p.price, po.amount 
    FROM product_order po 
    JOIN products p ON po.product_id = p.product_id 
    JOIN orders o ON po.order_id = o.order_id
    WHERE o.session_id = ? AND o.status = 'cart'
    ");
    $stmt->bind_param("s", $cartSessionId);
}

$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
$cartTotalPrice = 0;
$cartCount = 0;

if ($result) {
    while ($item = $result->fetch_assoc()) {
        $cartItems[] = [
            'id' => $item['product_id'],
            'name' => $item['name'],
            'price' => $item['price'],
            'amount' => $item['amount']
        ];
        $cartTotalPrice += $item['price'] * $item['amount'];
        $cartCount += $item['amount'];
    }
}

$inCart = false;
$cartAmount = 0;

foreach ($cartItems as $item) {
    if ($item['id'] == $productId) {
        $inCart = true;
        $cartAmount = $item['amount'];
        break;
    }
}

?>

<!-- Передаем PHP переменные в JS -->
<script>
    const cartAmount = <?= $cartAmount ?>;
</script>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<title><?= htmlspecialchars($productName) ?> - Boss Of This Gym</title>
		<base href="/">
		<link rel="stylesheet" href="/styles.css">
	</head>
	<body class="body">
        <div class="loader-overlay" id="loader">
            <img class="loader" src="img/loader.png" alt="Загрузка">
        </div>
        <div class="desktop">
            <?php require_once __DIR__ . '/header.php'; ?>
            <main class="main">
                <div class="product_left">
                    <a href="index.php">
                        <div class="button_return">
                            <div class="button_return_text">
                                На главную
                            </div>
                            <img class="button_return_img" src="img/arrow_back.png">
                        </div>
                    </a>
                    <div class="product_minor_images">
                        <?php for ($i = 0; $i < count($productImages); $i++) { ?>
                            <button class="product_minor_images_button">
                                <img class="product_img_2" src="<?= htmlspecialchars($productImages[$i]) ?>" alt="<?= htmlspecialchars($productName) ?>">
                            </button>
                        <?php } ?>
                    </div>
                    <img class="product_main_img" src="<?= htmlspecialchars($mainImage) ?>" alt="<?= htmlspecialchars($productName) ?>">
                    <div class="product_description_head">
                        О товаре:
                    </div>
                    <div class="product_description_text">
                        <?= $productDescription ?>
                    </div>
                </div>
                <div class="product_right">
                    <div class="product_inf">
                        <div class="product_name_2">
                            <?= htmlspecialchars($productName) ?>
                        </div>
                        <div class="product_price_2">
                            <?= number_format($productPrice, 0, '', ' ') ?> ₽
                        </div>
                        <div class="product_availability">
                            В наличии
                        </div>
                    </div>
                    <button class="product_button_add_not_in_cart open" type="button" id="product-button-add-not-in-cart" data-product-add-cart data-product-id="<?= $productId ?>">
                        Добавить в корзину
                    </button>
                    <div class="product_button_add_in_cart" type="button" id="product-button-add-in-cart">
                        <button class="product_sign_button">
                            <img class="product_interaction_sign" src="img/minus.png" data-product-subtract-cart data-product-id="<?= $productId ?>">
                        </button>
                            <span id="product-cart-counter"><?= $cartAmount ?></span>
                        <button class="product_sign_button">
                            <img class="product_interaction_sign" src="img/plus.png" data-product-add-cart data-product-id="<?= $productId ?>"> 
                        </button>
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php'; ?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/cart.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>