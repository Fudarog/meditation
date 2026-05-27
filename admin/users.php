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

// Добавление нового пользователя
if ($_POST['add_user'] ?? false) {
    // Получаем данные из формы добавления
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    // Проверяем, включён ли "verified" (подтверждённый email)
    $verified = isset($_POST['verified']) ? 1 : 0;

    // Проверяем обязательные поля: имя, email, пароль
    if (empty($name) || empty($email) || empty($password)) {
        $errors[] = 'Заполните все обязательные поля';
        // Проверяем формат email
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат email';
    } else {
        // Проверяем, существует ли уже пользователь с таким email
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $exists = mysqli_stmt_get_result($check_stmt)->num_rows > 0;
        mysqli_stmt_close($check_stmt);

        if ($exists) {
            $errors[] = 'Пользователь с таким email уже существует';
        } else {
            // Хешируем пароль
            $pass_hash = password_hash($password, PASSWORD_DEFAULT);
            // Получаем текущую дату и время
            $created_at = date('Y-m-d H:i:s');

            // Вставляем нового пользователя в БД
            $sql = "INSERT INTO users (name, email, phone, city, password_hash, role, status, created_at, verified, verify_token) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '')";
            $stmt = mysqli_prepare($conn, $sql);

            // Привязываем параметры
            mysqli_stmt_bind_param(
                $stmt,
                "ssssssssi",
                $name,
                $email,
                $phone,
                $city,
                $pass_hash,
                $role,
                $status,
                $created_at,
                $verified
            );

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Пользователь добавлен';
            } else {
                $errors[] = 'Ошибка добавления';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Редактирование пользователя
if ($_POST['edit_user'] ?? false) {
    // Получаем данные из формы редактирования
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $role = $_POST['role'];
    $status = $_POST['status'];
    $verified = isset($_POST['verified']) ? 1 : 0;

    // Проверяем обязательные поля: имя и email
    if (empty($name) || empty($email)) {
        $errors[] = 'Заполните обязательные поля';
        // Проверяем формат email
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Неверный формат email';
    } else {
        // Проверяем, существует ли уже другой пользователь с таким email 
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
        mysqli_stmt_bind_param($check_stmt, "si", $email, $id);
        mysqli_stmt_execute($check_stmt);
        $exists = mysqli_stmt_get_result($check_stmt)->num_rows > 0;
        mysqli_stmt_close($check_stmt);

        if ($exists) {
            $errors[] = 'Пользователь с таким email уже существует';
        } else {
            // Обновляем данные пользователя в БД 
            $sql = "UPDATE users SET name=?, email=?, phone=?, city=?, role=?, status=?, verified=? WHERE id=?";
            $stmt = mysqli_prepare($conn, $sql);
            // Привязываем параметры
            mysqli_stmt_bind_param($stmt, "ssssssii", $name, $email, $phone, $city, $role, $status, $verified, $id);

            if (mysqli_stmt_execute($stmt)) {
                $success = 'Пользователь обновлен';
            } else {
                $errors[] = 'Ошибка обновления';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Удаление пользователя
if ($_GET['delete'] ?? false) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Пользователь удален';
    } else {
        $errors[] = 'Ошибка удаления';
    }
    mysqli_stmt_close($stmt);
}

// Пагинация
$page = (int)($_GET['page'] ?? 1);       // Текущая страница (по умолчанию 1)
$per_page = 10;                          // Пользователей на странице
$offset = ($page - 1) * $per_page;

// Получаем общее количество пользователей
$total_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM users");
mysqli_stmt_execute($total_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($total_stmt))['total'];
mysqli_stmt_close($total_stmt);
$total_pages = max(1, ceil($total / $per_page));  // Общее количество страниц

// Получаем пользователей для текущей страницы 
$stmt = mysqli_prepare($conn, "SELECT * FROM users ORDER BY id DESC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, "ii", $per_page, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Преобразуем результат в массив
$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <title>Пользователи</title>
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
            color: white !important;
            transform: translateY(-1px);
        }

        .avatar-img {
            width: 36px;
            height: 36px;
            object-fit: cover;
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
                <h1><i class="bi bi-people text-warning me-2"></i>Пользователи</h1>
                <small class="text-muted">Всего: <?= $total ?> | Страница <?= $page ?> из <?= $total_pages ?></small>
            </div>
            <!-- Кнопка добавления нового пользователя -->
            <button class="btn btn-add text-white" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus me-2"></i>Добавить
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

        <!-- Таблица пользователей -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:60px;">ID</th>
                            <th style="min-width:130px;">Имя</th>
                            <th style="min-width:200px;">Email</th>
                            <th style="min-width:130px;">Телефон</th>
                            <th style="min-width:100px;">Город</th>
                            <th style="width:80px;">Роль</th>
                            <th style="width:85px;">Статус</th>
                            <th style="width:80px;">Проверен</th>
                            <th style="width:70px;">Аватар</th>
                            <th style="min-width:130px;">Создан</th>
                            <th style="width:130px;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 mb-3 opacity-50"></i>
                                    Пользователи не найдены
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr class="align-middle">
                                    <td><strong class="badge bg-primary"><?= $u['id'] ?></strong></td>
                                    <td><i class="bi bi-person me-1 text-secondary"></i><?= htmlspecialchars($u['name']) ?></td>
                                    <!-- Email обрезается до 35 символов, если длинный -->
                                    <td title="<?= htmlspecialchars($u['email']) ?>"><?= mb_strimwidth(htmlspecialchars($u['email']), 0, 35, "...") ?></td>
                                    <td><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($u['city'] ?: '-') ?></td>
                                    <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'danger' : 'primary' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                                    <td><span class="badge bg-<?= $u['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                                    <td><span class="badge bg-<?= $u['verified'] ? 'success' : 'warning' ?>"><?= $u['verified'] ? 'Да' : 'Нет' ?></span></td>
                                    <td>
                                        <!-- Показываем аватар -->
                                        <?php if (!empty($u['avatar'])): ?>
                                            <img src="../assets/avatars/<?= htmlspecialchars($u['avatar']) ?>" alt="Аватар" class="rounded-circle avatar-img">
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= date('d.m.Y', strtotime($u['created_at'])) ?><br><small class="text-muted"><?= date('H:i', strtotime($u['created_at'])) ?></small></div>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Кнопка редактирования — открывает модальное окно -->
                                            <button class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id'] ?>" title="Редактировать">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <!-- Кнопка удаления -->
                                            <a href="?delete=<?= $u['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Удалить пользователя «<?= htmlspecialchars($u['name']) ?>» (#<?= $u['id'] ?>)?')" title="Удалить">
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

    <!-- Модальное окно добавления пользователя -->
    <div class="modal fade" id="addUserModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-person-plus text-success me-2"></i>Добавить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="add_user" value="1">
                        <div class="mb-3">
                            <label class="form-label">Имя *</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Город</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль *</label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Роль</label>
                                <select name="role" class="form-select">
                                    <option value="user">user</option>
                                    <option value="admin">admin</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Статус</label>
                                <select name="status" class="form-select">
                                    <option value="active">active</option>
                                    <option value="inactive">inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" name="verified" class="form-check-input" id="verifiedAdd" value="1">
                                    <label class="form-check-label" for="verifiedAdd">Проверен</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-person-plus me-2"></i>Добавить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальные окна редактирования -->
    <?php foreach ($users as $u): ?>
        <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title"><i class="bi bi-pencil-square text-primary me-2"></i>Редактировать <?= htmlspecialchars($u['name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="edit_user" value="1">
                            <div class="mb-3">
                                <label class="form-label">Имя *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Телефон</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($u['phone']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Город</label>
                                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($u['city']) ?>">
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Роль</label>
                                    <select name="role" class="form-select">
                                        <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option>
                                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Статус</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>active</option>
                                        <option value="inactive" <?= $u['status'] === 'inactive' ? 'selected' : '' ?>>inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <div class="form-check">
                                        <input type="checkbox" name="verified" class="form-check-input" id="verified<?= $u['id'] ?>" value="1" <?= $u['verified'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="verified<?= $u['id'] ?>">Проверен</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Сохранить</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>