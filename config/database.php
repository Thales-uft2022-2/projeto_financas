<?php
// Configuração do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'projeto_financas');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuração de timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com o banco
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}

// Função para executar queries com segurança
function executeQuery($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch(PDOException $e) {
        error_log("Erro na query: " . $e->getMessage());
        throw $e;
    }
}
?>