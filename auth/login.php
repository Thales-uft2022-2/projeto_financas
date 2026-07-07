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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $error = 'Preencha todos os campos!';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_name'] = $usuario['nome'];
                $_SESSION['user_email'] = $usuario['email'];
                
                header('Location: ../pages/dashboard.php');
                exit;
            } else {
                $error = 'E-mail ou senha inválidos!';
            }
        } catch(PDOException $e) {
            $error = 'Erro ao fazer login: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FinControl</title>
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
        .remember-me { display: flex; align-items: center; gap: 10px; margin: 15px 0; }
        .remember-me input { width: auto; }
        .back-home { display: inline-block; margin-top: 10px; color: #64748b; text-decoration: none; }
        .back-home:hover { color: #2563eb; }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-header">
                <i class="fas fa-wallet" style="font-size: 40px; color: #2563eb;"></i>
                <h1>Bem-vindo de volta!</h1>
                <p>Faça login para acessar suas finanças</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
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
                               placeholder="Digite sua senha" required>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="lembrar" name="lembrar">
                    <label for="lembrar">Lembrar-me</label>
                </div>

                <button type="submit" class="btn-auth">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>

            <div class="auth-footer">
                Não tem uma conta? <a href="register.php">Cadastre-se</a>
                <br>
                <a href="../index.php" class="back-home">
                    <i class="fas fa-arrow-left"></i> Voltar para o início
                </a>
            </div>
        </div>
    </div>
</body>
</html>