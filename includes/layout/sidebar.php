<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Obtém o nome do arquivo atual
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-container">
            <img src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/logo-pma.png" alt="Logo PMA" class="logo-img">
        </div>
        <div class="menu-icon">
            <i class="fas fa-bars"></i>
        </div>
    </div>
    <nav class="nav flex-column">
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> <span class="nav-text">Dashboard</span>
        </a>
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/procedimentos.php" class="nav-link <?php echo $current_page == 'procedimentos.php' ? 'active' : ''; ?>">
            <i class="fas fa-procedures"></i> <span class="nav-text">Procedimentos</span>
        </a>
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/prestadores.php" class="nav-link <?php echo $current_page == 'prestadores.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-md"></i> <span class="nav-text">Prestadores</span>
        </a>
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/profissoes.php" class="nav-link <?php echo $current_page == 'profissoes.php' ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i> <span class="nav-text">Profissões</span>
        </a>
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/producao.php" class="nav-link <?php echo $current_page == 'producao.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> <span class="nav-text">Consulta de Produção</span>
        </a>
    </nav>
    <div class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-chevron-left"></i>
    </div>
    <div class="user-info">
        <small>Logado como:</small><br>
        <span class="username"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></span>
        <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/auth/logout.php" class="btn btn-sm btn-light mt-2 w-100">
            <i class="fas fa-sign-out-alt"></i> <span class="nav-text">Sair</span>
        </a>
    </div>
</div>
