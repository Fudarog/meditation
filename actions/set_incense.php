<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию
header('Content-Type: application/json; charset=utf-8'); // Устанавливаем заголовок JSON

// Подключаемся к БД
$conn = connect();
// Если подключение не удалось — ошибка
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка подключения к БД'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// Получаем данные из JSON-тела запроса
$data = json_decode(file_get_contents('php://input'), true);
$userId = (int)($data['user_id'] ?? 0);

// Проверяем корректность user_id
if ($userId <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Некорректный user_id'
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}

// Данные для записи
$message = 'Зажёг благовоние';
$date = date('Y-m-d H:i:s');
$count = 1;

// Подготавливаем INSERT-запрос
$stmt = mysqli_prepare($conn, "
    INSERT INTO incense (user_id, message, created_at, count)
    VALUES (?, ?, ?, ?)
");
// Если подготовка не удалась — ошибка
if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Ошибка подключения к БД'
    ], JSON_UNESCAPED_UNICODE);
    mysqli_close($conn);
    exit;
}
// Привязываем параметры
mysqli_stmt_bind_param($stmt, "isss", $userId, $message, $date, $count);

// Выполняем запрос
if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => mysqli_stmt_error($stmt)
    ], JSON_UNESCAPED_UNICODE);
}
// Закрываем запрос и подключение
mysqli_stmt_close($stmt);
mysqli_close($conn);
