<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado.']);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

/*
|--------------------------------------------------------------------------
| FUNÇÃO: RENDERIZA TABELA
|--------------------------------------------------------------------------
*/
function renderTabela(PDO $pdo, int $usuarioId): string
{
    $podeExcluir = pode($usuarioId, 'calibre_produto', 'excluir');

    $stmt = $pdo->query("
        SELECT 
            cp.produto_id,
            cp.calibre_id,
            p.descricao AS produto,
            c.calibre AS calibre
        FROM calibre_produto cp
        JOIN produtos p ON p.produto_id = cp.produto_id
        JOIN calibre c  ON c.calibre_id  = cp.calibre_id
        ORDER BY p.descricao, c.calibre
    ");

    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$dados) {
        return '<tr><td colspan="3">Nenhum registro encontrado</td></tr>';
    }

    $html = '';

    foreach ($dados as $l) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($l['produto']) . '</td>';
        $html .= '<td>' . htmlspecialchars($l['calibre']) . '</td>';
        $html .= '<td>';

        if ($podeExcluir) {
            $html .= '
                <button class="btn btn-sm btn-danger btn-excluir-calibre-produto"
                    data-produto="' . (int) $l['produto_id'] . '"
                    data-calibre="' . (int) $l['calibre_id'] . '">
                    Excluir
                </button>';
        } else {
            $html .= '<button class="btn btn-sm btn-secondary" disabled>Excluir</button>';
        }

        $html .= '</td></tr>';
    }

    return $html;
}

try {

    /*
    |--------------------------------------------------------------------------
    | ADICIONAR
    |--------------------------------------------------------------------------
    */
    if ($action === 'add') {

        if (!pode($usuarioId, 'calibre_produto', 'criar')) {
            throw new Exception('Você não tem permissão para criar vínculos.');
        }

        $produto_id = (int) ($_POST['produto_id'] ?? 0);
        $calibre_id = (int) ($_POST['calibre_id'] ?? 0);

        if (!$produto_id || !$calibre_id) {
            throw new Exception('Produto e calibre são obrigatórios.');
        }

        $pdo->beginTransaction();

        $check = $pdo->prepare("
            SELECT 1 FROM calibre_produto
            WHERE produto_id = ? AND calibre_id = ?
        ");
        $check->execute([$produto_id, $calibre_id]);

        if ($check->fetch()) {
            throw new Exception('Este vínculo já existe.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO calibre_produto (produto_id, calibre_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$produto_id, $calibre_id]);

        $pdo->commit();

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Vínculo criado com sucesso.',
            'tabela' => renderTabela($pdo, $usuarioId)
        ]);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | EXCLUIR
    |--------------------------------------------------------------------------
    */
    if ($action === 'delete') {

        if (!pode($usuarioId, 'calibre_produto', 'excluir')) {
            throw new Exception('Você não tem permissão para excluir vínculos.');
        }

        $produto_id = (int) ($_POST['produto_id'] ?? 0);
        $calibre_id = (int) ($_POST['calibre_id'] ?? 0);

        if (!$produto_id || !$calibre_id) {
            throw new Exception('Dados inválidos.');
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            DELETE FROM calibre_produto
            WHERE produto_id = ? AND calibre_id = ?
        ");
        $stmt->execute([$produto_id, $calibre_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Registro não encontrado.');
        }

        $pdo->commit();

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Vínculo removido com sucesso.',
            'tabela' => renderTabela($pdo, $usuarioId)
        ]);
        exit;
    }

    throw new Exception('Ação inválida.');

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage()
    ]);
}