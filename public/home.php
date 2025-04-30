<?php
session_start();
if (!isset($_SESSION['logado'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../app/conexao.php';
include __DIR__ . '/../app/menu.php';

// Consultas para os dados estatísticos
$total_livros = $conn->query("SELECT COUNT(*) FROM livros")->fetch_row()[0];
$emprestimos_ativos = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'emprestado'")->fetch_row()[0];
$usuarios_cadastrados = $conn->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0];
$atrasos = $conn->query("SELECT COUNT(*) FROM emprestimos WHERE data_devolucao < CURDATE() AND status = 'emprestado'")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Biblioteca</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="../styles/sense.css" rel="stylesheet">
</head>

<body>
    <div class="container-fluid mt-4">
        <div class="row g-4 mb-5">
            <!-- Cards Estatísticos -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-primary">
                    <div class="card-body">
                        <h5 class="card-title text-primary">📚 Total de Livros</h5>
                        <div class="card-counter text-primary"><?= number_format($total_livros) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-success">
                    <div class="card-body">
                        <h5 class="card-title text-success">🔄 Empréstimos Ativos</h5>
                        <div class="card-counter text-success"><?= number_format($emprestimos_ativos) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-info">
                    <div class="card-body">
                        <h5 class="card-title text-info">👥 Usuários Cadastrados</h5>
                        <div class="card-counter text-info"><?= number_format($usuarios_cadastrados) ?></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card data-card shadow-sm border-danger">
                    <div class="card-body">
                        <h5 class="card-title text-danger">⏳ Empréstimos Atrasados</h5>
                        <div class="card-counter text-danger"><?= number_format($atrasos) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-6 col-lg-6">
                <div class="card">
                    <div style="border: 1px solid gray; border-radius: 16px;" class="card-body">
                        <h5 style="margin-bottom: 60px;" class="card-title">📊 Distribuição por Categoria</h5>
                        <!-- Contêiner pai com altura fixa -->
                        <div style="position: relative; height: 500px;"> <!-- Altura ajustável -->
                            <canvas id="categoriasChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-6">
                <div style="border: 1px solid gray; border-radius: 16px;" class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title">🔥 Livros Populares</h5>
                        <ul class="list-group">
                            <?php
                            try {
                                $livros_populares = $conn->query("
                                SELECT l.titulo, COUNT(e.id) AS total
                                FROM emprestimos e
                                JOIN livros l ON e.livro_id = l.id
                                GROUP BY l.id
                                ORDER BY total DESC
                                LIMIT 5
                                ");

                                if ($livros_populares === false) {
                                    throw new Exception("Erro na consulta: " . $conn->error);
                                }

                                if ($livros_populares->num_rows === 0) {
                                    echo "<li class='list-group-item'>Nenhum livro popular encontrado.</li>";
                                } else {
                                    while ($livro = $livros_populares->fetch_assoc()) {
                                        echo '<li class="list-group-item d-flex justify-content-between align-items-center">'
                                            . htmlspecialchars($livro['titulo'])
                                            . '<span class="badge bg-primary rounded-pill">' . $livro['total'] . '</span>'
                                            . '</li>';
                                    }
                                }
                            } catch (Exception $e) {
                                error_log($e->getMessage());
                                echo "<li class='list-group-item text-danger'>Erro ao carregar livros populares.</li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Elemento canvas
            const ctx = document.getElementById('categoriasChart').getContext('2d');

            // Configuração do gráfico
            const config = {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: []
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            right: 20 // Espaço para a legenda
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'right', // Legenda à direita
                            align: 'start', // Alinhar no topo
                            labels: {
                                padding: 10,
                                boxWidth: 15, // Tamanho do quadrado da cor
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            enabled: true
                        }
                    }
                }
            };

            // Criar gráfico vazio inicialmente
            const chart = new Chart(ctx, config);

            // Buscar dados da API
            fetch('../api/categorias-chart.php')
                .then(response => {
                    if (!response.ok) throw new Error('Erro na API: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    // Atualizar dados do gráfico
                    chart.data.labels = data.labels;
                    chart.data.datasets[0].data = data.data;
                    chart.data.datasets[0].backgroundColor = data.colors;
                    chart.update();
                })
                .catch(error => {
                    console.error('Erro:', error);
                    document.getElementById('categoriasChart').closest('.card').innerHTML = `
                <div class="alert alert-danger m-3">
                    Não foi possível carregar o gráfico: ${error.message}
                </div>
            `;
                });
            // Timeout de sessão (15 minutos)
            let timeout = setTimeout(() => window.location.href = 'login.php', 900000);
            document.addEventListener('mousemove', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => window.location.href = 'login.php', 900000);
            });
        });
    </script>
</body>

</html>