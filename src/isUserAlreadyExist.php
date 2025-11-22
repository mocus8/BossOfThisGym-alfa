<?php
session_start();

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

try {
    $connect = getDB();

    if (!$connect) {
        throw new Exception('db connection error');
    }

    if (!isset($_POST["login"]) || empty(trim($_POST["login"]))) {
        echo json_encode([
            'success' => false,
            'message' => 'login_required'
        ]);
        exit;
    }

    $login = trim($_POST["login"]);

    $check = $connect->prepare("SELECT id FROM users WHERE login = ?");

    if (!$check) {
        throw new Exception('prepare statement failed');
    }

    $check->bind_param("s", $login);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'success' => false
        ]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'server_error'
    ]);
} finally {
    if (isset($check)) {
        $check->close();
    }
    
    if (isset($connect)) {
        $connect->close();
    }
}
?>


