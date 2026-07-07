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

// Filtros
$ano_selecionado = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$mes_selecionado = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos'; // todos, receita, despesa

// Lista de anos disponíveis
$stmt = $pdo->prepare("SELECT DISTINCT YEAR(data_transacao) as ano FROM transacoes WHERE usuario_id = ? ORDER BY ano DESC");
$stmt->execute([$usuario_id]);
$anos_disponiveis = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (empty($anos_disponiveis)) {
    $anos_disponiveis = [date('Y')];
}

// ============ GRÁFICO 1: Evolução Mensal ============
$stmt = $pdo->prepare("
    SELECT 
        MONTH(data_transacao) as mes,
        COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor ELSE 0 END), 0) as receitas,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END), 0) as despesas
    FROM transacoes 
    WHERE usuario_id = ? 
        AND YEAR(data_transacao) = ?
        AND status = 'pago'
    GROUP BY MONTH(data_transacao)
    ORDER BY mes
");
$stmt->execute([$usuario_id, $ano_selecionado]);
$dados_mensais = $stmt->fetchAll();

$meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
$receitas_mensais = array_fill(0, 12, 0);
$despesas_mensais = array_fill(0, 12, 0);

foreach ($dados_mensais as $dado) {
    $idx = $dado['mes'] - 1;
    $receitas_mensais[$idx] = (float)$dado['receitas'];
    $despesas_mensais[$idx] = (float)$dado['despesas'];
}

// ============ GRÁFICO 2: Top Categorias ============
$where_tipo = $tipo_filtro !== 'todos' ? "AND t.tipo = '$tipo_filtro'" : "";
$stmt = $pdo->prepare("
    SELECT 
        c.nome,
        c.cor,
        c.icone,
        SUM(t.valor) as total,
        COUNT(t.id) as quantidade
    FROM transacoes t
    JOIN categorias c ON t.categoria_id = c.id
    WHERE t.usuario_id = ? 
        AND YEAR(t.data_transacao) = ?
        AND MONTH(t.data_transacao) = ?
        AND t.status = 'pago'
        $where_tipo
    GROUP BY t.categoria_id
    ORDER BY total DESC
    LIMIT 10
");
$stmt->execute([$usuario_id, $ano_selecionado, $mes_selecionado]);
$top_categorias = $stmt->fetchAll();

// ============ GRÁFICO 3: Resumo do Período ============
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor ELSE 0 END), 0) as total_receitas,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END), 0) as total_despesas,
        COUNT(CASE WHEN tipo = 'receita' AND status = 'pago' THEN 1 END) as qtd_receitas,
        COUNT(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN 1 END) as qtd_despesas,
        AVG(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor END) as media_despesa,
        AVG(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor END) as media_receita
    FROM transacoes 
    WHERE usuario_id = ? 
        AND YEAR(data_transacao) = ?
        AND MONTH(data_transacao) = ?
");
$stmt->execute([$usuario_id, $ano_selecionado, $mes_selecionado]);
$resumo_periodo = $stmt->fetch();

// ============ GRÁFICO 4: Comparativo Anual ============
$stmt = $pdo->prepare("
    SELECT 
        YEAR(data_transacao) as ano,
        COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor ELSE 0 END), 0) as total_receitas,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END), 0) as total_despesas
    FROM transacoes 
    WHERE usuario_id = ? 
        AND status = 'pago'
    GROUP BY YEAR(data_transacao)
    ORDER BY ano
");
$stmt->execute([$usuario_id]);
$dados_anuais = $stmt->fetchAll();

// ============ GRÁFICO 5: Formas de Pagamento ============
$stmt = $pdo->prepare("
    SELECT 
        forma_pagamento,
        COUNT(*) as quantidade,
        SUM(valor) as total
    FROM transacoes 
    WHERE usuario_id = ? 
        AND YEAR(data_transacao) = ?
        AND MONTH(data_transacao) = ?
        AND status = 'pago'
    GROUP BY forma_pagamento
    ORDER BY total DESC
");
$stmt->execute([$usuario_id, $ano_selecionado, $mes_selecionado]);
$formas_pagamento = $stmt->fetchAll();

// Nomes das formas de pagamento
$forma_pagamento_nomes = [
    'dinheiro' => '💰 Dinheiro',
    'cartao_credito' => '💳 Cartão Crédito',
    'cartao_debito' => '💳 Cartão Débito',
    'pix' => '📱 PIX',
    'boleto' => '📄 Boleto',
    'transferencia' => '🏦 Transferência'
];

// ============ GRÁFICO 6: Transações Recentes ============
$stmt = $pdo->prepare("
    SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor 
    FROM transacoes t
    LEFT JOIN categorias c ON t.categoria_id = c.id
    WHERE t.usuario_id = ? 
        AND YEAR(t.data_transacao) = ?
        AND MONTH(t.data_transacao) = ?
        AND t.status = 'pago'
    ORDER BY t.data_transacao DESC
    LIMIT 15
");
$stmt->execute([$usuario_id, $ano_selecionado, $mes_selecionado]);
$transacoes_recentes = $stmt->fetchAll();

// Calcula o maior valor para os gráficos
$max_mensal = max(array_merge($receitas_mensais, $despesas_mensais, [1]));
$max_categoria = !empty($top_categorias) ? max(array_column($top_categorias, 'total')) : 1;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - FinControl</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-container {
            padding: 100px 0 40px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
        }

        .page-header h1 small {
            font-size: 16px;
            font-weight: 400;
            color: #64748b;
            display: block;
            margin-top: 5px;
        }

        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filter-form select {
            padding: 8px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }

        .filter-form select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .filter-form .btn-filter {
            padding: 8px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
        }

        .filter-form .btn-filter:hover {
            background: #1d4ed8;
        }

        .filter-form .btn-clear {
            padding: 8px 20px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.3s;
        }

        .filter-form .btn-clear:hover {
            background: #cbd5e1;
        }

        /* Cards de Resumo */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .summary-card:hover {
            transform: translateY(-3px);
        }

        .summary-card .icon {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .summary-card .label {
            font-size: 13px;
            color: #64748b;
        }

        .summary-card .value {
            font-size: 22px;
            font-weight: 700;
            margin-top: 5px;
        }

        .summary-card .value.receita { color: #22c55e; }
        .summary-card .value.despesa { color: #ef4444; }
        .summary-card .value.saldo { color: #2563eb; }
        .summary-card .value.media { color: #8b5cf6; }

        /* Grid de Gráficos */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-box h3 {
            font-size: 17px;
            margin-bottom: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-box .chart-container {
            position: relative;
            height: 280px;
        }

        .chart-box-small {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .chart-box-small h4 {
            font-size: 15px;
            margin-bottom: 15px;
            color: #1e293b;
        }

        .chart-box-small .chart-container {
            position: relative;
            height: 200px;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        /* Lista de Transações */
        .transactions-list {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .transactions-list h3 {
            font-size: 17px;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .transaction-category-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
        }

        .transaction-details .desc {
            font-weight: 500;
            color: #1e293b;
            font-size: 14px;
        }

        .transaction-details .meta {
            font-size: 12px;
            color: #94a3b8;
        }

        .transaction-amount {
            font-weight: 600;
            font-size: 15px;
        }

        .transaction-amount.receita {
            color: #22c55e;
        }

        .transaction-amount.despesa {
            color: #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        /* Formas de pagamento */
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 14px;
        }

        .payment-item .name {
            color: #475569;
        }

        .payment-item .total {
            font-weight: 600;
            color: #1e293b;
        }

        .payment-item .qtd {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Responsivo */
        @media (max-width: 992px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .summary-cards {
                grid-template-columns: 1fr 1fr;
            }
            .summary-card .value {
                font-size: 18px;
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-form select,
            .filter-form button {
                width: 100%;
            }
            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-left">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-wallet"></i>
                    <span>FinControl</span>
                </a>
            </div>
            <div class="nav-right">
                <span class="user-greeting">
                    <i class="fas fa-user-circle"></i>
                    Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                </span>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
                <a href="transactions.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> Transações
                </a>
                <a href="../logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <!-- Conteúdo -->
    <section class="page-container">
        <div class="container">
            <!-- Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-chart-bar" style="color: #2563eb;"></i> Relatórios
                    <small>Análise detalhada das suas finanças</small>
                </h1>
            </div>

            <!-- Filtros -->
            <form method="GET" class="filter-form">
                <select name="ano">
                    <?php foreach ($anos_disponiveis as $ano): ?>
                        <option value="<?php echo $ano; ?>" <?php echo $ano == $ano_selecionado ? 'selected' : ''; ?>>
                            <?php echo $ano; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="mes">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i == $mes_selecionado ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="tipo">
                    <option value="todos" <?php echo $tipo_filtro === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="receita" <?php echo $tipo_filtro === 'receita' ? 'selected' : ''; ?>>📈 Receitas</option>
                    <option value="despesa" <?php echo $tipo_filtro === 'despesa' ? 'selected' : ''; ?>>📉 Despesas</option>
                </select>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Aplicar
                </button>
                <a href="reports.php" class="btn-clear">
                    <i class="fas fa-undo"></i> Limpar
                </a>
            </form>

            <!-- Cards de Resumo -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="icon">💰</div>
                    <div class="label">Total Receitas</div>
                    <div class="value receita"><?php echo formatCurrency($resumo_periodo['total_receitas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon">💸</div>
                    <div class="label">Total Despesas</div>
                    <div class="value despesa"><?php echo formatCurrency($resumo_periodo['total_despesas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon">📊</div>
                    <div class="label">Saldo do Período</div>
                    <div class="value saldo"><?php echo formatCurrency($resumo_periodo['total_receitas'] - $resumo_periodo['total_despesas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon">📈</div>
                    <div class="label">Média Receitas</div>
                    <div class="value media"><?php echo formatCurrency($resumo_periodo['media_receita'] ?? 0); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon">📉</div>
                    <div class="label">Média Despesas</div>
                    <div class="value media"><?php echo formatCurrency($resumo_periodo['media_despesa'] ?? 0); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon">📝</div>
                    <div class="label">Total Transações</div>
                    <div class="value saldo"><?php echo ($resumo_periodo['qtd_receitas'] ?? 0) + ($resumo_periodo['qtd_despesas'] ?? 0); ?></div>
                </div>
            </div>

            <!-- Gráfico Principal: Evolução Mensal -->
            <div class="chart-box" style="margin-bottom: 30px;">
                <h3><i class="fas fa-chart-line" style="color: #2563eb;"></i> Evolução Mensal - <?php echo $ano_selecionado; ?></h3>
                <div class="chart-container">
                    <canvas id="mensalChart"></canvas>
                </div>
            </div>

            <!-- Grid de Gráficos -->
            <div class="charts-grid">
                <div class="chart-box">
                    <h3><i class="fas fa-chart-pie" style="color: #8b5cf6;"></i> Top Categorias</h3>
                    <div class="chart-container">
                        <canvas id="categoriasChart"></canvas>
                    </div>
                    <?php if (empty($top_categorias)): ?>
                        <p style="text-align: center; color: #94a3b8; font-size: 14px; margin-top: 10px;">
                            Nenhuma transação neste período
                        </p>
                    <?php endif; ?>
                </div>
                <div class="chart-box">
                    <h3><i class="fas fa-credit-card" style="color: #f59e0b;"></i> Formas de Pagamento</h3>
                    <div class="chart-container">
                        <canvas id="pagamentoChart"></canvas>
                    </div>
                    <?php if (empty($formas_pagamento)): ?>
                        <p style="text-align: center; color: #94a3b8; font-size: 14px; margin-top: 10px;">
                            Nenhuma transação neste período
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gráfico Comparativo Anual -->
            <?php if (count($dados_anuais) > 1): ?>
            <div class="chart-box" style="margin-bottom: 30px;">
                <h3><i class="fas fa-chart-bar" style="color: #22c55e;"></i> Comparativo Anual</h3>
                <div class="chart-container" style="height: 250px;">
                    <canvas id="anualChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- Últimas Transações do Período -->
            <div class="transactions-list">
                <h3>
                    <i class="fas fa-clock"></i> Transações do Período
                    <span style="font-size: 14px; font-weight: 400; color: #64748b; float: right;">
                        <?php echo date('F', mktime(0, 0, 0, $mes_selecionado, 1)); ?> de <?php echo $ano_selecionado; ?>
                    </span>
                </h3>
                
                <?php if (empty($transacoes_recentes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>Nenhuma transação encontrada neste período</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($transacoes_recentes as $transacao): ?>
                        <div class="transaction-item">
                            <div class="transaction-info">
                                <div class="transaction-category-icon" style="background: <?php echo $transacao['categoria_cor'] ?? '#64748b'; ?>">
                                    <i class="fas <?php echo $transacao['categoria_icone'] ?? 'fa-tag'; ?>"></i>
                                </div>
                                <div class="transaction-details">
                                    <div class="desc"><?php echo htmlspecialchars($transacao['descricao']); ?></div>
                                    <div class="meta">
                                        <?php echo formatDate($transacao['data_transacao']); ?>
                                        • <?php echo htmlspecialchars($transacao['categoria_nome'] ?? 'Sem categoria'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="transaction-amount <?php echo $transacao['tipo']; ?>">
                                <?php echo $transacao['tipo'] === 'receita' ? '+' : '-'; ?>
                                <?php echo formatCurrency($transacao['valor']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="text-align: center; margin-top: 15px;">
                        <a href="transactions.php" class="btn btn-outline">
                            Ver todas as transações <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <script>
        // Dados para os gráficos
        const mesesNomes = <?php echo json_encode($meses_nomes); ?>;
        const receitasMensais = <?php echo json_encode($receitas_mensais); ?>;
        const despesasMensais = <?php echo json_encode($despesas_mensais); ?>;
        const topCategorias = <?php echo json_encode($top_categorias); ?>;
        const formasPagamento = <?php echo json_encode($formas_pagamento); ?>;
        const dadosAnuais = <?php echo json_encode($dados_anuais); ?>;

        // Cores para gráficos
        const cores = [
            '#2563eb', '#22c55e', '#ef4444', '#f59e0b', '#8b5cf6',
            '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6366f1'
        ];

        // ============ GRÁFICO 1: Evolução Mensal ============
        const ctx1 = document.getElementById('mensalChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: mesesNomes,
                datasets: [
                    {
                        label: 'Receitas',
                        data: receitasMensais,
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#22c55e'
                    },
                    {
                        label: 'Despesas',
                        data: despesasMensais,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#ef4444'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });

        // ============ GRÁFICO 2: Top Categorias ============
        if (topCategorias.length > 0) {
            const ctx2 = document.getElementById('categoriasChart').getContext('2d');
            const labels = topCategorias.map(c => c.nome);
            const values = topCategorias.map(c => parseFloat(c.total));
            const colors = topCategorias.map(c => c.cor || '#64748b');
            
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 10,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': R$ ' + context.parsed.toFixed(2) + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        // ============ GRÁFICO 3: Formas de Pagamento ============
        if (formasPagamento.length > 0) {
            const ctx3 = document.getElementById('pagamentoChart').getContext('2d');
            const nomes = formasPagamento.map(f => {
                const nomesMap = <?php echo json_encode($forma_pagamento_nomes); ?>;
                return nomesMap[f.forma_pagamento] || f.forma_pagamento;
            });
            const valores = formasPagamento.map(f => parseFloat(f.total));
            
            const coresPagamento = ['#22c55e', '#2563eb', '#8b5cf6', '#f59e0b', '#ef4444', '#06b6d4'];
            
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: nomes,
                    datasets: [{
                        label: 'Total por forma de pagamento',
                        data: valores,
                        backgroundColor: coresPagamento.slice(0, valores.length),
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'R$ ' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // ============ GRÁFICO 4: Comparativo Anual ============
        <?php if (count($dados_anuais) > 1): ?>
        const ctx4 = document.getElementById('anualChart').getContext('2d');
        const anos = dadosAnuais.map(d => d.ano);
        const receitasAnuais = dadosAnuais.map(d => parseFloat(d.total_receitas));
        const despesasAnuais = dadosAnuais.map(d => parseFloat(d.total_despesas));
        
        new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: anos,
                datasets: [
                    {
                        label: 'Receitas',
                        data: receitasAnuais,
                        backgroundColor: 'rgba(34, 197, 94, 0.7)',
                        borderColor: '#22c55e',
                        borderWidth: 2,
                        borderRadius: 4
                    },
                    {
                        label: 'Despesas',
                        data: despesasAnuais,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
                        borderWidth: 2,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 20,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>