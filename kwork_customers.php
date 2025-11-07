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
                <div class="customers_infos">
                    <div class="customers_inf">
                        Этот сайт - демонстрация навыков его разработчика в сфере веб-разработки и дизайна. Сайт и его дизайн были созданы разработчиком с нуля.
                    </div>
                    <div class="customers_inf">
                       Были использованы HTML, CSS, PHP, JavaScript, MySQL, для разработки дизайна использовался конструктор Figma.
                    </div>
                    <div class="customers_inf">
                       Для функционирования карт были исользованы Yandex Maps API и DaData API, для оплаты подключён SDK ЮKassa
                    </div>
                    <div class="customers_inf">
                       Сайт представляет из себя макет интернет-магазина со всем необходимым функционалом, адаптирован под все устройства. 
                    </div>
                    <div class="customers_inf">
                       Связаться с разработчиком можно через почту <a href='mailto: mocus8@gmail' class="colour_href">(mocus8@gmail.com)</a>, через мессенджеры <a href="https://api.whatsapp.com/send?phone=89167413418" target="_blank" class="colour_href">WhatsApp (+7 916 741 34 18)</a> и <a href="https://t.me/sldkvil" target="_blank" class="colour_href">Telegram (@sldkvil)</a> 
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php'; ?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>