<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <a class="nav-link" href="index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-home"></i></div>
                Início
            </a>
            <a class="nav-link" href="producao.php">
                <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                Produção
            </a>
            <a class="nav-link" href="prestadores.php">
                <div class="sb-nav-link-icon"><i class="fas fa-user-md"></i></div>
                Prestadores
            </a>
            <a class="nav-link" href="procedimentos.php">
                <div class="sb-nav-link-icon"><i class="fas fa-procedures"></i></div>
                Procedimentos
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Logado como:</div>
        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>
    </div>
</nav>
