<?php
session_start();
require_once __DIR__ . '/smscFunctions.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';

if (isset($_SESSION['sms_blocked_until']) && $_SESSION['sms_blocked_until'] > time()) {
    echo json_encode([
        'success' => false,
        'error' => 'blocked',
        'blocked_until' => $_SESSION['sms_blocked_until']
    ]);
    exit;
}

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Введите код']);
    exit;
}

$result = verify_sms_code($code);
echo json_encode($result);
?>