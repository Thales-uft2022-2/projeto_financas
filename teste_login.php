<?php
require_once 'config/database.php';

try {
    // Testa a conexão
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $count = $stmt->fetchColumn();
    echo "✅ Conectado! Total de usuários: " . $count . "<br>";
    
    // Tenta inserir um usuário
    $nome = "Teste Manual";
    $email = "manual@teste.com";
    $senha = password_hash("123456", PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
    $stmt->execute([$nome, $email, $senha]);
    
    echo "✅ Usuário inserido com sucesso!<br>";
    echo "ID: " . $pdo->lastInsertId();
    
} catch(PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>