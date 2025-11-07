<?php
require_once __DIR__ . '/helpers.php';
// header('Content-Type: application/json');

$connect = getDB();
if (!$connect) {
    echo json_encode([]);
    exit;
}

$stmt = $connect->prepare("SELECT id, name, address, work_hours, phone, coordinates FROM stores");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    $stores = [];
    
    while ($row = $result->fetch_assoc()) {
        $coordinates = [];
        
        // Парсим текстовые координаты формата "55.666208,37.816980"
        if (!empty($row['coordinates'])) {
            $coords_array = explode(',', $row['coordinates']);
            if (count($coords_array) === 2) {
                $lat = floatval(trim($coords_array[0]));
                $lng = floatval(trim($coords_array[1]));
                $coordinates = [$lat, $lng];
            }
        }
        
        $stores[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'address' => $row['address'],
            'work_hours' => $row['work_hours'],
            'phone' => $row['phone'],
            'coordinates' => $coordinates
        ];
    }
    
    echo json_encode($stores);
} else {
    echo json_encode([]);
}

if ($stmt) $stmt->close();
$connect->close();
?>