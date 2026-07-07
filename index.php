<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Verifica se usuário está logado
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? $_SESSION['user_name'] : 'Visitante';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finanças Pessoais - Controle Inteligente</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header/Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-left">
                <a href="index.php" class="logo">
                    <i class="fas fa-wallet"></i>
                    <span>FinControl</span>
                </a>
            </div>
            <div class="nav-right">
                <?php if ($is_logged_in): ?>
                    <span class="user-greeting">
                        <i class="fas fa-user-circle"></i>
                        Olá, <?php echo htmlspecialchars($user_name); ?>
                    </span>
                    <a href="pages/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-chart-pie"></i> Dashboard
                    </a>
                    <a href="logout.php" class="btn btn-outline">
                        <i class="fas fa-sign-out-alt"></i> Sair
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline">
                        <i class="fas fa-sign-in-alt"></i> Entrar
                    </a>
                    <a href="auth/register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Cadastrar
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Controle suas <span class="highlight">finanças</span> de forma simples e eficiente</h1>
                    <p class="subtitle">
                        Organize seus gastos, acompanhe suas receitas e alcance seus objetivos financeiros.
                        Tudo em um só lugar, com segurança e praticidade.
                    </p>
                    <div class="hero-buttons">
                        <?php if ($is_logged_in): ?>
                            <a href="pages/dashboard.php" class="btn btn-large btn-primary">
                                <i class="fas fa-chart-line"></i> Ir para meu Dashboard
                            </a>
                        <?php else: ?>
                            <a href="auth/register.php" class="btn btn-large btn-primary">
                                <i class="fas fa-rocket"></i> Começar Agora
                            </a>
                            <a href="#features" class="btn btn-large btn-outline">
                                <i class="fas fa-info-circle"></i> Saiba Mais
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php if (!$is_logged_in): ?>
                        <div class="trust-badge">
                            <i class="fas fa-check-circle"></i>
                            <span>Grátis • Seguro • Fácil de usar</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="hero-image">
                    <div class="dashboard-preview">
                        <i class="fas fa-chart-pie" style="font-size: 120px; color: #2563eb; opacity: 0.2;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2>Por que escolher o <span class="highlight">FinControl</span>?</h2>
                <p>Ferramentas poderosas para transformar sua relação com o dinheiro</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon" style="background: #e8f5e9; color: #2e7d32;">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3>Visão Completa</h3>
                    <p>Dashboard interativo com gráficos e resumos para entender para onde seu dinheiro está indo.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: #e3f2fd; color: #1565c0;">
                        <i class="fas fa-tags"></i>
                    </div>
                    <h3>Categorias Inteligentes</h3>
                    <p>Organize seus gastos por categorias personalizadas e acompanhe cada área da sua vida financeira.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: #fff3e0; color: #e65100;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h3>Relatórios Detalhados</h3>
                    <p>Gere relatórios por período e veja sua evolução financeira com dados claros e objetivos.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon" style="background: #f3e5f5; color: #6a1b9a;">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Acesso em Qualquer Lugar</h3>
                    <p>O sistema é responsivo e funciona em qualquer dispositivo com acesso à internet.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>Como funciona?</h2>
                <p>Três passos simples para organizar sua vida financeira</p>
            </div>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3>Crie sua conta</h3>
                    <p>Cadastre-se gratuitamente e tenha acesso ao sistema completo.</p>
                </div>
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <h3>Adicione transações</h3>
                    <p>Registre suas receitas e despesas de forma rápida e intuitiva.</p>
                </div>
                <div class="step-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>Analise e planeje</h3>
                    <p>Visualize relatórios e tome decisões financeiras mais inteligentes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Pronto para começar a <span class="highlight">controlar</span> suas finanças?</h2>
                <p>Junte-se a milhares de pessoas que já transformaram sua vida financeira.</p>
                <?php if ($is_logged_in): ?>
                    <a href="pages/dashboard.php" class="btn btn-large btn-primary">
                        <i class="fas fa-chart-line"></i> Acessar Dashboard
                    </a>
                <?php else: ?>
                    <a href="auth/register.php" class="btn btn-large btn-primary">
                        <i class="fas fa-rocket"></i> Criar conta gratuita
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-wallet"></i> FinControl</h3>
                    <p>Organize suas finanças pessoais de forma inteligente e eficiente.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-github"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Links Rápidos</h4>
                    <ul>
                        <?php if ($is_logged_in): ?>
                            <li><a href="pages/dashboard.php">Dashboard</a></li>
                            <li><a href="logout.php">Sair</a></li>
                        <?php else: ?>
                            <li><a href="auth/login.php">Login</a></li>
                            <li><a href="auth/register.php">Cadastro</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contato</h4>
                    <ul>
                        <li><i class="fas fa-envelope"></i> contato@fincontrol.com</li>
                        <li><i class="fas fa-phone"></i> (11) 9999-9999</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> FinControl. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>