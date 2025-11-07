<?php
session_start();

require_once __DIR__ . '/helpers.php';

// Получаем данные ИЗ POST
$login = $_POST["login"];
$password = $_POST["password"]; 
$name = $_POST["name"];

$cartSessionId = getCartSessionId();

$connect = getDB();

// 1. Проверяем нет ли такого пользователя
$check = $connect->prepare("SELECT id FROM users WHERE login = ?");
$check->bind_param("s", $login);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'user_already_exists'
    ]);
    exit;
}

// 2. Хэшируем пароль
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 3. Безопасное добавление в БД
$stmt = $connect->prepare("
    INSERT INTO users (login, password, name) VALUES (?, ?, ?)
");
$stmt->bind_param("sss", $login, $hashedPassword, $name);

if ($stmt->execute()) {
    $new_user_id = $stmt->insert_id; // Сохраняем ID нового пользователя
    
    $_SESSION['user'] = ['id' => $new_user_id];
    
    // 4. Обновляем существующие заказы с session_id на user_id
    $update_stmt = $connect->prepare("
        UPDATE orders 
        SET user_id = ?, session_id = NULL 
        WHERE session_id = ?
    ");
    $update_stmt->bind_param("is", $new_user_id, $cartSessionId);
    $update_stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'error'
    ]);
}
?>