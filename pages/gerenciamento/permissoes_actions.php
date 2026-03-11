<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

// ---------- SEGURANÇA ----------
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada']);
    exit;
}

if (!pode($_SESSION['usuario_id'], 'usuarios', 'editar')) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão']);
    exit;
}

// ---------- DADOS ----------
$usuario_id = isset($_POST['usuario_id']) ? (int) $_POST['usuario_id'] : 0;
$permissoes = $_POST['permissoes'] ?? [];

if ($usuario_id <= 0) {
    http_response_code(422);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário inválido']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Remove permissões existentes
    $stmt = $pdo->prepare(
        "DELETE FROM usuario_permissoes WHERE usuario_id = ?"
    );
    $stmt->execute([$usuario_id]);

    // Insere novas permissões
    if (!empty($permissoes)) {
        $stmt = $pdo->prepare(
            "INSERT INTO usuario_permissoes (usuario_id, permissao_id)
             VALUES (?, ?)"
        );

        foreach ($permissoes as $pid) {
            $stmt->execute([
                $usuario_id,
                (int) $pid
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Permissões salvas com sucesso'
    ]);

} catch (Throwable $e) {
    $pdo->rollBack();

    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno',
        'erro' => $e->getMessage()
    ]);
}