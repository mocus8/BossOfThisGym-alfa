<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//подключаем файл хелперс с нужной функцией
require_once __DIR__ . '/src/helpers.php';
// ЕСЛИ $db УЖЕ СУЩЕСТВУЕТ - ИСПОЛЬЗУЕМ ЕГО, ЕСЛИ НЕТ - СОЗДАЕМ НОВЫЙ
if (isset($db) && $db instanceof PDO) {
    $connect = $db; // Используем существующее соединение
} else {
    $connect = getDB(); // Создаем новое
}
$cartSessionId = getCartSessionId();
//?-оператор, если условие верно, то $idUser = $_SESSION['user']['id'], если условие ложно, то $idUser = '' (пустая строка)
$idUser = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : '';

$headerCartCount = 0;

if ($idUser) {
    $sql = "SELECT SUM(po.amount) as total 
            FROM product_order po 
            JOIN orders o ON po.order_id = o.order_id
            WHERE o.user_id = '$idUser' AND o.status = 'cart'";
} else {
    $sql = "SELECT SUM(po.amount) as total 
            FROM product_order po 
            JOIN orders o ON po.order_id = o.order_id
            WHERE o.session_id = '$cartSessionId' AND o.status = 'cart'";
}

$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    $cartData = mysqli_fetch_assoc($result);
    $headerCartCount = $cartData['total'] ?? 0;
}



if ($idUser != '') {
    //ищем пользователя в бд
    $sql = "SELECT * FROM `users` WHERE `id` = ('$idUser')";
    $result = mysqli_query($connect, $sql);
    $result = mysqli_fetch_all($result);
    $login;
    $password;
    $name;
    //вытаскиваем логин из бд
    foreach ($result as $item) {
        $login = $item[1];
        $password = $item[2];
        $name = $item[3];
    }
}
?>

<header class="header">
    <a href="index.php" class="icon_href">
        <div class="internet_shop">
            Интернет-магазин спортивной атрибутики
        </div>
        <div class="boss_of_this_gym">
            “Boss Of This Gym”
        </div>
        <img class="icon_billy" src="img/icon_billy.svg" alt="Мужик, качёк, крутой парень, Билли Харрингтон">
<!--                                                                        Добавлять этот атрибут для CEO-->
    </a>
    <div class="header_buttons">
        <a href="cart.php">
            <div class="header_button">
                <img class="header_button_icon" src="img/cart.png">
                <div class="header_button_text">
                    Корзина (<span id="header-cart-counter"><?= $headerCartCount ?></span>)
                </div>
            </div>
        </a>
        <?php
        if (!$idUser) {
        ?>
        <a class="order-button-link" id="open-registration-modal-from-cart">
            <div class="header_button">
                <img class="header_button_icon" src="img/box.png">
                <div class="header_button_text">
                    Мои заказы
                </div>
            </div>
        </a>
        <?php
        } else {
        ?>
        <a href="my_orders.php">
            <div class="header_button">
                <img class="header_button_icon" src="img/box.png">
                <div class="header_button_text">
                    Мои заказы
                </div>
            </div>
        </a>
        <?php
        }
        ?>
        <a href="contacts.php">
            <div class="header_button">
                <img class="header_button_icon" src="img/phone.png">
                <div class="header_button_text">
                    Контакты
                </div>
            </div>
        </a>
        <a href="stores.php">
            <div class="header_button">
                <img class="header_button_icon" src="img/map.png">
                <div class="header_button_text">
                    Наши магазины
                </div>
            </div>
        </a>
        <a href="kwork_customers.php">
            <div class="header_button">
                <img class="header_button_icon" src="img/kwork.png">
                <div class="header_button_text">
                    Для заказчиков
                </div>
            </div>
        </a>
    </div>
    <div class="header_search">
        <div class="header_search_click">
            <img class="header_search_icon" src="img/glass.png">
            <span class="header_search_text">
                Поиск товаров:
            </span>
            <input type="text" id="header-search-input" placeholder="гриф для штанги ..." class="header_search_input">
        </div>
    </div>
    <?php
        if ($idUser == '') {
    ?>
        <div class="header_account">
            <img class="header_account_icon" src="img/person.png">
            <button class="header_account_button_guest"  id="open-authorization-modal">
                Войти в аккаунт
            </button>
            <button class="header_account_button_guest" id="open-registration-modal">
                Зарегистрироваться
            </button>
        </div>
    <?php
        } else {
    ?>
        <div class="header_account">
            <img class="header_account_icon" src="img/person.png">
            <div class="header_account_data">
                <div class="header_account_inf">
                    <?= $name ?>
                </div>
                <div class="header_account_inf">
                    <?= $login ?>
                </div>
            </div>
            <div class="header_account_buttons_logged_in">
                <button class="header_account_button_logged_in" id="open-account-editior-modal">
                    <img class="header_account_button_logged_in_icon"src="img/edit.png">
                </button>
                <button class="header_account_button_logged_in" id="open-account-exit-modal">
                    <img class="header_account_button_logged_in" src="img/exit.png">
                </button>
                <button class="header_account_button_logged_in" id="open-account-edit-modal">
                    <img class="header_account_button_logged_in_icon"src="img/trash.png">
                </button>
            </div>
        </div>
    <?php
        }
    ?>
    <div class="registration_modal_blur" id="registration-modal">
        <div class="registration_modal">
            <div class="registration_modal_entry_text">
                Регистрация
            </div>
            <form class="registration_modal_form" action="src/registration.php" method="post">
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваше имя и фамилия:
                    </span>
                    <input required class="registration_modal_input" type="text" name="name" autocomplete="name">
                </div>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш телефон:
                    </span>
                    <input required class="registration_modal_input" type="tel" placeholder="(+7 XXX XXX XX XX)" name="login" autocomplete="tel">
                </div>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш пароль:
                    </span>
                    <input required class="registration_modal_input" type="password" name="password" autocomplete="new-password">
                </div>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Подтверждение пароля:
                    </span>
                    <input required class="registration_modal_input" type="password" name="confirm-password" autocomplete="new-password">
                </div>
                <div class="registration_modal_buttons">
                    <button class="registration_modal_button" type="button" id="close-registration-modal">
                        Закрыть
                    </button>
                    <button class="registration_modal_button" type="submit">
                        Зарегистрироваться
                    </button>
                </div>
                <div class="user_already_exists_back" id="user-already-exists-modal">
                    <img class="error_modal_icon" src="img/error_modal_icon.png">
                    <div class="error_modal_text">
                        Пользователь уже зарегистрирован.
                    </div>
                </div>
                <div class="incorrect_phone_number_back" id="incorrect-phone-number-modal">
                    <img class="error_modal_icon" src="img/error_modal_icon.png">
                    <div class="error_modal_text">
                        Неверный формат номера.
                    </div>
                </div>
                <div class="password_mismatch_back" id="password-mismatch-modal">
                    <img class="error_modal_icon" src="img/error_modal_icon.png">
                    <div class="error_modal_text">
                        Пароли не совпадают.
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="authorization_modal_blur" id="authorization-modal">
        <div class="authorization_modal">
            <div class="authorization_modal_entry_text">
                Вход в аккаунт
            </div>
            <form class="authorization_modal_form" action="src/authorization.php" method="post">
                <div class="authorization_modal_input_back">
                    <span class="authorization_modal_input_text">
                        Ваш телефон:
                    </span>
                    <input required class="authorization_modal_input" type="tel" placeholder="(+7 XXX XXX XX XX)" name="login" autocomplete="tel">
                </div>
                <div class="authorization_modal_input_back">
                    <span class="authorization_modal_input_text">
                        Ваш пароль:
                    </span>
                    <input required class="authorization_modal_input" type="password" name="password" autocomplete="current-password">
                </div>
                <div class="authorization_modal_buttons">
                    <button class="authorization_modal_button" type="button" id="close-authorization-modal">
                        Закрыть
                    </button>
                    <button class="authorization_modal_button" type="submit">
                        Войти
                    </button>
                </div>
                <div class="uknown_user_back" id="uknown-user-modal">
                    <img class="error_modal_icon" src="img/error_modal_icon.png">
                    <div class="error_modal_text">
                        Пользователь с таким телефоном не зарегистрирован.
                    </div>
                </div>
                <div class="wrong_password_back" id="wrong-password-modal">
                    <img class="error_modal_icon" src="img/error_modal_icon.png">
                    <div class="error_modal_text">
                        Неверный пароль.
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="registration_modal_blur" id="account-edit-modal">
        <div class="account_edit_modal">
            <div class="account_edit_modal_entry_text">
                Редактирование аккаунта
            </div>
            <form class="registration_modal_form" action="src/editAccount.php" method="post">
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваше имя:
                    </span>
                    <input required class="registration_modal_input" type="text" value="<?= $name ?>" name="name" autocomplete="name">
                </div>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш старый пароль:
                    </span>
                    <input required class="registration_modal_input" type="password" name="oldPassword" id="password-input-1" autocomplete="current-password">
                    <div class="account_edit_modal_password_button" id="account-edit-modal-password-button-1">
                        Показать
                    </div>
                </div>
                <div class="registration_modal_input_back">
                    <span class="registration_modal_input_text">
                        Ваш новый пароль:
                    </span>
                    <input required class="registration_modal_input" type="password" name="newPassword" id="password-input-2" autocomplete="new-password">
                    <div class="account_edit_modal_password_button" id="account-edit-modal-password-button-2">
                        Показать
                    </div>
                </div>
                <div class="old_password_missmatch_back" id="old-password-missmatch-modal">
                    <img class="error_modal_icon" src="img/error_modal_icon.png">
                    <div class="error_modal_text">
                        Старый пароль не совпадает.
                    </div>
                </div>
                <div class="registration_modal_buttons">
                    <button class="registration_modal_button" type="button" id="close-account-edit-modal">
                        Отмена
                    </button>
                    <button class="registration_modal_button" type="submit">
                        Сохранить
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="registration_modal_blur" id="account-exit-modal">
        <div class="account_delete_modal">
            <div class="account_delete_modal_entry_text">
                Вы уверены что выйти из аккаунта?
            </div>
            <div class="registration_modal_form">
                <div class="registration_modal_buttons">
                    <button class="registration_modal_button" type="button" id="close-account-exit-modal">
                        Отмена
                    </button>
                    <a href="/src/logout.php" class="registration_modal_button">
                        Выйти
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="registration_modal_blur" id="account-delete-modal">
        <div class="account_delete_modal">
            <div class="account_delete_modal_entry_text">
                Вы уверены что хотите удалить аккаунт?
            </div>
            <div class="registration_modal_form">
                <div class="registration_modal_buttons">
                    <button class="registration_modal_button" type="button" id="close-account-delete-modal">
                        Отмена
                    </button>
                    <a href="/src/deleteAccount.php" class="registration_modal_button">
                        Удалить
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>




















