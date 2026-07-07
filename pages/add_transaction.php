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

// Busca categorias do usuário
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY tipo, nome");
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll();

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria_id = $_POST['categoria_id'] ?? '';
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = str_replace(['R$', '.', ' '], '', $_POST['valor'] ?? '');
    $valor = str_replace(',', '.', $valor);
    $tipo = $_POST['tipo'] ?? 'despesa';
    $data_transacao = $_POST['data_transacao'] ?? date('Y-m-d');
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'dinheiro';
    $status = $_POST['status'] ?? 'pago';
    $observacao = trim($_POST['observacao'] ?? '');

    // Validações
    if (empty($categoria_id)) {
        $error = 'Selecione uma categoria!';
    } elseif (empty($descricao)) {
        $error = 'Digite uma descrição!';
    } elseif (empty($valor) || $valor <= 0) {
        $error = 'Digite um valor válido!';
    } elseif (empty($data_transacao)) {
        $error = 'Selecione uma data!';
    } else {
        try {
            // Verifica se a categoria pertence ao usuário
            $stmt = $pdo->prepare("SELECT id, tipo FROM categorias WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$categoria_id, $usuario_id]);
            $categoria = $stmt->fetch();

            if (!$categoria) {
                $error = 'Categoria inválida!';
            } else {
                // Insere a transação
                $stmt = $pdo->prepare("
                    INSERT INTO transacoes (
                        usuario_id, categoria_id, descricao, valor, tipo, 
                        data_transacao, forma_pagamento, status, observacao
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $usuario_id,
                    $categoria_id,
                    $descricao,
                    $valor,
                    $tipo,
                    $data_transacao,
                    $forma_pagamento,
                    $status,
                    $observacao
                ]);

                $success = 'Transação cadastrada com sucesso!';
                
                // Limpa o formulário
                $_POST = [];
            }
        } catch(PDOException $e) {
            $error = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}

// Busca categorias separadas por tipo
$categorias_receita = array_filter($categorias, function($cat) {
    return $cat['tipo'] === 'receita';
});
$categorias_despesa = array_filter($categorias, function($cat) {
    return $cat['tipo'] === 'despesa';
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Transação - FinControl</title>
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

        .form-container {
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #334155;
            font-size: 14px;
        }

        .form-group label .required {
            color: #ef4444;
            margin-left: 3px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group .input-icon {
            position: relative;
        }

        .form-group .input-icon i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .form-group .input-icon input,
        .form-group .input-icon select {
            padding-left: 42px;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            margin-right: 8px;
        }

        .btn-cancel {
            display: inline-block;
            padding: 10px 24px;
            background: #e2e8f0;
            color: #475569;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
            margin-right: 10px;
        }

        .btn-cancel:hover {
            background: #cbd5e1;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .alert i {
            font-size: 18px;
        }

        .tipo-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 5px;
        }

        .tipo-btn {
            flex: 1;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            text-align: center;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 14px;
        }

        .tipo-btn:hover {
            border-color: #94a3b8;
        }

        .tipo-btn.active-receita {
            border-color: #22c55e;
            background: #f0fdf4;
            color: #16a34a;
        }

        .tipo-btn.active-despesa {
            border-color: #ef4444;
            background: #fef2f2;
            color: #dc2626;
        }

        .tipo-btn i {
            margin-right: 6px;
        }

        .valor-input {
            font-size: 20px !important;
            font-weight: 600 !important;
        }

        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .form-container {
                padding: 20px;
            }
            .page-header h1 {
                font-size: 22px;
            }
            .tipo-selector {
                flex-direction: column;
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
                    <i class="fas fa-plus-circle" style="color: #2563eb;"></i> Nova Transação
                    <small>Registre uma nova receita ou despesa</small>
                </h1>
                <a href="transactions.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            <div class="form-container">
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

                <form method="POST" id="formTransacao">
                    <!-- Tipo (Receita/Despesa) -->
                    <div class="form-group">
                        <label>Tipo de Transação <span class="required">*</span></label>
                        <div class="tipo-selector">
                            <button type="button" class="tipo-btn active-despesa" id="btnDespesa" onclick="setTipo('despesa')">
                                <i class="fas fa-arrow-down"></i> Despesa
                            </button>
                            <button type="button" class="tipo-btn" id="btnReceita" onclick="setTipo('receita')">
                                <i class="fas fa-arrow-up"></i> Receita
                            </button>
                        </div>
                        <input type="hidden" name="tipo" id="tipoInput" value="despesa">
                    </div>

                    <!-- Categoria -->
                    <div class="form-group">
                        <label for="categoria_id">Categoria <span class="required">*</span></label>
                        <select name="categoria_id" id="categoria_id" required>
                            <option value="">Selecione uma categoria...</option>
                            <optgroup label="📈 Receitas">
                                <?php foreach ($categorias_receita as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-tipo="receita">
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="📉 Despesas">
                                <?php foreach ($categorias_despesa as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-tipo="despesa" selected>
                                        <?php echo htmlspecialchars($cat['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Descrição -->
                    <div class="form-group">
                        <label for="descricao">Descrição <span class="required">*</span></label>
                        <div class="input-icon">
                            <i class="fas fa-pencil-alt"></i>
                            <input type="text" id="descricao" name="descricao" 
                                   value="<?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?>"
                                   placeholder="Ex: Supermercado, Salário, Aluguel..." required>
                        </div>
                    </div>

                    <!-- Valor e Data -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="valor">Valor <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-dollar-sign"></i>
                                <input type="text" id="valor" name="valor" 
                                       value="<?php echo isset($_POST['valor']) ? $_POST['valor'] : ''; ?>"
                                       placeholder="R$ 0,00" class="valor-input" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="data_transacao">Data <span class="required">*</span></label>
                            <div class="input-icon">
                                <i class="fas fa-calendar-alt"></i>
                                <input type="date" id="data_transacao" name="data_transacao" 
                                       value="<?php echo htmlspecialchars($_POST['data_transacao'] ?? date('Y-m-d')); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Forma de Pagamento e Status -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="forma_pagamento">Forma de Pagamento</label>
                            <select name="forma_pagamento" id="forma_pagamento">
                                <option value="dinheiro">💰 Dinheiro</option>
                                <option value="cartao_credito">💳 Cartão de Crédito</option>
                                <option value="cartao_debito">💳 Cartão de Débito</option>
                                <option value="pix">📱 PIX</option>
                                <option value="boleto">📄 Boleto</option>
                                <option value="transferencia">🏦 Transferência</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="pago">✅ Pago</option>
                                <option value="pendente">⏳ Pendente</option>
                                <option value="cancelado">❌ Cancelado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Observação -->
                    <div class="form-group">
                        <label for="observacao">Observação</label>
                        <textarea id="observacao" name="observacao" 
                                  placeholder="Observações adicionais (opcional)"><?php echo htmlspecialchars($_POST['observacao'] ?? ''); ?></textarea>
                    </div>

                    <!-- Botões -->
                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-save"></i> Salvar Transação
                        </button>
                        <a href="transactions.php" class="btn-cancel">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <script>
        // Máscara de valor
        document.getElementById('valor').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = (parseFloat(value) / 100).toFixed(2);
                value = value.replace('.', ',');
                value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                this.value = 'R$ ' + value;
            } else {
                this.value = '';
            }
        });

        // Selecionar tipo
        function setTipo(tipo) {
            document.getElementById('tipoInput').value = tipo;
            
            const btnDespesa = document.getElementById('btnDespesa');
            const btnReceita = document.getElementById('btnReceita');
            
            btnDespesa.className = 'tipo-btn';
            btnReceita.className = 'tipo-btn';
            
            if (tipo === 'despesa') {
                btnDespesa.classList.add('active-despesa');
                document.querySelector('optgroup[label="📈 Receitas"]').style.display = 'none';
                document.querySelector('optgroup[label="📉 Despesas"]').style.display = '';
                document.getElementById('categoria_id').value = '';
            } else {
                btnReceita.classList.add('active-receita');
                document.querySelector('optgroup[label="📈 Receitas"]').style.display = '';
                document.querySelector('optgroup[label="📉 Despesas"]').style.display = 'none';
                document.getElementById('categoria_id').value = '';
            }
            
            // Seleciona a primeira categoria do tipo
            const select = document.getElementById('categoria_id');
            for (let option of select.options) {
                if (option.dataset.tipo === tipo && option.value) {
                    select.value = option.value;
                    break;
                }
            }
        }

        // Inicializar com despesa
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar apenas despesas inicialmente
            document.querySelector('optgroup[label="📈 Receitas"]').style.display = 'none';
            document.querySelector('optgroup[label="📉 Despesas"]').style.display = '';
            
            // Seleciona primeira despesa
            const select = document.getElementById('categoria_id');
            for (let option of select.options) {
                if (option.dataset.tipo === 'despesa' && option.value) {
                    select.value = option.value;
                    break;
                }
            }
        });

        // Validação antes de enviar
        document.getElementById('formTransacao').addEventListener('submit', function(e) {
            const valor = document.getElementById('valor').value;
            if (!valor || valor === 'R$ 0,00' || valor === '') {
                e.preventDefault();
                alert('Por favor, digite um valor válido!');
                return false;
            }
        });
    </script>
</body>
</html>