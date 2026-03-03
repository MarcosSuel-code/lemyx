<?php

function pode(int $usuario_id, string $tela, string $acao): bool
{
    global $pdo;

    // REGRA 1 — Admin tem acesso total
    if (
        isset($_SESSION['perfil']) &&
        $_SESSION['perfil'] === 'admin'
    ) {
        return true;
    }

            $sql = "
            SELECT 1
            FROM usuario_permissoes up
            JOIN permissoes p ON p.permissao_id = up.permissao_id
            JOIN telas t ON t.tela_id = p.tela_id
            JOIN chave c ON c.chave_id = p.chave_id
            WHERE up.usuario_id = :usuario
            AND t.chave = :tela
            AND p.ativo = 1
            AND (c.chave = :acao OR c.chave = '*')
            LIMIT 1
        ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario' => $usuario_id,
        ':tela' => $tela,
        ':acao' => $acao
    ]);

    return (bool) $stmt->fetchColumn();
}

function requirePermissao(string $tela, string $acao): void
{
    if (
        !isset($_SESSION['usuario_id']) ||
        !pode($_SESSION['usuario_id'], $tela, $acao)
    ) {
        http_response_code(403);

        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Sem permissão para esta ação'
            ]);
        } else {
            exit('Acesso negado.');
        }

        exit;
    }
}