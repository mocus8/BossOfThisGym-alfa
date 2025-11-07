<?php

//подключаем файл хелперс с нужной функцией
require_once __DIR__ . '/helpers.php';

session_start();

$connect = getDB();

$idUser = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : '';
$oldPassword = $_POST['oldPassword'];
$newPassword = $_POST['newPassword'];
$name = $_POST['name'];

if ($idUser == '') {
    header("Location: /");
    exit;
 }

//берём старый пароль
$stmt = $connect->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $idUser);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();
if (!$user) {
    $_SESSION['error'] = 'Ошибка при обновлении данных';
    header("Location: /");
    exit;
}

if (!password_verify($oldPassword, $user['password'])) {
    //выводим сообщение что пароль не верный
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'old_password_missmatch'
    ]);
    exit;
} else {
    //меняем данные пользователя
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $connect->prepare("UPDATE `users` SET `password` = ?, `name` = ? WHERE `id` = ?");
    $stmt->bind_param("ssi", $hashedPassword, $name, $idUser);

    if ($stmt->execute()) {
        echo json_encode([
        'success' => true
    ]);
    } else {
        $_SESSION['error'] = 'Ошибка при обновлении данных';
        header("Location: /");
    }
}

