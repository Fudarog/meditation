<?php
// Начинаем сессию
session_start();
require_once '../config/config.php';

// Получаем ID пользователя
$user_id = (int)($_SESSION['user_id'] ?? 0);
// Подключаемся к БД
$conn = connect();

// Основная статистика
$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(SUM(duration), 0) as total_seconds,
           COALESCE(AVG(duration), 0) as avg_session,
           COUNT(*) as total_sessions
    FROM sessions WHERE user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: 
    ['total_seconds' => 0, 'avg_session' => 0, 'total_sessions' => 0];

// Счетчик благовоний
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS incense_count FROM incense WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$incense_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$incense_count = (int)($incense_row['incense_count'] ?? 0);

// Регулярность практики
// Получаем все уникальные даты сессий
$stmt = mysqli_prepare($conn, "SELECT DISTINCT DATE(date) AS day FROM sessions WHERE user_id = ? ORDER BY day DESC");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$streak_result = mysqli_stmt_get_result($stmt);

$streak = 0;
$expected = date('Y-m-d');
while ($row = mysqli_fetch_assoc($streak_result)) {
    if ($row['day'] === $expected) {
        $streak++;
        $expected = date('Y-m-d', strtotime($expected . ' -1 day'));
    } else {
        break;
    }
}

// Диаграмма часы по типам медитации
$stmt = mysqli_prepare($conn, "
    SELECT type, COALESCE(SUM(duration), 0) as total_duration
    FROM sessions WHERE user_id = ? GROUP BY type
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$chart_result = mysqli_stmt_get_result($stmt);
$chart_data = [];
while ($row = mysqli_fetch_assoc($chart_result)) {
    $chart_data[] = $row;
}
if (empty($chart_data)) {
    $chart_data = [['type' => 'Нет данных', 'total_duration' => 1]];
}

// Календарь активности
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n'); // Текущий месяц
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');   // Текущий год
// Корректируем границы
if ($month < 1) $month = 1;
if ($month > 12) $month = 12;
if ($year < 2000) $year = (int)date('Y');

// Предыдущий и следующий месяц для навигации
$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// Количество дней в месяце и первый день недели
$days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$first_day = date('w', strtotime("$year-$month-01"));

// Получаем активные дни месяца
$stmt = mysqli_prepare($conn, "
    SELECT DATE(date) AS day, COUNT(*) AS sessions_count
    FROM sessions WHERE user_id = ? AND YEAR(date) = ? AND MONTH(date) = ?
    GROUP BY DATE(date)
");
mysqli_stmt_bind_param($stmt, "iii", $user_id, $year, $month);
mysqli_stmt_execute($stmt);
$activity_result = mysqli_stmt_get_result($stmt);
$active_days = [];
while ($row = mysqli_fetch_assoc($activity_result)) {
    $active_days[$row['day']] = (int)$row['sessions_count'];
}

// История сессий
$stmt = mysqli_prepare($conn, "
    SELECT date, type, duration
    FROM sessions WHERE user_id = ? ORDER BY date DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$history_result = mysqli_stmt_get_result($stmt);

$total_hours = round($user_stats['total_seconds'] / 3600, 1); // Всего часов
$avg_session_seconds = (int)$user_stats['avg_session'];
$avg_minutes = floor($avg_session_seconds / 60);
$avg_seconds = $avg_session_seconds % 60;
$avg_session = sprintf('%02d:%02d', $avg_minutes, $avg_seconds);

// Названия месяцев
$month_names = [
    1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
    5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
    9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
];

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/statistics.css">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
</head>
<body>
    <nav>
        <ul>
            <li><a href="../index.php" class="logo"><img src="../assets/images/logo.png" width="70" alt="Logo"></a></li>
            <li><a href="test.php">Тест</a></li>
            <li><a href="practice.php">Практика</a></li>
            <li><a href="incense.php">Благовония</a></li>
            <li><a href="profile.php">Профиль</a></li>
            <li><a href="statistics.php" class="active">Статистика</a></li>
            <li><a href="../actions/logout.php">Выход</a></li>
        </ul>
    </nav>

    <div class="soft-blob"></div>
    <div class="container">
        <div class="grid-2cols">
            <!-- Диаграмма -->
            <div class="chart-section">
                <div class="card">
                    <div class="card-header">
                        <h5>Часы по типам медитаций</h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Карточки статистики -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4><?php echo $total_hours; ?></h4>
                        <p>Всего часов</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo $avg_session; ?></h4>
                        <p>Средняя сессия</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo $streak; ?></h4>
                        <p>Стрик (дней)</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo $incense_count; ?></h4>
                        <p>Благовоний</p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo (int)$user_stats['total_sessions']; ?></h4>
                        <p>Всего сессий</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2cols">
            <!-- Календарь -->
            <div class="card">
                <div class="card-header">
                    <h5>Активность за <?php echo $month_names[(int)$month]; ?> <?php echo $year; ?></h5>
                </div>
                <div class="card-body">
                    <div class="calendar-nav">
                        <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>">&larr; Назад</a>
                        <span><?php echo $month_names[(int)$month]; ?> <?php echo $year; ?></span>
                        <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>">Вперёд &rarr;</a>
                    </div>
                    <div class="calendar">
                        <div class="calendar-grid calendar-head">
                            <div>Пн</div><div>Вт</div><div>Ср</div><div>Чт</div><div>Пт</div><div>Сб</div><div>Вс</div>
                        </div>
                        <div class="calendar-grid">
                            <?php
                            // Корректируем первый день недели
                            $day_of_week = ($first_day == 0) ? 6 : $first_day - 1;
                            // Пустые ячейки до первого дня
                            for ($i = 0; $i < $day_of_week; $i++) {
                                echo "<div class='calendar-day empty'></div>";
                            }
                            // Дни месяца
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $date_key = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $is_active = isset($active_days[$date_key]);
                                $count = $active_days[$date_key] ?? 0;
                                $class = $is_active ? 'calendar-day active' : 'calendar-day';
                                echo "<div class='$class'>";
                                echo "<span class='day-num'>$day</span>";
                                if ($is_active) echo "<span class='day-count'>$count</span>";
                                echo "</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- История сессий -->
            <div class="card">
                <div class="card-header">
                    <h5>История сессий</h5>
                </div>
                <div class="sessions-container">
                    <?php
                    if (mysqli_num_rows($history_result) == 0) {
                        echo "<div class='no-sessions'>Сессии пока не найдены 😌</div>";
                    } else {
                        while ($row = mysqli_fetch_assoc($history_result)) {
                            $date = date('d.m H:i', strtotime($row['date']));
                            $minutes = floor($row['duration'] / 60);
                            $seconds = $row['duration'] % 60;
                            echo "<div class='sessions-item'>";
                            echo "<div class='session-info'>";
                            echo "<strong>{$date}</strong>";
                            echo "<span class='session-type'>" . htmlspecialchars($row['type']) . "</span>";
                            echo "</div>";
                            echo "<span class='session-duration'>" . sprintf('%02d:%02d', $minutes, $seconds) . "</span>";
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2026 Все права защищены</p>
    </footer>

    <!-- Диаграмма Chart.js -->
    <script>
        const ctx = document.getElementById('typeChart').getContext('2d');
        const chartData = <?php echo json_encode($chart_data); ?>;

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: chartData.map(item => item.type),
                datasets: [{
                    data: chartData.map(item => parseFloat(item.total_duration)),
                    backgroundColor: ['#f59e0b', '#ef4444', '#8b5cf6', '#10b981', '#3b82f6'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: { size: 14 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>