<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

header('Content-Type: application/json; charset=utf-8');

if (!pode($_SESSION['usuario_id'], 'chaves', 'editar')) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão']);
    exit;
}

$action = $_POST['action'] ?? '';
$chave_id = (int) ($_POST['chave_id'] ?? 0);
$chave_valor = trim($_POST['chave'] ?? '');

$response = ['sucesso' => false, 'mensagem' => '', 'tabelaChaves' => ''];

try {

    if ($action === 'delete') {
        if ($chave_id) {
            $stmt = $pdo->prepare("DELETE FROM chave WHERE chave_id = ?");
            $stmt->execute([$chave_id]);
            $response['sucesso'] = true;
            $response['mensagem'] = 'Chave excluída com sucesso';
        } else {
            throw new Exception('Chave inválida');
        }
    } else {
        if (!$chave_valor) {
            throw new Exception('Preencha a chave');
        }

        // Verifica se já existe outra chave com o mesmo valor
        if ($chave_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chave WHERE chave = ? AND chave_id != ?");
            $stmt->execute([$chave_valor, $chave_id]);
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM chave WHERE chave = ?");
            $stmt->execute([$chave_valor]);
        }

        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Já existe uma chave com esse valor');
        }

        if ($chave_id) {
            // Atualiza
            $stmt = $pdo->prepare("UPDATE chave SET chave = ? WHERE chave_id = ?");
            $stmt->execute([$chave_valor, $chave_id]);
            $response['mensagem'] = 'Chave atualizada com sucesso';
        } else {
            // Insere
            $stmt = $pdo->prepare("INSERT INTO chave (chave) VALUES (?)");
            $stmt->execute([$chave_valor]);
            $response['mensagem'] = 'Chave cadastrada com sucesso';
        }

        $response['sucesso'] = true;
    }

    // Renderiza a tabela atualizada
    $chaves = $pdo->query("SELECT * FROM chave ORDER BY chave")->fetchAll(PDO::FETCH_ASSOC);
    ob_start();
    foreach ($chaves as $c) {
        echo '<tr data-id="' . $c['chave_id'] . '">';
        echo '<td>' . htmlspecialchars($c['chave']) . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-warning btn-editar-chave">Editar</button> ';
        echo '<button class="btn btn-sm btn-danger btn-excluir-chave">Excluir</button>';
        echo '</td></tr>';
    }
    $response['tabelaChaves'] = ob_get_clean();

} catch (Throwable $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);