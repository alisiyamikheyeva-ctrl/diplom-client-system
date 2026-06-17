<?php
require_once 'auth_check.php';

$is_admin = ($current_user['role'] === 'admin');

$search = $_GET['search'] ?? '';
$cards_per_page = 8;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $cards_per_page;

$sql = "SELECT c.*, a.role as user_role, a.id as user_id 
        FROM clients c 
        LEFT JOIN admin a ON c.id = a.client_id";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE c.fio LIKE ? OR c.email LIKE ? OR c.phone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM clients c" . (!empty($search) ? " WHERE c.fio LIKE ? OR c.email LIKE ? OR c.phone LIKE ?" : ""));
$stmt_count->execute($params);
$total_clients = $stmt_count->fetchColumn();
$total_pages = ceil($total_clients / $cards_per_page);

$sql .= " ORDER BY c.id DESC LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
$stmt->bindValue(count($params) + 1, $cards_per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_role']) && $is_admin) {
    $client_id = $_POST['client_id'] ?? 0;
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'manager';
    
    if (!empty($login) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("SELECT id FROM admin WHERE client_id = ?");
            $stmt->execute([$client_id]);
            $existing_user = $stmt->fetch();
            
            if ($existing_user) {
                $stmt = $pdo->prepare("UPDATE admin SET login = ?, password = ?, role = ? WHERE client_id = ?");
                $stmt->execute([$login, $hashed_password, $role, $client_id]);
                $success = 'Роль обновлена';
            } else {
                $stmt = $pdo->prepare("INSERT INTO admin (login, password, role, client_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$login, $hashed_password, $role, $client_id]);
                $success = 'Роль назначена';
            }
        } catch (PDOException $e) {
            $error = 'Пользователь с таким логином уже существует';
        }
    } else {
        $error = 'Заполните логин и пароль';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body style="background-color: #E32636;">

    <nav class="navbar" style="background-color: #E32636;">
        <div class="container-fluid">
            <a class="navbar-brand" href="">
                <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
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
                        <a class="nav-link active" aria-current="true" href="карточки.php" style="background-color: #480607; color: #eaeaea">Карточки</a>
                    </li>
                </ul>
            </div>

            <div style="background-color: #480607; color: #eaeaea">
                <h1 style="text-align: left;">Карточки клиентов</h1>
            </div>

            <div class="card-body">
                <nav class="navbar bg-body-tertiary">
                    <div class="container-fluid">
                        <form class="d-flex" role="search" method="get">
                            <input class="form-control me-2" type="search" name="search" placeholder="Поиск по ФИО, email, телефону" aria-label="Поиск" value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-danger" type="submit">Поиск</button>
                            <?php if (!empty($search)): ?>
                                <a href="карточки.php" class="btn btn-secondary ms-2">Сбросить</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </nav>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success mt-2"><?= $success ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mt-2"><?= $error ?></div>
                <?php endif; ?>

                <div class="card-body p-3">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <?php if (empty($clients)): ?>
                            <div class="col-12 text-center text-muted">Клиентов не найдено</div>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                            <div class="col">
                                <div class="card <?= $client['user_role'] ? 'border-primary' : 'border-success' ?> h-100">
                                    <div class="card-header bg-transparent <?= $client['user_role'] ? 'border-primary' : 'border-success' ?>">
                                        Клиент
                                        <?php if ($client['user_role']): ?>
                                            <?php if ($client['user_role'] === 'admin'): ?>
                                                <span class="badge bg-danger float-end">Администратор</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary float-end">Менеджер</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body <?= $client['user_role'] ? 'text-primary' : 'text-success' ?>">
                                        <h5 class="card-title"><?= htmlspecialchars($client['fio']) ?></h5>
                                        <p class="card-text">
                                            Телефон: <?= htmlspecialchars($client['phone']) ?: '—' ?><br>
                                            Email: <?= htmlspecialchars($client['email']) ?: '—' ?><br>
                                            Адрес: <?= htmlspecialchars($client['address']) ?: '—' ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent <?= $client['user_role'] ? 'border-primary' : 'border-success' ?>">
                                        <?php if ($is_admin): ?>
                                            <button type="button" class="btn btn-sm <?= $client['user_role'] ? 'btn-primary' : 'btn-success' ?>" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#assignRoleModal"
                                                    onclick="assignRole(<?= $client['id'] ?>, '<?= htmlspecialchars($client['fio'], ENT_QUOTES) ?>', '<?= htmlspecialchars($client['user_role'] ?? '', ENT_QUOTES) ?>')">
                                                <?= $client['user_role'] ? 'ИЗМЕНИТЬ РОЛЬ' : 'НАЗНАЧИТЬ РОЛЬ' ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $client['user_role'] ? 'Роль назначена' : 'Роль не назначена' ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="client_form.php" class="btn btn-danger">
                    <h3 style="margin:0;">Создать нового клиента</h3>
                </a>
                <br>
                <br>

                <nav aria-label="Навигация по страницам">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link btn btn-dark" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>">Предыдущая</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item">
                                <a class="page-link btn btn-dark <?= $i === $current_page ? 'active' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link btn btn-dark" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>">Следующая</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <div class="fixed-bottom">
        <footer class="text-white text-center py-3" style="background-color: #E32636;">
            <p>© 2026 Гатчинский государственный университет. Все права защищены.</p>
        </footer>
    </div>

    <div class="modal fade" id="assignRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Назначить роль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="client_id" id="role_client_id">
                        <p>Клиент: <strong id="role_client_name"></strong></p>
                        <div class="mb-3">
                            <label>Логин</label>
                            <input type="text" class="form-control" name="login" required pattern="^[A-Za-z0-9]{6,}$" title="Не менее 6 латинских букв или цифр">
                        </div>
                        <div class="mb-3">
                            <label>Пароль</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label>Роль</label>
                            <select class="form-control" name="role">
                                <option value="manager">Менеджер</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="assign_role" class="btn btn-danger">Назначить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>

    <script>
    function assignRole(clientId, clientName, currentRole) {
        document.getElementById('role_client_id').value = clientId;
        document.getElementById('role_client_name').textContent = clientName;
        if (currentRole) {
            document.querySelector('#assignRoleModal select[name="role"]').value = currentRole;
        }
    }
    </script>

</body>
</html>