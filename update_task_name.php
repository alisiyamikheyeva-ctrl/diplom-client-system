<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $deadline = $_POST['deadline'] ?? null;
    
    $stmt = $pdo->prepare("UPDATE tasks SET name = ?, deadline = ? WHERE id = ? AND admin_id = ?");
    $stmt->execute([$name, $deadline, $id, $_SESSION['user_id']]);
    
    header('Location: главная.php');
}
?>