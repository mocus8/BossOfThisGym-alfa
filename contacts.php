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
                <div class="cart_in_cart_text">
                    Наши контакты:
                </div>
                <div class="contacts">
                    <div class="contact">
                        <div class="contact_type">
                            Телефон:
                        </div>
                        <div class="contact_inf">
                            <a href='tel: +74954042791' class="colour_href">+7 495 404 27 91</a> - по вопросам заказов и работе магазинов<br>
                            <a href='tel: +79228174046' class="colour_href">+7 922 817 40 46</a> - по вопросам о наличии товаров    
                        </div>
                    </div>
                    <div class="contact">
                        <div class="contact_type">
                            Электронная почта:
                        </div>
                        <div class="contact_inf">
                            <a href='mailto: xrqzzf2@smartnator.com' class="colour_href">xrqzzf2@smartnator.com</a> - по всем вопросам<br>
                            <a href='mailto: mocus8@gmail' class="colour_href">mocus8@gmail.com</a> - по вопросам сотрудничества
                        </div>
                    </div>
                    <div class="contact">
                        <div class="contact_type">
                            Социальные сети:
                        </div>
                        <div class="contact_inf">
                            <a href="https://t.me/sldkvil" target="_blank" class="colour_href">Наш Телеграм - канал</a><br>
                            <a href="https://vk.com/zabijaka1488" target="_blank" class="colour_href">Наше сообщество ВКонтакте</a>
                        </div>
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php'; ?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>