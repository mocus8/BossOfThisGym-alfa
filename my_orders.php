<?php
session_start();

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

            //тут в нормальной версии нужно обрабатывать ошибку при подключении и далее, и логировать ее как положено вместе с остальными
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
                    У вас еще нет заказов
                <?php
                } else {
                    foreach ($ordersInfo as $order) {
                ?>
                    <h3>Заказ #<?= $order['order_id'] ?></h3>
                <?php
                    }
                }
                ?>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>