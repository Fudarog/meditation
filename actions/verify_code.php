<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию
$conn = connect(); // Подключаемся к БД

// Если запрос не POST — перенаправляем на страницу входа
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Проверка CSRF-токена
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['errors'] = ['CSRF ошибка'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Проверяем, что сессия сброса существует
if (!isset($_SESSION['reset_code'])) {
    $_SESSION['errors'] = ['Сессия сброса истекла'];
    header('Location: ../pages/login.php?forgot=1');
    exit;
}

// Сравниваем введённый код с кодом из сессии
if ($_POST['code'] == $_SESSION['reset_code']) {
    // Код верный — помечаем как проверенный
    $_SESSION['code_verified'] = true;
    $_SESSION['message'] = 'Код верный! Установите новый пароль.';
} else {
    // Код неверный — ошибка и удаляем код из сессии
    $_SESSION['errors'] = ['Неверный код'];
    unset($_SESSION['reset_code']);
}

// Закрываем подключение и перенаправляем на страницу входа
mysqli_close($conn);
header('Location: ../pages/login.php?forgot=1');
exit;
