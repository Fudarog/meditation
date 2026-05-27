<?php
// Начинаем сессию
session_start();
// Подключаем конфигурацию
require_once '../config/config.php';

// Проверка: только администратор
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Подключаемся к БД
$conn = connect();
$errors = [];
$success = '';

// Удаления записи попытки входа
if ($_GET['delete'] ?? false) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM login_attempts WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Попытка входа удалена';
    } else {
        $errors[] = 'Ошибка удаления';
    }
    mysqli_stmt_close($stmt);
}

// Пагинация
$page = (int)($_GET['page'] ?? 1);       // Текущая страница
$per_page = 20;                          // Записей на странице
$offset = ($page - 1) * $per_page;       // Смещение

// Получаем общее количество попыток
$total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM login_attempts");
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['total'];
mysqli_stmt_close($total_stmt);
$total_pages = max(1, ceil($total / $per_page));

// Получаем попытки входа с именем пользователя 
$stmt = mysqli_prepare($conn, "SELECT la.*, u.name 
    FROM login_attempts la 
    LEFT JOIN users u ON la.user_id = u.id 
    ORDER BY la.attempt_at DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Преобразуем в массив
$attempts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $attempts[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Попытки входа</title>
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

        .status-ok {
            color: #198754;
        }

        .status-bad {
            color: #dc3545;
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
                <h1><i class="bi bi-shield-lock text-warning me-2"></i>Попытки входа</h1>
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

        <!-- Таблица попыток входа -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th>IP</th>
                            <th>Пользователь</th>
                            <th style="width:110px;">Успех</th>
                            <th style="min-width:150px;">Дата</th>
                            <th style="width:90px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attempts)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-exclamation fs-1 mb-3 opacity-50"></i>
                                    Попытки входа не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attempts as $a): ?>
                                <!-- Зелёная строка для успешных, красная для неудачных -->
                                <tr class="<?= ($a['success'] ?? 0) ? 'table-success' : 'table-danger' ?>">
                                    <td><strong><?= $a['id'] ?></strong></td>
                                    <td><code><?= htmlspecialchars($a['ip_address'] ?? '') ?></code></td>
                                    <td><?= htmlspecialchars($a['username'] ?? $a['name'] ?? 'Гость') ?></td>
                                    <td>
                                        <i class="bi <?= ($a['success'] ?? 0) ? 'bi-check-circle-fill status-ok' : 'bi-x-circle-fill status-bad' ?>" style="font-size:1.2rem;"></i>
                                    </td>
                                    <td><small class="text-muted"><?= date('d.m.Y H:i', strtotime($a['attempt_at'] ?? 'now')) ?></small></td>
                                    <td>
                                        <a href="?delete=<?= $a['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить попытку #<?= $a['id'] ?>?')" title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Пагинация -->
            <?php if ($total_pages > 1): ?>
                <div class="p-3 border-top bg-light">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Кнопка "назад" -->
                        <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
                        <!-- Номера страниц (по 5 вокруг текущей) -->
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <!-- Кнопка "вперёд" -->
                        <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>