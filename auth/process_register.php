<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit();
}

// Verificar token CSRF
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $_SESSION['register_error'] = 'Erro de validação do formulário. Por favor, tente novamente.';
    header('Location: ../register.php');
    exit();
}

// Obter e sanitizar dados do formulário
$username = sanitizeInput($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$email = sanitizeInput($_POST['email'] ?? '');
$full_name = sanitizeInput($_POST['full_name'] ?? '');

// Validar dados
if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($full_name)) {
    $_SESSION['register_error'] = 'Por favor, preencha todos os campos.';
    header('Location: ../register.php');
    exit();
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'Por favor, forneça um email válido.';
    header('Location: ../register.php');
    exit();
}

// Verificar se as senhas coincidem
if ($password !== $confirm_password) {
    $_SESSION['register_error'] = 'As senhas não coincidem.';
    header('Location: ../register.php');
    exit();
}

// Validar força da senha
if (strlen($password) < 6) {
    $_SESSION['register_error'] = 'A senha deve ter pelo menos 6 caracteres.';
    header('Location: ../register.php');
    exit();
}

try {
    // Conectar ao banco de dados
    $db = new Database();
    $conn = $db->getConnection();

    // Verificar se o usuário já existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['register_error'] = 'Usuário ou email já cadastrado.';
        header('Location: ../register.php');
        exit();
    }

    // Criar hash da senha
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Inserir novo usuário
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, email, full_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $password_hash, $email, $full_name]);

    // Registro bem-sucedido
    $_SESSION['register_success'] = 'Conta criada com sucesso! Você já pode fazer login.';
    logActivity('register', 'Novo usuário registrado: ' . $username);
    
    // Redirecionar para a página de login
    header('Location: ../login.php');
    exit();

} catch (PDOException $e) {
    // Erro de banco de dados
    $_SESSION['register_error'] = 'Erro ao criar conta. Por favor, tente novamente mais tarde.';
    logActivity('register_error', 'Erro de banco de dados: ' . $e->getMessage());
    header('Location: ../register.php');
    exit();
}
