<?php
// Начинаем сессию
session_start();
// Подключаем конфигурацию
require_once '../config/config.php';

// Проверка: только администратор
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Подключаемся к БД
$conn = connect();

// Получаем количество записей из каждой таблицы
$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users"))['c'],           // Пользователи
    'sessions' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM sessions"))['c'],     // Сессии практики
    'incense' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM incense"))['c'],       // Благовония
    'attempts' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM login_attempts"))['c'], // Попытки входа
    'users_achievements' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM user_achievements"))['c'], // Достижения пользователей
    'achievements' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM achievements"))['c'], // Доступные достижения
    'logs' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM password_logs"))['c']     // Логи сброса пароля
];
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="navbar-title">Админ-панель</h1>
            <div class="navbar-logout">
                <a href="../actions/logout.php">Выход</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row g-4 mb-5">
            <!-- Карточка: Пользователи -->
            <div class="col-md-3 col-sm-6">
                <a href="users.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill card-icon" style="--bs-icon-color: #FF6B35;"></i>
                        <h3 class="card-title"><?= number_format($stats['users']) ?></h3>
                        <p class="card-text">Пользователи</p>
                    </div>
                </a>
            </div>

            <!-- Карточка: Сессии -->
            <div class="col-md-3 col-sm-6">
                <a href="sessions.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-music-note-beamed card-icon" style="--bs-icon-color: #10B981;"></i>
                        <h3 class="card-title"><?= number_format($stats['sessions']) ?></h3>
                        <p class="card-text">Сессии</p>
                    </div>
                </a>
            </div>

            <!-- Карточка: Благовония -->
            <div class="col-md-3 col-sm-6">
                <a href="incense.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-fire card-icon" style="--bs-icon-color: #FF8C42;"></i>
                        <h3 class="card-title"><?= number_format($stats['incense']) ?></h3>
                        <p class="card-text">Благовония</p>
                    </div>
                </a>
            </div>

            <!-- Карточка: Попытки входа -->
            <div class="col-md-3 col-sm-6">
                <a href="login_attempts.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-lock-fill card-icon" style="--bs-icon-color: #EF4444;"></i>
                        <h3 class="card-title"><?= number_format($stats['attempts']) ?></h3>
                        <p class="card-text">Попытки входа</p>
                    </div>
                </a>
            </div>

            <!-- Карточка: Достижения -->
            <div class="col-md-3 col-sm-6">
                <a href="achievements.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-trophy-fill card-icon" style="--bs-icon-color: #F59E0B;"></i>
                        <h3 class="card-title"><?= number_format($stats['achievements']) ?></h3>
                        <p class="card-text">Достижения</p>
                    </div>
                </a>
            </div>

            <!-- Карточка: Достижения пользователей -->
            <div class="col-md-3 col-sm-6">
                <a href="users_achievements.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-award-fill card-icon" style="--bs-icon-color: #F59E0B;"></i>
                        <h3 class="card-title"><?= number_format($stats['users_achievements']) ?></h3>
                        <p class="card-text">Достижения пользователей</p>
                    </div>
                </a>
            </div>

            <!-- Карточка: Логи паролей -->
            <div class="col-md-3 col-sm-6">
                <a href="password_logs.php" class="card h-100 text-decoration-none text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-journal-text card-icon" style="--bs-icon-color: #6B7280;"></i>
                        <h3 class="card-title"><?= number_format($stats['logs']) ?></h3>
                        <p class="card-text">Логи паролей</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>