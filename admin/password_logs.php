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

// Удаление записи лога
if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM password_logs WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Лог удалён';
    } else {
        $errors[] = 'Ошибка удаления';
    }
    mysqli_stmt_close($stmt);
}

// Получение всех логов
$logs = [];
$result = mysqli_query($conn, "SELECT * FROM password_logs ORDER BY created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи паролей</title>
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
                <h1><i class="bi bi-journal-text text-warning me-2"></i>Логи паролей</h1>
                <small class="text-muted">Всего: <?= number_format(count($logs)) ?></small>
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

        <!-- Таблица логов -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Почта</th>
                            <th>Код сброса</th>
                            <th>Действие</th>
                            <th>IP</th>
                            <th>Создан</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-text fs-1 mb-3 opacity-50"></i>
                                    Логи не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td><strong><?= $l['id'] ?></strong></td>
                                    <td><i class="bi bi-envelope me-1 text-secondary"></i><?= htmlspecialchars($l['email'] ?? '') ?></td>
                                    <td><code><?= htmlspecialchars(substr($l['reset_code'] ?? '', 0, 8)) ?>...</code></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($l['action'] ?? '') ?></span></td>
                                    <td><code><?= htmlspecialchars($l['ip'] ?? '') ?></code></td>
                                    <td><small class="text-muted"><?= date('d.m H:i', strtotime($l['created_at'] ?? 'now')) ?></small></td>
                                    <td>
                                        <a href="?delete=<?= $l['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить лог #<?= $l['id'] ?>?')" title="Удалить">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>