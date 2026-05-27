<?php
require_once '../config/config.php'; // подключаем основной файл конфигурации (подключение к БД и другие настройки)

// Проверяет достижения пользователя на основе количества завершённых сессий практики
function checkUserAchievements($conn, $user_id)
{
    // Подготавливаем SQL-запрос для подсчёта завершённых сессий пользователя
    $stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) AS practice_count
    FROM sessions
    WHERE user_id = ? AND status = 'completed'
");

    // Привязываем параметр user_id (тип "i" = integer)
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    // Выполняем запрос
    mysqli_stmt_execute($stmt);

    // Получаем результат
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    // Получаем количество сессий и приводим к целому числу
    $practice_count = (int)$row['practice_count'];
    // Если есть хотя бы 1 сессия — разблокируем достижение "первая сессия"
    if ($practice_count >= 1) {
        unlockAchievement($conn, $user_id, 'first_session');
    }
    // Если 3+ сессии — разблокируем достижение "три сессии"
    if ($practice_count >= 3) {
        unlockAchievement($conn, $user_id, 'three_sessions');
    }
    // Если 10+ сессий — разблокируем достижение "десять сессий"
    if ($practice_count >= 10) {
        unlockAchievement($conn, $user_id, 'ten_sessions');
    }
}

// Разблокирует достижение для пользователя, если оно ещё не получено
function unlockAchievement($conn, $user_id, $code)
{
    // Проверяем, существует ли достижение с таким кодом и ещё не получено ли оно пользователем
    $stmt = mysqli_prepare($conn, "
        SELECT a.id
        FROM achievements a
        LEFT JOIN user_achievements ua 
            ON ua.achievement_id = a.id AND ua.user_id = ?
        WHERE a.code = ? AND ua.id IS NULL
        LIMIT 1
    ");

    // Привязываем параметры: user_id (integer), code (string)
    mysqli_stmt_bind_param($stmt, "is", $user_id, $code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $achievement_id);

    // Если достижение найдено и ещё не получено пользователем
    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);

        // Записываем достижение в таблицу user_achievements
        $insert = mysqli_prepare($conn, "
            INSERT INTO user_achievements (user_id, achievement_id)
            VALUES (?, ?)
        ");
        mysqli_stmt_bind_param($insert, "ii", $user_id, $achievement_id);
        mysqli_stmt_execute($insert);
        mysqli_stmt_close($insert);
    } else {
        // Достижение уже есть или не найдено — просто закрываем запрос
        mysqli_stmt_close($stmt);
    }
}
