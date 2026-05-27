<?php
// Начинаем сессию
session_start();
// Подключаем конфигурацию
require_once '../config/config.php';

// Проверка: только администратор может видеть эту страницу
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Подключаемся к БД
$conn = connect();
$errors = [];
$success = '';

// Удаление записи благовония 
if ($_GET['delete'] ?? false) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM incense WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Благовоние удалено';
    } else {
        $errors[] = 'Ошибка удаления';
    }
    mysqli_stmt_close($stmt);
}

// Пагинация
$page = (int)($_GET['page'] ?? 1);       // Текущая страница
$per_page = 15;                          // Записей на странице
$offset = ($page - 1) * $per_page;       // Смещение

// Получаем общее количество записей
$total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM incense");
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['total'];
mysqli_stmt_close($total_stmt);
$total_pages = max(1, ceil($total / $per_page));

// Получаем записи благовоний с именем пользователя
$stmt = mysqli_prepare($conn, "SELECT i.*, u.name 
    FROM incense i 
    JOIN users u ON i.user_id = u.id 
    ORDER BY i.created_at DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Преобразуем в массив
$incense = [];
while ($row = mysqli_fetch_assoc($result)) {
    $incense[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Благовония</title>
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .table-responsive {
            max-height: calc(100vh - 220px);
            overflow: auto;
        }

        .table th {
            position: sticky;
            top: 0;
            z-index: 10;
            background: linear-gradient(135deg, var(--orange-dark), var(--orange-light)) !important;
            color: white !important;
            font-weight: 600;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-1px);
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar">
        <div class="container">
            <div class="navbar-back"><a href="index.php">← Назад</a></div>
            <div class="navbar-logout"><a href="../actions/logout.php">Выход</a></div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1><i class="bi bi-flower1 text-warning me-2"></i>Благовония</h1>
                <small class="text-muted">Всего: <?= $total ?> | Страница <?= $page ?> из <?= $total_pages ?></small>
            </div>
        </div>

        <!-- Сообщение об успехе -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Ошибки -->
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <!-- Таблица благовоний -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Заметка</th>
                            <th>Дата</th>
                            <th>Количество</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($incense)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-flower1 fs-1 mb-3 opacity-50"></i>
                                    Благовония не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($incense as $i): ?>
                                <tr>
                                    <td><strong><?= $i['id'] ?></strong></td>
                                    <td><i class="bi bi-person me-1 text-secondary"></i><?= htmlspecialchars($i['name']) ?></td>
                                    <td><span class="badge bg-primary"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($i['message']) ?></span></td>
                                    <td><i class="bi bi-calendar-event me-1 text-success"></i><?= date('d.m H:i', strtotime($i['created_at'])) ?></td>
                                    <td><strong><?= $i['count'] ?></strong></td>
                                    <td>
                                        <a href="?delete=<?= $i['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить «<?= htmlspecialchars($i['message']) ?>» (#<?= $i['id'] ?>)?')" title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>