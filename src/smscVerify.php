<?php
session_start();
require_once __DIR__ . '/smscFunctions.php';

header('Content-Type: application/json');

$code = $_POST['code'] ?? '';

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Введите код']);
    exit;
}

$result = verify_sms_code($code);
echo json_encode($result);
?>