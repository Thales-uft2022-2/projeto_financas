<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verifica se usuário está logado
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$usuario_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: transactions.php?error=ID inválido');
    exit;
}

try {
    // Verifica se a transação pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM transacoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    
    if (!$stmt->fetch()) {
        header('Location: transactions.php?error=Transação não encontrada');
        exit;
    }
    
    // Exclui a transação
    $stmt = $pdo->prepare("DELETE FROM transacoes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    
    header('Location: transactions.php?success=Transação excluída com sucesso');
    exit;
    
} catch(PDOException $e) {
    header('Location: transactions.php?error=Erro ao excluir: ' . $e->getMessage());
    exit;
}
?>