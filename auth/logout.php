<?php
session_start();
require_once '../includes/functions.php';

// Registrar atividade de logout
if (isset($_SESSION['user_id'])) {
    logActivity('logout', 'Usuário realizou logout');
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie da sessão se existir
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destruir a sessão
session_destroy();

// Redirecionar para a página de login
header('Location: ../login.php');
exit();
