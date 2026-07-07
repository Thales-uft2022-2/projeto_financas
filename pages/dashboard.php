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
$usuario_nome = $_SESSION['user_name'];

// Filtros
$mes_atual = date('m');
$ano_atual = date('Y');
$mes_selecionado = isset($_GET['mes']) ? $_GET['mes'] : $mes_atual;
$ano_selecionado = isset($_GET['ano']) ? $_GET['ano'] : $ano_atual;

// Busca resumo financeiro
$balanco = getTotalBalance($usuario_id, $pdo);

// Busca resumo por categoria (despesas)
$categorias_despesas = getCategorySummary($usuario_id, $pdo, 'despesa', $mes_selecionado, $ano_selecionado);

// Busca resumo por categoria (receitas)
$categorias_receitas = getCategorySummary($usuario_id, $pdo, 'receita', $mes_selecionado, $ano_selecionado);

// Busca resumo mensal do ano
$resumo_mensal = getMonthlySummary($usuario_id, $pdo, $ano_selecionado);

// Prepara dados para os gráficos
$chart_despesas = getChartData($categorias_despesas);
$chart_receitas = getChartData($categorias_receitas);

// Calcula estatísticas do mês
$total_receitas_mes = array_sum(array_column($categorias_receitas, 'total'));
$total_despesas_mes = array_sum(array_column($categorias_despesas, 'total'));
$saldo_mes = $total_receitas_mes - $total_despesas_mes;

// Busca transações do mês para listagem
$stmt = $pdo->prepare("
    SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone
    FROM transacoes t
    LEFT JOIN categorias c ON t.categoria_id = c.id
    WHERE t.usuario_id = ? 
        AND MONTH(t.data_transacao) = ? 
        AND YEAR(t.data_transacao) = ?
        AND t.status = 'pago'
    ORDER BY t.data_transacao DESC
    LIMIT 20
");
$stmt->execute([$usuario_id, $mes_selecionado, $ano_selecionado]);
$transacoes_mes = $stmt->fetchAll();

// Lista de anos para o filtro
$stmt = $pdo->prepare("
    SELECT DISTINCT YEAR(data_transacao) as ano 
    FROM transacoes 
    WHERE usuario_id = ? 
    ORDER BY ano DESC
");
$stmt->execute([$usuario_id]);
$anos_disponiveis = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($anos_disponiveis)) {
    $anos_disponiveis = [date('Y')];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FinControl</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard { padding: 100px 0 40px; background: #f8fafc; min-height: 100vh; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .dashboard-header h1 { font-size: 28px; font-weight: 700; color: #1e293b; }
        .dashboard-header h1 small { font-size: 16px; font-weight: 400; color: #64748b; display: block; margin-top: 5px; }
        .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-form select { padding: 8px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white; cursor: pointer; }
        .filter-form select:focus { outline: none; border-color: #2563eb; }
        .filter-form .btn-filter { padding: 8px 20px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; transition: background 0.3s; }
        .filter-form .btn-filter:hover { background: #1d4ed8; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .summary-card:hover { transform: translateY(-3px); }
        .summary-card .label { font-size: 14px; color: #64748b; margin-bottom: 8px; }
        .summary-card .value { font-size: 28px; font-weight: 700; }
        .summary-card .value.positive { color: #22c55e; }
        .summary-card .value.negative { color: #ef4444; }
        .summary-card .value.primary { color: #2563eb; }
        .summary-card .icon { float: right; font-size: 30px; opacity: 0.2; }
        .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 30px; }
        .chart-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .chart-box h3 { font-size: 18px; margin-bottom: 20px; color: #1e293b; }
        .chart-box .chart-container { position: relative; height: 250px; }
        .transactions-list { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .transactions-list h3 { font-size: 18px; margin-bottom: 20px; color: #1e293b; display: flex; justify-content: space-between; align-items: center; }
        .transactions-list h3 a { font-size: 14px; color: #2563eb; text-decoration: none; }
        .transaction-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .transaction-item:last-child { border-bottom: none; }
        .transaction-info { display: flex; align-items: center; gap: 12px; }
        .transaction-category-icon { width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 14px; }
        .transaction-details .desc { font-weight: 500; color: #1e293b; }
        .transaction-details .meta { font-size: 12px; color: #94a3b8; }
        .transaction-amount { font-weight: 600; font-size: 16px; }
        .transaction-amount.receita { color: #22c55e; }
        .transaction-amount.despesa { color: #ef4444; }
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.3; }
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
        .chart-box-small { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .chart-box-small h4 { font-size: 15px; margin-bottom: 15px; color: #1e293b; }
        .chart-box-small .chart-container { position: relative; height: 200px; }
        @media (max-width: 992px) { .dashboard-grid { grid-template-columns: 1fr; } .charts-row { grid-template-columns: 1fr; } }
        @media (max-width: 600px) { .dashboard-header { flex-direction: column; align-items: stretch; } .filter-form { flex-wrap: wrap; } .summary-cards { grid-template-columns: 1fr 1fr; } .summary-card .value { font-size: 20px; } }
    </style>
</head>
<body>
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
                    Olá, <?php echo htmlspecialchars($usuario_nome); ?>
                </span>
                <a href="transactions.php" class="btn btn-outline">
                    <i class="fas fa-list"></i> Transações
                </a>
                <a href="categories.php" class="btn btn-outline">
                    <i class="fas fa-tags"></i> Categorias
                </a>
                <a href="reports.php" class="btn btn-outline">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
                <a href="add_transaction.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nova
                </a>
                <a href="../logout.php" class="btn btn-outline">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>

    <section class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-chart-pie" style="color: #2563eb;"></i> Dashboard
                    <small>Visão geral das suas finanças</small>
                </h1>
                <form method="GET" class="filter-form">
                    <select name="mes">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $mes_selecionado ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="ano">
                        <?php foreach ($anos_disponiveis as $ano): ?>
                            <option value="<?php echo $ano; ?>" <?php echo $ano == $ano_selecionado ? 'selected' : ''; ?>>
                                <?php echo $ano; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>

            <div class="summary-cards">
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-wallet"></i></div>
                    <div class="label">Saldo Total</div>
                    <div class="value <?php echo $balanco['saldo'] >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo formatCurrency($balanco['saldo']); ?>
                    </div>
                </div>
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-arrow-up" style="color: #22c55e;"></i></div>
                    <div class="label">Total Receitas</div>
                    <div class="value positive"><?php echo formatCurrency($balanco['total_receitas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-arrow-down" style="color: #ef4444;"></i></div>
                    <div class="label">Total Despesas</div>
                    <div class="value negative"><?php echo formatCurrency($balanco['total_despesas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="label">Saldo do Mês</div>
                    <div class="value <?php echo $saldo_mes >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo formatCurrency($saldo_mes); ?>
                    </div>
                </div>
            </div>

            <div class="charts-row">
                <div class="chart-box-small">
                    <h4><i class="fas fa-chart-pie" style="color: #ef4444;"></i> Despesas por Categoria</h4>
                    <div class="chart-container">
                        <canvas id="despesasChart"></canvas>
                    </div>
                    <?php if (empty($categorias_despesas)): ?>
                        <p style="text-align: center; color: #94a3b8; font-size: 14px;">Nenhuma despesa registrada</p>
                    <?php endif; ?>
                </div>
                <div class="chart-box-small">
                    <h4><i class="fas fa-chart-pie" style="color