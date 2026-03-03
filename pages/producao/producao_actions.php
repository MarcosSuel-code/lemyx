<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();

    // =========================
    // INSERIR PRODUÇÃO
    // =========================
    $stmtProducao = $pdo->prepare("
        INSERT INTO producao (peso_medio, status)
        VALUES (?, ?)
    ");

    // Aqui vamos calcular peso_medio_final antes de salvar
    $pesoMedioFinal = null;

    // Calcular soma das MPs
    $totalMP = 0;
    if (!empty($data['mp'])) {
        $stmtVolume = $pdo->prepare("SELECT quantidade FROM volumes WHERE volume_id = ?");
        foreach ($data['mp'] as $mp) {
            $stmtVolume->execute([$mp['volume_id']]);
            $quantidadeVolume = $stmtVolume->fetchColumn();
            $quantidadeCaixa = (float) ($mp['quantidade_caixa'] ?? 0);
            $totalMP += $quantidadeVolume * $quantidadeCaixa;
        }
    }

    // PA
    if (empty($data['pa']) || count($data['pa']) !== 1) {
        throw new Exception('A produção deve conter exatamente 1 produto acabado (PA).');
    }

    $pa = $data['pa'][0];
    $unidadePA = strtolower($pa['unidade'] ?? '');
    $caixasPA = (float) ($pa['quantidade_caixa'] ?? 0);
    $quantidadePA = (float) ($pa['quantidade'] ?? 0);

    // Calcular peso médio final de acordo com a unidade
    if ($unidadePA === 'kg') {
        $pesoMedioFinal = $caixasPA > 0 ? ($totalMP / $caixasPA) : 0;
    } elseif ($unidadePA === 'un') {
        $pesoMedioFinal = $quantidadePA > 0 ? ($totalMP / $quantidadePA) : 0;
    }

    // Salvar produção
    $stmtProducao->execute([
        $pesoMedioFinal,
        $data['status'] ?? 'EM PRODUCAO'
    ]);
    $producao_id = $pdo->lastInsertId();

    // =========================
    // INSERIR MP
    // =========================
    if (!empty($data['mp'])) {
        $stmtMP = $pdo->prepare("
            INSERT INTO producao_mp
            (producao_id, produto_id, volume_id, quantidade_caixa, peso_liquido)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data['mp'] as $mp) {
            $stmtVolume->execute([$mp['volume_id']]);
            $quantidadeVolume = $stmtVolume->fetchColumn();
            $quantidadeCaixa = (float) ($mp['quantidade_caixa'] ?? 0);
            $pesoLiquido = $quantidadeVolume * $quantidadeCaixa;

            $stmtMP->execute([
                $producao_id,
                $mp['produto_id'],
                $mp['volume_id'],
                $quantidadeCaixa,
                $pesoLiquido
            ]);
        }
    }

    // =========================
    // INSERIR PA
    // =========================
    $stmtPA = $pdo->prepare("
        INSERT INTO producao_pa
        (producao_id, produto_id, quantidade_caixa, quantidade, peso_medio)
        VALUES (?, ?, ?, ?, ?)
    ");

    $pesoMedioPA = $caixasPA > 0 ? ($quantidadePA / $caixasPA) : 0; // peso médio da linha PA

    $stmtPA->execute([
        $producao_id,
        $pa['produto_id'],
        $caixasPA,
        $quantidadePA,
        $pesoMedioPA
    ]);

    $pdo->commit();

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Produção salva com sucesso',
        'producao_id' => $producao_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
}
