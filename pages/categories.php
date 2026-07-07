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
$error = '';
$success = '';

// Processa ações
$action = $_GET['action'] ?? '';

// Adicionar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $nome = trim($_POST['nome'] ?? '');
    $tipo = $_POST['tipo'] ?? 'despesa';
    $cor = $_POST['cor'] ?? '#2563eb';
    $icone = $_POST['icone'] ?? 'fa-tag';

    if (empty($nome)) {
        $error = 'Digite um nome para a categoria!';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nome, tipo, cor, icone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $nome, $tipo, $cor, $icone]);
            $success = 'Categoria adicionada com sucesso!';
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Esta categoria já existe!';
            } else {
                $error = 'Erro ao adicionar: ' . $e->getMessage();
            }
        }
    }
}

// Editar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $nome = trim($_POST['nome'] ?? '');
    $tipo = $_POST['tipo'] ?? 'despesa';
    $cor = $_POST['cor'] ?? '#2563eb';
    $icone = $_POST['icone'] ?? 'fa-tag';

    if (empty($nome)) {
        $error = 'Digite um nome para a categoria!';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, tipo = ?, cor = ?, icone = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $tipo, $cor, $icone, $id, $usuario_id]);
            $success = 'Categoria atualizada com sucesso!';
        } catch(PDOException $e) {
            $error = 'Erro ao atualizar: ' . $e->getMessage();
        }
    }
}

// Excluir categoria
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Verifica se a categoria tem transações
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transacoes WHERE categoria_id = ? AND usuario_id = ?");
        $stmt->execute([$id, $usuario_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = 'Não é possível excluir esta categoria pois existem ' . $count . ' transações vinculadas!';
        } else {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuario_id]);
            $success = 'Categoria excluída com sucesso!';
        }
    } catch(PDOException $e) {
        $error = 'Erro ao excluir: ' . $e->getMessage();
    }
}

// Busca categorias
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY tipo, nome");
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll();

// Lista de ícones disponíveis
$icones_disponiveis = [
    'fa-wallet', 'fa-chart-line', 'fa-utensils', 'fa-car', 'fa-home',
    'fa-heartbeat', 'fa-graduation-cap', 'fa-gamepad', 'fa-shopping-bag',
    'fa-tag', 'fa-coffee', 'fa-gift', 'fa-music', 'fa-film',
    'fa-book', 'fa-briefcase', 'fa-calendar', 'fa-camera', 'fa-cart-plus'
];

// Lista de cores disponíveis
$cores_disponiveis = [
    '#22c55e', '#2563eb', '#8b5cf6', '#f59e0b', '#ef4444',
    '#ec4899', '#06b6d4', '#f97316', '#14b8a6', '#6366f1',
    '#84cc16', '#a855f7', '#e11d48', '#0ea5e9', '#d946ef'
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - FinControl</title>
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

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .form-container,
        .list-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-container h3,
        .list-container h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 4px;
            color: #334155;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2563eb;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .color-picker {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .color-option {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
        }

        .color-option:hover {
            transform: scale(1.1);
        }

        .color-option.active {
            border-color: #1e293b;
            transform: scale(1.1);
        }

        .icon-picker {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .icon-option {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 18px;
            background: white;
        }

        .icon-option:hover {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .icon-option.active {
            border-color: #2563eb;
            background: #dbeafe;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background: #1d4ed8;
        }

        .category-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.2s;
        }

        .category-item:hover {
            background: #f8fafc;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .category-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .category-name {
            font-weight: 500;
            color: #1e293b;
        }

        .category-type {
            font-size: 12px;
            color: #94a3b8;
            padding: 2px 10px;
            border-radius: 12px;
            background: #f1f5f9;
        }

        .category-type.receita {
            background: #d1fae5;
            color: #065f46;
        }

        .category-type.despesa {
            background: #fee2e2;
            color: #991b1b;
        }

        .category-actions {
            display: flex;
            gap: 8px;
        }

        .category-actions a {
            padding: 4px 10px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
        }

        .btn-edit-cat {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .btn-edit-cat:hover {
            background: #bfdbfe;
        }

        .btn-delete-cat {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete-cat:hover {
            background: #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 40px;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        /* Modal de Edição */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #94a3b8;
        }

        .modal-close:hover {
            color: #475569;
        }

        @media (max-width: 992px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            .category-item {
                flex-wrap: wrap;
                gap: 10px;
            }
            .category-actions {
                width: 100%;
                justify-content: flex-end;
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
            <div class="page-header">
                <h1>
                    <i class="fas fa-tags" style="color: #8b5cf6;"></i> Categorias
                    <small>Gerencie suas categorias de receitas e despesas</small>
                </h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="content-grid">
                <!-- Formulário -->
                <div class="form-container">
                    <h3><i class="fas fa-plus-circle" style="color: #22c55e;"></i> Nova Categoria</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="nome">Nome da Categoria</label>
                            <input type="text" id="nome" name="nome" placeholder="Ex: Mercado, Academia..." required>
                        </div>

                        <div class="form-group">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo">
                                <option value="despesa">📉 Despesa</option>
                                <option value="receita">📈 Receita</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Cor</label>
                            <div class="color-picker" id="colorPicker">
                                <?php foreach ($cores_disponiveis as $cor): ?>
                                    <div class="color-option" style="background: <?php echo $cor; ?>" data-color="<?php echo $cor; ?>" onclick="selectColor(this, '<?php echo $cor; ?>')"></div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="cor" id="selectedColor" value="#2563eb">
                        </div>

                        <div class="form-group">
                            <label>Ícone</label>
                            <div class="icon-picker" id="iconPicker">
                                <?php foreach ($icones_disponiveis as $icone): ?>
                                    <div class="icon-option" data-icon="<?php echo $icone; ?>" onclick="selectIcon(this, '<?php echo $icone; ?>')">
                                        <i class="fas <?php echo $icone; ?>"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="icone" id="selectedIcon" value="fa-tag">
                        </div>

                        <button type="submit" name="add_category" class="btn-submit">
                            <i class="fas fa-save"></i> Adicionar Categoria
                        </button>
                    </form>
                </div>

                <!-- Lista -->
                <div class="list-container">
                    <h3><i class="fas fa-list"></i> Minhas Categorias</h3>
                    <?php if (empty($categorias)): ?>
                        <div class="empty-state">
                            <i class="fas fa-tags"></i>
                            <p>Nenhuma categoria criada ainda.</p>
                            <p style="font-size: 14px;">Adicione sua primeira categoria ao lado!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categorias as $cat): ?>
                            <div class="category-item">
                                <div class="category-info">
                                    <div class="category-color" style="background: <?php echo $cat['cor']; ?>"></div>
                                    <span class="category-name">
                                        <i class="fas <?php echo $cat['icone']; ?>" style="color: <?php echo $cat['cor']; ?>;"></i>
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </span>
                                    <span class="category-type <?php echo $cat['tipo']; ?>">
                                        <?php echo $cat['tipo'] === 'receita' ? '📈 Receita' : '📉 Despesa'; ?>
                                    </span>
                                </div>
                                <div class="category-actions">
                                    <a href="#" class="btn-edit-cat" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['nome']); ?>', '<?php echo $cat['tipo']; ?>', '<?php echo $cat['cor']; ?>', '<?php echo $cat['icone']; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $cat['id']; ?>" class="btn-delete-cat" onclick="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal de Edição -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Editar Categoria</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Nome da Categoria</label>
                    <input type="text" name="nome" id="editNome" required>
                </div>
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="tipo" id="editTipo">
                        <option value="despesa">📉 Despesa</option>
                        <option value="receita">📈 Receita</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Cor</label>
                    <input type="color" name="cor" id="editCor" value="#2563eb">
                </div>
                <div class="form-group">
                    <label>Ícone</label>
                    <select name="icone" id="editIcone">
                        <?php foreach ($icones_disponiveis as $icone): ?>
                            <option value="<?php echo $icone; ?>">
                                <i class="fas <?php echo $icone; ?>"></i> <?php echo $icone; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="edit_category" class="btn-submit">
                    <i class="fas fa-save"></i> Atualizar Categoria
                </button>
            </form>
        </div>
    </div>

    <script>
        // Selecionar cor
        function selectColor(element, color) {
            document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('selectedColor').value = color;
        }

        // Selecionar ícone
        function selectIcon(element, icon) {
            document.querySelectorAll('.icon-option').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            document.getElementById('selectedIcon').value = icon;
        }

        // Selecionar cor padrão
        document.addEventListener('DOMContentLoaded', function() {
            const firstColor = document.querySelector('.color-option');
            if (firstColor) {
                firstColor.classList.add('active');
                document.getElementById('selectedColor').value = firstColor.dataset.color;
            }

            const firstIcon = document.querySelector('.icon-option');
            if (firstIcon) {
                firstIcon.classList.add('active');
                document.getElementById('selectedIcon').value = firstIcon.dataset.icon;
            }
        });

        // Editar categoria
        function editCategory(id, nome, tipo, cor, icone) {
            document.getElementById('editId').value = id;
            document.getElementById('editNome').value = nome;
            document.getElementById('editTipo').value = tipo;
            document.getElementById('editCor').value = cor;
            document.getElementById('editIcone').value = icone;
            document.getElementById('editModal').style.display = 'flex';
        }

        // Fechar modal
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fecha modal ao clicar fora
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>