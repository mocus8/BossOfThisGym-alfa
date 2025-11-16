<?php
require_once __DIR__ . '/smsc_api.php';

function send_sms_verification($phone) {
    
    // Генерируем код
    $code = rand(1000, 9999);
    
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
            'debug_code' => $code //это для теста без реальных sms, потом убрать!!!
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

/**
 * Проверка SMS кода
 */
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
    
    // Проверяем попытки
    if ($data['attempts'] >= 5) {
        unset($_SESSION['sms_verification']);
        return ['success' => false, 'error' => 'Превышено количество попыток'];
    }
    
    // Увеличиваем счетчик попыток
    $_SESSION['sms_verification']['attempts']++;
    
    // Проверяем код
    if ($data['code'] == $inputCode) {
        $phone = $data['phone'];
        unset($_SESSION['sms_verification']);

        //мб тут это убрать получится если где нужно передаётся везде и так
        $_SESSION['verified_phone'] = $phone;
        $_SESSION['phone_verified_at'] = time();

        return ['success' => true, 'phone' => $phone];
    } else {
        return ['success' => false, 'error' => 'Неверный код'];
    }
}
?>