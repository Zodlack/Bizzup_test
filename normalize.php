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

// Получаем список пользователей (по 50 за раз)
$start = 0;
$users = [];

do {
    $res = callB24('user.get', [
        'start' => $start,
        'order' => ['ID' => 'ASC'],
        'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
    ]);
    if (isset($res['result'])) {
        $users = array_merge($users, $res['result']);
        $start += 50;
    } else {
        break;
    }
} while (count($res['result']) === 50);

echo "Найдено пользователей: " . count($users) . PHP_EOL;

foreach ($users as $user) {
    $name = $user['NAME'];
    $secondName = $user['SECOND_NAME'];
    $lastName = $user['LAST_NAME'];
    $userId = $user['ID'];

    // Проверяем, есть ли в имени пробел (например: "Иван Иванович")
    if (strpos($name, ' ') !== false) {
        $parts = explode(' ', $name, 2);
        $newName = $parts[0];
        $newSecondName = $parts[1];

        if ($newSecondName !== $secondName) {
            // Обновляем пользователя
            $updateRes = callB24('user.update', [
                'id' => $userId,
                'fields' => [
                    'NAME' => $newName,
                    'SECOND_NAME' => $newSecondName,
                ],
            ]);
            if (isset($updateRes['result']) && $updateRes['result'] === true) {
                echo "Обновлен пользователь ID $userId: NAME='$newName', SECOND_NAME='$newSecondName'" . PHP_EOL;
            } else {
                echo "Ошибка обновления пользователя ID $userId" . PHP_EOL;
            }
        }
    }
}

echo "Готово!" . PHP_EOL;
