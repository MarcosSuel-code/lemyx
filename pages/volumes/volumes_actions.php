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

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Sessão expirada. Faça login novamente.'
    ]);
    exit;
}

$usuarioId = $_SESSION['usuario_id'];
$action = $_POST['action'] ?? '';

/*
|--------------------------------------------------------------------------
| FUNÇÃO: GERAR TABELA
|--------------------------------------------------------------------------
*/
function gerarTabelaVolumes(PDO $pdo): string
{
    $stmt = $pdo->query("
        SELECT volume_id, volume, tipo, quantidade
        FROM volumes
        ORDER BY volume_id ASC
    ");

    $volumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$volumes) {
        return '<tr><td colspan="5" class="text-center">Nenhum volume cadastrado.</td></tr>';
    }

    $html = '';

    foreach ($volumes as $v) {
        $html .= '<tr>';
        $html .= '<td>' . (int) $v['volume_id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($v['volume']) . '</td>';
        $html .= '<td>' . htmlspecialchars($v['tipo']) . '</td>';
        $html .= '<td>' . htmlspecialchars($v['quantidade']) . '</td>';
        $html .= '
            <td class="text-center">
                <button class="btn btn-sm btn-warning btn-edit"
                    data-id="' . $v['volume_id'] . '"
                    data-tipo="' . htmlspecialchars($v['tipo']) . '"
                    data-quantidade="' . $v['quantidade'] . '">
                    Editar
                </button>
                <button class="btn btn-sm btn-danger btn-delete"
                    data-id="' . $v['volume_id'] . '">
                    Excluir
                </button>
            </td>
        ';
        $html .= '</tr>';
    }

    return $html;
}

try {

    switch ($action) {

        /*
        |--------------------------------------------------------------------------
        | CADASTRAR
        |--------------------------------------------------------------------------
        */
        case 'add':

            if (!pode($usuarioId, 'volumes', 'criar')) {
                throw new Exception('Você não tem permissão para cadastrar volumes.');
            }

            $tipo = trim($_POST['tipo'] ?? '');
            $quantidade = (float) ($_POST['quantidade'] ?? 0);

            if ($tipo === '' || $quantidade <= 0) {
                throw new Exception('Informe corretamente o tipo e a quantidade.');
            }

            $volume = $tipo . ' ' . $quantidade;

            $stmt = $pdo->prepare("
                INSERT INTO volumes (volume, tipo, quantidade)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$volume, $tipo, $quantidade]);

            $response['sucesso'] = true;
            $response['mensagem'] = 'Volume cadastrado com sucesso.';
            break;

        /*
        |--------------------------------------------------------------------------
        | EDITAR
        |--------------------------------------------------------------------------
        */
        case 'edit':

            if (!pode($usuarioId, 'volumes', 'editar')) {
                throw new Exception('Você não tem permissão para editar volumes.');
            }

            $volumeId = (int) ($_POST['volume_id'] ?? 0);
            $tipo = trim($_POST['tipo'] ?? '');
            $quantidade = (float) ($_POST['quantidade'] ?? 0);

            if ($volumeId <= 0) {
                throw new Exception('Volume não encontrado.');
            }

            if ($tipo === '' || $quantidade <= 0) {
                throw new Exception('Preencha corretamente o tipo e a quantidade.');
            }

            $volume = $tipo . ' ' . $quantidade;

            $stmt = $pdo->prepare("
                UPDATE volumes
                SET volume = ?, tipo = ?, quantidade = ?
                WHERE volume_id = ?
            ");
            $stmt->execute([$volume, $tipo, $quantidade, $volumeId]);

            $response['sucesso'] = true;
            $response['mensagem'] = 'Volume atualizado com sucesso.';
            break;

        /*
        |--------------------------------------------------------------------------
        | EXCLUIR
        |--------------------------------------------------------------------------
        */
        case 'delete':

            if (!pode($usuarioId, 'volumes', 'excluir')) {
                throw new Exception('Você não tem permissão para excluir volumes.');
            }

            $volumeId = (int) ($_POST['volume_id'] ?? 0);

            if ($volumeId <= 0) {
                throw new Exception('Volume não encontrado ou inválido.');
            }

            $stmt = $pdo->prepare("DELETE FROM volumes WHERE volume_id = ?");
            $stmt->execute([$volumeId]);

            $response['sucesso'] = true;
            $response['mensagem'] = 'Volume excluído com sucesso.';
            break;

        default:
            throw new Exception('Ação inválida. Recarregue a página e tente novamente.');
    }

    if ($response['sucesso']) {
        $response['tabela'] = gerarTabelaVolumes($pdo);
    }

/*
|--------------------------------------------------------------------------
| TRATAMENTO DE ERROS PDO (BANCO)
|--------------------------------------------------------------------------
*/
} catch (PDOException $e) {

    // ERRO DE DUPLICIDADE (UNIQUE)
    if (isset($e->errorInfo[1]) && $e->errorInfo[1] === 1062) {
        $response['mensagem'] = 'Já existe um volume cadastrado com essas informações.';
    } else {
        $response['mensagem'] = 'Erro ao processar a operação no banco de dados.';
    }

/*
|--------------------------------------------------------------------------
| ERROS DE REGRA / PERMISSÃO
|--------------------------------------------------------------------------
*/
} catch (Exception $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);