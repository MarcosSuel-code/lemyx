<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';

header('Content-Type: application/json');

$produto_id = (int) ($_GET['produto_id'] ?? 0);

if (!$produto_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT c.calibre_id, c.calibre
    FROM calibre c
    INNER JOIN calibre_produto cp 
        ON cp.calibre_id = c.calibre_id
    WHERE cp.produto_id = ?
    ORDER BY c.calibre
");
$stmt->execute([$produto_id]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
