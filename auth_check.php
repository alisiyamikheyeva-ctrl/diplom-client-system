<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: вход.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$current_user = $stmt->fetch();
?>