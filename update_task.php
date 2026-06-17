<?php
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ? AND admin_id = ?");
    $stmt->execute([$status, $id, $_SESSION['user_id']]);
}
?>