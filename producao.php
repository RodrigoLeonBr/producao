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
    <title>Consulta de Produção</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/css/sidebar.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <style>
        .prestador-inativo {
            background-color: #ffebee !important;
        }
        
        .loading-spinner {
            animation: spin 1s linear infinite;
            will-change: transform;
            transform: translateZ(0);
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        /* Estilos do paginador */
        .pagination {
            margin-bottom: 0;
        }
        
        .pagination .page-link {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            min-width: 32px;
            text-align: center;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }
    </style>
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
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header py-2">
                            <h5 class="card-title mb-0">Seleção de Colunas</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <button class="btn btn-sm btn-outline-primary me-2" onclick="toggleAllColumns(true)">
                                    <i class="fas fa-check-square me-1"></i>Selecionar Todos
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleAllColumns(false)">
                                    <i class="fas fa-square me-1"></i>Desmarcar Todos
                                </button>
                            </div>
                            <div id="column-selection"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header py-2">
                            <h5 class="card-title mb-0">Filtros</h5>
                        </div>
                        <div class="card-body py-2">
                            <button id="add-filter-btn" class="btn btn-primary btn-sm mb-2">
                                <i class="fas fa-plus me-1"></i>Adicionar Filtro
                            </button>
                            <div id="filters-container"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button id="executeQuery" class="btn btn-primary btn-sm" onclick="executeQuery()">
                            <i class="fas fa-play me-1"></i>Executar
                        </button>
                        <button id="btnExportCSV" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                        <button id="btnExportXLS" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-file-excel me-1"></i>XLS
                        </button>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header py-1">
                            <h5 class="card-title mb-0">SQL Gerado</h5>
                        </div>
                        <div class="card-body py-1">
                            <pre id="sql-preview" class="mb-0" style="max-height: 60px; overflow-y: auto; font-size: 0.8rem;"></pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <!-- Resultados -->
                    <div class="card mt-3">
                        <div class="card-header py-2">
                            <h5 class="card-title mb-0">Resultados</h5>
                        </div>
                        <div class="card-body">
                            <div id="results-container" class="table-responsive">
                                <table id="resultsTable" class="table table-striped table-hover table-sm">
                                </table>
                            </div>
                            <div id="pagination" class="d-flex justify-content-center mt-3"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
    <script src="./assets/js/sidebar.js"></script>
    <script src="./assets/js/filters.js"></script>
    <script src="./assets/js/column-selection.js"></script>
    <script src="./assets/js/query.js"></script>
    <script src="./assets/js/main.js"></script>
</body>
</html>
