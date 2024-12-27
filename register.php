<?php
session_start();
require_once 'includes/functions.php';

// Mensagem de erro ou sucesso (se houver)
$error = $_SESSION['register_error'] ?? '';
$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_error'], $_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Dashboard de Produção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            color: #0d6efd;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .form-floating {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Registro de Novo Usuário</h1>
                <p class="text-muted">Preencha os dados para criar sua conta</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form action="auth/process_register.php" method="POST" class="register-form">
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

                <div class="form-floating">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirme a Senha" required>
                    <label for="confirm_password">Confirme a Senha</label>
                </div>

                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Email" required>
                    <label for="email">Email</label>
                </div>

                <div class="form-floating">
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           placeholder="Nome Completo" required>
                    <label for="full_name">Nome Completo</label>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Registrar</button>
                </div>
            </form>

            <div class="text-center mt-3">
                <p class="text-muted">
                    Já tem uma conta? <a href="login.php">Faça login</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
