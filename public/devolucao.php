<?php
ob_start(); // Inicia o buffer de saída
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

// Processamento da devolução
if (isset($_POST['devolver'])) {
    $emprestimo_id = (int) $_POST['emprestimo_id'];

    // No bloco de devolução:
    try {
        $conn->begin_transaction();

        // 1. Atualiza o status do empréstimo
        $stmt = $conn->prepare("UPDATE emprestimos SET status = 'devolvido' WHERE id = ?");
        $stmt->bind_param("i", $emprestimo_id);
        $stmt->execute();

        // 2. Libera o livro (IMPORTANTE!)
        $stmt = $conn->prepare("
            UPDATE livros 
            SET disponivel = 1 
            WHERE id = (SELECT livro_id FROM emprestimos WHERE id = ?)
        ");
        $stmt->bind_param("i", $emprestimo_id);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success'] = "Livro devolvido!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Erro: " . $e->getMessage();
    }

    header("Location: emprestimo.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Devolução</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
</head>

<body>
    <div class="container deep">
        <div class="container">
            <h2>📚 Devolução de Livros</h2>
            <div class="table-responsive">
                <table class="tabela">
                    <thead class="table-light">
                        <tr>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Data Empréstimo</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT e.id, l.titulo, u.nome, e.data_emprestimo 
                        FROM emprestimos e
                        JOIN livros l ON e.livro_id = l.id
                        JOIN usuarios u ON e.usuario_id = u.id
                        WHERE e.status = 'emprestado'"; // ✅ Filtro correto
                        $result = $conn->query($sql);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>{$row['titulo']}</td>
                                    <td>{$row['nome']}</td>
                                    <td>" . date('d/m/Y', strtotime($row['data_emprestimo'])) . "</td>
                                    <td>
                                        <form method='POST'>
                                            <input type='hidden' name='emprestimo_id' value='{$row['id']}'>
                                            <button type='submit' name='devolver' class='btn btn-success'>✔ Devolver</button>
                                        </form>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>Nenhum empréstimo ativo</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
<?php ob_end_flush(); // Finaliza o buffer ?>