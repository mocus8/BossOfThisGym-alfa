<?php
session_start();
require_once __DIR__ . '/helpers.php';

// Получаем данные из формы авторизации методом POST
$login = $_POST["login"];
$password = $_POST["password"];

// Устанавливаем соединение с базой данных
$connect = getDB();

// Используем подготовленные выражения для защиты от SQL-инъекций
$stmt = $connect->prepare("SELECT id, password, name FROM users WHERE login = ?");
// Привязываем параметр (s - string) к запросу
$stmt->bind_param("s", $login);
// Выполняем запрос
$stmt->execute();
// Получаем результат запроса
$result = $stmt->get_result();

// Если пользователь с таким логином не найден
if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'user_not_found'
    ]);
    exit; // Завершаем выполнение скрипта
}

// Получаем данные пользователя из результата запроса
$user = $result->fetch_assoc();

// Проверяем соответствие пароля используя безопасное сравнение
// password_verify() сравнивает хэш из БД с введенным паролем
if (password_verify($password, $user['password'])) {
    // Сохраняем данные пользователя в сессию
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name']
    ];
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'wrong_password'
    ]);
}
?>