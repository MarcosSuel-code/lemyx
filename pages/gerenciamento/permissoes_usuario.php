<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

if (!pode($_SESSION['usuario_id'], 'usuarios', 'editar')) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão']);
    exit;
}

$usuario_id = (int) ($_GET['usuario_id'] ?? 0);

if (!$usuario_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT permissao_id
    FROM usuario_permissoes
    WHERE usuario_id = ?
");
$stmt->execute([$usuario_id]);

$permissoes = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'sucesso' => true,
    'permissoes' => $permissoes
]);