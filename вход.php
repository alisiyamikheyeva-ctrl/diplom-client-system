<?php
session_start();

$host = 'localhost';
$db = 'diplom';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных");
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($login) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE login = ?");
        $stmt->execute([$login]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_login'] = $admin['login'];
            
            header('Location: главная.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    } else {
        $error = 'Заполните все поля';
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
<nav class="navbar"  style="background-color: #E32636;">
  <div class="container-fluid">
    <a class="navbar-brand" href="">
      <img src="logo.png" alt="Logo" width="30" height="24" class="d-inline-block align-text-top">
      Гатчинский государственный университет
    </a>
  </div>
</nav>
<div class="position-absolute top-50 start-50 translate-middle">
  <form method="post" class="row g-3" style="background-color: #FFFFFF; border: 3px solid #000000; border-radius: 2%">
    <h2>ВХОД</h2>
    Пожалуйста, введите логин и пароль для авторизации:
    <div class="mb-3">
        <label for="loginInput" class="form-label">Логин</label>
        <input type="text" name="login" class="form-control" id="loginInput" aria-describedby="loginHelp">
        <div id="loginHelp" class="form-text">Введите ваш логин для входа в систему.</div>
    </div>
    <div class="mb-3">
        <label for="exampleInputPassword1" class="form-label">Пароль</label>
        <input type="password" name="password" class="form-control" id="exampleInputPassword1">
    </div>
    <div class="col-12">
      <button type="submit" class="btn btn-danger">ВОЙТИ</button>
      <?php if (!empty($error)): ?>
        <h5 style="color: red;"><?= $error ?></h5>
      <?php endif; ?>
    </div>
  </form>   
</div>
<div class="fixed-bottom">>
    <footer class="text-white text-center py-3" style="background-color: #E32636;">
        <p>© 2026 Гатчинский государственный университет. Все права защищены.</p>
    </footer>
</div>
</body>
<script src="./bootstrap.bundle.min.js"></script>
</html>