<?php
ob_start();
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}
include __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';
// Função para calcular data de devolução
function calcularDevolucao($extensoes = 0)
{
    $max_extensoes = 3;
    $extensoes = min($extensoes, $max_extensoes);
    return date('Y-m-d', strtotime("+" . (7 + ($extensoes * 7)) . " days"));
}

if (isset($_POST['emprestar'])) {
    $livro_id = (int) $_POST['livro'];
    $usuario_id = (int) $_POST['usuario'];
    $data_devolucao = calcularDevolucao();

    // No bloco de empréstimo:
    $conn->begin_transaction();

    try {
        // 1. Atualiza o livro para indisponível
        $stmt = $conn->prepare("UPDATE livros SET disponivel = 0 WHERE id = ?");
        $stmt->bind_param("i", $livro_id);
        $stmt->execute();

        // 2. Insere o empréstimo com status 'emprestado'
        $stmt = $conn->prepare("
        INSERT INTO emprestimos 
        (livro_id, usuario_id, data_emprestimo, data_devolucao, status) 
        VALUES (?, ?, CURDATE(), ?, 'emprestado')
    ");
        $stmt->bind_param("iis", $livro_id, $usuario_id, $data_devolucao);
        $stmt->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // Trate o erro
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Empréstimo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/sense.css" rel="stylesheet">
</head>

<body>
    <div class="container deep">
        <h2 class="mb-5">📚 Realizar Empréstimo</h2>
        <form method="POST">
            <div class="row g-4">
                <!-- Campo de pesquisa de usuário -->
                <div class="col-md-6">
                    <label class="form-label">Usuário:</label>
                    <div class="search-container">
                        <input style="border: black 2px solid;" type="text" id="usuarioSearch" class="form-control"
                            placeholder="Digite o nome do usuário..." autocomplete="off">
                        <input type="hidden" name="usuario" id="usuarioId">
                        <div class="search-results" id="usuarioResults"></div>
                    </div>
                </div>

                <!-- Campo de pesquisa de livro -->
                <div class="col-md-6">
                    <label class="form-label">Livro Disponível:</label>
                    <div class="search-container">
                        <input style="border: black 2px solid;" type="text" id="livroSearch" class="form-control"
                            placeholder="Digite título ou autor..." autocomplete="off">
                        <input type="hidden" name="livro" id="livroId">
                        <div class="search-results" id="livroResults"></div>
                    </div>
                </div>

                <div class="col-12">
                    <button style="background-color:#155680; border: #072a3a 2px solid; color: white;" type="submit"
                        name="emprestar" class="btn w-100">
                        ✨ Realizar Empréstimo
                    </button>
                </div>
            </div>
        </form>

        <!-- Listagem de empréstimos ativos -->
        <div class="mt-5">
            <h4>🔍 Empréstimos Ativos</h4>
            <div class="table-responsive">
                <table class="tabela">
                    <thead class="table-light">
                        <tr>
                            <th>Livro</th>
                            <th>Usuário</th>
                            <th>Data Devolução</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $emprestimos = $conn->query("
                        SELECT e.id, l.titulo, u.nome, e.data_devolucao, e.extensoes 
                        FROM emprestimos e
                        JOIN livros l ON e.livro_id = l.id
                        JOIN usuarios u ON e.usuario_id = u.id
                        WHERE e.status = 'emprestado' 
                    ");

                        if ($emprestimos->num_rows > 0) {
                            while ($emp = $emprestimos->fetch_assoc()) {
                                echo '<tr>
                                    <td>' . htmlspecialchars($emp['titulo']) . '</td>
                                    <td>' . htmlspecialchars($emp['nome']) . '</td>
                                    <td>' . date('d/m/Y', strtotime($emp['data_devolucao'])) . '</td>
                                    <td>
                                        <!-- Botões de ação -->
                                    </td>
                                </tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4" class="text-center">Nenhum empréstimo ativo</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Pesquisa de Usuários
        document.getElementById('usuarioSearch').addEventListener('input', function (e) {
            const query = e.target.value.trim();
            const results = document.getElementById('usuarioResults');

            if (query.length < 2) {
                results.innerHTML = '';
                return;
            }

            fetch(`../api/pesquisar-usuario.php?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na API');
                    return response.json();
                })
                .then(data => {
                    results.innerHTML = data.length > 0
                        ? data.map(user => `
                        <div class="search-item" 
                            data-id="${user.id}"
                            onclick="selectUsuario(${user.id}, '${user.nome.replace(/'/g, "\\'")}')">
                            ${user.nome}
                        </div>
                      `).join('')
                        : '<div class="search-item">Nenhum resultado</div>';
                })
                .catch(error => {
                    results.innerHTML = '<div class="search-item text-danger">Erro na pesquisa</div>';
                });
        });

        // Pesquisa de Livros (ADICIONE ESTA PARTE)
        document.getElementById('livroSearch').addEventListener('input', function (e) {
            const query = e.target.value.trim();
            const results = document.getElementById('livroResults');

            if (query.length < 2) {
                results.innerHTML = '';
                return;
            }

            fetch(`../api/pesquisar-livro.php?q=${encodeURIComponent(query)}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na API');
                    return response.json();
                })
                .then(data => {
                    results.innerHTML = data.length > 0
                        ? data.map(livro => `
                        <div class="search-item" 
                            data-id="${livro.id}"
                            onclick="selectLivro(${livro.id}, \`${livro.titulo} (${livro.autor})\`)">
                            ${livro.titulo} - ${livro.autor}
                        </div>
                      `).join('')
                        : '<div class="search-item">Nenhum livro encontrado</div>';
                })
                .catch(error => {
                    results.innerHTML = '<div class="search-item text-danger">Erro na pesquisa</div>';
                });
        });

        // Funções de seleção
        window.selectUsuario = function (id, nome) {
            document.getElementById('usuarioId').value = id;
            document.getElementById('usuarioSearch').value = nome;
            document.getElementById('usuarioResults').innerHTML = '';
        };

        window.selectLivro = function (id, texto) {
            document.getElementById('livroId').value = id;
            document.getElementById('livroSearch').value = texto;
            document.getElementById('livroResults').innerHTML = '';
        };
    </script>
</body>

</html>
<?php ob_end_flush(); ?>