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
$sql_count = "SELECT COUNT(*) as total FROM prestador";
$sql = "SELECT RE_CUNID, RE_CNOME, RE_TIPO, CNPJ, Area, tipouni, Relatorio, ativo FROM prestador";

// Adiciona pesquisa se houver
if (!empty($pesquisa)) {
    $pesquisa = "%$pesquisa%";
    $sql_count .= " WHERE RE_CUNID LIKE ? OR RE_CNOME LIKE ? OR CNPJ LIKE ?";
    $sql .= " WHERE RE_CUNID LIKE ? OR RE_CNOME LIKE ? OR CNPJ LIKE ?";
}

// Adiciona ordenação e limite
$sql .= " ORDER BY ativo DESC, RE_CNOME LIMIT ?, ?";

// Prepara e executa a consulta de contagem
$stmt_count = $pdo->prepare($sql_count);
if (!empty($pesquisa)) {
    $stmt_count->execute([$pesquisa, $pesquisa, $pesquisa]);
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
}
$params[] = $inicio;
$params[] = $registros_por_pagina;
$stmt->execute($params);
$prestadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Arrays para os dropdowns
$tipos_relatorio = [
    'U' => 'Atenção Primária',
    'H' => 'Hospital',
    'P' => 'Particular/Único'
];

$tipos_unidade = [
    'M' => 'Municipal',
    'F' => 'Filantrópico',
    'P' => 'Particular',
    'E' => 'Estadual',
    'OS' => 'Organização Social'
];

$relatorios_quadrimestrais = [
    'Atenção Básica',
    'Atenção Ambulatorial',
    'Atenção PsicoSocial',
    'Hospitalar'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Prestadores - Consulta de Produção Ambulatorial</title>
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
                <!-- Título e Botões -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3>Gerenciamento de Prestadores</h3>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPrestador">
                        <i class="fas fa-plus"></i> Novo Prestador
                    </button>
                </div>

                <!-- Barra de Pesquisa -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="d-flex gap-2">
                            <div class="flex-grow-1 search-box">
                                <div class="input-group">
                                    <input type="text" name="pesquisa" class="form-control" placeholder="Pesquisar por CNES, Nome ou CNPJ" value="<?php echo htmlspecialchars($pesquisa ?? ''); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <?php if (!empty($pesquisa)): ?>
                                <a href="prestadores.php" class="btn btn-outline-secondary">Limpar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Tabela de Prestadores -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>CNES</th>
                                        <th>Nome</th>
                                        <th>Tipo</th>
                                        <th>CNPJ</th>
                                        <th>Área</th>
                                        <th>Tipo Unidade</th>
                                        <th>Relatório</th>
                                        <th>Ativo</th>
                                        <th class="actions">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($prestadores as $prestador): ?>
                                    <tr class="<?php echo ($prestador['ativo'] == 0) ? 'prestador-inativo' : ''; ?>">
                                        <td><?php echo htmlspecialchars($prestador['RE_CUNID']); ?></td>
                                        <td><?php echo htmlspecialchars($prestador['RE_CNOME']); ?></td>
                                        <td><?php echo htmlspecialchars($tipos_relatorio[$prestador['RE_TIPO']] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($prestador['CNPJ'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($prestador['Area'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($tipos_unidade[$prestador['tipouni']] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($prestador['Relatorio'] ?? ''); ?></td>
                                        <td><?php echo ($prestador['ativo'] == 1) ? 'Sim' : 'Não'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarPrestador(<?php echo htmlspecialchars(json_encode($prestador)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="confirmarExclusao('<?php echo htmlspecialchars($prestador['RE_CUNID']); ?>')">
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

    <!-- Modal de Prestador -->
    <div class="modal fade" id="modalPrestador" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Novo Prestador</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formPrestador">
                        <input type="hidden" id="acao" name="acao" value="criar">
                        <div class="mb-3">
                            <label for="cnes" class="form-label">CNES</label>
                            <input type="text" class="form-control" id="cnes" name="cnes" maxlength="7" required>
                        </div>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" maxlength="35" required>
                        </div>
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo Relatório</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($tipos_relatorio as $valor => $texto): ?>
                                    <option value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($texto); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input type="text" class="form-control" id="cnpj" name="cnpj" maxlength="15">
                        </div>
                        <div class="mb-3">
                            <label for="area" class="form-label">Área Planejamento</label>
                            <input type="number" class="form-control" id="area" name="area">
                        </div>
                        <div class="mb-3">
                            <label for="tipouni" class="form-label">Tipo Unidade</label>
                            <select class="form-select" id="tipouni" name="tipouni" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($tipos_unidade as $valor => $texto): ?>
                                    <option value="<?php echo htmlspecialchars($valor); ?>"><?php echo htmlspecialchars($texto); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="relatorio" class="form-label">Relatório Quadrimestral</label>
                            <select class="form-select" id="relatorio" name="relatorio" required>
                                <option value="">Selecione o relatório</option>
                                <?php foreach ($relatorios_quadrimestrais as $relatorio): ?>
                                    <option value="<?php echo htmlspecialchars($relatorio); ?>">
                                        <?php echo htmlspecialchars($relatorio); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ativo" class="form-label">Ativo</label>
                            <select class="form-select" id="ativo" name="ativo" required>
                                <option value="">Selecione...</option>
                                <option value="S">Sim</option>
                                <option value="N">Não</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarPrestador()">Salvar</button>
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
                    <p>Tem certeza que deseja excluir este prestador?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="excluirPrestador()">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./assets/js/sidebar.js"></script>
    <script>
        let prestadorParaExcluir = '';
        const modalPrestador = new bootstrap.Modal(document.getElementById('modalPrestador'));
        const modalConfirmacao = new bootstrap.Modal(document.getElementById('modalConfirmacao'));

        function editarPrestador(prestador) {
            document.getElementById('acao').value = 'editar';
            document.getElementById('cnes').value = prestador.RE_CUNID;
            document.getElementById('cnes').readOnly = true;
            document.getElementById('nome').value = prestador.RE_CNOME;
            document.getElementById('tipo').value = prestador.RE_TIPO;
            document.getElementById('cnpj').value = prestador.CNPJ;
            document.getElementById('area').value = prestador.Area;
            document.getElementById('tipouni').value = prestador.tipouni;
            document.getElementById('relatorio').value = prestador.Relatorio;
            document.getElementById('ativo').value = prestador.ativo == 1 ? 'S' : 'N';
            document.getElementById('modalTitle').textContent = 'Editar Prestador';
            modalPrestador.show();
        }

        function novoPrestador() {
            document.getElementById('prestadorForm').reset();
            document.getElementById('cnes').readOnly = false;
            document.getElementById('modalTitle').textContent = 'Novo Prestador';
            document.getElementById('ativo').value = 'S'; // Define como ativo por padrão
            modalPrestador.show();
        }

        function confirmarExclusao(cnes) {
            prestadorParaExcluir = cnes;
            modalConfirmacao.show();
        }

        function limparFormulario() {
            document.getElementById('formPrestador').reset();
            document.getElementById('acao').value = 'criar';
            document.getElementById('cnes').readOnly = false;
            document.getElementById('modalTitle').textContent = 'Novo Prestador';
        }

        function salvarPrestador() {
            const form = document.getElementById('formPrestador');
            const formData = new FormData(form);

            fetch('actions/prestador_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalPrestador.hide();
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

        function excluirPrestador() {
            const formData = new FormData();
            formData.append('acao', 'excluir');
            formData.append('cnes', prestadorParaExcluir);

            fetch('actions/prestador_actions.php', {
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
        document.getElementById('modalPrestador').addEventListener('hidden.bs.modal', limparFormulario);

        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,3})(\d{0,3})(\d{0,4})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : x[1] + '.' + x[2] + '.' + x[3] + '/' + x[4] + (x[5] ? '-' + x[5] : '');
        });

        // Validação de CNES (7 dígitos numéricos)
        document.getElementById('cnes').addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 7);
        });
    </script>
</body>
</html>
