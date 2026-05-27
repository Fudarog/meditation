<?php
session_start();
require_once '../config/config.php';

// Проверяем, есть ли токен и ID пользователя в GET-параметрах
if (isset($_GET['token']) && isset($_GET['user'])) {
    $token = $_GET['token'];
    $user_id = (int)$_GET['user'];
    
    $conn = connect();
    // Обновляем статус пользователя на active, если токен верный
    $stmt = mysqli_prepare($conn, "UPDATE users SET status = 'active', activation_token = NULL WHERE id = ? AND activation_token = ? AND status = 'pending'");
    mysqli_stmt_bind_param($stmt, "is", $user_id, $token);
    $success = mysqli_stmt_execute($stmt);
    
    if ($success && mysqli_stmt_affected_rows($stmt) > 0) {
        // Аккаунт успешно подтверждён
        $_SESSION['message'] = 'Аккаунт успешно подтвержден! Можете войти.';
        $status = 'success';
    } else {
        // Неверная или истекшая ссылка
        $_SESSION['errors'] = ['Неверная или истекшая ссылка подтверждения.'];
        $status = 'error';
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

// Перенаправляем на страницу входа
header('Location: login.php');
exit();
?>