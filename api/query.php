<?php
session_start();
require_once '../config/database.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar método da requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

// Obter dados da requisição
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'JSON inválido: ' . json_last_error_msg()]);
    exit();
}

$sql = $data['sql'] ?? '';
$page = isset($data['page']) ? (int)$data['page'] : 1;
$limit = isset($data['limit']) ? (int)$data['limit'] : 50;

if (empty($sql)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'SQL não fornecido']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Debug log
    error_log("SQL Original: " . $sql);
    error_log("Page: " . $page);
    error_log("Limit: " . $limit);

    // Contar total de registros
    $countSql = "SELECT COUNT(*) as total FROM ($sql) as subquery";
    error_log("Count SQL: " . $countSql);
    
    $stmt = $pdo->prepare($countSql);
    $stmt->execute();
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    error_log("Total Count: " . $totalCount);

    // Adicionar LIMIT e OFFSET para paginação
    $offset = ($page - 1) * $limit;
    $paginatedSql = $sql . " LIMIT $limit OFFSET $offset";
    
    error_log("Paginated SQL: " . $paginatedSql);

    // Executar consulta paginada
    $stmt = $pdo->prepare($paginatedSql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'results' => $results,
        'total' => $totalCount,
        'page' => $page,
        'limit' => $limit,
        'sql' => $paginatedSql // Para debug
    ]);

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro ao executar consulta: ' . $e->getMessage()]);
    exit();
} catch (Exception $e) {
    error_log("General Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
    exit();
}
