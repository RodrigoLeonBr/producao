<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Obtém a conexão com o banco de dados
$database = new Database();
$pdo = $database->getConnection();

// Configurações da paginação
$registros_por_pagina = 30;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $registros_por_pagina;

// Termo de pesquisa
$pesquisa = isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '';

// Consulta SQL base
$sql_count = "SELECT COUNT(*) as total FROM procedimento";
$sql = "SELECT codigo, procedimento, PA_TOTAL, RUB_TOTAL, RUB_DC, PA_RUB, FINANCIAMENTO FROM procedimento";

// Adiciona pesquisa se houver
if (!empty($pesquisa)) {
    $pesquisa = "%$pesquisa%";
    $sql_count .= " WHERE codigo LIKE ? OR procedimento LIKE ? OR RUB_DC LIKE ? OR FINANCIAMENTO LIKE ?";
    $sql .= " WHERE codigo LIKE ? OR procedimento LIKE ? OR RUB_DC LIKE ? OR FINANCIAMENTO LIKE ?";
}

// Adiciona ordenação e limite
$sql .= " ORDER BY codigo LIMIT ?, ?";

// Prepara e executa a consulta de contagem
$stmt_count = $pdo->prepare($sql_count);
if (!empty($pesquisa)) {
    $stmt_count->execute([$pesquisa, $pesquisa, $pesquisa, $pesquisa]);
} else {
    $stmt_count->execute();
}
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Prepara e executa a consulta principal
$stmt = $pdo->prepare($sql);
$params = [];
if (!empty($pesquisa)) {
    $params[] = $pesquisa;
    $params[] = $pesquisa;
    $params[] = $pesquisa;
    $params[] = $pesquisa;
}
$params[] = $inicio;
$params[] = $registros_por_pagina;
$stmt->execute($params);
$procedimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Procedimentos - Consulta de Produção Ambulatorial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="./assets/css/sidebar.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <style>
        .table th {
            background-color: #f8f9fa;
        }
        .actions {
            width: 120px;
        }
        .search-box {
            max-width: 300px;
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
                <!-- Título e Botões -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Gerenciamento de Procedimentos</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalProcedimento">
                        <i class="fas fa-plus me-2"></i>Novo Procedimento
                    </button>
                </div>

                <!-- Barra de Pesquisa -->
                <div class="mb-4">
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="pesquisa" class="form-control search-box" 
                               placeholder="Pesquisar por código, descrição..." 
                               value="<?php echo htmlspecialchars($pesquisa); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($pesquisa)): ?>
                            <a href="?" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabela -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Código Procedimento</th>
                                        <th>Descrição Procedimento</th>
                                        <th>Valor</th>
                                        <th>Código Rúbrica</th>
                                        <th>Descrição da Rúbrica</th>
                                        <th>Código Financiamento</th>
                                        <th>Descrição Financiamento</th>
                                        <th class="actions">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($procedimentos as $proc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($proc['codigo'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($proc['procedimento'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($proc['PA_TOTAL'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($proc['RUB_TOTAL'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($proc['RUB_DC'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($proc['PA_RUB'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($proc['FINANCIAMENTO'] ?? ''); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarProcedimento(<?php echo htmlspecialchars(json_encode($proc)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao('<?php echo htmlspecialchars($proc['codigo']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <nav aria-label="Navegação de páginas" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=1<?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>" aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <?php
                                $inicio_paginacao = max(1, $pagina - 2);
                                $fim_paginacao = min($total_paginas, $pagina + 2);
                                
                                for ($i = $inicio_paginacao; $i <= $fim_paginacao; $i++):
                                ?>
                                <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $i; ?><?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>" aria-label="Última">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Procedimento -->
    <div class="modal fade" id="modalProcedimento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Procedimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formProcedimento">
                        <input type="hidden" id="acao" name="acao" value="criar">
                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código Procedimento</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required>
                        </div>
                        <div class="mb-3">
                            <label for="procedimento" class="form-label">Descrição Procedimento</label>
                            <input type="text" class="form-control" id="procedimento" name="procedimento" required>
                        </div>
                        <div class="mb-3">
                            <label for="pa_total" class="form-label">Valor</label>
                            <input type="number" step="0.01" class="form-control" id="pa_total" name="pa_total" required>
                        </div>
                        <div class="mb-3">
                            <label for="rub_total" class="form-label">Código Rúbrica</label>
                            <input type="text" class="form-control" id="rub_total" name="rub_total" required>
                        </div>
                        <div class="mb-3">
                            <label for="rub_dc" class="form-label">Descrição da Rúbrica</label>
                            <input type="text" class="form-control" id="rub_dc" name="rub_dc" required>
                        </div>
                        <div class="mb-3">
                            <label for="pa_rub" class="form-label">Código Financiamento</label>
                            <input type="text" class="form-control" id="pa_rub" name="pa_rub" required>
                        </div>
                        <div class="mb-3">
                            <label for="financiamento" class="form-label">Descrição Financiamento</label>
                            <input type="text" class="form-control" id="financiamento" name="financiamento" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarProcedimento()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="modalConfirmacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir este procedimento?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="excluirProcedimento()">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let procedimentoParaExcluir = '';
        const modalProcedimento = new bootstrap.Modal(document.getElementById('modalProcedimento'));
        const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));

        function editarProcedimento(procedimento) {
            document.getElementById('acao').value = 'editar';
            document.getElementById('codigo').value = procedimento.codigo || '';
            document.getElementById('codigo').readOnly = true;
            document.getElementById('procedimento').value = procedimento.procedimento || '';
            document.getElementById('pa_total').value = procedimento.PA_TOTAL || '';
            document.getElementById('rub_total').value = procedimento.RUB_TOTAL || '';
            document.getElementById('rub_dc').value = procedimento.RUB_DC || '';
            document.getElementById('pa_rub').value = procedimento.PA_RUB || '';
            document.getElementById('financiamento').value = procedimento.FINANCIAMENTO || '';
            document.getElementById('modalTitle').textContent = 'Editar Procedimento';
            modalProcedimento.show();
        }

        function novoProcedimento() {
            document.getElementById('formProcedimento').reset();
            document.getElementById('acao').value = 'criar';
            document.getElementById('codigo').readOnly = false;
            document.getElementById('modalTitle').textContent = 'Novo Procedimento';
            modalProcedimento.show();
        }

        function confirmarExclusao(codigo) {
            procedimentoParaExcluir = codigo;
            modalConfirmacao.show();
        }

        function salvarProcedimento() {
            const formData = new FormData(document.getElementById('formProcedimento'));
            
            fetch('actions/procedimento_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar a requisição');
            });
        }

        function excluirProcedimento() {
            const formData = new FormData();
            formData.append('acao', 'excluir');
            formData.append('codigo', procedimentoParaExcluir);
            
            fetch('actions/procedimento_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao processar a requisição');
            });
        }
    </script>
</body>
</html>
