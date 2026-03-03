<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';

header('Content-Type: application/json');

$response = [
    'sucesso' => false,
    'mensagem' => '',
    'tabela' => ''
];

$action = $_POST['action'] ?? null;

try {

    if (!$action) {
        throw new Exception('Ação não informada.');
    }

    /*
    |--------------------------------------------------------------------------
    | REGISTRAR CHEGADA (PORTARIA)
    |--------------------------------------------------------------------------
    */
    if ($action === 'registrar_chegada') {

        $entradaId = (int) ($_POST['entrada_id'] ?? 0);

        if (!$entradaId) {
            throw new Exception('Entrada inválida.');
        }

        $stmt = $pdo->prepare("
            UPDATE entradas
               SET chegada = NOW(),
                   status  = 'CONCLUIDO'
             WHERE entrada_id = ?
               AND chegada IS NULL
        ");
        $stmt->execute([$entradaId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Chegada já registrada ou entrada inexistente.');
        }

        $response['sucesso'] = true;
        $response['mensagem'] = 'Chegada registrada com sucesso.';
    }

    /*
    |--------------------------------------------------------------------------
    | ADICIONAR
    |--------------------------------------------------------------------------
    */ elseif ($action === 'add') {

        $parceiro_id = (int) ($_POST['parceiro_id'] ?? 0);
        $data_entrada = $_POST['data_entrada'] ?? null;
        $status = 'PENDENTE';

        if (!$parceiro_id || !$data_entrada) {
            throw new Exception('Dados obrigatórios não informados.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO entradas (parceiro_id, data_entrada, status)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$parceiro_id, $data_entrada, $status]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Entrada cadastrada com sucesso.';
    }

    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */ elseif ($action === 'edit') {

        $entrada_id = (int) ($_POST['entrada_id'] ?? 0);
        $parceiro_id = (int) ($_POST['parceiro_id'] ?? 0);
        $data_entrada = $_POST['data_entrada'] ?? null;

        if (!$entrada_id || !$parceiro_id || !$data_entrada) {
            throw new Exception('Dados obrigatórios não informados.');
        }

        // Busca status atual
        $check = $pdo->prepare("
        SELECT status FROM entradas WHERE entrada_id = ?
    ");
        $check->execute([$entrada_id]);
        $statusAtual = strtoupper($check->fetchColumn());

        if (in_array($statusAtual, ['CONCLUIDO'])) {
            throw new Exception('Entrada concluída, não podem ser editadas.');
        }

        $stmt = $pdo->prepare("
        UPDATE entradas
           SET parceiro_id = ?,
               data_entrada  = ?
         WHERE entrada_id = ?
    ");
        $stmt->execute([$parceiro_id, $data_entrada, $entrada_id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Entrada atualizada com sucesso.';
    } /*
|--------------------------------------------------------------------------
| CONCLUIR
|--------------------------------------------------------------------------
*/ elseif ($action === 'concluir') {

        $entrada_id = (int) ($_POST['entrada_id'] ?? 0);

        if (!$entrada_id) {
            throw new Exception('Entrada inválida.');
        }

        // 1) Verifica se existem produtos vinculados
        $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM entrada_produtos 
        WHERE entradas_id = ?
    ");
        $stmt->execute([$entrada_id]);
        $totalProdutos = (int) $stmt->fetchColumn();

        if ($totalProdutos === 0) {
            throw new Exception('Não é possível concluir: a entrada não possui produtos vinculados.');
        }

        // 2) Verifica se existe algum produto NÃO concluído
        $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM entrada_produtos 
        WHERE entradas_id = ?
          AND status <> 'CONCLUIDO'
    ");
        $stmt->execute([$entrada_id]);
        $produtosPendentes = (int) $stmt->fetchColumn();

        if ($produtosPendentes > 0) {
            throw new Exception('Não é possível concluir: existem produtos pendentes.');
        }

        // 3) Conclui a entrada
        $stmt = $pdo->prepare("
        UPDATE entradas
           SET status = 'CONCLUIDO'
         WHERE entrada_id = ?
           AND status = 'PENDENTE'
    ");
        $stmt->execute([$entrada_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('A entrada já foi concluída.');
        }

        $response['sucesso'] = true;
        $response['mensagem'] = 'Entrada concluída com sucesso.';

    }/*
|--------------------------------------------------------------------------
| EXCLUIR
|--------------------------------------------------------------------------
*/ elseif ($action === 'delete') {

        $entrada_id = (int) ($_POST['entrada_id'] ?? 0);

        if (!$entrada_id) {
            throw new Exception('ID inválido.');
        }

        $check = $pdo->prepare("SELECT status FROM entradas WHERE entrada_id = ?");
        $check->execute([$entrada_id]);
        $statusAtual = strtoupper($check->fetchColumn());

        if ($statusAtual === 'CONCLUIDO') {
            throw new Exception('Entradas concluídas não podem ser excluídas.');
        }

        // Excluir produtos vinculados
        $stmt = $pdo->prepare("
        DELETE FROM entrada_produtos
        WHERE entradas_id = ?
    ");
        $stmt->execute([$entrada_id]);

        // Excluir entrada
        $stmt = $pdo->prepare("
        DELETE FROM entradas
        WHERE entrada_id = ?
    ");
        $stmt->execute([$entrada_id]);

        $response['sucesso'] = true;
        $response['mensagem'] = 'Entrada excluída com sucesso.';
    } else {
        throw new Exception('Ação inválida.');
    }

    /*
    |--------------------------------------------------------------------------
    | RECARREGA TABELA
    |--------------------------------------------------------------------------
    */
    $data_inicio = $_POST['data_inicio'] ?? null;
    $data_fim = $_POST['data_fim'] ?? null;
    $status = $_POST['status'] ?? null;

    $sql = "
    SELECT 
        e.entrada_id,
        e.parceiro_id,
        pa.nome AS parceiro_nome,
        e.data_entrada,
        e.chegada,
        e.status
    FROM entradas e
    JOIN parceiros pa ON pa.parceiro_id = e.parceiro_id
    WHERE 1 = 1
";

    $params = [];

    if ($data_inicio) {
        $sql .= " AND e.data_entrada >= ?";
        $params[] = $data_inicio;
    }

    if ($data_fim) {
        $sql .= " AND e.data_entrada <= ?";
        $params[] = $data_fim;
    }

    if ($status) {
        $sql .= " AND e.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY pa.nome ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    foreach ($entradas as $e):
        $status = strtoupper($e['status'] ?? 'PENDENTE');
        ?>
        <tr data-id="<?= $e['entrada_id'] ?>" data-parceiro-id="<?= $e['parceiro_id'] ?>"
            data-data="<?= $e['data_entrada'] ?>" data-chegada="<?= $e['chegada'] ?>" data-status="<?= $status ?>">
            <td><?= $e['entrada_id'] ?></td>
            <td><?= $e['parceiro_id'] ?></td>
            <td><?= htmlspecialchars($e['parceiro_nome']) ?></td>
            <td><?= date('d/m/Y', strtotime($e['data_entrada'])) ?></td>
            <td>
                <?= $e['chegada']
                    ? date('d/m/Y H:i:s', strtotime($e['chegada']))
                    : '-' ?>
            </td>
            <td class="text-center">
                <span class="status <?= $status ?>">
                    <?= $status ?>
                </span>
            </td>
        </tr>
        <?php
    endforeach;

    $response['tabela'] = ob_get_clean();

} catch (Throwable $e) {
    $response['mensagem'] = $e->getMessage();
}

echo json_encode($response);
