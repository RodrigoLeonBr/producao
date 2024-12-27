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

try {
    $acao = $_POST['acao'] ?? '';
    
    switch ($acao) {
        case 'criar':
            // Validação dos campos obrigatórios
            $campos_obrigatorios = ['codigo', 'procedimento', 'pa_total', 'rub_total', 'rub_dc', 'pa_rub', 'financiamento'];
            foreach ($campos_obrigatorios as $campo) {
                if (empty($_POST[$campo])) {
                    throw new Exception("O campo $campo é obrigatório");
                }
            }

            // Verifica se o código já existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM procedimento WHERE codigo = ?");
            $stmt->execute([$_POST['codigo']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Este código de procedimento já está cadastrado');
            }

            // Define o PA_ID como o código sem o último caractere
            $pa_id = substr($_POST['codigo'], 0, -1);

            // Insere o novo procedimento
            $stmt = $pdo->prepare("INSERT INTO procedimento (codigo, procedimento, PA_TOTAL, RUB_TOTAL, RUB_DC, PA_RUB, PA_ID, FINANCIAMENTO) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['codigo'],
                $_POST['procedimento'],
                $_POST['pa_total'],
                $_POST['rub_total'],
                $_POST['rub_dc'],
                $_POST['pa_rub'],
                $pa_id,
                $_POST['financiamento']
            ]);

            $response = ['success' => true, 'message' => 'Procedimento cadastrado com sucesso'];
            break;

        case 'editar':
            // Validação dos campos obrigatórios
            $campos_obrigatorios = ['codigo', 'procedimento', 'pa_total', 'rub_total', 'rub_dc', 'pa_rub', 'financiamento'];
            foreach ($campos_obrigatorios as $campo) {
                if (empty($_POST[$campo])) {
                    throw new Exception("O campo $campo é obrigatório");
                }
            }

            // Define o PA_ID como o código sem o último caractere
            $pa_id = substr($_POST['codigo'], 0, -1);

            // Atualiza o procedimento
            $stmt = $pdo->prepare("UPDATE procedimento SET procedimento = ?, PA_TOTAL = ?, RUB_TOTAL = ?, RUB_DC = ?, PA_RUB = ?, PA_ID = ?, FINANCIAMENTO = ? WHERE codigo = ?");
            $stmt->execute([
                $_POST['procedimento'],
                $_POST['pa_total'],
                $_POST['rub_total'],
                $_POST['rub_dc'],
                $_POST['pa_rub'],
                $pa_id,
                $_POST['financiamento'],
                $_POST['codigo']
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Procedimento não encontrado');
            }

            $response = ['success' => true, 'message' => 'Procedimento atualizado com sucesso'];
            break;

        case 'excluir':
            if (empty($_POST['codigo'])) {
                throw new Exception('Código do procedimento não fornecido');
            }

            $stmt = $pdo->prepare("DELETE FROM procedimento WHERE codigo = ?");
            $stmt->execute([$_POST['codigo']]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Procedimento não encontrado');
            }

            $response = ['success' => true, 'message' => 'Procedimento excluído com sucesso'];
            break;

        default:
            throw new Exception('Ação inválida');
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
