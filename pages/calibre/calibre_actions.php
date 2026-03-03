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

// ---------- AUTENTICAÇÃO ----------
if (!isset($_SESSION['usuario_id'])) {
    $response['mensagem'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

// ---------- FUNÇÃO: RENDER TABELA ----------
function renderTabela(PDO $pdo, int $usuarioId): string
{
    $podeEditar = pode($usuarioId, 'calibre', 'editar');
    $podeExcluir = pode($usuarioId, 'calibre', 'excluir');

    $stmt = $pdo->query("SELECT calibre_id, calibre FROM calibre ORDER BY calibre ASC");
    $calibres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    foreach ($calibres as $c): ?>
        <tr>
            <td><?= (int) $c['calibre_id'] ?></td>
            <td><?= htmlspecialchars($c['calibre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?php if ($podeEditar): ?>
                    <button class="btn btn-sm btn-warning btn-edit-calibre" data-id="<?= (int) $c['calibre_id'] ?>"
                        data-calibre="<?= htmlspecialchars($c['calibre'], ENT_QUOTES, 'UTF-8') ?>">
                        Editar
                    </button>
                <?php endif; ?>

                <?php if ($podeExcluir): ?>
                    <button class="btn btn-sm btn-danger btn-delete-calibre" data-id="<?= (int) $c['calibre_id'] ?>">
                        Excluir
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;

    return ob_get_clean();
}

// ---------- EXECUÇÃO ----------
try {

    // ---------- CRIAR ----------
    if ($action === 'add') {

        if (!pode($usuarioId, 'calibre', 'criar')) {
            throw new Exception('Você não tem permissão para cadastrar calibres.');
        }

        $calibre = trim($_POST['calibre'] ?? '');

        if ($calibre === '') {
            throw new Exception('Informe o calibre.');
        }

        // Duplicidade
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM calibre WHERE calibre = ?");
        $stmt->execute([$calibre]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Este calibre já está cadastrado.');
        }

        $stmt = $pdo->prepare("INSERT INTO calibre (calibre) VALUES (?)");
        $stmt->execute([$calibre]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Calibre cadastrado com sucesso.';
        $response['tabela'] = renderTabela($pdo, $usuarioId);
    }

    // ---------- EDITAR ----------
    elseif ($action === 'edit') {

        if (!pode($usuarioId, 'calibre', 'editar')) {
            throw new Exception('Você não tem permissão para editar calibres.');
        }

        $id = (int) ($_POST['calibre_id'] ?? 0);
        $calibre = trim($_POST['calibre'] ?? '');

        if ($id <= 0 || $calibre === '') {
            throw new Exception('Dados inválidos para edição.');
        }

        // Duplicidade ignorando o próprio ID
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM calibre 
            WHERE calibre = ? AND calibre_id <> ?
        ");
        $stmt->execute([$calibre, $id]);

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Já existe outro calibre com esse nome.');
        }

        $stmt = $pdo->prepare("UPDATE calibre SET calibre = ? WHERE calibre_id = ?");
        $stmt->execute([$calibre, $id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Calibre atualizado com sucesso.';
        $response['tabela'] = renderTabela($pdo, $usuarioId);
    }

    // ---------- EXCLUIR ----------
    elseif ($action === 'delete') {

        if (!pode($usuarioId, 'calibre', 'excluir')) {
            throw new Exception('Você não tem permissão para excluir calibres.');
        }

        $id = (int) ($_POST['calibre_id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        $stmt = $pdo->prepare("DELETE FROM calibre WHERE calibre_id = ?");
        $stmt->execute([$id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Calibre excluído com sucesso.';
        $response['tabela'] = renderTabela($pdo, $usuarioId);
    } else {
        throw new Exception('Ação inválida.');
    }

} catch (Exception $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);