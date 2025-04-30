<?php
session_start();
require __DIR__ . '/../app/conexao.php'; // Ajuste o caminho

header('Content-Type: application/json; charset=utf-8');

try {
    // Query corrigida para contagem correta
    $query = "
        SELECT 
            c.nome AS categoria,
            COUNT(lc.livro_id) AS total
        FROM categoria c
        LEFT JOIN livro_categoria lc ON c.id = lc.categoria_id
        GROUP BY c.id
        ORDER BY total DESC
    ";

    $result = $conn->query($query);
    
    // Cores dinâmicas
    $colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6c757d',  // Cores originais
        '#858796', '#6e8c69', '#3a3b45', '#5de9d9', '#17a673', '#2c9faf',  // Tons de azul e verde
        '#dda20a', '#be2617', '#6f42c1', '#e83e8c', '#fd7e14', '#20c997',  // Roxo, rosa, laranja, turquesa
        '#b52e31', '#5f2c3e', '#ff6699'              // Vermelho escuro, vinho, preto, etc.
    ];
    $data = ['labels' => [], 'data' => [], 'colors' => []];
    $colorIndex = 0;

    while ($row = $result->fetch_assoc()) {
        $data['labels'][] = $row['categoria'];
        $data['data'][] = (int)$row['total'];
        $data['colors'][] = $colors[$colorIndex % count($colors)];
        $colorIndex++;
    }

    // Forçar retorno mesmo se vazio
    if(empty($data['labels'])) {
        $data = [
            'labels' => ['Sem dados'],
            'data' => [1],
            'colors' => ['#ff6699']
        ];
    }

    echo json_encode($data);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}