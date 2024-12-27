<?php
session_start();
require_once 'includes/functions.php';

// Se já estiver logado, redireciona para index.php
if (isAuthenticated()) {
    header('Location: index.php');
    exit();
}

// Mensagem de erro (se houver)
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Dashboard de Produção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #0d6efd;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .login-form {
            margin-bottom: 20px;
        }
        .form-floating {
            margin-bottom: 15px;
        }
        .alert {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Dashboard de Produção</h1>
                <p class="text-muted">Entre com suas credenciais para acessar</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="auth/process_login.php" method="POST" class="login-form">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Usuário" required>
                    <label for="username">Usuário</label>
                </div>

                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Senha" required>
                    <label for="password">Senha</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Entrar</button>
                </div>
            </form>

            <div class="text-center">
                <p class="text-muted">
                    Não tem uma conta? <a href="register.php">Registre-se</a><br>
                    Esqueceu sua senha? Entre em contato com o administrador.
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
