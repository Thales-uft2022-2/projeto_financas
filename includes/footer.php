    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-wallet"></i> FinControl</h3>
                    <p>Organize suas finanças pessoais de forma inteligente e eficiente.</p>
                </div>
                <div class="footer-section">
                    <h4>Links Rápidos</h4>
                    <ul>
                        <li><a href="index.php">Início</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="pages/dashboard.php">Dashboard</a></li>
                            <li><a href="pages/transactions.php">Transações</a></li>
                            <li><a href="pages/reports.php">Relatórios</a></li>
                            <li><a href="logout.php">Sair</a></li>
                        <?php else: ?>
                            <li><a href="pages/login.php">Login</a></li>
                            <li><a href="pages/register.php">Cadastro</a></li>
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
    <script src="../assets/js/script.js"></script>
</body>
</html>