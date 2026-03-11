<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';

/*
|--------------------------------------------------------------------------
| LISTAGEM DE PRODUÇÃO (BASE NOVA)
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        pa.producao_pa_id,
        pa.produto_id              AS codigo_pa,
        pr.descricao               AS produto_pa,
        pa.quantidade_caixa,
        pa.quantidade              AS peso_total,
        pa.peso_medio,
        pa.media_final,
        pa.status
    FROM producao_pa pa
    JOIN produtos pr ON pr.produto_id = pa.produto_id
    ORDER BY pa.producao_pa_id DESC
";

$producoes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="producao-lista-wrapper">

    <!-- NOVA PRODUÇÃO -->
    <div class="lista-header">
        <h2>Central de Produções</h2>
        <button class="btn-novaproducao">
            + Nova produção
        </button>
    </div>
    <div class="table-responsive producao-scroll">
        <table class="table table-striped" id="tabelaProducao">
            <thead  style="position: sticky; top: 0; z-index: 2;">
                <tr>
                    <th>ID Produção</th>
                    <th>Código PA</th>
                    <th>Produto</th>
                    <th>Qtd. Caixas</th>
                    <th>Peso Total (kg)</th>
                    <th>Peso Médio (kg)</th>
                    <th>Média Final</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>

                <?php if (!empty($producoes)): ?>
                    <?php foreach ($producoes as $p): ?>
                        <tr>
                            <td><?= $p['producao_pa_id'] ?></td>
                            <td><?= $p['codigo_pa'] ?></td>
                            <td><?= htmlspecialchars($p['produto_pa']) ?></td>
                            <td><?= $p['quantidade_caixa'] ?></td>
                            <td><?= number_format((float) $p['peso_total'], 2, ',', '.') ?></td>
                            <td><?= number_format((float) $p['peso_medio'], 2, ',', '.') ?></td>
                            <td><?= number_format((float) $p['media_final'], 2, ',', '.') ?></td>
                            <td>
                                <?php
                                $statusRaw = strtoupper($p['status'] ?? 'PENDENTE');
                                ?>
                                <span class="status <?= $statusRaw ?>">
                                    <?= $statusRaw ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn-acao" data-producao_pa_id="<?= $p['producao_pa_id'] ?>">
                                    Central
                                </button>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center;">
                            Nenhuma produção encontrada
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>
</div>

<script>
    // ---------- CENTRAL NO MESMO PAINEL(EDIÇÃO / VISUALIZAÇÃO) ----------
    $(document).off('click', '.btn-acao').on('click', '.btn-acao', function () {
        const producaoId = $(this).data('producao_pa_id');
        if (!producaoId) {
            console.error('ID da produção não encontrado');
            return;
        }
        const url = `../pages/producao/central_producao_content.php?producao_pa_id=${producaoId}`;
        const abaAtiva = $('#dynamicTabContent .tab-pane.active');
        abaAtiva.load(url, function () {
            const abaId = abaAtiva.attr('id');
            $(`#dynamicTabs a[href="#${abaId}"]`).contents().first()[0].textContent = `Central de produções`;
        });
    });

    // ---------- NOVA PRODUÇÃO NO MESMO PAINEL(CENTRAL VAZIA) ----------
    $(document).off('click', '.btn-novaproducao').on('click', '.btn-novaproducao', function () {
        const url = `../pages/producao/central_producao_content.php`;
        const abaAtiva = $('#dynamicTabContent .tab-pane.active');
        abaAtiva.load(url, function () {
            const abaId = abaAtiva.attr('id');
            $(`#dynamicTabs a[href="#${abaId}"]`).contents().first()[0].textContent = `Central de produções`;
        });
    });

</script>