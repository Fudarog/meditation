<?php
// Начинаем сессию
session_start();
// Подключаем конфигурацию (подключение к БД и настройки)
require_once '../config/config.php';

// Проверяем, что пользователь администратор — иначе перенаправляем на вход
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit;
}

// Подключаемся к БД
$conn = connect();
$success = '';  // Сообщение об успехе
$errors = [];   // Массив ошибок

// Обработка добавления достижения 
if ($_POST['add_achievements'] ?? false) {
    // Получаем данные из формы
    $code = trim($_POST['code']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $icon = trim($_POST['icon']);

    // Проверяем, что все поля заполнены
    if (empty($code) || empty($title) || empty($description) || empty($icon)) {
        $errors[] = 'Заполните все поля';
    } else {
        // Проверяем, существует ли уже достижение с таким кодом
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM achievements WHERE code = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $code);
        mysqli_stmt_execute($check_stmt);
        $exists = mysqli_stmt_get_result($check_stmt)->num_rows > 0;
        mysqli_stmt_close($check_stmt);

        if ($exists) {
            $errors[] = 'Код уже существует';
        } else {
            // Вставляем новое достижение в БД
            $stmt = mysqli_prepare($conn, "INSERT INTO achievements (code, title, description, icon) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "ssss", $code, $title, $description, $icon);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Достижение добавлено';
            } else {
                $errors[] = 'Ошибка добавления';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Обработка удаления достижения 
if ($_GET['delete'] ?? false) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM achievements WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Достижение удалено';
    } else {
        $errors[] = 'Ошибка удаления';
    }
    mysqli_stmt_close($stmt);
}

// Пагинация
$page = (int)($_GET['page'] ?? 1);       // Текущая страница (по умолчанию 1)
$per_page = 20;                          // Достижений на странице
$offset = ($page - 1) * $per_page;

// Получаем общее количество достижений
$total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM achievements");
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['total'];
mysqli_stmt_close($total_stmt);
$total_pages = max(1, ceil($total / $per_page));  // Общее количество страниц

// Получаем достижения для текущей страницы
$stmt = mysqli_prepare($conn, "SELECT * FROM achievements ORDER BY id DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Преобразуем результат в массив
$achievements = [];
while ($row = mysqli_fetch_assoc($result)) {
    $achievements[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Достижения</title>
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .table-responsive {
            max-height: calc(100vh - 200px);
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

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            box-shadow: var(--btn-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .icon-preview {
            font-size: 1.3rem;
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(255, 140, 66, 0.1), rgba(255, 107, 53, 0.1));
            border: 2px solid rgba(255, 140, 66, 0.2);
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

        .code-badge {
            background: rgba(0, 0, 0, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-family: monospace;
            font-size: 0.85rem;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545 !important;
            color: white !important;
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
                <h1><i class="bi bi-trophy text-warning me-2"></i>Достижения</h1>
                <small class="text-muted">Всего: <?= number_format($total) ?> | Страница <?= $page ?> из <?= $total_pages ?></small>
            </div>
            <button class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addModal">
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

        <!-- Таблица достижений -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:70px;">ID</th>
                            <th style="min-width:140px;">Код</th>
                            <th style="min-width:180px;">Название</th>
                            <th style="min-width:300px;">Описание</th>
                            <th style="width:90px;">Иконка</th>
                            <th style="width:100px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($achievements)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-trophy fs-1 mb-3 opacity-50"></i>
                                    <div>Достижения не найдены</div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($achievements as $a): ?>
                                <tr class="align-middle">
                                    <td><strong class="badge bg-primary"><?= $a['id'] ?></strong></td>
                                    <td><span class="code-badge"><?= htmlspecialchars($a['code']) ?></span></td>
                                    <td><strong><?= htmlspecialchars($a['title']) ?></strong></td>
                                    <td title="<?= htmlspecialchars($a['description']) ?>"><?= mb_strimwidth(htmlspecialchars($a['description']), 0, 80, "...") ?></td>
                                    <td>
                                        <div class="icon-preview"><i class="bi <?= htmlspecialchars($a['icon']) ?>"></i></div>
                                    </td>
                                    <td>
                                        <a href="?delete=<?= $a['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить «<?= htmlspecialchars($a['title']) ?>» (#<?= $a['id'] ?>)?')">
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

    <!-- Модальное окно добавления достижения -->
    <div class="modal fade" id="addModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title"><i class="bi bi-plus-circle text-success me-2"></i>Добавить достижение</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="add_achievements" value="1">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Код <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="achievement-level-1" required maxlength="50">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Название <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Новое достижение" required maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Описание <span class="text-danger">*</span></label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Что нужно сделать для получения..." required maxlength="255"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Иконка <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">bi-</span>
                                <input type="text" name="icon" class="form-control" placeholder="trophy" value="trophy" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle me-2"></i>Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>