<?php
// Начинаем сессию
session_start();
// Получаем роль пользователя (если не авторизован — 'guest')
$role = $_SESSION['role'] ?? 'guest';

// Функция для проверки авторизации
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: pages/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta-теги -->
    <title>Буддийские медитации</title>
    <meta name="description" content="Буддийские медитации и практики осознанности. Пройдите тест, подберите медитацию для себя, используйте таймер сессий и благовония для глубокого погружения. InnerPeace — ваш путь к внутреннему покою.">
    <meta name="keywords" content="буддийские медитации, практика осознанности, InnerPeace, медитация для начинающих, подбор медитации, таймер для медитации, благовония, духовные практики, осознанность, покой ума">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <link rel="icon" href="assets/images/logo.png" type="image/png">
</head>

<body>
    <!-- Навигация -->
    <nav>
        <ul>
            <li><a href="index.php" class="logo">
                <img src="assets/images/logo.png" width="70px" alt="Логотип">
            </a></li>
            
            <!-- Для гостей -->
            <?php if ($role == 'guest'): ?>
                <li><a href="pages/test.php">Тест</a></li>
                <li><a href="pages/login.php">Вход</a></li>
                <li><a href="pages/register.php">Регистрация</a></li>
            
            <!-- Для авторизованных пользователей -->
            <?php elseif ($role == 'user'): ?>
                <li><a href="pages/test.php">Тест</a></li>
                <li><a href="pages/practice.php">Практика</a></li>
                <li><a href="pages/incense.php">Благовония</a></li>
                <li><a href="pages/profile.php">Профиль</a></li>
                <li><a href="pages/statistics.php">Статистика</a></li>
                <li><a href="actions/logout.php">Выход</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main>
        <!-- Главный экран -->
        <section class="hero">
            <h1>INNERPEACE</h1>
            <p>Пройди тест и подбери медитацию<br>специально для тебя</p>
            <a href="pages/test.php" class="btn">Пройти тест</a>
        </section>

        <!-- Блок "Зачем нужна медитация?" -->
        <section class="info">
            <h2>Зачем нужна медитация?</h2>
            <div class="info-grid">
                <div class="info-text">
                    <p>Медитация — это способ побыть наедине с собой, не «делать», а просто быть и наблюдать за своим умом и телом.</p>
                    <p>Это помогает замедлиться, когда всё вокруг крутится слишком быстро, и немного остыть от тревог, раздражения и перегруза.</p>
                </div>
                <div class="info-image">
                    <img src="assets/images/meditation.jpg" alt="Медитация">
                </div>
            </div>
        </section>

        <!-- Три функции приложения -->
        <section class="features">
            <div class="feature-item">
                <div class="feature-image">
                    <img src="assets/images/incense.png" alt="Благовония">
                </div>
                <div class="feature-content">
                    <h3>Благовония</h3>
                    <p>Поставьте благовоние как подношение и точку начала медитации</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="assets/images/timer.png" alt="Таймер">
                </div>
                <div class="feature-content">
                    <h3>Таймер сессии</h3>
                    <p>Запустите сессию — таймер и мелодия помогают не отвлекаться</p>
                </div>
            </div>
            <div class="feature-item">
                <div class="feature-image">
                    <img src="assets/images/question.png" alt="Тест">
                </div>
                <div class="feature-content">
                    <h3>Подбор практики</h3>
                    <p>Ответьте на вопросы и получите персональную рекомендацию</p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <p>© 2026 Все права защищены</p>
    </footer>
</body>
</html>