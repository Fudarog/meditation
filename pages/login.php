<?php
// Начинаем сессию
session_start();
require_once '../config/config.php';

// Подключаем защиту 
security_headers();

// DDoS-защита
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !check_rate_limit('form_' . basename($_SERVER['PHP_SELF']))) {
    htmlspecialchars($_SESSION['error'] = 'Слишком много попыток. Подождите минуту.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Очистка сессии при переходе по ссылке 
if (isset($_GET['clear'])) {
    session_unset();
    header('Location: /pages/login.php?forgot=1');
    exit;
}

// Генерируем CSRF-токен, если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Показываем форму восстановления пароля
$showForgotForm = isset($_GET['forgot']) && $_GET['forgot'] === '1';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>
<body>
    <div class="page-wrapper">
        <!-- Сообщения об успехе -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <!-- Сообщения об ошибках -->
        <?php if (isset($_SESSION['errors'])): ?>
            <div class="alert error">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <div class="login-container">
            <?php if ($showForgotForm): ?>
                <!-- Шаг 1: email -->
                <?php if (!isset($_SESSION['reset_email'])): ?>
                    <form action="../actions/forgot_password.php" method="post" class="auth-form">
                        <h2>Восстановление пароля</h2>
                        <p class="step-info">Шаг 1/3: Введите email</p>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="email" name="email" placeholder="Email" required>
                        <button type="submit" class="btn-primary">Получить код</button>
                        <p class="form-footer">
                            <a href="/pages/login.php" class="link-back">← Назад к входу</a>
                        </p>
                    </form>

                <!-- Шаг 2: ввод кода -->
                <?php elseif (isset($_SESSION['reset_code']) && !isset($_SESSION['code_verified'])): ?>
                    <form action="../actions/verify_code.php" method="post" class="auth-form">
                        <h2>Восстановление пароля</h2>
                        <p class="step-info">Шаг 2/3</p>
                        <div class="code-display">Ваш код: <strong><?= $_SESSION['reset_code'] ?></strong></div>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="text" name="code" placeholder="Введите код" maxlength="6" required>
                        <button type="submit" class="btn-primary">Проверить код</button>
                        <p class="form-footer">
                            <a href="/pages/login.php" class="link-back">← Назад к входу</a>
                        </p>
                    </form>

                <!-- Шаг 3: новый пароль -->
                <?php elseif (isset($_SESSION['code_verified'])): ?>
                    <form action="../actions/reset_final.php" method="post" class="auth-form">
                        <h2>Новый пароль</h2>
                        <p class="step-info">Шаг 3/3</p>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="password" name="new_password" placeholder="Новый пароль" required>
                        <input type="password" name="confirm_password" placeholder="Повторите пароль" required>
                        <button type="submit" class="btn-primary">Сохранить новый пароль</button>
                        <p class="form-footer">
                            <a href="/pages/login.php" class="link-back">← Назад к входу</a>
                        </p>
                    </form>
                <?php endif; ?>

            <?php else: ?>
                <!-- Форма авторизации -->
                <form action="../actions/login.php" method="post" class="auth-form">
                    <h2>Авторизация</h2>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="form_timestamp" value="<?= time() ?>">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Пароль" required>
                    <button type="submit" class="btn-primary">Войти</button>
                    <p class="form-footer">
                        <a href="/pages/login.php?forgot=1" class="link-forgot">Забыли пароль?</a>
                    </p>
                    <div class="divider"></div>
                    <p class="form-footer">
                        Нет аккаунта? <a href="register.php" class="link-register">Зарегистрироваться</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>