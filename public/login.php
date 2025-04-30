<?php
session_start();
require __DIR__ . '/../app/conexao.php';

if (isset($_POST['entrar'])) {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT * FROM administradores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        if (password_verify($senha, $admin['senha'])) {
            $_SESSION['logado'] = true;
            $_SESSION['admin_nome'] = $admin['nome'];
            header("Location: home.php");
            exit();
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Usuário não encontrado!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div style="margin-top: 320px;" class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Acesso do Sistema</h2>
                        
                        <?php if (isset($erro)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">E-mail:</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Senha:</label>
                                <input type="password" name="senha" class="form-control" required>
                            </div>
                            <button type="submit" name="entrar" class="btn btn-primary w-100">Entrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </divstyle>
</body>
</html>