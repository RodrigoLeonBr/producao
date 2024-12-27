<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procedimentos - Consulta de Produção Ambulatorial</title>
    <!-- Include your CSS and other head elements here -->
</head>
<body>
    <h1>Procedimentos</h1>
    <p>Esta página está em construção.</p>
</body>
</html>
