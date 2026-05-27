<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию

unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['code_verified']); // Очищаем переменные сессии, связанные с сбросом пароля

// Подключаемся к БД
$conn = connect();
// Если подключение не удалось — сохраняем ошибку и перенаправляем на страницу входа
if (!$conn) {
    $_SESSION['errors'] = ['Ошибка БД'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Если запрос не POST — перенаправляем обратно на страницу входа
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Проверка CSRF-токена (защита от подделки запросов)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['errors'] = ['CSRF ошибка'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Получаем и очищаем email из запроса
$email = trim($_POST['email']);

// Если email пустой — ошибка
if (empty($email)) {
    $_SESSION['errors'] = ['Email обязателен'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Проверка пользователя
$stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Если пользователь не найден — ошибка
if (!$user) {
    $_SESSION['errors'] = ['Email не найден'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Генерация 6-значного кода для восстановления
$reset_code = sprintf("%06d", mt_rand(1, 999999));
$_SESSION['reset_code'] = $reset_code;
$_SESSION['reset_email'] = $email;

// Сохраняем сообщение для пользователя с кодом
$_SESSION['show_forgot_success'] = true;
$_SESSION['forgot_message'] = "Ваш код восстановления: <strong>$reset_code</strong><br>Запомните код и введите его ниже.";

// Записываем в лог попытку сброса пароля
$log_stmt = mysqli_prepare($conn, "INSERT INTO password_logs (email, reset_code, created_at, ip) VALUES (?, ?, NOW(), ?)");
mysqli_stmt_bind_param($log_stmt, "sss", $email, $reset_code, $_SERVER['REMOTE_ADDR']);
mysqli_stmt_execute($log_stmt);

// Закрываем подключение и перенаправляем на страницу входа
mysqli_close($conn);
header('Location: ../pages/login.php?forgot=1');
exit;
