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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/sidebar.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <style>
        .prestador-inativo {
            background-color: #ffebee !important;
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
        <div class="content-area">
            <div class="container-fluid">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header py-2">
                                <h5 class="card-title mb-0">Seleção de Colunas</h5>
                            </div>
                            <div class="card-body py-2">
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
                            <button id="execute-query" class="btn btn-primary btn-sm">
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
                        <div class="card">
                            <div class="card-header py-2">
                                <h5 class="card-title mb-0">Resultados</h5>
                            </div>
                            <div class="card-body p-0">
                                <div id="results-container" class="table-responsive">
                                    <table class="table table-striped table-bordered mb-0">
                                        <thead id="results-header"></thead>
                                        <tbody id="results-table"></tbody>
                                    </table>
                                </div>
                                <div id="pagination" class="d-flex justify-content-center py-2"></div>
                            </div>
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
