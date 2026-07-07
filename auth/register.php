<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redireciona se já estiver logado
if (isLoggedIn()) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (empty($nome) || empty($email) || empty($senha)) {
        $error = 'Todos os campos são obrigatórios!';
    } elseif (strlen($nome) < 3) {
        $error = 'O nome deve ter pelo menos 3 caracteres!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido!';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres!';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este e-mail já está cadastrado!';
            } else {
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash]);
                $usuario_id = $pdo->lastInsertId();
                
                $categorias_padrao = [
                    ['Salário', 'receita', '#22c55e', 'fa-wallet'],
                    ['Investimentos', 'receita', '#3b82f6', 'fa-chart-line'],
                    ['Alimentação', 'despesa', '#f59e0b', 'fa-utensils'],
                    ['Transporte', 'despesa', '#8b5cf6', 'fa-car'],
                    ['Moradia', 'despesa', '#ef4444', 'fa-home'],
                    ['Saúde', 'despesa', '#ec4899', 'fa-heartbeat'],
                    ['Educação', 'despesa', '#06b6d4', 'fa-graduation-cap'],
                    ['Lazer', 'despesa', '#f97316', 'fa-gamepad'],
                    ['Compras', 'despesa', '#14b8a6', 'fa-shopping-bag']
                ];
                
                $stmt = $pdo->prepare("INSERT INTO categorias (usuario_id, nome, tipo, cor, icone) VALUES (?, ?, ?, ?, ?)");
                foreach ($categorias_padrao as $cat) {
                    $stmt->execute([$usuario_id, $cat[0], $cat[1], $cat[2], $cat[3]]);
                }
                
                $success = 'Cadastro realizado com sucesso! Faça login para continuar.';
                $_POST = [];
            }
        } catch(PDOException $e) {
            $error = 'Erro ao cadastrar: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - FinControl</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 20px; }
        .auth-container { background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.1); padding: 40px; max-width: 450px; width: 100%; }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header h1 { font-size: 28px; font-weight: 700; color: #1e293b; }
        .auth-header p { color: #64748b; margin-top: 8px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 500; margin-bottom: 6px; color: #334155; }
        .form-group input { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; transition: border-color 0.3s; }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        .form-group .input-icon { position: relative; }
        .form-group .input-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .form-group .input-icon input { padding-left: 42px; }
        .btn-auth { width: 100%; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        .btn-auth:hover { background: #1d4ed8; }
        .auth-footer { text-align: center; margin-top: 20px; color: #64748b; }
        .auth-footer a { color: #2563eb; text-decoration: none; font-weight: 500; }
        .auth-footer a:hover { text-decoration: underline; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .password-strength { height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 8px; overflow: hidden; }
        .password-strength-bar { height: 100%; width: 0%; transition: width 0.3s, background 0.3s; border-radius: 2px; }
        .password-hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }
        .back-home { display: inline-block; margin-top: 10px; color: #64748b; text-decoration: none; }
        .back-home:hover { color: #2563eb; }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-header">
                <i class="fas fa-wallet" style="font-size: 40px; color: #2563eb;"></i>
                <h1>Criar Conta</h1>
                <p>Comece a controlar suas finanças hoje</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label for="nome">Nome Completo</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="nome" name="nome" 
                               value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>"
                               placeholder="Seu nome completo" required minlength="3">
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               placeholder="seu@email.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="senha" name="senha" 
                               placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-hint" id="passwordHint">Digite uma senha forte</div>
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha</label>
                    <div class="input-icon">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" 
                               placeholder="Digite a senha novamente" required>
                    </div>
                    <div id="passwordMatch" style="font-size: 14px; margin-top: 4px;"></div>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fas fa-user-plus"></i> Criar Conta
                </button>
            </form>

            <div class="auth-footer">
                Já tem uma conta? <a href="login.php">Faça login</a>
                <br>
                <a href="../index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i> Voltar para o início
                </a>
            </div>
        </div>
    </div>

    <script>
        const senha = document.getElementById('senha');
        const confirmarSenha = document.getElementById('confirmar_senha');
        const strengthBar = document.getElementById('strengthBar');
        const passwordHint = document.getElementById('passwordHint');
        const passwordMatch = document.getElementById('passwordMatch');

        senha.addEventListener('input', function() {
            const value = this.value;
            let strength = 0;
            
            if (value.length >= 6) strength++;
            if (value.match(/[a-z]+/)) strength++;
            if (value.match(/[A-Z]+/)) strength++;
            if (value.match(/[0-9]+/)) strength++;
            if (value.match(/[$@#&!]+/)) strength++;
            
            const percentage = (strength / 5) * 100;
            strengthBar.style.width = percentage + '%';
            
            const colors = ['#ef4444', '#f59e0b', '#f59e0b', '#22c55e', '#22c55e'];
            const hints = ['Senha muito fraca', 'Senha fraca', 'Senha média', 'Senha forte', 'Senha muito forte'];
            
            strengthBar.style.background = colors[Math.min(strength, colors.length - 1)];
            passwordHint.textContent = hints[Math.min(strength, hints.length - 1)];
            
            checkPasswordMatch();
        });

        confirmarSenha.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            if (confirmarSenha.value.length > 0) {
                if (senha.value === confirmarSenha.value) {
                    passwordMatch.innerHTML = '<span style="color: #22c55e;"><i class="fas fa-check"></i> Senhas coincidem</span>';
                } else {
                    passwordMatch.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times"></i> Senhas não coincidem</span>';
                }
            } else {
                passwordMatch.innerHTML = '';
            }
        }
    </script>
</body>
</html>