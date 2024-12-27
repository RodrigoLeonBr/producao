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
            $cbo = trim($_POST['cbo'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');

            // Validação dos campos
            if (empty($cbo) || empty($descricao)) {
                throw new Exception('Todos os campos são obrigatórios');
            }

            if (strlen($cbo) > 8) {
                throw new Exception('O CBO deve ter no máximo 8 caracteres');
            }

            if (strlen($descricao) > 150) {
                throw new Exception('A descrição deve ter no máximo 150 caracteres');
            }

            // Verifica se o CBO já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cbo WHERE CBO = ?");
            $stmt->execute([$cbo]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Este CBO já está cadastrado');
            }

            // Insere o novo CBO
            $stmt = $pdo->prepare("INSERT INTO cbo (CBO, DESCRICAO) VALUES (?, ?)");
            $stmt->execute([$cbo, $descricao]);

            $response = ['success' => true, 'message' => 'CBO cadastrado com sucesso'];
            break;

        case 'editar':
            $cbo = trim($_POST['cbo'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');

            // Validação dos campos
            if (empty($cbo) || empty($descricao)) {
                throw new Exception('Todos os campos são obrigatórios');
            }

            if (strlen($descricao) > 150) {
                throw new Exception('A descrição deve ter no máximo 150 caracteres');
            }

            // Atualiza o CBO
            $stmt = $pdo->prepare("UPDATE cbo SET DESCRICAO = ? WHERE CBO = ?");
            $stmt->execute([$descricao, $cbo]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('CBO não encontrado');
            }

            $response = ['success' => true, 'message' => 'CBO atualizado com sucesso'];
            break;

        case 'excluir':
            $cbo = trim($_POST['cbo'] ?? '');

            if (empty($cbo)) {
                throw new Exception('CBO não especificado');
            }

            // Verifica se o CBO está sendo usado em outras tabelas
            // Adicione aqui as verificações necessárias de integridade referencial

            // Exclui o CBO
            $stmt = $pdo->prepare("DELETE FROM cbo WHERE CBO = ?");
            $stmt->execute([$cbo]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('CBO não encontrado');
            }

            $response = ['success' => true, 'message' => 'CBO excluído com sucesso'];
            break;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
} catch (PDOException $e) {
    $response = ['success' => false, 'message' => 'Erro ao processar a operação no banco de dados'];
}

header('Content-Type: application/json');
echo json_encode($response);
