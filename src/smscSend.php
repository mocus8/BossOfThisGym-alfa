<?php
session_start();
require_once __DIR__ . '/smscFunctions.php';

header('Content-Type: application/json');

$phone = $_POST['phone'] ?? '';

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Введите номер телефона']);
    exit;
}

$result = send_sms_verification($phone);
echo json_encode($result);
?>