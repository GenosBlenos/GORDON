<?php
session_start();
include __DIR__ . '/../app/conexao.php';

$mensagem = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = trim($_POST['senha']);
    $confirmar_senha = trim($_POST['confirmar_senha']);

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = "Todos os campos são obrigatórios!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido!";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 8) {
        $erro = "A senha deve ter no mínimo 8 caracteres!";
    } else {
        // Verifica se o e-mail já existe
        $verifica = $conn->prepare("SELECT id FROM administradores WHERE email = ?");
        $verifica->bind_param("s", $email);
        $verifica->execute();
        $verifica->store_result();
        
        if ($verifica->num_rows > 0) {
            $erro = "Este e-mail já está cadastrado!";
        } else {
            // Cria o hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            // Insere no banco de dados
            $stmt = $conn->prepare("INSERT INTO administradores (nome, email, senha) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nome, $email, $senha_hash);
            
            if ($stmt->execute()) {
                $mensagem = "Administrador cadastrado com sucesso!";
                // Limpa os campos do formulário
                $nome = $email = '';
            } else {
                $erro = "Erro ao cadastrar administrador: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Administrador - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="..\styles\sense.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            margin-top: 50px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3>Cadastro de Administrador</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($erro): ?>
                            <div class="alert alert-danger"><?= $erro ?></div>
                        <?php endif; ?>
                        
                        <?php if ($mensagem): ?>
                            <div class="alert alert-success"><?= $mensagem ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome Completo</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?= isset($nome) ? htmlspecialchars($nome) : '' ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha" required>
                                <small class="text-muted">Mínimo de 8 caracteres</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Cadastrar</button>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <a href="login.php" class="text-decoration-none">Voltar para o login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>