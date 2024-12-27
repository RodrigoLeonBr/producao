<?php
/**
 * Arquivo de funções auxiliares para o sistema
 */

/**
 * Sanitiza input do usuário
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Verifica se o usuário está autenticado
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Gera um token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se o token CSRF é válido
 * @param string $token
 * @return bool
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formata valor monetário
 * @param float $value
 * @return string
 */
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata data para o padrão brasileiro
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Verifica se uma string é uma data válida
 * @param string $date
 * @return bool
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Gera log de atividade
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details) {
    $user_id = $_SESSION['user_id'] ?? 'guest';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] User {$user_id}: {$action} - {$details}\n";
    
    $log_file = __DIR__ . '/../logs/activity.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Valida uma consulta SQL para garantir que apenas SELECT statements são permitidos
 * @param string $sql
 * @return bool
 */
function validateSelectQuery($sql) {
    $sql = trim(strtoupper($sql));
    return strpos($sql, 'SELECT') === 0 && 
           strpos($sql, 'INSERT') === false && 
           strpos($sql, 'UPDATE') === false && 
           strpos($sql, 'DELETE') === false && 
           strpos($sql, 'DROP') === false && 
           strpos($sql, 'TRUNCATE') === false;
}

/**
 * Verifica se uma coluna é numérica (para aplicar SUM automaticamente)
 * @param string $columnType
 * @return bool
 */
function isNumericColumn($columnType) {
    $numericTypes = ['int', 'decimal', 'float', 'double', 'real', 'numeric'];
    foreach ($numericTypes as $type) {
        if (stripos($columnType, $type) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Exporta dados para CSV
 * @param array $data
 * @param string $filename
 */
function exportToCSV($data, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    $output = fopen('php://output', 'w');
    
    // BOM para Excel reconhecer UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Dados
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
}

/**
 * Exporta dados para Excel (XLS)
 * @param array $data
 * @param string $filename
 */
function exportToXLS($data, $filename = 'export.xls') {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    echo "<table border='1'>";
    
    // Cabeçalho
    if (!empty($data)) {
        echo "<tr>";
        foreach (array_keys($data[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
    }
    
    // Dados
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    
    echo "</table>";
}
?>
