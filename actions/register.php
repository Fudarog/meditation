<?php
session_start(); // Начинаем сессию
require_once '../config/config.php'; // Подключаем конфигурацию

// Подключаемся к БД
$conn = connect();
if (!$conn) {
    $_SESSION['message'] = 'Ошибка подключения к БД';
    header('Location: ../pages/register.php');
    exit;
}

// Если запрос POST — обрабатываем регистрацию
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errors'] = ['Неверный CSRF токен'];
    } else {
        // Получаем данные из формы
        $email = trim($_POST['email']);
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        // Очистка телефона от некодирующих символов
        $cleanedPhone = preg_replace('/[^\d]/', '', $phone);
        // Проверка формата телефона (+7 или 7, 10 цифр)
        if (empty($cleanedPhone) || !preg_match('/^[78]\d{10}$/', $cleanedPhone)) {
            $errors[] = 'Телефон: +7 или 7, 10 цифр';
        }
        
        // Массив ошибок валидации
        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Неверный email';
        if (strlen($password) < 6) $errors[] = 'Пароль минимум 6 символов';
        if ($password !== $confirm_password) $errors[] = 'Пароли не совпадают';
        if (empty($name)) $errors[] = 'Имя обязательно';

        // Проверка уникальности email
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) $errors[] = 'Email уже зарегистрирован';
        }

        // Если всё валидно — хешируем пароль и сохраняем пользователя
        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (email, password_hash, name, phone, city) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $email, $hash, $name, $phone, $city);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Регистрация успешна! Проверьте почту';
                unset($_SESSION['csrf_token']);
                header('Location: ../pages/login.php');
                exit;
            } else {
                $_SESSION['errors'] = ['Ошибка сохранения в БД'];
            }
        } else {
            $_SESSION['errors'] = $errors;
        }
    }
    // Перенаправляем обратно на страницу регистрации
    header('Location: ../pages/register.php');
    exit;
}
