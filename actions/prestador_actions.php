<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Obtém a conexão com o banco de dados
$database = new Database();
$pdo = $database->getConnection();

// Valores válidos para o campo Relatório
$relatorios_validos = [
    'Atenção Básica',
    'Atenção Ambulatorial',
    'Atenção PsicoSocial',
    'Hospitalar'
];

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

$acao = $_POST['acao'] ?? '';
$response = ['success' => false, 'message' => 'Ação inválida'];

try {
    switch ($acao) {
        case 'criar':
            $cnes = trim($_POST['cnes'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $cnpj = trim($_POST['cnpj'] ?? '');
            $area = trim($_POST['area'] ?? '');
            $tipouni = trim($_POST['tipouni'] ?? '');
            $relatorio = trim($_POST['relatorio'] ?? '');

            // Validação dos campos
            if (empty($cnes) || empty($nome) || empty($tipo) || empty($tipouni) || empty($relatorio)) {
                throw new Exception('Os campos CNES, Nome, Tipo, Tipo Unidade e Relatório são obrigatórios');
            }

            // Validações específicas
            if (strlen($cnes) != 7 || !ctype_digit($cnes)) {
                throw new Exception('O CNES deve ter exatamente 7 dígitos numéricos');
            }

            if (strlen($nome) > 35) {
                throw new Exception('O nome deve ter no máximo 35 caracteres');
            }

            if (!empty($cnpj) && strlen($cnpj) > 15) {
                throw new Exception('O CNPJ deve ter no máximo 15 caracteres');
            }

            // Validação do Relatório
            if (!in_array($relatorio, $relatorios_validos)) {
                throw new Exception('Valor inválido para o campo Relatório');
            }

            if (strlen($relatorio) > 45) {
                throw new Exception('O Relatório deve ter no máximo 45 caracteres');
            }

            // Verifica se o CNES já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM prestador WHERE RE_CUNID = ?");
            $stmt->execute([$cnes]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Este CNES já está cadastrado');
            }

            // Insere o novo prestador
            $stmt = $pdo->prepare("INSERT INTO prestador (RE_CUNID, RE_CNOME, RE_TIPO, CNPJ, Area, tipouni, Relatorio, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ativo = ($_POST['ativo'] === 'S') ? 1 : 0;
            $stmt->execute([$cnes, $nome, $tipo, $cnpj, $area, $tipouni, $relatorio, $ativo]);

            $response = ['success' => true, 'message' => 'Prestador cadastrado com sucesso'];
            break;

        case 'editar':
            $cnes = trim($_POST['cnes'] ?? '');
            $nome = trim($_POST['nome'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $cnpj = trim($_POST['cnpj'] ?? '');
            $area = trim($_POST['area'] ?? '');
            $tipouni = trim($_POST['tipouni'] ?? '');
            $relatorio = trim($_POST['relatorio'] ?? '');

            // Validação dos campos
            if (empty($cnes) || empty($nome) || empty($tipo) || empty($tipouni) || empty($relatorio)) {
                throw new Exception('Os campos CNES, Nome, Tipo, Tipo Unidade e Relatório são obrigatórios');
            }

            if (strlen($nome) > 35) {
                throw new Exception('O nome deve ter no máximo 35 caracteres');
            }

            if (!empty($cnpj) && strlen($cnpj) > 15) {
                throw new Exception('O CNPJ deve ter no máximo 15 caracteres');
            }

            // Validação do Relatório
            if (!in_array($relatorio, $relatorios_validos)) {
                throw new Exception('Valor inválido para o campo Relatório');
            }

            if (strlen($relatorio) > 45) {
                throw new Exception('O Relatório deve ter no máximo 45 caracteres');
            }

            // Atualiza o prestador
            $stmt = $pdo->prepare("UPDATE prestador SET RE_CNOME = ?, RE_TIPO = ?, CNPJ = ?, Area = ?, tipouni = ?, Relatorio = ?, ativo = ? WHERE RE_CUNID = ?");
            $ativo = ($_POST['ativo'] === 'S') ? 1 : 0;
            $stmt->execute([$nome, $tipo, $cnpj, $area, $tipouni, $relatorio, $ativo, $cnes]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Prestador não encontrado');
            }

            $response = ['success' => true, 'message' => 'Prestador atualizado com sucesso'];
            break;

        case 'excluir':
            $cnes = trim($_POST['cnes'] ?? '');

            if (empty($cnes)) {
                throw new Exception('CNES não especificado');
            }

            // Verifica se o prestador está sendo usado em outras tabelas
            // Adicione aqui as verificações necessárias de integridade referencial

            // Exclui o prestador
            $stmt = $pdo->prepare("DELETE FROM prestador WHERE RE_CUNID = ?");
            $stmt->execute([$cnes]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Prestador não encontrado');
            }

            $response = ['success' => true, 'message' => 'Prestador excluído com sucesso'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Erro ao processar a operação no banco de dados'];
}

header('Content-Type: application/json');
echo json_encode($response);
