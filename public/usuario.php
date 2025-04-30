<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nome'];
    $email = $_POST['email'];

    try {
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, email) VALUES (?, ?)");
        $stmt->bind_param("ss", $nome, $email);

        if ($stmt->execute()) {
            $sucesso = "Usuário cadastrado com sucesso!";
        } else {
            $erro = "Erro ao cadastrar usuário: " . $stmt->error;
        }
    } catch (mysqli_sql_exception $e) {
        $erro = "Erro: Este e-mail já está cadastrado!";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Cadastro de Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="..\styles\sense.css" rel="stylesheet">
</head>

<body>
    <div class="container deep">
        <div class="container mt-4">
            <h2>📝 Cadastro de Usuários</h2>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <?php if (isset($sucesso)): ?>
                <div class="alert alert-success"><?= $sucesso ?></div>
            <?php endif; ?>

            <form method="POST" class="mt-3">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome Completo:</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">E-mail:</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="cadastrar" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Cadastrar Usuário
                </button>
            </form>

            <hr>

            <h3 class="mt-4">👥 Usuários Cadastrados</h3>
            <div class="table-responsive">
                <table class="tabela">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Data Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT * FROM usuarios ORDER BY id DESC";
                        $result = $conn->query($sql);

                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>{$row['id']}</td>
                                <td>{$row['nome']}</td>
                                <td>{$row['email']}</td>
                                <td>" . date('d/m/Y', strtotime($row['data_cadastro'])) . "</td>
                              </tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>