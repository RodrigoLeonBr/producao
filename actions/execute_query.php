<?php
// Habilitar log de erros em arquivo
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');
error_log("Iniciando execução da query");

// Desabilitar exibição de erros no output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir cabeçalho como JSON
header('Content-Type: application/json');

try {
    // Verificar se o arquivo de configuração existe
    $configFile = realpath(__DIR__ . '/../config/database.php');
    if (!file_exists($configFile)) {
        throw new Exception('Arquivo de configuração não encontrado');
    }
    
    // Incluir o arquivo de configuração do banco de dados
    require_once $configFile;
    error_log("Arquivo de configuração carregado");
    
    // Verificar se a classe Database existe
    if (!class_exists('Database')) {
        throw new Exception('Classe Database não encontrada');
    }
    
    // Obter e decodificar dados JSON
    $rawInput = file_get_contents('php://input');
    if ($rawInput === false) {
        throw new Exception('Erro ao ler input');
    }
    error_log("Input recebido: " . $rawInput);
    
    // Decodificar JSON
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
    }
    
    // Validar SQL
    if (!isset($input['sql']) || empty($input['sql'])) {
        throw new Exception('SQL query não fornecida ou vazia');
    }
    
    // Validar SQL básico
    $sql = trim($input['sql']);
    if (!preg_match('/^SELECT\s+/i', $sql)) {
        throw new Exception('Query deve começar com SELECT');
    }
    
    error_log("SQL recebido: " . $sql);
    
    // Validar paginação
    $page = isset($input['page']) ? (int)$input['page'] : 1;
    if ($page < 1) {
        throw new Exception('Página inválida');
    }
    
    $itemsPerPage = isset($input['itemsPerPage']) ? (int)$input['itemsPerPage'] : 50;
    if ($itemsPerPage < 1 || $itemsPerPage > 1000) {
        throw new Exception('Número de itens por página inválido');
    }
    
    // Obter conexão com o banco
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        if (!$pdo instanceof PDO) {
            throw new Exception('Conexão inválida retornada');
        }
        error_log("Conexão com o banco estabelecida");
    } catch (PDOException $e) {
        throw new Exception('Erro ao conectar ao banco: ' . $e->getMessage());
    }
    
    // Calcular offset
    $offset = ($page - 1) * $itemsPerPage;
    
    // Adicionar LIMIT e OFFSET à query
    $sqlWithLimit = $sql . " LIMIT $itemsPerPage OFFSET $offset";
    error_log("SQL com limite: " . $sqlWithLimit);
    
    // Executar a query principal
    try {
        $stmt = $pdo->prepare($sqlWithLimit);
        if (!$stmt) {
            $error = $pdo->errorInfo();
            throw new Exception('Erro ao preparar a consulta: ' . $error[2]);
        }
        error_log("Query preparada");
        
        $success = $stmt->execute();
        if (!$success) {
            $error = $stmt->errorInfo();
            throw new Exception('Erro ao executar a consulta: ' . $error[2]);
        }
        error_log("Query executada");
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Resultados obtidos: " . count($results) . " registros");
        
        // Contar total de registros
        $countSql = "SELECT COUNT(*) as total FROM ($sql) as subquery";
        error_log("SQL contagem: " . $countSql);
        
        $countStmt = $pdo->prepare($countSql);
        if (!$countStmt) {
            $error = $pdo->errorInfo();
            throw new Exception('Erro ao preparar a consulta de contagem: ' . $error[2]);
        }
        
        $success = $countStmt->execute();
        if (!$success) {
            $error = $countStmt->errorInfo();
            throw new Exception('Erro ao executar a consulta de contagem: ' . $error[2]);
        }
        
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        error_log("Total de registros: " . $totalCount);
        
    } catch (PDOException $e) {
        throw new Exception('Erro na execução da query: ' . $e->getMessage());
    }
    
    // Retornar resultados
    $response = [
        'success' => true,
        'results' => $results,
        'total' => $totalCount,
        'sql' => $sql
    ];
    
    error_log("Resposta preparada");
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    error_log("Resposta enviada com sucesso");
    
} catch (PDOException $e) {
    error_log("Erro PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Erro Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    error_log("Erro Fatal: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
