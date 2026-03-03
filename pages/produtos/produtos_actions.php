<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'sucesso' => false,
    'mensagem' => '',
    'tabela' => ''
];

$action = $_POST['action'] ?? null;

if (!$action) {
    $response['mensagem'] = 'Ação não informada.';
    echo json_encode($response);
    exit;
}

/**
 * Renderiza a tabela de produtos
 */
function renderTabela(PDO $pdo): string
{
    $stmt = $pdo->query("
        SELECT 
            p.produto_id,
            p.descricao,
            p.categoria_id,
            c.nome AS categoria_nome
        FROM produtos p
        LEFT JOIN categorias c ON c.categoria_id = p.categoria_id
        ORDER BY p.descricao ASC
    ");

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    foreach ($produtos as $p): ?>
        <tr>
            <td><?= $p['produto_id'] ?></td>
            <td><?= htmlspecialchars($p['descricao']) ?></td>
            <td><?= htmlspecialchars($p['categoria_nome'] ?? 'Sem categoria') ?></td>
            <td class="text-center">
                <?php if (pode($_SESSION['usuario_id'], 'produtos', 'editar')): ?>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $p['produto_id'] ?>"
                        data-descricao="<?= htmlspecialchars($p['descricao']) ?>" data-categoria="<?= $p['categoria_id'] ?>">
                        Editar
                    </button>
                <?php endif; ?>

                <?php if (pode($_SESSION['usuario_id'], 'produtos', 'excluir')): ?>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $p['produto_id'] ?>">
                        Excluir
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;

    return ob_get_clean();
}

try {

    // =========================
    // CRIAR PRODUTO
    // =========================
    if ($action === 'add') {
        requirePermissao('produtos', 'criar');

        $descricao = trim($_POST['descricao'] ?? '');
        $categoria_id = $_POST['categoria_id'] ?: null;

        if (!$descricao) {
            throw new Exception('Descrição é obrigatória.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO produtos (descricao, categoria_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$descricao, $categoria_id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto cadastrado com sucesso.';
        $response['tabela'] = renderTabela($pdo);
    }

    // =========================
    // EDITAR PRODUTO
    // =========================
    if ($action === 'edit') {
        requirePermissao('produtos', 'editar');

        $produto_id = (int) ($_POST['produto_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria_id = $_POST['categoria_id'] ?: null;

        if (!$produto_id || !$descricao) {
            throw new Exception('Dados inválidos para edição.');
        }

        $stmt = $pdo->prepare("
            UPDATE produtos
            SET descricao = ?, categoria_id = ?
            WHERE produto_id = ?
        ");
        $stmt->execute([$descricao, $categoria_id, $produto_id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto atualizado com sucesso.';
        $response['tabela'] = renderTabela($pdo);
    }

    // =========================
    // EXCLUIR PRODUTO
    // =========================
    if ($action === 'delete') {
        requirePermissao('produtos', 'excluir');

        $produto_id = (int) ($_POST['produto_id'] ?? 0);

        if (!$produto_id) {
            throw new Exception('ID inválido para exclusão.');
        }

        $stmt = $pdo->prepare("DELETE FROM produtos WHERE produto_id = ?");
        $stmt->execute([$produto_id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto excluído com sucesso.';
        $response['tabela'] = renderTabela($pdo);
    }

} catch (PDOException $e) {
    $response['mensagem'] = 'Erro de banco de dados.';
} catch (Exception $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);