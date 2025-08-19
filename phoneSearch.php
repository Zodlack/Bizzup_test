<?php
require_once 'config.php';

function callB24($method, $params = []) {
    $url = WEBHOOK_URL . $method;
    $query = http_build_query($params);
    $fullUrl = $url . '?' . $query;

    $response = file_get_contents($fullUrl);
    if ($response === false) {
        throw new Exception("Ошибка запроса к Битрикс24");
    }
    return json_decode($response, true);
}

// функция для очистки телефона
function normalizePhone($phone) {
    return preg_replace('/\D+/', '', $phone); // оставляем только цифры
}

// номер по которому хотим найти сотрудника ва списке 
$phone = '+7 913 553-11-44';
$normalizedPhone = normalizePhone($phone);

$response = callB24('user.get', [
    'select' => ['ID', 'NAME', 'LAST_NAME', 'PERSONAL_MOBILE', 'PERSONAL_PHONE']
]);

if (isset($response['error'])) {
    echo "Ошибка: " . $response['error_description'] . PHP_EOL;
    exit;
}

$users = $response['result'] ?? [];
$found = [];

foreach ($users as $u) {
    $phones = [];
    if (!empty($u['PERSONAL_MOBILE'])) $phones[] = $u['PERSONAL_MOBILE'];
    if (!empty($u['PERSONAL_PHONE'])) $phones[] = $u['PERSONAL_PHONE'];

    foreach ($phones as $p) {
        if (normalizePhone($p) === $normalizedPhone) {
            $found[] = $u;
        }
    }
}

if (empty($found)) {
    echo "Контактов с телефоном $phone не найдено" . PHP_EOL;
} else {
    echo "Найдены пользователи:" . PHP_EOL;
    foreach ($found as $f) {
        echo "ID: {$f['ID']}, Имя: {$f['NAME']} {$f['LAST_NAME']}" . PHP_EOL;
    }
}
