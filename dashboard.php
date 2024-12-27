<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Consulta de Produção Ambulatorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/sidebar.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include './includes/layout/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header-fixed">
            <div>
                <h1 class="h3 mb-0">Secretaria de Saúde</h1>
                <h2 class="h6 text-muted mb-0">Diretoria de Planejamento</h2>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card">
                            <h5>Total de Procedimentos</h5>
                            <h2>1,234</h2>
                            <small class="text-success">
                                <i class="fas fa-arrow-up"></i> 5.3% desde o último mês
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card">
                            <h5>Prestadores Ativos</h5>
                            <h2>89</h2>
                            <small class="text-primary">
                                <i class="fas fa-user-plus"></i> 3 novos este mês
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card">
                            <h5>Profissões Cadastradas</h5>
                            <h2>42</h2>
                            <small class="text-info">
                                <i class="fas fa-info-circle"></i> Atualizado hoje
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card">
                            <h5>Produção Mensal</h5>
                            <h2>5,678</h2>
                            <small class="text-warning">
                                <i class="fas fa-clock"></i> Último mês
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebar.js"></script>
</body>
</html>
