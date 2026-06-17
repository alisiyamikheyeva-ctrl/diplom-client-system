<?php
require_once 'auth_check.php';

$edit_mode = false;
$client = null;
$error = '';
$success = '';

if (isset($_GET['id'])) {
    $edit_mode = true;
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $client = $stmt->fetch();
    
    if (!$client) {
        header('Location: справочник.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fio = $_POST['fio'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($fio)) {
        $error = 'Заполните ФИО';
    } else {
        if ($edit_mode) {
            $stmt = $pdo->prepare("UPDATE clients SET fio = ?, phone = ?, email = ? WHERE id = ?");
            $stmt->execute([$fio, $phone, $email, $client['id']]);
            $success = 'Клиент обновлён';
            
            if (!empty($login) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin (login, password) VALUES (?, ?)");
                $stmt->execute([$login, $hashed_password]);
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO clients (fio, phone, email) VALUES (?, ?, ?)");
            $stmt->execute([$fio, $phone, $email]);
            $client_id = $pdo->lastInsertId();
            $success = 'Клиент создан';
            
            if (!empty($login) && !empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin (login, password) VALUES (?, ?)");
                $stmt->execute([$login, $hashed_password]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./bootstrap.min.css">
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
    
    <div class="position-absolute top-50 start-50 translate-middle">
        <form method="post" class="row g-3" style="background-color: #FFFFFF; border: 3px solid #000000; border-radius: 2%">
            <h2><?= $edit_mode ? 'РЕДАКТИРОВАНИЕ КЛИЕНТА' : 'СОЗДАНИЕ КЛИЕНТА' ?></h2>
            
            <div class="col-12">
                <div class="col-12">
                    <div class="col-12">
                        <label for="inputLogin" class="form-label">ЛОГИН <?= $edit_mode ? '(необязательно)' : '(необязательно)' ?></label>
                        <input
                            type="text"
                            name="login"
                            class="form-control"
                            id="inputLogin"
                            placeholder="Введите логин"
                            pattern="^[A-Za-z0-9]{6,}$"
                            title="Логин должен содержать не менее 6 латинских букв или цифр"
                            <?= $edit_mode ? 'disabled' : '' ?>
                            value="<?= $edit_mode && isset($_POST['login']) ? htmlspecialchars($_POST['login']) : '' ?>"
                        />
                        <div class="form-text">Если заполните - будет создан пользователь системы</div>
                    </div>
                    
                    <div class="col-12">
                        <label for="inputEmail" class="form-label">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            class="form-control" 
                            id="inputEmail" 
                            value="<?= $edit_mode ? htmlspecialchars($client['email']) : (isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '') ?>"
                        >
                    </div>
                    
                    <div class="col-12">
                        <label for="inputPassword" class="form-label">ПАРОЛЬ <?= $edit_mode ? '(оставьте пустым, если не нужно менять)' : '(необязательно)' ?></label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            id="inputPassword"
                            <?= $edit_mode ? '' : '' ?>
                        />
                        <div class="form-text">Если заполните - будет создан пользователь системы</div>
                    </div>
                    
                    <div class="col-12">
                        <label for="inputUsername" class="form-label">ФИО</label>
                        <input 
                            type="text" 
                            name="fio" 
                            class="form-control" 
                            id="inputUsername" 
                            required
                            value="<?= $edit_mode ? htmlspecialchars($client['fio']) : (isset($_POST['fio']) ? htmlspecialchars($_POST['fio']) : '') ?>"
                        >
                    </div>
                    
                    <div class="col-12">
                        <label for="inputPhone" class="form-label">ТЕЛЕФОН</label>
                        <input 
                            type="text" 
                            name="phone" 
                            class="form-control" 
                            id="inputPhone" 
                            placeholder="+7 (___) ___-__-__"
                            value="<?= $edit_mode ? htmlspecialchars($client['phone']) : (isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '') ?>"
                        >
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-danger"><?= $edit_mode ? 'СОХРАНИТЬ' : 'СОЗДАТЬ' ?></button>
                        <?php if (!empty($error)): ?>
                            <h6 style="color: red;"><?= $error ?></h6>
                        <?php endif; ?>
                        <?php if (!empty($success)): ?>
                            <h6 style="color: green;"><?= $success ?></h6>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>   
    </div>
    
    <div class="fixed-bottom">
        <footer class="text-white text-center py-3" style="background-color: #E32636;">
            <p>© 2026 КОРОЧКИНЕТ. Все права защищены.</p>
        </footer>
    </div>
</body>
<script src="./bootstrap.bundle.min.js"></script>
</html>