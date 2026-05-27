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
$errors = [];   // Массив ошибок
$success = '';  // Сообщение об успехе

// Добавление достижения пользователю 
if ($_POST['add_userAchievements'] ?? false) {
    // Получаем данные из формы добавления
    $user_id = (int)$_POST['user_id'];
    $achievement_id = (int)$_POST['achievement_id'];
    $unlocked_at = trim($_POST['unlocked_at']);

    // Проверяем, что все поля заполнены
    if (empty($user_id) || empty($achievement_id) || empty($unlocked_at)) {
        $errors[] = 'Заполните все поля';
    } else {
        // Вставляем запись в таблицу 
        $stmt = mysqli_prepare($conn, "INSERT INTO user_achievements (user_id, achievement_id, unlocked_at) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iis", $user_id, $achievement_id, $unlocked_at);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Достижение добавлено';
        } else {
            $errors[] = 'Ошибка добавления';
        }
        mysqli_stmt_close($stmt);
    }
}

// Редактирование достижения
if ($_POST['edit_userAchievement'] ?? false) {
    // Получаем данные из формы редактирования
    $id = (int)$_POST['id'];
    $user_id = (int)$_POST['user_id'];
    $achievement_id = (int)$_POST['achievement_id'];
    $unlocked_at = trim($_POST['unlocked_at']);

    // Проверяем заполненность полей
    if (empty($user_id) || empty($achievement_id) || empty($unlocked_at)) {
        $errors[] = 'Заполните все поля';
    } else {
        // Обновляем запись в БД
        $stmt = mysqli_prepare($conn, "UPDATE user_achievements SET user_id=?, achievement_id=?, unlocked_at=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "iisi", $user_id, $achievement_id, $unlocked_at, $id);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Достижение обновлено';
        } else {
            $errors[] = 'Ошибка обновления';
        }
        mysqli_stmt_close($stmt);
    }
}

// Удаление достижения
if ($_GET['delete'] ?? false) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM user_achievements WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Достижение удалено';
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
$total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM user_achievements");
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['total'];
mysqli_stmt_close($total_stmt);
$total_pages = max(1, ceil($total / $per_page));

// Получаем записи с именами пользователей и названиями достижений
$stmt = mysqli_prepare($conn, "SELECT ua.*, u.name, a.title 
    FROM user_achievements ua 
    JOIN users u ON ua.user_id = u.id 
    JOIN achievements a ON ua.achievement_id = a.id 
    ORDER BY ua.id DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Преобразуем в массив
$userAchievements = [];
while ($row = mysqli_fetch_assoc($result)) {
    $userAchievements[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <title>Достижения пользователей</title>
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

        .btn-add {
            background: linear-gradient(135deg, var(--orange-dark), var(--orange-light));
            border: none;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 16px;
            box-shadow: var(--btn-shadow);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 53, 0.4);
        }

        .btn-outline-warning:hover {
            background-color: #ffc107 !important;
            color: #000 !important;
            transform: translateY(-1px);
        }

        .btn-outline-danger:hover {
            background-color: #dc3545 !important;
            color: #fff !important;
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
                <h1><i class="bi bi-trophy text-warning me-2"></i>Достижения пользователей</h1>
                <small class="text-muted">Всего: <?= $total ?> | Страница <?= $page ?> из <?= $total_pages ?></small>
            </div>
            <!-- Кнопка добавления нового достижения -->
            <button class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addAchievementModal">
                <i class="bi bi-plus-circle me-2"></i>Добавить
            </button>
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

        <!-- Таблица достижений пользователей -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="min-width:150px;">Пользователь</th>
                            <th style="min-width:250px;">Достижение</th>
                            <th style="min-width:140px;">Разблокирован</th>
                            <th style="width:120px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($userAchievements)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-trophy fs-1 mb-3 opacity-50"></i>
                                    Достижения не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($userAchievements as $ua): ?>
                                <tr class="align-middle">
                                    <td><strong class="badge bg-primary"><?= $ua['id'] ?></strong></td>
                                    <td><i class="bi bi-person me-1 text-secondary"></i><?= htmlspecialchars($ua['name']) ?></td>
                                    <td><strong><i class="bi bi-award me-1 text-warning"></i><?= htmlspecialchars($ua['title']) ?></strong></td>
                                    <td>
                                        <div>
                                            <i class="bi bi-calendar-event me-1 text-success"></i><?= date('d.m.Y', strtotime($ua['unlocked_at'])) ?><br>
                                            <small class="text-muted"><?= date('H:i', strtotime($ua['unlocked_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Кнопка редактирования -->
                                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editAchievementModal<?= $ua['id'] ?>" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Кнопка удаления -->
                                            <a href="?delete=<?= $ua['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить достижение «<?= htmlspecialchars($ua['title']) ?>» (#<?= $ua['id'] ?>)?')" title="Удалить">
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
                        <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left"></i></a></li><?php endif; ?>
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a></li>
                        <?php endfor; ?>
                        <?php if ($page < $total_pages): ?><li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right"></i></a></li><?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Модальное окно добавления достижения -->
    <div class="modal fade" id="addAchievementModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-trophy text-warning me-2"></i>Добавить достижение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="add_userAchievements" value="1">
                        <div class="mb-3">
                            <label class="form-label">ID пользователя *</label>
                            <input type="number" name="user_id" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ID достижения *</label>
                            <input type="number" name="achievement_id" class="form-control" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Дата разблокировки *</label>
                            <input type="datetime-local" name="unlocked_at" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle me-2"></i>Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна редактирования -->
    <?php foreach ($userAchievements as $ua): ?>
        <div class="modal fade" id="editAchievementModal<?= $ua['id'] ?>" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title"><i class="bi bi-pencil-square text-warning me-2"></i>Редактировать: <?= htmlspecialchars($ua['title']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="edit_userAchievement" value="1">
                            <input type="hidden" name="id" value="<?= $ua['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label">ID пользователя</label>
                                <input type="number" name="user_id" class="form-control" value="<?= $ua['user_id'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ID достижения</label>
                                <input type="number" name="achievement_id" class="form-control" value="<?= $ua['achievement_id'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Дата разблокировки</label>
                                <input type="datetime-local" name="unlocked_at" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($ua['unlocked_at'])) ?>" required>
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