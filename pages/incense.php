<?php
// Начинаем сессию
session_start(); 
// Сбрасываем флаг 
unset($_SESSION['incense_done']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Зажечь благовоние</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="stylesheet" href="../assets/css/incense.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>

<body>
    <header>
        <nav>
            <ul>
                <li><a href="../index.php" class="logo">
                    <img src="../assets/images/logo.png" width="70px" alt="Logo">
                </a></li>
                <li><a href="test.php">Тест</a></li>
                <li><a href="practice.php">Практика</a></li>
                <li><a href="incense.php" class="active">Благовония</a></li>
                <li><a href="profile.php">Профиль</a></li>
                <li><a href="statistics.php">Статистика</a></li>
                <li><a href="../actions/logout.php">Выход</a></li>
            </ul>
        </nav>
    </header>

    <div class="incense-wrapper" style="overflow: hidden; position: relative;">
        <div class="incense-box">
            <p>Нажмите на палочку, чтобы зажечь</p>
            
            <!-- Контейнер с двумя картинками -->
            <div class="incense-container" onclick="lightUp()">
                <img src="../assets/images/incense_off.png" id="incenseImg" alt="Благовоние">
                <img src="../assets/images/incense_smoke.gif" id="smokeEffect" alt="Дым">
            </div>

            <br>
            <button id="altarBtn" onclick="placeOnAltar(<?php echo $_SESSION['user_id'] ?? 1; ?>)">
                Поставить на алтарь
            </button>
        </div>
    </div>

    <footer>
        <p>© 2026 Все права защищены</p>
    </footer>

    <script>
        // Функция: зажечь благовоние 
        function lightUp() {
            // Показываем слой с дымом
            document.getElementById('smokeEffect').style.display = 'block';
            // Показываем кнопку
            document.getElementById('altarBtn').style.display = 'inline-block';
        }

        // Функция: поставить на алтарь 
        function placeOnAltar(userId) {
            const btn = document.getElementById('altarBtn');
            btn.innerText = 'Записываю...';
            btn.disabled = true;

            // Отправляем POST-запрос 
            fetch('../actions/set_incense.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    // Переходим на страницу статистики
                    window.location.href = 'statistics.php';
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    window.location.href = '../index.php';
                });
        }
    </script>
</body>
</html>