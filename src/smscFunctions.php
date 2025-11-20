<?php
require_once __DIR__ . '/smsc_api.php';

function send_sms_verification($phone) {
    
    // Генерируем код
    $code = rand(10000, 99999);
    
    // Сохраняем в сессии
    $_SESSION['sms_verification'] = [
        'phone' => $phone,
        'code' => $code,
        'time' => time(),
        'attempts' => 0
    ];
    
    // Текст сообщения
    $message = "Код подтверждения: $code";
    $testMode = (SMSC_TEST_MODE === 'true');
    $query = $testMode ? "cost=3" : "";
    
    // Отправка через SDK
    list($smsId, $smsCount, $cost, $balance) = send_sms(
        $phone, $message, 0, 0, 0, 0, SMSC_SENDER, $query
    );
    
    if ($smsCount > 0) {
        return [
            'success' => true,
            'test_mode' => $testMode,
            'debug_code' => $code, //это для теста без реальных sms, потом убрать!!!
            'debug_phone' => $phone //это для теста без реальных sms, потом убрать!!!
        ];
    } else {
        error_log("SMSC Error: $smsId for phone $phone");
        unset($_SESSION['sms_verification']);
        return [
            'success' => false,
            'error' => 'Ошибка отправки SMS'
        ];
    }
}

function verify_sms_code($inputCode) {
    if (!isset($_SESSION['sms_verification'])) {
        return ['success' => false, 'error' => 'Код не отправлялся'];
    }
    
    $data = $_SESSION['sms_verification'];
    
    // Проверяем время (10 минут)
    if (time() - $data['time'] > 600) {
        unset($_SESSION['sms_verification']);
        return ['success' => false, 'error' => 'Код устарел'];
    }

    // увеличиваем счетчик попыток
    $_SESSION['sms_verification']['attempts']++;
    $currentAttempts = $_SESSION['sms_verification']['attempts'];
    
    // Проверяем попытки
    if ($currentAttempts >= 5) {
        // Устанавливаем блокировку
        // $_SESSION['sms_blocked_until'] = time() + 1800; // 30 минут
        $_SESSION['sms_blocked_until'] = time() + 45; // 45 секунд ДЛЯ ТЕСТА

        unset($_SESSION['sms_verification']);
        
        // return ['success' => false, 'error' => 'Превышено количество попыток'];
        return [
            'success' => false, 
            'error' => 'blocked', 
            'blocked_until' => $_SESSION['sms_blocked_until']
        ];
    }
    
    // Проверяем код
    if ($data['code'] == $inputCode) {
        $phone = $data['phone'];
        unset($_SESSION['sms_verification']);

        $_SESSION['verified_phone'] = $phone;
        $_SESSION['phone_verified_at'] = time();

        return ['success' => true, 'phone' => $phone];
    } else {
        return ['success' => false, 'error' => 'Неверный код. Осталось попыток: ' . (5 - $currentAttempts)];
    }
}
?>