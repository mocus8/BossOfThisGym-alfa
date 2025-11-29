<?php
session_start();

require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json');

// Получаем данные из GET (в url)
$query = trim(htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8'));
$query = substr($query, 0, 150);

if (!$query) {
    http_response_code(400);
    echo json_encode(['error' => 'empty_query']);
    exit;
}

// разбиваем на слова для лучшего поиска
$words = explode(' ', $query);
$conditions = [];
$params = [];
$types = '';

// условия каждого слова для поиска
foreach ($words as $word) {
    if (mb_strlen($word) >= 3) {
        $baseWord = mb_substr($word, 0, -2);
        $conditions[] = "(prdct.name LIKE ? OR prdct.name LIKE ? OR prdct.description LIKE ? OR prdct.description LIKE ?)";
        $params[] = '%' . $word . '%';
        $params[] = '%' . $baseWord . '%';
        $params[] = '%' . $word . '%';
        $params[] = '%' . $baseWord . '%';
        $types .= 'ssss';
    }
}

// нет подходящих условий - пустой
if (empty($conditions)) {
    http_response_code(200);
    echo json_encode([]);
    exit;
}

$connect = getDB();
$connect->set_charset("utf8mb4");

if (!$connect) {
    http_response_code(500);
    echo json_encode(['error' => 'db_connection_failed']);
    exit;
}

// базовые параметры для релевантности
$exactMatch = $query;
$searchTermStart = $query . '%';
$searchTerm = '%' . $query . '%';

// Ищем в бд схожие названия
$sql = "
    SELECT 
        prdct.product_id as prdct_id,
        prdct.slug as prdct_slug,
        prdct.name as prdct_name,
        prdct.price as prdct_price,
        prdct.description as prdct_description,
        img.image_id as img_id,
        img.image_path as img_path,
        (CASE
            WHEN prdct.name LIKE ? THEN 40
            WHEN prdct.name LIKE ? THEN 30
            WHEN prdct.name LIKE ? THEN 20
            WHEN prdct.description LIKE ? THEN 1
            ELSE 0
        END) as relevance
    FROM products prdct
    LEFT JOIN product_images img ON prdct.product_id = img.product_id
        AND img.image_id = (
            SELECT MIN(img2.image_id) 
            FROM product_images img2 
            WHERE img2.product_id = prdct.product_id
        )
    WHERE " . implode(' OR ', $conditions) . "
    HAVING relevance > 0
    ORDER BY relevance DESC, prdct.name
";

// оформляем параметры
array_unshift($params, $exactMatch, $searchTermStart, $searchTerm, $searchTerm);
$types = 'ssss' . $types;

$stmt = $connect->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    http_response_code(200);
    echo json_encode([]);
    exit;
}

// формируем массив полученных товаров
$queryProducts = [];
    
while ($row = $result->fetch_assoc()) {
    $queryProducts[] = [
        'id' => $row['prdct_id'],
        'slug' => $row['prdct_slug'],
        'name' => $row['prdct_name'],
        'price' => $row['prdct_price'],
        'image_path' => !empty($row['img_path']) ? '/'.$row['img_path'] : '/img/default.png'
    ];
}

$stmt->close();
$connect->close();

// если все проверки прошли выдаем найденные товары
http_response_code(200);
echo json_encode($queryProducts);
exit;
?>