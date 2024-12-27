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
$sql_count = "SELECT COUNT(*) as total FROM cbo";
$sql = "SELECT CBO, DESCRICAO FROM cbo";

// Adiciona pesquisa se houver
if (!empty($pesquisa)) {
    $pesquisa = "%$pesquisa%";
    $sql_count .= " WHERE CBO LIKE ? OR DESCRICAO LIKE ?";
    $sql .= " WHERE CBO LIKE ? OR DESCRICAO LIKE ?";
}

// Adiciona ordenação e limite
$sql .= " ORDER BY CBO LIMIT ?, ?";

// Prepara e executa a consulta de contagem
$stmt_count = $pdo->prepare($sql_count);
if (!empty($pesquisa)) {
    $stmt_count->execute([$pesquisa, $pesquisa]);
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
}
$params[] = $inicio;
$params[] = $registros_por_pagina;
$stmt->execute($params);
$cbos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de CBOs - Consulta de Produção Ambulatorial</title>
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
                    <h3>Gerenciamento de CBOs</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCBO">
                        <i class="fas fa-plus"></i> Novo CBO
                    </button>
                </div>

                <!-- Barra de Pesquisa -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <div class="flex-grow-1 search-box">
                                <div class="input-group">
                                    <input type="text" name="pesquisa" class="form-control" placeholder="Pesquisar CBO ou Descrição" value="<?php echo htmlspecialchars($pesquisa ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($pesquisa)): ?>
                                <a href="profissoes.php" class="btn btn-outline-secondary">Limpar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tabela de CBOs -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>CBO</th>
                                        <th>Descrição</th>
                                        <th class="actions">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cbos as $cbo): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cbo['CBO']); ?></td>
                                        <td><?php echo htmlspecialchars($cbo['DESCRICAO']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarCBO('<?php echo htmlspecialchars($cbo['CBO']); ?>', '<?php echo htmlspecialchars($cbo['DESCRICAO']); ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao('<?php echo htmlspecialchars($cbo['CBO']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <?php if ($total_paginas > 1): ?>
                        <nav aria-label="Navegação de páginas" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=1<?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>" aria-label="Primeira">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina-1; ?><?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>" aria-label="Anterior">
                                        <span aria-hidden="true">&lsaquo;</span>
                                    </a>
                                </li>

                                <?php
                                // Sempre mostra página atual e uma antes (se existir)
                                if ($pagina > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?pagina='.($pagina-1).
                                         (!empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : '').
                                         '">'.($pagina-1).'</a></li>';
                                }
                                echo '<li class="page-item active"><a class="page-link">'.$pagina.'</a></li>';

                                // Reticências
                                if ($total_paginas > 4) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }

                                // Últimas duas páginas (se não forem as atuais)
                                if ($pagina < $total_paginas - 1) {
                                    echo '<li class="page-item"><a class="page-link" href="?pagina='.($total_paginas-1).
                                         (!empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : '').
                                         '">'.($total_paginas-1).'</a></li>';
                                }
                                if ($pagina < $total_paginas) {
                                    echo '<li class="page-item"><a class="page-link" href="?pagina='.$total_paginas.
                                         (!empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : '').
                                         '">'.$total_paginas.'</a></li>';
                                }
                                ?>

                                <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $pagina+1; ?><?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>" aria-label="Próxima">
                                        <span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?pagina=<?php echo $total_paginas; ?><?php echo !empty($pesquisa) ? '&pesquisa='.urlencode($pesquisa) : ''; ?>" aria-label="Última">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de CBO -->
    <div class="modal fade" id="modalCBO" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo CBO</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formCBO">
                        <input type="hidden" id="acao" name="acao" value="criar">
                        <div class="mb-3">
                            <label for="cbo" class="form-label">CBO</label>
                            <input type="text" class="form-control" id="cbo" name="cbo" maxlength="8" required>
                        </div>
                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <input type="text" class="form-control" id="descricao" name="descricao" maxlength="150" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarCBO()">Salvar</button>
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
                    <p>Tem certeza que deseja excluir este CBO?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="excluirCBO()">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebar.js"></script>
    <script>
        let cboParaExcluir = '';
        const modalCBO = new bootstrap.Modal(document.getElementById('modalCBO'));
        const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));

        function editarCBO(cbo, descricao) {
            document.getElementById('acao').value = 'editar';
            document.getElementById('cbo').value = cbo;
            document.getElementById('cbo').readOnly = true;
            document.getElementById('descricao').value = descricao;
            document.getElementById('modalTitle').textContent = 'Editar CBO';
            modalCBO.show();
        }

        function confirmarExclusao(cbo) {
            cboParaExcluir = cbo;
            modalConfirmacao.show();
        }

        function limparFormulario() {
            document.getElementById('formCBO').reset();
            document.getElementById('acao').value = 'criar';
            document.getElementById('cbo').readOnly = false;
            document.getElementById('modalTitle').textContent = 'Novo CBO';
        }

        function salvarCBO() {
            const form = document.getElementById('formCBO');
            const formData = new FormData(form);

            fetch('actions/cbo_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalCBO.hide();
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

        function excluirCBO() {
            const formData = new FormData();
            formData.append('acao', 'excluir');
            formData.append('cbo', cboParaExcluir);

            fetch('actions/cbo_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalConfirmacao.hide();
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

        // Limpar formulário quando o modal for fechado
        document.getElementById('modalCBO').addEventListener('hidden.bs.modal', limparFormulario);
    </script>
</body>
</html>
