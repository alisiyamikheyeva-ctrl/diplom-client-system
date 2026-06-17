<?php
require_once 'auth_check.php';

$sort = $_GET['sort'] ?? 'id_desc';
$search = $_GET['search'] ?? '';
$clients_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $clients_per_page;

$sort_options = [
    'fio_asc' => 'fio ASC',
    'fio_desc' => 'fio DESC',
    'name_asc' => 'fio ASC',
    'name_desc' => 'fio DESC',
    'id_desc' => 'id DESC'
];

$sort_by = $sort_options[$sort] ?? 'id DESC';

$sql = "SELECT * FROM clients";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE fio LIKE ? OR email LIKE ? OR phone LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param];
}

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM clients" . (!empty($search) ? " WHERE fio LIKE ? OR email LIKE ? OR phone LIKE ?" : ""));
$stmt_count->execute($params);
$total_clients = $stmt_count->fetchColumn();
$total_pages = ceil($total_clients / $clients_per_page);

$sql .= " ORDER BY $sort_by LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key + 1, $value);
}
$stmt->bindValue(count($params) + 1, $clients_per_page, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll();

$is_admin = ($current_user['login'] === 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client']) && $is_admin) {
    $id = $_POST['client_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: справочник.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Справочник клиентов</title>
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
                        <a class="nav-link active" aria-current="true" href="справочник.php" style="background-color: #480607; color: #eaeaea">Справочник клиентов</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="карточки.php" style="color: #696969;">Карточки</a>
                    </li>
                </ul>
            </div>

            <div style="background-color: #480607; color: #eaeaea">
                <h1 style="text-align: left;">Таблица со всеми клиентами</h1>
            </div>

            <div class="card-body">
                <button class="btn btn-dark d-flex align-items-center gap-2" type="button" disabled>
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Всего клиентов: <?= $total_clients ?>
                </button>
                <br>

                <form method="get" class="d-flex gap-2 mb-3">
                    <input type="text" name="search" class="form-control" placeholder="Поиск по ФИО, email, телефону..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-dark">Найти</button>
                    <?php if (!empty($search)): ?>
                        <a href="справочник.php" class="btn btn-secondary">Сбросить</a>
                    <?php endif; ?>
                </form>

<div class="mb-3">
    <div class="btn-group dropend">
        <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            Фильтр
        </button>
        <ul class="dropdown-menu" style="min-width: 180px;">
            <li><a class="dropdown-item" href="справочник.php?search=<?= urlencode($search) ?>">Все клиенты</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="справочник.php?sort=fio_asc&search=<?= urlencode($search) ?>">Фамилия А-Я</a></li>
            <li><a class="dropdown-item" href="справочник.php?sort=fio_desc&search=<?= urlencode($search) ?>">Фамилия Я-А</a></li>
            <li><a class="dropdown-item" href="справочник.php?sort=name_asc&search=<?= urlencode($search) ?>">Имя А-Я</a></li>
            <li><a class="dropdown-item" href="справочник.php?sort=name_desc&search=<?= urlencode($search) ?>">Имя Я-А</a></li>
        </ul>
    </div>
</div>

                <br>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Фамилия</th>
                            <th scope="col">Имя</th>
                            <th scope="col">Отчество</th>
                            <th scope="col">Эл. почта</th>
                            <th scope="col">Телефон</th>
                            <th scope="col">Адрес</th>
                            <th scope="col"> </th>
                            <th scope="col"> </th>
                            <th scope="col"> </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">Клиентов не найдено</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $index => $client): ?>
                                <?php
                                $fio_parts = explode(' ', $client['fio']);
                                $surname = $fio_parts[0] ?? '';
                                $name = $fio_parts[1] ?? '';
                                $patronymic = $fio_parts[2] ?? '';
                                ?>
                                <tr>
                                    <th scope="row"><?= $offset + $index + 1 ?></th>
                                    <td><?= htmlspecialchars($surname) ?></td>
                                    <td><?= htmlspecialchars($name) ?></td>
                                    <td><?= htmlspecialchars($patronymic) ?></td>
                                    <td><?= htmlspecialchars($client['email']) ?></td>
                                    <td><?= htmlspecialchars($client['phone']) ?></td>
                                    <td><?= htmlspecialchars($client['address']) ?></td>
                                    <?php if ($is_admin): ?>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                                <button type="submit" name="delete_client" class="btn btn-danger btn-sm" onclick="return confirm('Удалить клиента?')">Удалить</button>
                                            </form>
                                        </td>
                                    <?php else: ?>
                                        <td></td>
                                    <?php endif; ?>
                                    <td>
                                        <a href="client_form.php?id=<?= $client['id'] ?>" class="btn btn-warning btn-sm">Редактировать</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                
                    <a href="client_form.php" class="btn btn-danger">
                        <h3 style="margin:0;">Создать нового клиента</h3>
                    </a>
                    <?php if ($is_admin): ?>
                    <a href="users.php" class="btn btn-dark">
                        <h3 style="margin:0;">Пользователи</h3>
                    </a>
                    <?php endif; ?>
                
                <br>
                <br>

                <nav aria-label="Навигация по страницам">
                    <ul class="pagination justify-content-center">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link btn btn-dark" href="?page=<?= $current_page - 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>">Предыдущая</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item">
                                <a class="page-link btn btn-dark <?= $i === $current_page ? 'active' : '' ?>" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link btn btn-dark" href="?page=<?= $current_page + 1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>">Следующая</a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>