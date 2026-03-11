<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'sucesso' => false,
    'mensagem' => '',
    'tabela' => ''
];

$action = $_POST['action'] ?? '';

/**
 * Renderiza a tabela atualizada
 */
function renderTabela(PDO $pdo): string
{
    $stmt = $pdo->query("SELECT * FROM categorias ORDER BY categoria_id ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    foreach ($categorias as $cat): ?>
        <tr>
            <td><?= $cat['categoria_id'] ?></td>
            <td><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-center">

                <?php if (pode($_SESSION['usuario_id'], 'categorias', 'editar')): ?>
                    <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $cat['categoria_id'] ?>"
                        data-nome="<?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>">
                        Editar
                    </button>
                <?php endif; ?>

                <?php if (pode($_SESSION['usuario_id'], 'categorias', 'excluir')): ?>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $cat['categoria_id'] ?>">
                        Excluir
                    </button>
                <?php endif; ?>

            </td>
        </tr>
    <?php endforeach;

    return ob_get_clean();
}

try {

    // ---------- CADASTRAR ----------
    if ($action === 'add') {

        requirePermissao('categorias', 'criar');

        $nome = trim($_POST['nome'] ?? '');

        if ($nome === '') {
            throw new Exception('Informe o nome da categoria.');
        }

        $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
        $stmt->execute([$nome]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Categoria cadastrada com sucesso.';
        $response['tabela'] = renderTabela($pdo);
    }

    // ---------- EDITAR ----------
    elseif ($action === 'edit') {

        requirePermissao('categorias', 'editar');

        $id = (int) ($_POST['categoria_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');

        if ($id <= 0 || $nome === '') {
            throw new Exception('Dados inválidos para edição.');
        }

        $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE categoria_id = ?");
        $stmt->execute([$nome, $id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Categoria atualizada com sucesso.';
        $response['tabela'] = renderTabela($pdo);
    }

    // ---------- EXCLUIR ----------
    elseif ($action === 'delete') {

        requirePermissao('categorias', 'excluir');

        $id = (int) ($_POST['categoria_id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Categoria inválida.');
        }

        $stmt = $pdo->prepare("DELETE FROM categorias WHERE categoria_id = ?");
        $stmt->execute([$id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Categoria excluída com sucesso.';
        $response['tabela'] = renderTabela($pdo);
    } else {
        throw new Exception('Ação inválida.');
    }

} catch (Throwable $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);
exit;