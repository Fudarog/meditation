<?php
// Начинаем сессию
session_start();
require_once '../config/config.php';
security_headers();

// DDoS check для форм
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !check_rate_limit('form_' . basename($_SERVER['PHP_SELF']))) {
    $_SESSION['error'] = 'Слишком много попыток. Подождите минуту.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Генерируем CSRF-токен, если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="../assets/css/register.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>
<body>
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

    <div class="register-container">
        <form method="post" action="../actions/register.php" class="auth-form">
            <h2>Регистрация</h2>
            
            <!-- CSRF токен -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="form_timestamp" value="<?= time() ?>">
            <input type="email" name="email" placeholder="Почта" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <input type="text" name="name" placeholder="Имя" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            <input type="tel" name="phone" placeholder="+79001234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
            <input type="text" name="city" placeholder="Город" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <input type="password" name="confirm_password" placeholder="Подтверждение пароля" required>
            <button type="submit" class="btn-primary">Зарегистрироваться</button>
            <p class="form-footer">
                Уже есть аккаунт? <a href="login.php" class="link-login">Войти</a>
            </p>
        </form>
    </div>

    <!-- Маска для телефона -->
    <script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
    <script>
        Inputmask("+7 (999) 999-99-99").mask(document.querySelector('input[name="phone"]'));
    </script>
</body>
</html>