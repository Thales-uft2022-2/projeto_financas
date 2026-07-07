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
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$filtro_busca = $_GET['busca'] ?? '';
$filtro_status = $_GET['status'] ?? '';

// Paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

// Monta a query com filtros
$where = "t.usuario_id = ?";
$params = [$usuario_id];

if (!empty($filtro_tipo)) {
    $where .= " AND t.tipo = ?";
    $params[] = $filtro_tipo;
}

if (!empty($filtro_categoria)) {
    $where .= " AND t.categoria_id = ?";
    $params[] = $filtro_categoria;
}

if (!empty($filtro_data_inicio)) {
    $where .= " AND t.data_transacao >= ?";
    $params[] = $filtro_data_inicio;
}

if (!empty($filtro_data_fim)) {
    $where .= " AND t.data_transacao <= ?";
    $params[] = $filtro_data_fim;
}

if (!empty($filtro_status)) {
    $where .= " AND t.status = ?";
    $params[] = $filtro_status;
}

if (!empty($filtro_busca)) {
    $where .= " AND (t.descricao LIKE ? OR t.observacao LIKE ?)";
    $busca = "%$filtro_busca%";
    $params[] = $busca;
    $params[] = $busca;
}

// Busca total de registros para paginação
$sql_count = "SELECT COUNT(*) as total FROM transacoes t WHERE $where";
$stmt = $pdo->prepare($sql_count);
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Busca as transações
$sql = "
    SELECT t.*, c.nome as categoria_nome, c.cor as categoria_cor, c.icone as categoria_icone
    FROM transacoes t
    LEFT JOIN categorias c ON t.categoria_id = c.id
    WHERE $where
    ORDER BY t.data_transacao DESC, t.created_at DESC
    LIMIT $offset, $limite
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transacoes = $stmt->fetchAll();

// Busca categorias para o filtro
$stmt = $pdo->prepare("SELECT id, nome, tipo FROM categorias WHERE usuario_id = ? ORDER BY tipo, nome");
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll();

// Resumo das transações (totais)
$sql_resumo = "
    SELECT 
        COALESCE(SUM(CASE WHEN tipo = 'receita' AND status = 'pago' THEN valor ELSE 0 END), 0) as total_receitas,
        COALESCE(SUM(CASE WHEN tipo = 'despesa' AND status = 'pago' THEN valor ELSE 0 END), 0) as total_despesas
    FROM transacoes 
    WHERE usuario_id = ? 
";
$stmt = $pdo->prepare($sql_resumo);
$stmt->execute([$usuario_id]);
$resumo = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transações - FinControl</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Cards de resumo */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .summary-card .label {
            font-size: 14px;
            color: #64748b;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
            margin-top: 5px;
        }

        .summary-card .value.receita { color: #22c55e; }
        .summary-card .value.despesa { color: #ef4444; }
        .summary-card .value.total { color: #2563eb; }

        /* Filtros */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .filters-grid .form-group {
            margin-bottom: 0;
        }

        .filters-grid label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #64748b;
            margin-bottom: 4px;
        }

        .filters-grid input,
        .filters-grid select {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .filters-grid input:focus,
        .filters-grid select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-filter {
            padding: 8px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            white-space: nowrap;
        }

        .btn-filter:hover {
            background: #1d4ed8;
        }

        .btn-clear {
            padding: 8px 20px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            text-decoration: none;
        }

        .btn-clear:hover {
            background: #cbd5e1;
        }

        /* Tabela */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        th {
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .categoria-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }

        .categoria-badge i {
            font-size: 12px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-pago {
            background: #d1fae5;
            color: #065f46;
        }

        .status-pendente {
            background: #fef3c7;
            color: #92400e;
        }

        .status-cancelado {
            background: #fee2e2;
            color: #991b1b;
        }

        .valor-receita {
            color: #22c55e;
            font-weight: 600;
        }

        .valor-despesa {
            color: #ef4444;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions a {
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn-edit {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .btn-edit:hover {
            background: #bfdbfe;
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #fecaca;
        }

        .btn-view {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-view:hover {
            background: #cbd5e1;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 20px;
            color: #475569;
            margin-bottom: 10px;
        }

        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .pagination a,
        .pagination span {
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s;
        }

        .pagination a {
            background: #f1f5f9;
            color: #475569;
        }

        .pagination a:hover {
            background: #e2e8f0;
        }

        .pagination .active {
            background: #2563eb;
            color: white;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr 1fr;
            }
            .filter-actions {
                grid-column: span 2;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            .header-actions {
                justify-content: stretch;
            }
            .header-actions a {
                flex: 1;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            .filter-actions {
                grid-column: span 1;
                flex-direction: column;
            }
            .filter-actions button,
            .filter-actions a {
                width: 100%;
                text-align: center;
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
                <a href="add_transaction.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nova
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
                    <i class="fas fa-list" style="color: #2563eb;"></i> Transações
                    <small>Gerencie todas as suas receitas e despesas</small>
                </h1>
                <div class="header-actions">
                    <a href="add_transaction.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nova Transação
                    </a>
                    <a href="reports.php" class="btn btn-outline">
                        <i class="fas fa-chart-bar"></i> Relatórios
                    </a>
                </div>
            </div>

            <!-- Resumo -->
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="label">💰 Total de Receitas</div>
                    <div class="value receita"><?php echo formatCurrency($resumo['total_receitas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">💸 Total de Despesas</div>
                    <div class="value despesa"><?php echo formatCurrency($resumo['total_despesas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">📊 Saldo Total</div>
                    <div class="value total"><?php echo formatCurrency($resumo['total_receitas'] - $resumo['total_despesas']); ?></div>
                </div>
                <div class="summary-card">
                    <div class="label">📝 Total de Transações</div>
                    <div class="value total"><?php echo $total_registros; ?></div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo">
                                <option value="">Todos</option>
                                <option value="receita" <?php echo $filtro_tipo === 'receita' ? 'selected' : ''; ?>>Receitas</option>
                                <option value="despesa" <?php echo $filtro_tipo === 'despesa' ? 'selected' : ''; ?>>Despesas</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="categoria">Categoria</label>
                            <select name="categoria" id="categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nome']); ?> (<?php echo $cat['tipo'] === 'receita' ? '📈' : '📉'; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="">Todos</option>
                                <option value="pago" <?php echo $filtro_status === 'pago' ? 'selected' : ''; ?>>✅ Pago</option>
                                <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>⏳ Pendente</option>
                                <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>❌ Cancelado</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="data_inicio">Data Início</label>
                            <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $filtro_data_inicio; ?>">
                        </div>
                        <div class="form-group">
                            <label for="data_fim">Data Fim</label>
                            <input type="date" name="data_fim" id="data_fim" value="<?php echo $filtro_data_fim; ?>">
                        </div>
                        <div class="form-group">
                            <label for="busca">Buscar</label>
                            <input type="text" name="busca" id="busca" placeholder="Descrição ou observação..." value="<?php echo htmlspecialchars($filtro_busca); ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                            <a href="transactions.php" class="btn-clear">
                                <i class="fas fa-undo"></i> Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Tabela -->
            <div class="table-container">
                <div class="table-responsive">
                    <?php if (empty($transacoes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h3>Nenhuma transação encontrada</h3>
                            <p>Comece registrando sua primeira transação!</p>
                            <a href="add_transaction.php" class="btn btn-primary" style="display: inline-block; margin-top: 15px;">
                                <i class="fas fa-plus"></i> Adicionar Transação
                            </a>
                        </div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th>Categoria</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transacoes as $transacao): ?>
                                    <tr>
                                        <td><?php echo formatDate($transacao['data_transacao']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($transacao['descricao']); ?></strong>
                                            <?php if (!empty($transacao['observacao'])): ?>
                                                <br>
                                                <small style="color: #94a3b8; font-size: 12px;">
                                                    <i class="fas fa-comment"></i> <?php echo htmlspecialchars($transacao['observacao']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="categoria-badge" style="background: <?php echo $transacao['categoria_cor'] ?? '#64748b'; ?>">
                                                <i class="fas <?php echo $transacao['categoria_icone'] ?? 'fa-tag'; ?>"></i>
                                                <?php echo htmlspecialchars($transacao['categoria_nome'] ?? 'Sem categoria'); ?>
                                            </span>
                                        </td>
                                        <td class="<?php echo $transacao['tipo'] === 'receita' ? 'valor-receita' : 'valor-despesa'; ?>">
                                            <?php echo $transacao['tipo'] === 'receita' ? '+' : '-'; ?>
                                            <?php echo formatCurrency($transacao['valor']); ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $transacao['status']; ?>">
                                                <?php 
                                                    $status_labels = [
                                                        'pago' => '✅ Pago',
                                                        'pendente' => '⏳ Pendente',
                                                        'cancelado' => '❌ Cancelado'
                                                    ];
                                                    echo $status_labels[$transacao['status']] ?? $transacao['status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="actions">
                                                <a href="edit_transaction.php?id=<?php echo $transacao['id']; ?>" class="btn-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="#" onclick="confirmDelete(<?php echo $transacao['id']; ?>, '<?php echo addslashes($transacao['descricao']); ?>')" class="btn-delete" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                    <div class="pagination">
                        <?php if ($pagina > 1): ?>
                            <a href="?pagina=<?php echo $pagina - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'pagina'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <?php if ($i == $pagina): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?pagina=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'pagina'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagina < $total_paginas): ?>
                            <a href="?pagina=<?php echo $pagina + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'pagina'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                Próximo <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal de Confirmação de Exclusão -->
    <div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 12px; max-width: 400px; width: 90%;">
            <h2 style="color: #dc2626; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle"></i> Confirmar Exclusão
            </h2>
            <p style="color: #475569; margin-bottom: 20px;">
                Tem certeza que deseja excluir a transação: <br>
                <strong id="deleteDescricao" style="color: #1e293b;"></strong>?
            </p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="closeDelete()" style="padding: 10px 24px; background: #e2e8f0; border: none; border-radius: 8px; cursor: pointer; font-weight: 500;">
                    Cancelar
                </button>
                <a href="#" id="deleteLink" style="padding: 10px 24px; background: #dc2626; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 500; text-decoration: none;">
                    <i class="fas fa-trash"></i> Excluir
                </a>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(id, descricao) {
            document.getElementById('deleteDescricao').textContent = descricao;
            document.getElementById('deleteLink').href = 'delete_transaction.php?id=' + id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDelete() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Fecha modal ao clicar fora
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDelete();
            }
        });
    </script>
</body>
</html>