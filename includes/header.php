<?php
session_start();

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
    <title>Sistema de Produção</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .header-fixed {
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            padding: 10px 20px;
            height: 70px;
            transition: left 0.3s;
        }

        body.sb-sidenav-toggled .header-fixed {
            left: 0;
        }

        .content-wrapper {
            margin-left: 250px;
            padding-top: 70px;
            transition: margin-left 0.3s;
        }

        body.sb-sidenav-toggled .content-wrapper {
            margin-left: 0;
        }

        .content {
            padding: 20px;
        }

        @media (max-width: 768px) {
            .header-fixed {
                left: 0;
            }
            .content-wrapper {
                margin-left: 0;
            }
        }

        /* Ajustes para cards e filtros */
        .card {
            margin-bottom: 1rem;
        }

        #filters-container .filter-row {
            margin-bottom: 0.5rem;
        }

        .table-responsive {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header fixo -->
    <header class="header-fixed">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Secretaria de Saúde</h1>
                <h2 class="h6 text-muted mb-0">Diretoria de Planejamento</h2>
            </div>
            <button class="btn btn-link" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
