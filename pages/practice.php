<?php
// Начинаем сессию
session_start();
require_once '../config/config.php';
$role = $_SESSION['role'] ?? 'guest'; 
security_headers(); // XSS headers

// Защита от DDoS для форм
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !check_rate_limit('form_' . basename($_SERVER['PHP_SELF']))) {
    $_SESSION['error'] = 'Слишком много попыток. Подождите минуту.';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Функция для проверки авторизации
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
requireAuth(); // Требуем авторизацию
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Практика</title>
    <meta name="description" content="Практикуйте буддийские медитации Випассана, Саматха и Метта. Встроенный таймер сессии, аудиосопровождение, заметки и сохранение результатов практики.">
    <meta name="keywords" content="практика медитации, буддийская медитация онлайн, таймер для медитации, випассана, саматха, метта, осознанность, практика осознанности, аудиомедитация">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/practice.css">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../index.php" class="logo"><img src="../assets/images/logo.png" width="70" alt="Logo"></a></li>
            <?php if ($role == 'user'): ?>
                <li><a href="test.php">Тест</a></li>
                <li><a href="practice.php" class="active">Практика</a></li>
                <li><a href="incense.php">Благовония</a></li>
                <li><a href="profile.php">Профиль</a></li>
                <li><a href="statistics.php">Статистика</a></li>
                <li><a href="../actions/logout.php">Выход</a></li>
            <?php elseif ($role == 'admin'): ?>
                <li><a href="../admin/index.php">Админ-панель</a></li>
                <li><a href="profile.php">Профиль</a></li>
                <li><a href="../actions/logout.php">Выход</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="practice">
        <!-- Статус аудио -->
        <div class="audio-mini">
            <span id="audioStatus" class="audio-status">Аудио будет включаться вместе с практикой</span>
        </div>

        <form id="practiceForm" onsubmit="return false;">
            <!-- Выбор типа практики -->
            <select name="practice_type" id="practiceSelect">
                <option value="">Выбери практику</option>
                <option value="vipassana">Випассана</option>
                <option value="samatha">Саматха</option>
                <option value="metta">Метта</option>
            </select>

            <!-- Описание практики -->
            <div id="meditationInfo" class="meditation-info">
                Выбери практику, чтобы увидеть описание.
            </div>

            <!-- Заметки о практике -->
            <textarea name="notes" id="practiceNotes" placeholder="Заметки о практике..."></textarea>

            <!-- Кнопка начала практики -->
            <button type="button" id="startBtn">Начать практику</button>
        </form>

        <!-- Аудио-плеер -->
        <audio id="meditationAudio" preload="auto" style="display:none;"></audio>

        <!-- Таймер -->
        <div id="timer" style="display:none;">
            <div id="timeDisplay">00:00</div>
            <div class="timer-controls">
                <button type="button" class="timer-btn" id="pauseBtn">Пауза</button>
                <button type="button" class="timer-btn" id="resumeBtn">Продолжить</button>
                <button type="button" class="timer-btn" id="resetBtn">Сбросить</button>
                <button type="button" class="timer-btn save-btn" id="saveBtn">Сохранить сессию</button>
            </div>
        </div>
    </main>

    <!-- JS-файл таймера -->
    <script src="../assets/js/timer.js?v=<?php echo filemtime('../assets/js/timer.js'); ?>"></script>

    <footer>
        <p>© 2026 Все права защищены</p>
    </footer>
</body>
</html>