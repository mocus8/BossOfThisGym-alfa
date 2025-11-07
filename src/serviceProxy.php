<?php
require_once '../config/config.php';

header('Content-Type: application/json');
echo json_encode(['key' => DADATA_API_KEY]);
?>