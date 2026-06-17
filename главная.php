<?php
require_once 'auth_check.php';

$active_tasks = [];
$active_count = 0;
$tasks = [];
$tasks_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $tasks_per_page;
$filter = $_GET['filter'] ?? 'all';

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE admin_id = ?");
$stmt_count->execute([$_SESSION['user_id']]);
$total_tasks = $stmt_count->fetchColumn();
$total_pages = ceil($total_tasks / $tasks_per_page);

$sql = "SELECT * FROM tasks WHERE admin_id = ?";
$params = [$_SESSION['user_id']];

if ($filter === 'active') {
    $sql .= " AND status = 'в процессе'";
} elseif ($filter === 'completed') {
    $sql .= " AND status = 'завершен'";
} elseif ($filter === 'overdue') {
    $sql .= " AND status != 'завершен' AND deadline < CURDATE()";
}

$sql .= " ORDER BY deadline DESC LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $params[0], PDO::PARAM_INT);
$stmt->bindValue(2, $tasks_per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$tasks = $stmt->fetchAll();

$stmt_active = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE admin_id = ? AND status = 'в процессе'");
$stmt_active->execute([$_SESSION['user_id']]);
$active_count = $stmt_active->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_task'])) {
    $task_name = $_POST['task_name'] ?? '';
    $task_deadline = $_POST['task_deadline'] ?? null;
    
    if (!empty($task_name)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (name, deadline, status, admin_id) VALUES (?, ?, 'в процессе', ?)");
        $stmt->execute([$task_name, $task_deadline, $_SESSION['user_id']]);
        header('Location: главная.php');
        exit;
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

  <nav class="navbar"  style="background-color: #E32636;">
    <div class="container-fluid">
      <a class="navbar-brand" href="">
        <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
        Гатчинский государственный университет
      </a>
    </div>
  </nav>
<div style = "border: 17px solid #E32636;">
    <div class="card text-center" style = "border: 10px solid  #480607;">
      <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs gap-2">
          <li class="nav-item">
            <a class="nav-link active" aria-current="true" href="главная.php" style = "background-color: #480607; color: #eaeaea">Главная</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="справочник.php" style = "color: #696969;">Справочник клиентов</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="карточки.php" style = "color: #696969;">Карточки</a>
          </li>
        </ul>
      </div>
      <div style = "background-color: #480607; color: #eaeaea"><h1 style="text-align: left;">Задачи</h1>
      <p style="text-align: left; color: #eaeaea"><?= date('d.m.Y') ?></p> </div>

      <div class="card-body">

    <div class="w-100 text-start" style="margin: 0 !important; padding: 0 !important;">
      <div class="btn-group dropend">
        <button type="button" class="btn dropdown-toggle text-start" 
                style="background: transparent; border: none; color: #000; font-size: 2rem; font-weight: bold; padding: 0; box-shadow: none; min-width: 200px;" 
                data-bs-toggle="dropdown" aria-expanded="false">
          Мои задачи
        </button>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="?filter=all">Все задачи</a></li>
          <li><a class="dropdown-item" href="?filter=active">В процессе</a></li>
          <li><a class="dropdown-item" href="?filter=overdue">Просроченные</a></li>
          <li><a class="dropdown-item" href="?filter=completed">Выполнено</a></li>
        </ul>
      </div>
    </div>
      <br>
      <button class="btn btn-dark d-flex align-items-center gap-2" type="button" disabled>
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        Всего в работе: <?= $active_count ?>
      </button>
      <br>
  <ol class="list-group list-group-numbered">
  <?php if (empty($tasks)): ?>
      <li class="list-group-item text-center text-muted">Задач пока нет</li>
  <?php else: ?>
      <?php foreach ($tasks as $task): ?>
      <li class="list-group-item d-flex justify-content-between align-items-start">
        <div class="ms-2 me-auto">
          <div class="fw-bold"><?= htmlspecialchars($task['name']) ?></div>
          <small class="text-muted">Статус: <?= htmlspecialchars($task['status']) ?></small>
        </div>
        <div class="d-flex gap-2 align-items-center">
          <span class="badge rounded-pill bg-secondary">
            <?= $task['deadline'] ? date('d.m.Y', strtotime($task['deadline'])) : '—' ?>
          </span>
          
          <?php if ($task['status'] !== 'завершен'): ?>
          <button type="button" class="btn btn-sm btn-success" 
                  onclick="changeStatus(<?= $task['id'] ?>, 'завершен')" title="Завершить">✓</button>
          <?php endif; ?>
          
          <button type="button" class="btn btn-sm btn-primary" 
                  onclick="editTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['name'], ENT_QUOTES) ?>', '<?= $task['deadline'] ?>')" title="Редактировать">✎</button>
          
          <button type="button" class="btn btn-sm btn-danger" 
                  onclick="deleteTask(<?= $task['id'] ?>)" title="Удалить">×</button>
        </div>
      </li>
      <?php endforeach; ?>
  <?php endif; ?>
  </ol>
  <br>
  <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addTaskModal">+</button>

  <div class="modal fade" id="addTaskModal" tabindex="-1" aria-labelledby="addTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addTaskModalLabel">Новая задача</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
        </div>
        <form method="post">
          <div class="modal-body">
            <div class="mb-3">
              <label for="task_name" class="form-label">Название задачи</label>
              <input type="text" class="form-control" id="task_name" name="task_name" required>
            </div>
            <div class="mb-3">
              <label for="task_deadline" class="form-label">Дедлайн</label>
              <input type="date" class="form-control" id="task_deadline" name="task_deadline">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" name="new_task" class="btn btn-danger">Создать</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Редактировать задачу</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="post" action="update_task_name.php">
          <div class="modal-body">
            <input type="hidden" name="id" id="edit_task_id">
            <div class="mb-3">
              <label>Название</label>
              <input type="text" class="form-control" id="edit_task_name" name="name" required>
            </div>
            <div class="mb-3">
              <label>Дедлайн</label>
              <input type="date" class="form-control" id="edit_task_deadline" name="deadline">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
            <button type="submit" class="btn btn-primary">Сохранить</button>
          </div>
        </form>
      </div>
    </div>
  </div>

        <br>
          <nav aria-label="Навигация по страницам">
            <ul class="pagination justify-content-center">
              <?php if ($current_page > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $current_page - 1 ?>&filter=<?= $filter ?>" style="color: #000000">Предыдущая</a>
                </li>
              <?php endif; ?>
              
              <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $i === $current_page ? 'active' : '' ?>">
                  <a class="page-link" href="?page=<?= $i ?>&filter=<?= $filter ?>" style="color: #000000"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              
              <?php if ($current_page < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?page=<?= $current_page + 1 ?>&filter=<?= $filter ?>" style="color: #000000">Следующая</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>

      </div>
</div>

 
<div class="fixed-bottom">
    <footer class="text-white text-center py-3" style="background-color: #E32636;">
        <p>© 2026 Гатчинский государственный университет. Все права защищены.</p>
    </footer>
</div>

<script>
function changeStatus(taskId, status) {
    if (confirm('Изменить статус задачи?')) {
        fetch('update_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + taskId + '&status=' + status
        }).then(() => location.reload());
    }
}

function editTask(id, name, deadline) {
    document.getElementById('edit_task_id').value = id;
    document.getElementById('edit_task_name').value = name;
    document.getElementById('edit_task_deadline').value = deadline || '';
    new bootstrap.Modal(document.getElementById('editTaskModal')).show();
}

function deleteTask(id) {
    if (confirm('Удалить задачу?')) {
        fetch('delete_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        }).then(() => location.reload());
    }
}
</script>

<script src="./bootstrap.bundle.min.js"></script>
</html>