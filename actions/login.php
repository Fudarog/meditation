<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию

// Подключаемся к БД
$conn = connect();
// Если подключение не удалось — ошибка
if (!$conn) {
    $_SESSION['errors'] = ['Ошибка БД'];
    header('Location: ../pages/login.php');
    exit;
}

// Если запрос POST — пытаемся войти
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errors'] = ['CSRF ошибка'];
        header('Location: ../pages/login.php');
        exit;
    }

    // Получаем email и пароль
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Массив для ошибок валидации
    $errors = [];
    // Проверка формата email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Неверный email';
    // Проверка длины пароля
    if (strlen($password) < 6) $errors[] = 'Неверный пароль';

    // Если ошибок нет — пробуем найти пользователя
    if (empty($errors)) {
        // Единый запрос для получения данных пользователя
        $stmt = $conn->prepare("SELECT id, name, password_hash, verified, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Если пользователь найден и пароль верный
        if ($user && password_verify($password, $user['password_hash'])) {
            // Проверяем, подтверждён ли email
            if ($user['verified'] == 0) {
                $_SESSION['errors'] = ['Подтвердите email! Проверьте почту/спам.'];
                header('Location: ../pages/login.php');
                exit;
            }

            // Логин успешен — сохраняем данные в сессии
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['role'] = $user['role'];

            // Перенаправляем в зависимости от роли
            if ($user['role'] === 'admin') {
                header('Location: ../admin/index.php');  // Админ-панель
            } else {
                header('Location: ../pages/profile.php');  // Обычный пользователь
            }
            exit;
        } else {
            // Неверный email или пароль
            $_SESSION['errors'] = ['Неверный email/пароль'];
        }
    }

    // Если были ошибки валидации — сохраняем их
    if (!empty($errors)) $_SESSION['errors'] = $errors;
    header('Location: ../pages/login.php');
    exit;
}
