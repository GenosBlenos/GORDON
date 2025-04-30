<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

// Carregar categorias
$categorias = $conn->query("SELECT * FROM categoria ORDER BY nome");

if (isset($_POST['cadastrar'])) {
    $id = (int) $_POST['id']; // ID manual
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $autor = $conn->real_escape_string($_POST['autor']);
    $ano = (int) $_POST['ano'];
    $isbn = $conn->real_escape_string($_POST['isbn']);
    $categorias_selecionadas = isset($_POST['categorias']) ? array_unique($_POST['categorias']) : [];

    try {
        $conn->begin_transaction();

        // Verificar se o ID já existe
        $check_id = $conn->prepare("SELECT id FROM livros WHERE id = ?");
        $check_id->bind_param("i", $id);
        $check_id->execute();
        $check_id->store_result();

        if ($check_id->num_rows > 0) {
            throw new Exception("ID $id já está em uso!");
        }

        // Inserir livro com ID manual
        // Exemplo no registro de empréstimo
        $stmt = $conn->prepare("INSERT INTO emprestimos 
        (livro_id, usuario_id, data_emprestimo, data_devolucao) 
        VALUES (?, ?, CURDATE(), ?)");
        $stmt->bind_param("iis", $livro_id, $usuario_id, $data_devolucao);
        $stmt->execute();
        $stmt->bind_param("issis", $id, $titulo, $autor, $ano, $isbn);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao inserir livro: " . $stmt->error);
        }

        // Inserir categorias
        foreach ($categorias_selecionadas as $cat_id) {
            $cat_id = (int) $cat_id;

            // Verificar se a combinação já existe
            $check = $conn->prepare("SELECT * FROM livro_categoria WHERE livro_id = ? AND categoria_id = ?");
            $check->bind_param("ii", $id, $cat_id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                $stmt_cat = $conn->prepare("INSERT INTO livro_categoria (livro_id, categoria_id) VALUES (?, ?)");
                $stmt_cat->bind_param("ii", $id, $cat_id);

                if (!$stmt_cat->execute()) {
                    throw new Exception("Erro ao inserir categoria: " . $stmt_cat->error);
                }
            }
        }

        $conn->commit();
        $sucesso = "Livro cadastrado com sucesso!";
    } catch (Exception $e) {
        $conn->rollback();
        $erro = "Erro: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de Livros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="..\styles\sense.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-4">
        <h2 class="mb-4">📚 Cadastrar Novo Livro</h2>

        <?php if (isset($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (isset($sucesso)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <!-- Campo para ID manual -->
                <div class="col-md-2">
                    <label class="form-label">Tombo (ID)</label>
                    <input type="number" name="id" class="form-control" style="border: 2px solid gray;" required min="1"
                        step="1">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Título</label>
                    <input type="text" name="titulo" class="form-control" style="border: 2px solid gray;" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">ISBN</label>
                    <input type="text" name="isbn" class="form-control" style="border: 2px solid gray;" pattern="\d{13}"
                        title="13 dígitos numéricos" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Autor(a)</label>
                    <input type="text" name="autor" class="form-control" style="border: 2px solid gray;" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ano de Publicação</label>
                    <input type="number" name="ano" class="form-control" style="border: 2px solid gray;" min="1"
                        max="<?= date('Y') ?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Categorias</label>
                    <select name="categorias[]" class="form-select select2" style="border: 2px solid gray;" multiple
                        required>
                        <?php while ($categoria = $categorias->fetch_assoc()): ?>
                            <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

            </div>
            <button type="submit" name="cadastrar" class="btn btn-success mt-3">
                <i class="bi bi-bookmark-plus"></i> Cadastrar Livro
            </button>
        </form>

        <hr class="my-5">

        <h3 class="mb-3">📖 Últimos Livros Cadastrados</h3>
        <div class="table-responsive">
            <table class="tabela">
                <thead class="table-dark">
                    <tr>
                        <th>Tombo</th>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Ano</th>
                        <th>ISBN</th>
                        <th>Categorias</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $livros = $conn->query("
                SELECT 
                    l.id,
                    l.titulo,
                    l.autor,
                    l.ano_publicacao,
                    l.isbn,
                    l.disponivel,
                    GROUP_CONCAT(c.nome SEPARATOR ', ') AS categorias
                FROM livros l
                LEFT JOIN livro_categoria lc ON l.id = lc.livro_id
                LEFT JOIN categoria c ON lc.categoria_id = c.id
                GROUP BY l.id
                ORDER BY l.titulo
            ");

                    while ($livro = $livros->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($livro['id']) ?></td>
                            <td><?= htmlspecialchars($livro['titulo']) ?></td>
                            <td><?= htmlspecialchars($livro['autor']) ?></td>
                            <td><?= htmlspecialchars($livro['ano_publicacao']) ?></td>
                            <td><?= htmlspecialchars($livro['isbn'] ?? 'ISBN não cadastrado') ?></td>
                            <td><?= htmlspecialchars($livro['categorias'] ?? 'Nenhuma categoria') ?></td>
                            <td>
                                <span class="badge <?= $livro['disponivel'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $livro['disponivel'] ? 'Disponível' : 'Emprestado' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>