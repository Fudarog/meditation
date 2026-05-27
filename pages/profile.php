<?php
// Начинаем сессию
session_start();
require_once '../config/config.php';
$role = $_SESSION['role'] ?? 'guest';
$user_id = $_SESSION['user_id'];

// Защита от XSS
security_headers();
header("Content-Security-Policy: default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net 'unsafe-inline'; ...");

// Защита от DDoS 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !check_rate_limit('form_' . basename($_SERVER['PHP_SELF']))) {
    htmlspecialchars($_SESSION['error'] = 'Слишком много попыток. Подождите минуту.');
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Функция для проверки авторизации
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}
requireAuth();

// Подключаемся к БД
$conn = connect();

// Проверяем, существует ли пользователь
$user_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, name, avatar FROM users WHERE id = $user_id"));
if (!$user_check) {
    session_destroy();
    header('Location: ../login.php?error=no_user');
    exit;
}
$user = $user_check;

// Получаем данные пользователя
$user_result = mysqli_query($conn, "SELECT id, name, email, phone, city, role, avatar FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_result) ?: [
    'id' => $user_id,
    'name' => '',
    'email' => '',
    'phone' => '',
    'city' => '',
    'role' => 'user',
    'avatar' => 'avatar1.jpg'
];

// Получаем достижения пользователя
$achievements_result = mysqli_query($conn, "
    SELECT a.title, a.description, a.icon, ua.unlocked_at
    FROM user_achievements ua
    JOIN achievements a ON ua.achievement_id = a.id
    WHERE ua.user_id = $user_id
    ORDER BY ua.unlocked_at DESC
");

$achievements = [];
while ($row = mysqli_fetch_assoc($achievements_result)) {
    $achievements[] = $row;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/nav.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="icon" href="../assets/images/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <nav>
        <ul>
            <li><a href="../index.php" class="logo"><img src="../assets/images/logo.png" width="70" alt="Logo"></a></li>
            <li><a href="test.php">Тест</a></li>
            <li><a href="practice.php">Практика</a></li>
            <li><a href="incense.php">Благовония</a></li>
            <li><a href="profile.php" class="active">Профиль</a></li>
            <li><a href="statistics.php">Статистика</a></li>
            <li><a href="../actions/logout.php">Выход</a></li>
        </ul>
    </nav>

    <div class="container">
        <!-- Карточка профиля -->
        <div class="card">
            <div class="card-header">
                <h3>Профиль</h3>
            </div>
            <div class="card-body">
                <div class="profile-layout">
                    <!-- Левая часть: аватар -->
                    <div class="profile-left">
                        <img class="profile-avatar" id="profileAvatar"
                            src="../assets/avatars/<?php echo htmlspecialchars($user['avatar'] ?: 'avatar1.jpg'); ?>"
                            alt="Avatar">

                        <button type="button" class="btn btn-primary avatar-btn" onclick="toggleAvatarList()">
                            Изменить аватар
                        </button>

                        <!-- Список аватаров -->
                        <div id="chooseAvatars" class="choose-avatars">
                            <div class="avatar-list">
                                <?php
                                $avatars = ['avatar1.jpg', 'avatar2.jpg', 'avatar3.jpg', 'avatar4.jpg', 'avatar5.jpg', 'avatar6.jpg'];
                                foreach ($avatars as $avatar):
                                ?>
                                    <label class="avatar-option">
                                        <input type="radio" name="avatar" value="<?php echo $avatar; ?>"
                                            <?php echo ($user['avatar'] ?? '') === $avatar ? 'checked' : ''; ?>
                                            onchange="previewAvatar('<?php echo $avatar; ?>')">
                                        <img src="../assets/avatars/<?php echo $avatar; ?>" alt="<?php echo $avatar; ?>">
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="avatar-save-btn" id="saveAvatarBtn">Сохранить аватар</button>
                        </div>
                    </div>

                    <!-- Правая часть: информация -->
                    <div class="profile-right">
                        <div class="user-info">
                            <strong>Имя:</strong> <span id="name"><?php echo htmlspecialchars($user['name']); ?></span><br>
                            <strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($user['email']); ?></span><br>
                            <strong>Телефон:</strong> <span id="phone"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span><br>
                            <strong>Город:</strong> <span id="city"><?php echo htmlspecialchars($user['city'] ?? ''); ?></span><br>
                        </div>

                        <button type="button" class="btn btn-primary" onclick="toggleEdit()">Изменить данные</button>

                        <!-- Форма редактирования -->
                        <form id="editForm" class="edit-form">
                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                            <input type="hidden" name="avatar" id="selectedAvatar" value="<?php echo htmlspecialchars($user['avatar'] ?: 'avatar1.jpg'); ?>">
                            
                            <div class="form-row">
                                <input type="text" name="name" placeholder="Имя" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="form-row">
                                <input type="text" name="phone" placeholder="Телефон" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                <input type="text" name="city" placeholder="Город" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                            </div>

                            <button type="button" class="btn btn-success" onclick="saveInfo()">Сохранить</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Карточка достижений -->
        <div class="card">
            <div class="card-header">
                <h5>Достижения</h5>
            </div>
            <div class="card-body">
                <?php if (count($achievements) === 0): ?>
                    <p>Пока нет достижений. Проведи первую сессию, чтобы получить награду ✨</p>
                <?php else: ?>
                    <div class="achievements-grid">
                        <?php foreach ($achievements as $ach): ?>
                            <div class="achievement-card">
                                <i class="bi <?php echo htmlspecialchars($ach['icon']); ?> achievement-icon"></i>
                                <h6><?php echo htmlspecialchars($ach['title']); ?></h6>
                                <p><?php echo htmlspecialchars($ach['description']); ?></p>
                                <small>Получено: <?php echo date('d.m.Y H:i', strtotime($ach['unlocked_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>© 2026 Все права защищены</p>
    </footer>

    <script>
        // Открыть/закрыть список аватаров
        function toggleAvatarList() {
            const list = document.getElementById('chooseAvatars');
            list.classList.toggle('active');
        }

        // Предпросмотр аватара
        function previewAvatar(filename) {
            document.getElementById('profileAvatar').src = '../assets/avatars/' + filename + '?v=' + Date.now();
            document.getElementById('selectedAvatar').value = filename;
            document.getElementById('saveAvatarBtn').style.display = 'block';
        }

        // Сохранить аватар
        document.getElementById('saveAvatarBtn').addEventListener('click', function() {
            const filename = document.getElementById('selectedAvatar').value;
            saveAvatarAndClose(filename);
        });

        function saveAvatarAndClose(filename) {
            const formData = new FormData();
            formData.append('avatar', filename);
            formData.append('only_avatar', '1');
            
            const btn = document.getElementById('saveAvatarBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Сохраняем...';
            btn.disabled = true;
            
            fetch('../actions/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('chooseAvatars');
                    list.classList.add('closing');
                    
                    setTimeout(() => {
                        list.classList.remove('active', 'closing');
                        btn.style.display = 'none';
                    }, 300);
                    
                    showToast('Аватар сохранён! ✨');
                } else {
                    alert(data.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error('Ошибка:', err);
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        }
        
        // Открыть/закрыть форму редактирования
        function toggleEdit() {
            document.getElementById('editForm').classList.toggle('active');
        }

        // Сохранить данные профиля
        function saveInfo() {
            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            fetch('../actions/update_user.php', {
                method: 'POST',
                body: formData
            })
            .then(async (r) => {
                const data = await r.json();
                if (data.success) {
                    // Обновляем аватар
                    const newAvatar = data.avatar;
                    document.getElementById('profileAvatar').src = '../assets/avatars/' + newAvatar + '?v=' + Date.now();
                    document.getElementById('selectedAvatar').value = newAvatar;
                    
                    // Обновляем текстовые поля
                    document.getElementById('name').textContent = formData.get('name');
                    document.getElementById('email').textContent = formData.get('email');
                    document.getElementById('phone').textContent = formData.get('phone') || '';
                    document.getElementById('city').textContent = formData.get('city') || '';
                    
                    alert('Сохранено!');
                    document.getElementById('editForm').classList.remove('active');
                } else {
                    alert(data.message);
                }
            })
            .catch(err => alert('Ошибка сети: ' + err));
        }
    </script>
</body>
</html>