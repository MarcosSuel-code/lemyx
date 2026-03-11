<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$response = ['sucesso' => false, 'mensagem' => '', 'tabela' => ''];

/**
 * Verifica se a entrada está aberta
 */
function validarEntradaAberta(PDO $pdo, int $entrada_id): void
{
    $stmt = $pdo->prepare("SELECT status FROM entradas WHERE entrada_id = ?");
    $stmt->execute([$entrada_id]);
    $status = $stmt->fetchColumn();

    if (!$status) {
        throw new Exception('Entrada não encontrada.');
    }

    if ($status === 'CONCLUIDO') {
        throw new Exception('Entrada concluída não pode ser alterada.');
    }
}

/**
 * Atualiza tabela de produtos
 */
function atualizarTabela(PDO $pdo, int $entrada_id): string
{
    $stmt = $pdo->prepare("
        SELECT 
            ep.entrada_produtos_id,
            ep.produtos_id,
            ep.calibre_id,
            ep.status,
            p.descricao AS produto_nome,
            p.produto_id AS codigo_produto,
            c.calibre AS calibre_nome,
            COALESCE(SUM(conf.quantidade_cx), 0) AS quantidade_cx,
            COALESCE(SUM(conf.peso_liquido), 0) AS peso_liquido,
            CASE 
                WHEN SUM(conf.quantidade_cx) > 0
                THEN ROUND(SUM(conf.peso_liquido) / SUM(conf.quantidade_cx), 2)
                ELSE 0
            END AS pesomedio
        FROM entrada_produtos ep
        JOIN produtos p ON p.produto_id = ep.produtos_id
        LEFT JOIN calibre c ON c.calibre_id = ep.calibre_id
        LEFT JOIN conferencia conf ON conf.entrada_produtos_id = ep.entrada_produtos_id
        WHERE ep.entradas_id = ?
        GROUP BY ep.entrada_produtos_id
        ORDER BY ep.entrada_produtos_id DESC
    ");
    $stmt->execute([$entrada_id]);

    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    foreach ($produtos as $p): ?>
        <tr class="linha-produto" data-id="<?= $p['entrada_produtos_id'] ?>" data-produto="<?= $p['produtos_id'] ?>"
            data-calibre="<?= $p['calibre_id'] ?>" data-status="<?= $p['status'] ?>">
            <td><?= $p['codigo_produto'] ?></td>
            <td><?= htmlspecialchars($p['produto_nome']) ?></td>
            <td><?= htmlspecialchars($p['calibre_nome'] ?? '') ?></td>
            <td><?= number_format($p['quantidade_cx'], 0, ',', '.') ?></td>
            <td><?= number_format($p['peso_liquido'], 2, ',', '.') ?></td>
            <td><?= number_format($p['pesomedio'], 2, ',', '.') ?></td>
            <td class="text-center"><?= $p['status'] ?></td>
        </tr>
    <?php endforeach;

    return ob_get_clean();
}

try {

    /* ================== ADD ================== */
    if ($action === 'add') {

        $entrada_id = (int) $_POST['entrada_id'];
        $produto_id = (int) $_POST['produto_id'];
        $calibre_id = !empty($_POST['calibre_id']) ? (int) $_POST['calibre_id'] : null;

        validarEntradaAberta($pdo, $entrada_id);

        if ($calibre_id) {
            $chk = $pdo->prepare("
                SELECT 1 FROM calibre_produto 
                WHERE produto_id = ? AND calibre_id = ?
            ");
            $chk->execute([$produto_id, $calibre_id]);
            if (!$chk->fetch()) {
                throw new Exception('Calibre inválido para o produto.');
            }
        }

        $ins = $pdo->prepare("
            INSERT INTO entrada_produtos (entradas_id, produtos_id, calibre_id, status)
            VALUES (?, ?, ?, 'PENDENTE')
        ");
        $ins->execute([$entrada_id, $produto_id, $calibre_id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto adicionado com sucesso.';
        $response['tabela'] = atualizarTabela($pdo, $entrada_id);
    }

    /* ================== EDIT ================== */ elseif ($action === 'edit') {

        $id = (int) $_POST['id'];
        $produto_id = (int) $_POST['produto_id'];
        $calibre_id = !empty($_POST['calibre_id']) ? (int) $_POST['calibre_id'] : null;

        $stmt = $pdo->prepare("
            SELECT entradas_id FROM entrada_produtos WHERE entrada_produtos_id = ?
        ");
        $stmt->execute([$id]);
        $entrada_id = (int) $stmt->fetchColumn();

        if (!$entrada_id) {
            throw new Exception('Produto não encontrado.');
        }

        validarEntradaAberta($pdo, $entrada_id);

        $upd = $pdo->prepare("
            UPDATE entrada_produtos
            SET produtos_id = ?, calibre_id = ?
            WHERE entrada_produtos_id = ?
        ");
        $upd->execute([$produto_id, $calibre_id, $id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto atualizado com sucesso.';
        $response['tabela'] = atualizarTabela($pdo, $entrada_id);
    }

    /* ================== DELETE ================== */ elseif ($action === 'delete') {

        $id = (int) $_POST['id'];

        $stmt = $pdo->prepare("
            SELECT entradas_id FROM entrada_produtos WHERE entrada_produtos_id = ?
        ");
        $stmt->execute([$id]);
        $entrada_id = (int) $stmt->fetchColumn();

        if (!$entrada_id) {
            throw new Exception('Produto não encontrado.');
        }

        validarEntradaAberta($pdo, $entrada_id);

        $del = $pdo->prepare("
            DELETE FROM entrada_produtos WHERE entrada_produtos_id = ?
        ");
        $del->execute([$id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto excluído com sucesso.';
        $response['tabela'] = atualizarTabela($pdo, $entrada_id);
    }

    /* ================== CONCLUIR ================== */ elseif ($action === 'concluir') {

        $id = (int) ($_POST['entrada_produtos_id'] ?? 0);

        if (!$id) {
            throw new Exception('Produto inválido.');
        }

        // Busca produto e entrada
        $stmt = $pdo->prepare("
        SELECT ep.status, ep.entradas_id
        FROM entrada_produtos ep
        WHERE ep.entrada_produtos_id = ?
    ");
        $stmt->execute([$id]);
        $produto = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$produto) {
            throw new Exception('Produto não encontrado.');
        }

        // Verifica se a entrada está aberta
        validarEntradaAberta($pdo, (int) $produto['entradas_id']);

        if ($produto['status'] === 'CONCLUIDO') {
            throw new Exception('Produto já está concluído.');
        }

        // 🔒 REGRA NOVA: só concluir se houver conferência
        $stmtConf = $pdo->prepare("
        SELECT COUNT(*) 
        FROM conferencia
        WHERE entrada_produtos_id = ?
    ");
        $stmtConf->execute([$id]);
        $totalConferencias = (int) $stmtConf->fetchColumn();

        if ($totalConferencias === 0) {
            throw new Exception('Não é possível concluir o produto sem conferência registrada.');
        }

        // Conclui o produto
        $upd = $pdo->prepare("
        UPDATE entrada_produtos
        SET status = 'CONCLUIDO'
        WHERE entrada_produtos_id = ?
    ");
        $upd->execute([$id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Produto concluído com sucesso.';
    } else {
        throw new Exception('Ação inválida.');
    }

} catch (Exception $e) {
    $response['sucesso'] = false;
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);
