<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $_SESSION['login_error'] = 'Erro de validação do formulário. Por favor, tente novamente.';
    header('Location: ../login.php');
    exit();
}

// Obter e sanitizar dados do formulário
$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validar dados
if (empty($username) || empty($password)) {
    $_SESSION['login_error'] = 'Por favor, preencha todos os campos.';
    header('Location: ../login.php');
    exit();
}

try {
    // Conectar ao banco de dados
    $db = new Database();
    $conn = $db->getConnection();

    // Preparar e executar consulta
    $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar se usuário existe e senha está correta
    if ($user && password_verify($password, $user['password_hash'])) {
        // Login bem-sucedido
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Registrar atividade
        logActivity('login', 'Login bem-sucedido');
        
        // Redirecionar para a página principal
        header('Location: ../index.php');
        exit();
    } else {
        // Login falhou
        $_SESSION['login_error'] = 'Usuário ou senha incorretos.';
        logActivity('login_failed', 'Tentativa de login falhou para usuário: ' . $username);
        header('Location: ../login.php');
        exit();
    }

} catch (PDOException $e) {
    // Erro de banco de dados
    $_SESSION['login_error'] = 'Erro ao processar login. Por favor, tente novamente mais tarde.';
    logActivity('login_error', 'Erro de banco de dados: ' . $e->getMessage());
    header('Location: ../login.php');
    exit();
}
