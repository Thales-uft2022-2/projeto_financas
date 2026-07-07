<?php
/**
 * Funções auxiliares do sistema
 */

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function getTotalBalance($usuario_id, $pdo) {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END), 0) as total_receitas,
            COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END), 0) as total_despesas
        FROM transacoes 
        WHERE usuario_id = ? AND status = 'pago'
    ");
    $stmt->execute([$usuario_id]);
    $result = $stmt->fetch();
    
    return [
        'total_receitas' => $result['total_receitas'],
        'total_despesas' => $result['total_despesas'],
        'saldo' => $result['total_receitas'] - $result['total_despesas']
    ];
}

function getLatestTransactions($usuario_id, $pdo, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone 
        FROM transacoes t
        LEFT JOIN categorias c ON t.categoria_id = c.id
        WHERE t.usuario_id = ? 
        ORDER BY t.data_transacao DESC, t.created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$usuario_id, $limit]);
    return $stmt->fetchAll();
}

function getCategorySummary($usuario_id, $pdo, $tipo = 'despesa', $mes = null, $ano = null) {
    if (!$mes) $mes = date('m');
    if (!$ano) $ano = date('Y');
    
    $stmt = $pdo->prepare("
        SELECT 
            c.nome,
            c.cor,
            c.icone,
            SUM(t.valor) as total
        FROM transacoes t
        JOIN categorias c ON t.categoria_id = c.id
        WHERE t.usuario_id = ? 
            AND t.tipo = ?
            AND t.status = 'pago'
            AND MONTH(t.data_transacao) = ?
            AND YEAR(t.data_transacao) = ?
        GROUP BY t.categoria_id
        ORDER BY total DESC
    ");
    $stmt->execute([$usuario_id, $tipo, $mes, $ano]);
    return $stmt->fetchAll();
}

function getMonthlySummary($usuario_id, $pdo, $ano = null) {
    if (!$ano) $ano = date('Y');
    
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(data_transacao) as mes,
            COALESCE(SUM(CASE WHEN tipo = 'receita' THEN valor ELSE 0 END), 0) as receitas,
            COALESCE(SUM(CASE WHEN tipo = 'despesa' THEN valor ELSE 0 END), 0) as despesas
        FROM transacoes 
        WHERE usuario_id = ? 
            AND status = 'pago'
            AND YEAR(data_transacao) = ?
        GROUP BY MONTH(data_transacao)
        ORDER BY mes
    ");
    $stmt->execute([$usuario_id, $ano]);
    return $stmt->fetchAll();
}

function getChartData($categorias) {
    $labels = [];
    $values = [];
    $colors = [];
    
    foreach ($categorias as $cat) {
        $labels[] = $cat['nome'];
        $values[] = floatval($cat['total']);
        $colors[] = $cat['cor'];
    }
    
    return [
        'labels' => $labels,
        'values' => $values,
        'colors' => $colors
    ];
}

function getPaymentMethods() {
    return [
        'dinheiro' => 'Dinheiro',
        'cartao_credito' => 'Cartão de Crédito',
        'cartao_debito' => 'Cartão de Débito',
        'pix' => 'PIX',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferência'
    ];
}

function getTransactionStatus() {
    return [
        'pendente' => 'Pendente',
        'pago' => 'Pago',
        'cancelado' => 'Cancelado'
    ];
}
?>