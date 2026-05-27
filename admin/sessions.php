<?php
// Начинаем сессию
session_start();
// Подключаем конфигурацию (подключение к БД и настройки)
require_once '../config/config.php';

// Проверка: только администратор может видеть эту страницу
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Подключаемся к БД
$conn = connect();
$errors = [];   // Массив ошибок
$success = '';  // Сообщение об успехе

// Удаление сессии
if ($_GET['delete'] ?? false) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM sessions WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Сессия удалена';
    } else {
        $errors[] = 'Ошибка удаления';
    }
    mysqli_stmt_close($stmt);
}

// Редактирование сессии
if ($_POST['edit_session'] ?? false) {
    // Получаем данные из формы редактирования
    $id = (int)$_POST['id'];
    $type = trim($_POST['type']);
    $duration = (int)$_POST['duration'];
    $date = trim($_POST['date']);

    // Проверяем, что все поля заполнены корректно
    if (empty($type) || empty($date) || $duration < 1) {
        $errors[] = 'Заполните все поля корректно';
    } else {
        // Обновляем сессию в БД
        $stmt = mysqli_prepare($conn, "UPDATE sessions SET type=?, duration=?, date=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sisi", $type, $duration, $date, $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Сессия обновлена';
        } else {
            $errors[] = 'Ошибка обновления';
        }
        mysqli_stmt_close($stmt);
    }
}

// Пагинация
$page = (int)($_GET['page'] ?? 1);       // Текущая страница (по умолчанию 1)
$per_page = 20;                          // Сессий на странице
$offset = ($page - 1) * $per_page;

// Получаем общее количество сессий
$total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM sessions");
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['total'];
mysqli_stmt_close($total_stmt);
$total_pages = max(1, ceil($total / $per_page));  // Общее количество страниц

// Получаем сессии с именем пользователя
$stmt = mysqli_prepare($conn, "SELECT s.*, u.name FROM sessions s JOIN users u ON s.user_id = u.id ORDER BY s.date DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Преобразуем результат в массив
$sessions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sessions[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <title>Сессии</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
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

        .btn-outline-warning:hover {
            background-color: #ffc107 !important;
            color: #000 !important;
            transform: translateY(-1px);
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-1px);
        }

        .session-type {
            font-size: 0.8rem;
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
                <h1><i class="bi bi-music-note-list text-warning me-2"></i>Сессии</h1>
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

        <!-- Таблица сессий -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="min-width:150px;">Пользователь</th>
                            <th style="width:100px;">Тип</th>
                            <th style="width:120px;">Длительность</th>
                            <th style="min-width:140px;">Дата</th>
                            <th style="width:90px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sessions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-clock-history fs-1 mb-3 opacity-50"></i>
                                    Сессии не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sessions as $s): ?>
                                <tr class="align-middle">
                                    <td><strong class="badge bg-primary"><?= $s['id'] ?></strong></td>
                                    <td><i class="bi bi-person me-1 text-secondary"></i><?= htmlspecialchars($s['name']) ?></td>
                                    <td><span class="badge bg-info session-type"><?= htmlspecialchars($s['type']) ?></span></td>
                                    <td><strong class="text-success"><?= $s['duration'] ?> мин</strong></td>
                                    <td>
                                        <div><i class="bi bi-calendar-event me-1 text-success"></i><?= date('d.m.Y', strtotime($s['date'])) ?><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($s['date'])) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Кнопка редактирования — открывает модальное окно -->
                                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editSessionModal<?= $s['id'] ?>" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Кнопка удаления -->
                                            <a href="?delete=<?= $s['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить сессию #<?= $s['id'] ?>?')" title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
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
                        <!-- Номера страниц -->
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

    <!-- Модальные окна редактирования -->
    <?php foreach ($sessions as $s): ?>
        <div class="modal fade" id="editSessionModal<?= $s['id'] ?>" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title"><i class="bi bi-pencil-square text-warning me-2"></i>Редактировать сессию #<?= $s['id'] ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="edit_session" value="1">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">Тип</label>
                                <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($s['type']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Длительность</label>
                                <input type="number" name="duration" class="form-control" value="<?= $s['duration'] ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Дата</label>
                                <input type="datetime-local" name="date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($s['date'])) ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-warning"><i class="bi bi-save me-2"></i>Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>