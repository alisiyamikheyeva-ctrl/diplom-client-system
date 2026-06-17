<?php
$host = 'localhost';
$db = 'diplom';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения");
}

$login = 'admin';

// Сначала удаляем старого админа (если есть)
$pdo->prepare("DELETE FROM admin WHERE login = ?")->execute([$login]);

// Создаём нового с правильным паролем
$password = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO admin (login, password) VALUES (?, ?)");
$stmt->execute([$login, $password]);

echo "✅ Администратор создан!<br>Логин: <b>admin</b><br>Пароль: <b>admin123</b><br><br><a href='вход.php'>Перейти ко входу</a>";
?>