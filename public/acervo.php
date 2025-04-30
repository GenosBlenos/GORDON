<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../app/conexao.php';

// Processamento da pesquisa
$termo_pesquisa = $_GET['pesquisa'] ?? '';
$where = '';

if (!empty($termo_pesquisa)) {
    $termo_pesquisa = htmlspecialchars($termo_pesquisa);
    $termo_like = "%{$termo_pesquisa}%";
    
    $where = "WHERE 
        l.titulo LIKE ? OR 
        l.autor LIKE ? OR 
        l.isbn LIKE ? OR 
        c.nome LIKE ?";
}

// Consulta SQL
$sql = "
    SELECT 
        l.*,
        CASE 
            WHEN e.status = 'emprestado' THEN 'Emprestado' 
            ELSE 'Disponível' 
        END AS status
    FROM livros l
    LEFT JOIN (
        SELECT livro_id, MAX(status) AS status 
        FROM emprestimos 
        GROUP BY livro_id
    ) e ON l.id = e.livro_id
";

// Execução da consulta
if (!empty($where)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $termo_like, $termo_like, $termo_like, $termo_like);
    $stmt->execute();
    $livros = $stmt->get_result();
} else {
    $livros = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acervo Completo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../app/menu.php'; ?>

    <div class="container deep">
        <h2 class="mb-4">📚 Acervo Completo</h2>

        <!-- Formulário de Pesquisa -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input 
                    style="border: solid 2px black;"
                    type="text" 
                    name="pesquisa" 
                    class="form-control" 
                    placeholder="Pesquisar por título, autor, ISBN ou categoria..."
                    value="<?= htmlspecialchars($termo_pesquisa) ?>"
                >
                <button type="submit" class="btn btn-primary">🔍 Buscar</button>
            </div>
        </form>

        <?php if (!empty($termo_pesquisa)): ?>
            <div class="alert alert-info mb-3">
                Resultados para: <strong>"<?= $termo_pesquisa ?>"</strong>
                <a href="acervo.php" class="float-end">Limpar pesquisa</a>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="tabela">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor(a)</th>
                        <th>Ano</th>
                        <th>ISBN</th>
                        <th>Categorias</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($livro = $livros->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($livro['titulo']) ?></td>
                            <td><?= htmlspecialchars($livro['autor']) ?></td>
                            <td><?= $livro['ano_publicacao'] ?></td>
                            <td><?= $livro['isbn'] ?? 'ISBN não cadastrado' ?></td>
                            <td><?= $livro['categorias'] ?? 'Nenhuma categoria' ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>