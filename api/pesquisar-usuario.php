<?php
require __DIR__ . '/../app/conexao.php';

$termo = $_GET['q'] ?? '';
$termo = "%$termo%";

$stmt = $conn->prepare("
    SELECT id, nome 
    FROM usuarios 
    WHERE nome LIKE ? 
    LIMIT 10
");
$stmt->bind_param("s", $termo);
$stmt->execute();

$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($usuarios);