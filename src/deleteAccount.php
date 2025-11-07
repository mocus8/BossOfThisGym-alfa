<?php

require_once __DIR__ . '/helpers.php';

session_start();

$idUser = $_SESSION['user']['id'] ?? '';

if (!$idUser) {
    header("Location: /");
    exit;
}

try {
    $connect = getDB();
    
    // Начинаем транзакцию для безопасности (либо все выполняются либо ни один запросы)
    $connect->begin_transaction();
    
    // Удаляем товары в заказах пользователя
    $stmt = $connect->prepare("
        DELETE po FROM product_order po 
        INNER JOIN orders o ON po.order_id = o.order_id 
        WHERE o.user_id = ?
    ");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    
    // Удаляем заказы пользователя
    $stmt = $connect->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();

    // Удаляем адреса доставки пользователя
    $stmt = $connect->prepare("DELETE FROM delivery_addresses WHERE user_id = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    
    // Удаляем самого пользователя
    $stmt = $connect->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $idUser);
    $stmt->execute();
    
    // Подтверждаем все изменения
    $connect->commit();
    
    header("Location: /src/logout.php");
    exit;
    
} catch (Exception $e) {
    // Откатываем изменения в случае ошибки
    if (isset($connect)) {
        $connect->rollback();
    }
    // Логируем ошибку и показываем сообщение пользователю
    error_log("Error deleting account: " . $e->getMessage());
    $_SESSION['error'] = "Произошла ошибка при удалении аккаунта";
    header("Location: /");
    exit;
}