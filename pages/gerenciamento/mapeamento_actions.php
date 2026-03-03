<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = [
    'sucesso' => false,
    'mensagem' => '',
    'tabelaTelas' => '',
    'tabelaPermissoes' => ''
];

$action = $_POST['action'] ?? null;

if (!$action) {
    $response['mensagem'] = 'Ação não recebida. Verifique o JS.';
    echo json_encode($response);
    exit;
}

function renderTelas(PDO $pdo)
{
    $telas = $pdo->query("SELECT * FROM telas ORDER BY ordem")->fetchAll(PDO::FETCH_ASSOC);
    $html = '';
    foreach ($telas as $t) {
        $html .= '<tr data-id="' . $t['tela_id'] . '">
            <td>' . htmlspecialchars($t['nome']) . '</td>
            <td>' . htmlspecialchars($t['chave']) . '</td>
            <td>' . $t['ordem'] . '</td>
            <td>' . ($t['ativo'] ? 'Sim' : 'Não') . '</td>
            <td>
                <button class="btn btn-sm btn-warning btn-editar-tela">Editar</button>
                <button class="btn btn-sm btn-danger btn-excluir-tela">Excluir</button>
            </td>
        </tr>';
    }
    return $html;
}

function renderPermissoes(PDO $pdo)
{
    $permissoes = $pdo->query("
        SELECT p.*, t.nome AS tela_nome, c.chave AS chave_nome
        FROM permissoes p
        JOIN telas t ON t.tela_id = p.tela_id
        JOIN chave c ON c.chave_id = p.chave_id
        ORDER BY t.nome, c.chave
    ")->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    foreach ($permissoes as $p) {
        $html .= '<tr data-id="' . $p['permissao_id'] . '">
            <td>' . htmlspecialchars($p['tela_nome']) . '</td>
            <td>' . htmlspecialchars($p['chave_nome']) . '</td>
            <td>' . htmlspecialchars($p['descricao']) . '</td>
            <td>' . ($p['ativo'] ? 'Sim' : 'Não') . '</td>
            <td>
                <button class="btn btn-sm btn-warning btn-editar-permissao">Editar</button>
                <button class="btn btn-sm btn-danger btn-excluir-permissao">Excluir</button>
            </td>
        </tr>';
    }
    return $html;
}

try {
    // ===== TELAS =====
    if ($action === 'salvar_tela') {
        $tela_id = (int) ($_POST['tela_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $chave = trim($_POST['chave'] ?? '');
        $ordem = (int) ($_POST['ordem'] ?? 0);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!$nome || !$chave)
            throw new Exception('Nome e Chave são obrigatórios.');

        if ($tela_id) {
            $stmt = $pdo->prepare("UPDATE telas SET nome=?, chave=?, ordem=?, ativo=? WHERE tela_id=?");
            $stmt->execute([$nome, $chave, $ordem, $ativo, $tela_id]);
            $response['mensagem'] = 'Tela atualizada com sucesso';
        } else {
            $stmt = $pdo->prepare("INSERT INTO telas (nome, chave, ordem, ativo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $chave, $ordem, $ativo]);
            $response['mensagem'] = 'Tela criada com sucesso';
        }

        $response['sucesso'] = true;
        $response['tabelaTelas'] = renderTelas($pdo);
    }

    if ($action === 'delete_tela') {
        $tela_id = (int) ($_POST['tela_id'] ?? 0);
        if (!$tela_id)
            throw new Exception('ID inválido para exclusão.');
        $stmt = $pdo->prepare("DELETE FROM telas WHERE tela_id=?");
        $stmt->execute([$tela_id]);
        $response['sucesso'] = true;
        $response['mensagem'] = 'Tela excluída com sucesso';
        $response['tabelaTelas'] = renderTelas($pdo);
    }

    // ===== PERMISSÕES =====
    if ($action === 'salvar_permissao') {
        $permissao_id = (int) ($_POST['permissao_id'] ?? 0);
        $tela_id = (int) ($_POST['tela_id'] ?? 0);
        $chave_id = (int) ($_POST['chave_id'] ?? 0);
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if (!$tela_id || !$chave_id)
            throw new Exception('Tela e Chave são obrigatórios.');

        if (!$descricao) {
            $stmt = $pdo->prepare("
                SELECT 
                    t.chave AS tela_chave,
                    c.chave AS permissao_chave
                FROM telas t
                JOIN chave c ON c.chave_id = ?
                WHERE t.tela_id = ?
                 ");
            $stmt->execute([$chave_id, $tela_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Não foi possível gerar a descrição automática.');
            }

            $descricao = $row['permissao_chave'] . ' ' . $row['tela_chave'];
            $descricao = mb_convert_case($descricao, MB_CASE_TITLE, 'UTF-8');
        }

        if ($permissao_id) {
            $stmt = $pdo->prepare("UPDATE permissoes SET tela_id=?, chave_id=?, descricao=?, ativo=? WHERE permissao_id=?");
            $stmt->execute([$tela_id, $chave_id, $descricao, $ativo, $permissao_id]);
            $response['mensagem'] = 'Permissão atualizada com sucesso';
        } else {
            $stmt = $pdo->prepare("INSERT INTO permissoes (tela_id, chave_id, descricao, ativo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tela_id, $chave_id, $descricao, $ativo]);
            $response['mensagem'] = 'Permissão criada com sucesso';
        }

        $response['sucesso'] = true;
        $response['tabelaPermissoes'] = renderPermissoes($pdo);
    }

    if ($action === 'delete_permissao') {
        $permissao_id = (int) ($_POST['permissao_id'] ?? 0);
        if (!$permissao_id)
            throw new Exception('ID inválido para exclusão.');
        $stmt = $pdo->prepare("DELETE FROM permissoes WHERE permissao_id=?");
        $stmt->execute([$permissao_id]);
        $response['sucesso'] = true;
        $response['mensagem'] = 'Permissão excluída com sucesso';
        $response['tabelaPermissoes'] = renderPermissoes($pdo);
    }

} catch (PDOException $e) {
    $response['mensagem'] = 'Erro de banco de dados: ' . $e->getMessage();
} catch (Exception $e) {
    $response['mensagem'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);