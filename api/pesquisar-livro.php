<?php
require __DIR__ . '/../app/conexao.php';

header('Content-Type: application/json');

try {
    $termo = $_GET['q'] ?? '';
    error_log("Termo de pesquisa recebido: " . $termo); // Log do termo
    
    $termo = "%$termo%";
    
    $stmt = $conn->prepare("
        SELECT id, titulo, autor 
        FROM livros 
        WHERE 
            disponivel = 1 
            AND (titulo LIKE ? OR autor LIKE ?)
        LIMIT 10
    ");
    
    $stmt->bind_param("ss", $termo, $termo);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $livros = $result->fetch_all(MYSQLI_ASSOC);
    
    error_log("Número de livros encontrados: " . count($livros)); // Log de resultados
    
    echo json_encode($livros);
    
} catch (Exception $e) {
    error_log("Erro na pesquisa de livros: " . $e->getMessage()); // Log de erro
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}