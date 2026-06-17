<?php
require_once 'auth_check.php';

if ($current_user['login'] !== 'admin') {
    header('Location: главная.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_user'])) {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($login) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (login, password) VALUES (?, ?)");
        try {
            $stmt->execute([$login, $hashed_password]);
            header('Location: users.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Пользователь с таким логином уже существует';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = $_POST['user_id'] ?? 0;
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: users.php');
        exit;
    }
}

$stmt = $pdo->query("SELECT * FROM admin ORDER BY id");
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Управление пользователями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #E32636;">
    <nav class="navbar" style="background-color: #E32636;">
        <div class="container-fluid">
            <a class="navbar-brand text-white" href="главная.php">
                <img src="logo.png" alt="Logo" width="30" height="24">
                Гатчинский государственный университет
            </a>
        </div>
    </nav>
    
    <div style="border: 17px solid #E32636;">
        <div class="card text-center" style="border: 10px solid #480607;">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs gap-2">
                    <li class="nav-item">
                        <a class="nav-link" href="главная.php" style="color: #696969;">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="справочник.php" style="color: #696969;">Справочник клиентов</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php" style="background-color: #480607; color: #eaeaea;">Пользователи</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="выход.php" style="color: #696969;">Выйти</a>
                    </li>
                </ul>
            </div>
            
            <div style="background-color: #480607; color: #eaeaea">
                <h1 style="text-align: left;">Управление пользователями</h1>
            </div>

            <div class="card-body">
                <button type="button" class="btn btn-danger mb-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <h3 style="margin:0;">Создать нового пользователя</h3>
                </button>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Логин</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">Пользователей нет</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['login']) ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Удалить пользователя?')">Удалить</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Это вы</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Новый пользователь</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Логин</label>
                            <input type="text" class="form-control" name="login" required pattern="^[A-Za-z0-9]{6,}$" title="Не менее 6 латинских букв или цифр">
                        </div>
                        <div class="mb-3">
                            <label>Пароль</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="new_user" class="btn btn-danger">Создать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="fixed-bottom">
        <footer class="text-white text-center py-3" style="background-color: #E32636;">
            <p>© 2026 Гатчинский государственный университет. Все права защищены.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>