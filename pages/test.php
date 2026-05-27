<?php
session_start();
$role = $_SESSION['role'] ?? 'guest';

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
    <title>Тест</title>
    <meta name="description" content="Пройдите тест из 7 вопросов и узнайте, какая буддийская медитация подходит именно вам: Випассана, Саматха или Метта. Персональная рекомендация за 2 минуты.">
    <meta name="keywords" content="тест на медитацию, подбор медитации, буддийские практики, випассана, саматха, метта, осознанность, какой тип медитации подходит">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/test.css">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../index.php" class="logo"><img src="../assets/images/logo.png" width="70px" alt="Logo"></a></li>
            <?php if ($role == 'guest'): ?>
                <li><a href="login.php">Вход</a></li>
                <li><a href="register.php">Регистрация</a></li>
            <?php elseif ($role == 'user'): ?>
                <li><a href="test.php" class="active">Тест</a></li>
                <li><a href="practice.php">Практика</a></li>
                <li><a href="incense.php">Благовония</a></li>
                <li><a href="profile.php">Профиль</a></li>
                <li><a href="statistics.php">Статистика</a></li>
                <li><a href="../actions/logout.php">Выход</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <main class="quiz-container">
        <!-- Прогресс-бар -->
        <div class="progress-bar">
            Вопрос <span id="current-q">1</span>/7
        </div>

        <!-- Блок с вопросом (заполняется JS) -->
        <div id="quiz">

        </div>

        <!-- Блок с результатом -->
        <div id="result" style="display:none;">
            <h2 id="recommendation"></h2>
            <p id="description"></p>
            <a href="practice.php" class="btn-primary">Начать практику</a>
            <a href="profile.php" class="btn-secondary">В профиль</a>
        </div>
    </main>

    <footer>
        <p>© 2026 Все права защищены</p>
    </footer>

    <script>
        // 7 вопросов теста с баллами для каждого варианта
        const questions = [{
                q: "Какое у Вас настроение сейчас?",
                options: ["Тревожно, мысли скачут", "Спокойно, но рассеянно", "Раздражённо"],
                scores: [1, 1, 3]
            },
            {
                q: "Что беспокоит чаще?",
                options: ["Стресс, будущее", "Сложно сосредоточиться", "Обиды, конфликты"],
                scores: [2, 1, 3]
            },
            {
                q: "Что хотите улучшить?",
                options: ["Осознанность", "Глубокий фокус", "Теплоту к людям"],
                scores: [1, 2, 3]
            },
            {
                q: "Какова Ваша энергия?",
                options: ["Перегружен мыслями", "Рассеянная", "Напряжённая"],
                scores: [1, 2, 2]
            },
            {
                q: "Реакция на проблемы?",
                options: ["Анализирую", "Отвлекаюсь", "Злюсь"],
                scores: [1, 2, 3]
            },
            {
                q: "Идеальное состояние?",
                options: ["Ясность", "Спокойствие", "Любовь"],
                scores: [1, 2, 3]
            },
            {
                q: "Что пробовали раньше?",
                options: ["Дыхание/наблюдение", "Мантра/фокус на объекте", "Благословения"],
                scores: [1, 2, 3]
            }
        ];

        let currentQuestion = 0;
        let totalScore = 0;

        // Показать текущий вопрос
        function showQuestion() {
            const q = questions[currentQuestion];
            document.getElementById('quiz').innerHTML = `
        <h3>${q.q}</h3>
        ${q.options.map((opt, i) => 
            `<button onclick="selectAnswer(${q.scores[i]})">${opt}</button>`
        ).join('')}
    `;
            document.getElementById('current-q').textContent = currentQuestion + 1;
        }

        // Выбор ответа
        function selectAnswer(score) {
            totalScore += score;
            currentQuestion++;
            if (currentQuestion < questions.length) {
                showQuestion();
            } else {
                showResult();
            }
        }

        // Показать результат
        function showResult() {
            document.getElementById('quiz').style.display = 'none';
            document.getElementById('result').style.display = 'block';

            let practice, desc;
            // Логика подбора:
            if (totalScore <= 14) {
                practice = "Випассана";
                desc = "Осознанность дыхания";
            } else if (totalScore <= 21) {
                practice = "Саматха";
                desc = "Концентрация и покой";
            } else {
                practice = "Метта";
                desc = "Любовь ко всем";
            }

            document.getElementById('recommendation').textContent = practice;
            document.getElementById('description').textContent = desc;
        }

        showQuestion(); // Запуск теста
    </script>
</body>
</html>