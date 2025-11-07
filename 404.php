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
                <div class="error_back">
                    <div class="error_name">
                        Ошибка 404!
                    </div>
                    <div class="error_text">
                        Такой страницы уже/ещё не существует, пожалуйста, проверьте адрес и попробуйте еще раз.
                    </div>
                </div>
            </main>
            <?php require_once __DIR__ . '/footer.php';?>
        </div>
        <script src="js/loader.js"></script>
        <script defer src="js/modals.js"></script>
	</body>
</html>