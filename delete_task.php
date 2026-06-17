<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND admin_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
}
?>