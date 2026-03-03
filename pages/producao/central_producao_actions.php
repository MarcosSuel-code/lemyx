<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Payload inválido']);
    exit;
}

// EXCLUSÃO
if (($data['acao'] ?? '') === 'excluir') {
    $id = (int) ($data['producao_pa_id'] ?? 0);
    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM producao_mp WHERE producao_pa_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM producao_pa WHERE producao_pa_id = ?")->execute([$id]);
        $pdo->commit();
        echo json_encode(['sucesso' => true]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
        exit;
    }
}

// SALVAR / ATUALIZAR PRODUÇÃO
try {
    $pdo->beginTransaction();

    $producaoId = $data['producao_pa_id'] ?? null;
    $mediaFinal = (float) ($data['media_final'] ?? 0);
    $status = $data['status'] ?? 'EM PRODUCAO';
    $paList = $data['pa'] ?? [];
    $mpList = $data['mp'] ?? [];

    if (empty($paList) || empty($paList[0]['produto_id'])) {
        throw new Exception('Produto Acabado não informado.');
    }

    $paItem = $paList[0];

    // calcula peso_medio automaticamente se não enviado
    $pesoMedio = isset($paItem['peso_medio']) && $paItem['peso_medio'] > 0
        ? (float) $paItem['peso_medio']
        : (($paItem['quantidade_caixa'] > 0) ? ((float) $paItem['quantidade'] / (float) $paItem['quantidade_caixa']) : 0);

    if ($pesoMedio <= 0) {
        throw new Exception('Peso médio do PA inválido.');
    }

    // soma MP
    $totalMP = 0;
    foreach ($mpList as $mp) {
        if (empty($mp['volume_id']) || empty($mp['quantidade_caixa']))
            continue;
        $stmt = $pdo->prepare("SELECT quantidade FROM volumes WHERE volume_id = ?");
        $stmt->execute([$mp['volume_id']]);
        $quantidadeBase = (float) $stmt->fetchColumn();
        $totalMP += $quantidadeBase * (float) $mp['quantidade_caixa'];
    }

    // valida media_final com tolerância de 0.05
    $calculado = (strtolower($paItem['unidade']) === 'kg')
        ? ($paItem['quantidade_caixa'] > 0 ? $totalMP / $paItem['quantidade_caixa'] : 0)
        : ($paItem['quantidade'] > 0 ? $totalMP / $paItem['quantidade'] : 0);

    if (abs($mediaFinal - $calculado) > 0.05) {
        throw new Exception('Peso médio final inconsistente com a matéria-prima.');
    }

    // INSERT / UPDATE PA
    if ($producaoId) {
        $stmt = $pdo->prepare("
            UPDATE producao_pa
            SET produto_id = ?, quantidade_caixa = ?, quantidade = ?, peso_medio = ?, media_final = ?, status = ?
            WHERE producao_pa_id = ?
        ");
        $stmt->execute([
            $paItem['produto_id'],
            $paItem['quantidade_caixa'],
            $paItem['quantidade'],
            $pesoMedio,
            $mediaFinal,
            $status,
            $producaoId
        ]);

        $pdo->prepare("DELETE FROM producao_mp WHERE producao_pa_id = ?")->execute([$producaoId]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO producao_pa (produto_id, quantidade_caixa, quantidade, peso_medio, media_final, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $paItem['produto_id'],
            $paItem['quantidade_caixa'],
            $paItem['quantidade'],
            $pesoMedio,
            $mediaFinal,
            $status
        ]);
        $producaoId = $pdo->lastInsertId();
    }

    // INSERT MP
    $stmt = $pdo->prepare("INSERT INTO producao_mp (producao_pa_id, produto_id, volume_id, quantidade_caixa) VALUES (?, ?, ?, ?)");
    foreach ($mpList as $mp) {
        if (empty($mp['produto_id']) || empty($mp['volume_id']))
            continue;
        $stmt->execute([$producaoId, $mp['produto_id'], $mp['volume_id'], $mp['quantidade_caixa']]);
    }

    $pdo->commit();
    echo json_encode(['sucesso' => true, 'mensagem' => 'Produção salva com sucesso.', 'producao_pa_id' => $producaoId]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    exit;
}
