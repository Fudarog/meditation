<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию
header('Content-Type: application/json; charset=UTF-8'); // Устанавливаем заголовок JSON

// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    error_log("ОШИБКА: Нет сессии");
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

// Получаем user_id из сессии
$user_id = (int)$_SESSION['user_id'];
error_log("user_id из сессии: $user_id");

// Если обновляем только аватар
if (isset($_POST['only_avatar']) && $_POST['only_avatar'] === '1') {
    $avatar = trim($_POST['avatar'] ?? 'avatar1.jpg');
    error_log("Только аватар: $avatar для user_id $user_id");
    // Подключаемся к БД
    $conn = connect();
    // Подготавливаем UPDATE только для аватара
    $stmt = mysqli_prepare($conn, "UPDATE users SET avatar = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "si", $avatar, $user_id);
    // Выполняем запрос
    if (mysqli_stmt_execute($stmt)) {
        $affected = mysqli_stmt_affected_rows($stmt);
        error_log("Аватар UPDATE: affected=$affected");
        // Если строка обновлена — успех
        if ($affected > 0) {
            echo json_encode(['success' => true, 'avatar' => $avatar]);
        } else {
            echo json_encode(['success' => false, 'message' => "Пользователь не найден (ID: $user_id)"]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . mysqli_stmt_error($stmt)]);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit;
}

// Полное обновление
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$avatar = trim($_POST['avatar'] ?? 'avatar1.jpg');
// Проверяем, что имя и email заполнены
if ($name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Заполни имя и email']);
    exit;
}

// Подключаемся к БД
$conn = connect();
// Подготавливаем UPDATE для всех полей
$stmt = mysqli_prepare($conn, "UPDATE users SET name = ?, email = ?, phone = ?, city = ?, avatar = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $phone, $city, $avatar, $user_id);

// Выполняем запрос
if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'success' => true,
        'message' => 'Обновлено: ' . mysqli_stmt_affected_rows($stmt) . ' строк',
        'avatar' => $avatar
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . mysqli_stmt_error($stmt)]);
}

// Закрываем запрос и подключение
mysqli_stmt_close($stmt);
mysqli_close($conn);
