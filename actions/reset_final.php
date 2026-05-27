<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию
$conn = connect(); // Подключаемся к БД

// Проверка защиты от DDoS
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_rate_limit('reset_final')) {
    $_SESSION['errors'] = ['Ошибка запроса'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Проверка CSRF-токена
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['errors'] = ['CSRF ошибка'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Проверяем, что сессия сброса существует и код уже был проверен
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['code_verified'])) {
    $_SESSION['errors'] = ['Сессия сброса истекла'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Получаем новые пароли
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Пароли должны совпадать
if ($new_password !== $confirm_password) {
    $_SESSION['errors'] = ['Пароли не совпадают'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Пароль должен быть минимум 6 символов
if (strlen($new_password) < 6) {
    $_SESSION['errors'] = ['Пароль слишком короткий (минимум 6 символов)'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Хешируем новый пароль и обновляем в БД
$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE email = ?");
mysqli_stmt_bind_param($stmt, "ss", $hashed, $_SESSION['reset_email']);
$result = mysqli_stmt_execute($stmt);

// Если обновление не удалось — ошибка
if (!$result) {
    $_SESSION['errors'] = ['Ошибка БД'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Записываем лог успешного сброса пароля
$ip = $_SERVER['REMOTE_ADDR'];
$log_stmt = mysqli_prepare($conn, "INSERT INTO password_logs (email, action, ip, created_at) VALUES (?, 'reset_success', ?, NOW())");

// Обработка возможных ошибок при записи лога
if ($log_stmt === false) {
    error_log("Prepare failed: " . mysqli_error($conn));
} else {
    if (!mysqli_stmt_bind_param($log_stmt, "ss", $_SESSION['reset_email'], $ip)) {
        error_log("Bind failed: " . mysqli_stmt_error($log_stmt));
    } else {
        if (!mysqli_stmt_execute($log_stmt)) {
            error_log("Execute failed: " . mysqli_stmt_error($log_stmt));
        }
    }
    mysqli_stmt_close($log_stmt);
}

// Удаляем старые записи лога (кроме успешного сброса)
$del_stmt = mysqli_prepare($conn, "DELETE FROM password_logs WHERE email = ? AND action != 'reset_success'");
if ($del_stmt !== false) {
    mysqli_stmt_bind_param($del_stmt, "s", $_SESSION['reset_email']);
    mysqli_stmt_execute($del_stmt);
    mysqli_stmt_close($del_stmt);
}
// Закрываем подключение
mysqli_close($conn);

// Очищаем сессию сброса и показываем успешное сообщение
unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['code_verified']);
$_SESSION['message'] = 'Пароль успешно изменён! Можете войти.';
header('Location: ../pages/login.php');
exit;
