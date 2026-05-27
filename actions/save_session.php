<?php
session_start(); // Начинаем сессию
header('Content-Type: application/json; charset=utf-8'); // Устанавливаем заголовок JSON

// Подключаем конфигурацию
require_once '../config/config.php';

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Не авторизован'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Получаем user_id из сессии
$user_id = (int)$_SESSION['user_id'];
// Получаем JSON-данные из тела запроса
$input = json_decode(file_get_contents('php://input'), true);

// Проверяем, что передан тип практики
if (!$input || empty($input['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Нет типа практики'], JSON_UNESCAPED_UNICODE);
    exit;
}
// Подключаемся к БД
$conn = connect();

// Экранируем данные и получаем параметры
$type = mysqli_real_escape_string($conn, $input['type']);
$duration = (int)($input['duration'] ?? 0);
$notes = mysqli_real_escape_string($conn, $input['notes'] ?? '');
$date = date('Y-m-d H:i:s');

// Вставляем запись о сессии с status = 'completed'
$sql = "INSERT INTO sessions (user_id, type, duration, notes, date, status) 
        VALUES ($user_id, '$type', $duration, '$notes', '$date', 'completed')";

// Если вставка успешна
if (mysqli_query($conn, $sql)) {
    $session_id = mysqli_insert_id($conn);

    // Проверяем и разблокируем достижения
    require_once 'check_achievements.php';
    checkUserAchievements($conn, $user_id);
    // Возвращаем успешный ответ
    echo json_encode([
        'success' => true,
        'id' => $session_id,
        'duration' => $duration,
        'type' => $type,
        'achievements_checked' => true
    ], JSON_UNESCAPED_UNICODE);
} else {
    // Ошибка при вставке
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($conn)], JSON_UNESCAPED_UNICODE);
}

// Закрываем подключение
mysqli_close($conn);
