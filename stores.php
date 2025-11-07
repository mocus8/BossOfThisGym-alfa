<?php
require_once __DIR__ . '/config/config.php';
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
            <img class="loader" src="img/loader.png" alt="Загрузка">
        </div>
        <div class="desktop">
            <?php require_once __DIR__ . '/header.php';?>
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
                    Наши магазины:
                </div>
                <div class="stores" id="stores-container">
                    Загрузка магазинов...
                </div>
                <!-- здесь начало перебора магазинов -->
                <script defer>
                // Загружаем магазины из БД
                fetch('src/getStores.php')
                .then(response => response.json())
                .then(stores => {

                    const container = document.getElementById('stores-container');
                    container.innerHTML = '';

                    stores.forEach((store, index) => {
                        if (store.coordinates && store.coordinates.length === 2) {
                            const yandexMapsUrl = `https://yandex.ru/maps/?text=${encodeURIComponent(store.address.replace(/<br>/g, ', '))}`;
                            
                            // СОЗДАЕМ HTML ЧЕРЕЗ innerHTML
                            const storeHTML = `
                                <div class="store">
                                    <div class="store_name">
                                    <strong>${store.name}</strong>
                                    </div>
                                    <div class="store_address">
                                        Адрес:<br>
                                        ${store.address}
                                    </div>
                                    <div class="store_time">
                                        Время работы:<br>
                                        ${store.work_hours.replace(/\n/g, '<br>')}
                                    </div>
                                    <div class="store_time">
                                        Телефон:<br>
                                        <a href='tel: ${store.phone}' class="colour_href">
                                            <div style="margin-top: 10px;">${store.phone}</div>
                                        </a>
                                    </div>
                                    <a href="${yandexMapsUrl}" target="_blank">
                                        <div class="store_button">
                                            На карте
                                        </div>
                                    </a>
                                </div>
                            `;
                            
                            container.innerHTML += storeHTML;
                        }
                    });
                })
                .catch(error => {
                    console.error('Ошибка загрузки магазинов:', error);
                    document.getElementById('stores-container').innerHTML = '<p>Магазины временно недоступны</p>';
                });
                </script>
                <div class="stores_right">
                    <div class="stores_map_loader">
                        <img class="loader" src="img/loader.png" alt="Загрузка карты">
                    </div>
                    <div class="yandex_map_back"></div>
                    <div id="stores-map" class="stores-map-absolute"></div>
                    <div class="error_stores_map">
                        Карта временно недоступна :(
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php'; ?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
        <script src="https://api-maps.yandex.ru/2.1/?apikey=<?= YANDEX_MAPS_KEY ?>&lang=ru_RU"></script>
        <script defer src="js/maps.js"></script>
	</body>
</html>