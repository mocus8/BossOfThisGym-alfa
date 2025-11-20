<?php
session_start();
require_once __DIR__ . '/smscFunctions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$phone = $input['phone'] ?? '';

if (isset($_SESSION['sms_blocked_until']) && $_SESSION['sms_blocked_until'] > time()) {
    echo json_encode([
        'success' => false,
        'error' => 'blocked',
        'blocked_until' => $_SESSION['sms_blocked_until']
    ]);
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'error' => 'Введите номер телефона']);
    exit;
}

$result = send_sms_verification($phone);
echo json_encode($result);
?>