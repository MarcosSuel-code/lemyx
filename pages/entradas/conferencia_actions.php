<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
header('Content-Type: application/json');

$response = [
    'sucesso' => false,
    'mensagem' => '',
    'tabela' => ''
];

$action = $_POST['action'] ?? '';

try {
    // ---------------- VERIFICA STATUS DO PRODUTO ----------------
    $entrada_produtos_id = $_POST['entrada_produtos_id'] ?? null;

    if ($entrada_produtos_id) {
        $stmtStatus = $pdo->prepare("
            SELECT e.status
            FROM entrada_produtos ep
            JOIN entradas e ON e.entrada_id = ep.entradas_id
            WHERE ep.entrada_produtos_id = ?
        ");
        $stmtStatus->execute([$entrada_produtos_id]);
        $statusProduto = strtoupper($stmtStatus->fetchColumn() ?? '');

        if ($statusProduto === 'CONCLUIDO' && in_array($action, ['add', 'edit', 'delete'])) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Não é possível alterar conferências de produtos concluídos.',
                'tabela' => ''
            ]);
            exit;
        }
    }

    switch ($action) {

        // ---------------- NOVA CONFERÊNCIA ----------------
        case 'add':
            $usuario_id = (int) $_POST['usuario_id'];
            $quantidade_cx = $_POST['quantidade_cx'];
            $peso_bruto = $_POST['peso_bruto'];
            $peso_caixa = $_POST['peso_caixa'] ?? 0;
            $peso_operacional = $_POST['peso_operacional'] ?? 0;

            $peso_liquido = $peso_bruto - ($peso_operacional + ($quantidade_cx * $peso_caixa));
            $peso_medio = $quantidade_cx > 0 ? ($peso_liquido / $quantidade_cx) : 0;

            // Próximo pallet
            $stmtPallet = $pdo->prepare("
                SELECT COALESCE(MAX(pallet), 0) 
                FROM conferencia 
                WHERE entrada_produtos_id = ?
            ");
            $stmtPallet->execute([$entrada_produtos_id]);
            $novoPallet = ((int) $stmtPallet->fetchColumn()) + 1;

            $stmt = $pdo->prepare("
                INSERT INTO conferencia 
                (entrada_produtos_id, usuario_id, pallet, quantidade_cx, peso_bruto, peso_caixa, peso_operacional, peso_liquido, peso_medio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $entrada_produtos_id,
                $usuario_id,
                $novoPallet,
                $quantidade_cx,
                $peso_bruto,
                $peso_caixa,
                $peso_operacional,
                $peso_liquido,
                $peso_medio
            ]);

            $response['sucesso'] = true;
            $response['mensagem'] = "Conferência adicionada (Pallet nº {$novoPallet}).";
            break;

        // ---------------- EDITAR CONFERÊNCIA ----------------
        case 'edit':
            $conferencia_id = (int) $_POST['conferencia_id'];
            $usuario_id = (int) $_POST['usuario_id'];
            $quantidade_cx = $_POST['quantidade_cx'];
            $peso_bruto = $_POST['peso_bruto'];
            $peso_caixa = $_POST['peso_caixa'] ?? 0;
            $peso_operacional = $_POST['peso_operacional'] ?? 0;

            $peso_liquido = $peso_bruto - ($peso_operacional + ($quantidade_cx * $peso_caixa));
            $peso_medio = $quantidade_cx > 0 ? ($peso_liquido / $quantidade_cx) : 0;

            $stmt = $pdo->prepare("
                UPDATE conferencia 
                SET usuario_id=?, quantidade_cx=?, peso_bruto=?, peso_caixa=?, peso_operacional=?, peso_liquido=?, peso_medio=? 
                WHERE conferencia_id=?
            ");
            $stmt->execute([
                $usuario_id,
                $quantidade_cx,
                $peso_bruto,
                $peso_caixa,
                $peso_operacional,
                $peso_liquido,
                $peso_medio,
                $conferencia_id
            ]);

            $response['sucesso'] = true;
            $response['mensagem'] = 'Conferência atualizada com sucesso.';
            break;

        // ---------------- EXCLUIR CONFERÊNCIA ----------------
        case 'delete':
            $conferencia_id = (int) $_POST['conferencia_id'];
            $stmt = $pdo->prepare("DELETE FROM conferencia WHERE conferencia_id=?");
            $stmt->execute([$conferencia_id]);

            $response['sucesso'] = true;
            $response['mensagem'] = 'Conferência excluída com sucesso.';
            break;

        default:
            throw new Exception('Ação inválida.');
    }

    // ---------- RECARREGA TABELA ----------
    if ($response['sucesso'] && in_array($action, ['add', 'edit', 'delete'])) {

        if (!isset($entrada_produtos_id)) {
            $stmtTmp = $pdo->prepare("SELECT entrada_produtos_id FROM conferencia WHERE conferencia_id = ?");
            $stmtTmp->execute([$conferencia_id]);
            $entrada_produtos_id = $stmtTmp->fetchColumn();
        }

        $stmt = $pdo->prepare("
            SELECT conf.*, 
                   u.nome AS usuario_nome
            FROM conferencia conf
            JOIN usuarios u ON u.usuario_id = conf.usuario_id
            WHERE conf.entrada_produtos_id = ?
            ORDER BY conf.conferencia_id ASC
        ");
        $stmt->execute([$entrada_produtos_id]);

        while ($co = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $response['tabela'] .= "
                <tr data-id='{$co['conferencia_id']}'>
                    <td>{$co['usuario_nome']}</td>
                    <td>{$co['pallet']}</td>
                    <td>{$co['quantidade_cx']}</td>
                    <td>{$co['peso_bruto']}</td>
                    <td>{$co['peso_caixa']}</td>
                    <td>{$co['peso_operacional']}</td>
                    <td>{$co['peso_liquido']}</td>
                    <td>{$co['peso_medio']}</td>
                </tr>
            ";
        }
    }

} catch (Throwable $e) {
    $response['sucesso'] = false;
    $response['mensagem'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response);
exit;
