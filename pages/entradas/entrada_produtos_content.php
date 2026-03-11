<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

// 🔒 Verifica permissão para visualizar a tela
if (!pode($_SESSION['usuario_id'], 'entrada_produtos', 'visualizar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

$entrada_id = (int) ($_GET['entrada_id'] ?? 0);

// Busca entrada
$stmtEntrada = $pdo->prepare("
    SELECT e.*, pa.nome AS parceiro_nome, pa.parceiro_id AS codigo_parceiro
    FROM entradas e
    JOIN parceiros pa ON pa.parceiro_id = e.parceiro_id
    WHERE e.entrada_id = ?
");
$stmtEntrada->execute([$entrada_id]);
$entrada = $stmtEntrada->fetch(PDO::FETCH_ASSOC);
$entradaConcluida = ($entrada && $entrada['status'] === 'CONCLUIDO');

// Produtos da entrada
$stmt = $pdo->prepare("
    SELECT 
        ep.entrada_produtos_id,
        ep.produtos_id,
        ep.calibre_id,
        ep.status,

        p.descricao AS produto_nome,
        p.produto_id AS codigo_produto,
        c.calibre AS calibre_nome,

        pa.nome AS parceiro_nome,
        pa.parceiro_id AS codigo_parceiro,

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
    JOIN entradas e ON e.entrada_id = ep.entradas_id
    JOIN parceiros pa ON pa.parceiro_id = e.parceiro_id
    LEFT JOIN conferencia conf 
           ON conf.entrada_produtos_id = ep.entrada_produtos_id
    WHERE ep.entradas_id = ?
    GROUP BY ep.entrada_produtos_id
    ORDER BY ep.entrada_produtos_id DESC
");

$stmt->execute([$entrada_id]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Listas para selects
$listaProdutos = $pdo->query("SELECT * FROM produtos ORDER BY descricao ASC")->fetchAll(PDO::FETCH_ASSOC);

// Permissões do usuário
$podeCriar = pode($_SESSION['usuario_id'], 'entrada_produtos', 'criar');
$podeEditar = pode($_SESSION['usuario_id'], 'entrada_produtos', 'editar');
$podeExcluir = pode($_SESSION['usuario_id'], 'entrada_produtos', 'excluir');
$podeConcluir = pode($_SESSION['usuario_id'], 'entrada_produtos', 'concluir');
$podeAdicionar = pode($_SESSION['usuario_id'], 'entrada_produtos', 'adicionar');

?>

<div id="mensagemEntradaProdutos" class="mt-2"></div>

<div class="mb-4">
    <h2>Produtos Entregues</h2>
    <small class=".text-muted">Produtos recebidos por este parceiro</small>
</div>

<!-- Cabeçalho da Entrada -->
<?php if ($entrada): ?>

    <div class="entrada-summary shadow-sm card mb-3 p-3">
        <div class="row">
            <div class="col-md-2"><strong>Nº Entrada: </strong> <?= $entrada['entrada_id'] ?></div>
            <div class="col-md-2"><strong>Parceiro: </strong> <?= $entrada['codigo_parceiro'] ?></div>
            <div class="col-md-4"><strong>Nome: </strong> <?= htmlspecialchars($entrada['parceiro_nome']) ?></div>
            <div class="col-md-2"><strong>Data: </strong> <?= date('d/m/Y', strtotime($entrada['data_entrada'])) ?></div>
            <div class="col-md-2"><strong>Status: </strong> <?= $entrada['status'] ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Botão Novo Produto -->
<?php if ($podeAdicionar): ?>
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalNovoProduto" <?= $entradaConcluida ? 'disabled title="Entrada concluída"' : '' ?>>
        <i class="bi bi-plus-lg"></i> Novo Produto
    </button>
<?php endif; ?>

<div class="d-flex gap-2 mb-2" id="acoesProdutos">
    <button id="btnConferencia" class="btn btn-info" disabled>
        📋 Conferência
    </button>

    <?php if ($podeConcluir): ?>
        <button id="btnConcluirProduto" class="btn btn-success" disabled>
            ✅ Concluir
        </button>
    <?php endif; ?>

    <?php if ($podeEditar): ?>
        <button id="btnEditarProduto" class="btn btn-warning" disabled>
            ✏️ Editar
        </button>
    <?php endif; ?>

    <?php if ($podeExcluir): ?>
        <button id="btnExcluirProduto" class="btn btn-danger" disabled>
            🗑️ Excluir
        </button>
    <?php endif; ?>
</div>

<!-- Tabela de Produtos -->
<div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
    <table class="table table-striped" id="tabelaEntradaProdutos">
        <thead style="position: sticky; top: 0; z-index: 2;">
            <tr>
                <th>Código</th>
                <th>Produto</th>
                <th>Calibre</th>
                <th>Quantidade CX</th>
                <th>Peso liquido</th>
                <th>Peso Médio</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr class="linha-produto" data-id="<?= $p['entrada_produtos_id'] ?>" data-produto="<?= $p['produtos_id'] ?>"
                    data-calibre="<?= $p['calibre_id'] ?>" data-status="<?= $p['status'] ?>"
                    data-produto_nome="<?= htmlspecialchars($p['produto_nome']) ?>"
                    data-parceiro_nome="<?= htmlspecialchars($p['parceiro_nome']) ?>">
                    <td><?= $p['codigo_produto'] ?></td>
                    <td><?= htmlspecialchars($p['produto_nome']) ?></td>
                    <td><?= htmlspecialchars($p['calibre_nome'] ?? '') ?></td>
                    <td><?= $p['quantidade_cx'] ?? 0 ?></td>
                    <td><?= number_format($p['peso_liquido'], 2, ',', '.') ?></td>
                    <td><?= number_format($p['pesomedio'], 2, ',', '.') ?></td>
                    <td class="text-center"><?= $p['status'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
</div>

<!-- Modal Novo Produto -->
<div class="modal fade" id="modalNovoProduto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formNovoProduto">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="entrada_id" value="<?= $entrada_id ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Produto da Entrada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Código do Produto</label>
                        <input type="text" name="codigo_produto" id="codigoProduto" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Produto</label>
                        <select name="produto_id" id="produtoSelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaProdutos as $prod): ?>
                                <option value="<?= $prod['produto_id'] ?>" data-codigo="<?= $prod['produto_id'] ?>">
                                    <?= htmlspecialchars($prod['descricao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Calibre</label>
                        <select name="calibre_id" id="calibreSelect" class="form-select">
                            <option value="">-- Nenhum --</option>
                        </select>
                    </div>
                    <small>As quantidades serão salvas como 0 por padrão.</small>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Fechar</button>
                    <button class="btn btn-primary" type="submit">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Produto -->
<div class="modal fade" id="modalEditarProduto" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarProduto">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editarProdutoId">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Produto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Produto</label>
                        <select name="produto_id" id="editarProdutoSelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaProdutos as $prod): ?>
                                <option value="<?= $prod['produto_id'] ?>"><?= htmlspecialchars($prod['descricao']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Calibre</label>
                        <select name="calibre_id" id="editarCalibreSelect" class="form-select">
                            <option value="">-- Nenhum --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Fechar</button>
                    <button class="btn btn-primary" type="submit">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function () {

        let produtoSelecionado = null;

        const entradaConcluida = <?= $entradaConcluida ? 'true' : 'false' ?>;

        if (entradaConcluida) {
            // Impede abrir modais de edição ou nova entrada
            $('#modalNovoProduto, #modalEditarProduto').on('show.bs.modal', function (e) {
                e.preventDefault();
                mostrarMensagemEntradaProdutos('Esta entrada está concluída. Nenhuma ação pode ser realizada.', 4000);
                return false;
            });
        }

        // ---------- SINCRONIZAR INPUT E SELECT ----------
        function sincronizarSelect(inputSelector, selectSelector) {
            $(inputSelector).on('input', function () {
                const valor = $(this).val().trim();
                $(selectSelector + ' option').each(function () {
                    $(this).prop('selected', $(this).data('codigo') == valor);
                });
            });

            $(selectSelector).on('change', function () {
                const codigo = $(this).find('option:selected').data('codigo') || '';
                $(inputSelector).val(codigo);
            });
        }
        sincronizarSelect('#codigoProduto', '#produtoSelect');

        // ---------- FILTRAR CALIBRES POR PRODUTO ----------
        function carregarCalibres(produtoId, selectId, calibreSelecionado = null) {

            const $select = $(selectId);
            $select.html('<option value="">-- Nenhum --</option>');

            if (!produtoId) return;

            $.getJSON('../pages/calibre/get_calibres_produto.php', { produto_id: produtoId })
                .done(function (dados) {

                    dados.forEach(c => {
                        $select.append(
                            `<option value="${c.calibre_id}">${c.calibre}</option>`
                        );
                    });

                    if (calibreSelecionado) {
                        $select.val(calibreSelecionado);
                    }
                });
        }

        $('#produtoSelect').on('change', function () {
            carregarCalibres($(this).val(), '#calibreSelect');
        });

        // ---------- SELEÇÃO DE PRODUTOS ----------
        $('#editarProdutoSelect').on('change', function () {
            atualizarCalibres($(this).val(), '#editarCalibreSelect');
        });

        // ---------- CONCLUIR CONFERENCIA ----------
        $('#btnConcluirProduto').on('click', function () {
            if (!produtoSelecionado) return;

            Swal.fire({
                title: 'Concluir produto?',
                text: 'Após concluir, não será possível editar este produto.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, concluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '../pages/entradas/entrada_produtos_actions.php',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'concluir',
                        entrada_produtos_id: produtoSelecionado.id
                    },
                    success: function (res) {
                        if (res.sucesso) {
                            Swal.fire('Concluído!', res.mensagem, 'success');

                            // Atualiza status visual na tabela
                            const $linha = $(`tr[data-id="${produtoSelecionado.id}"]`);
                            $linha.find('td:last').text('CONCLUIDO');
                            $linha.attr('data-status', 'CONCLUIDO');

                            // Atualiza o objeto selecionado
                            produtoSelecionado.status = 'CONCLUIDO';

                            // Garante que a linha continue selecionada e a função funcione
                            $('.linha-produto').removeClass('selecionada');
                            $linha.addClass('selecionada');

                            // Atualiza botões corretamente
                            atualizarBotoesAcoes();

                        } else {
                            Swal.fire('Erro', res.mensagem, 'error');
                        }
                    }
                });

            });
        });

        // ---------- ADICIONAR NOVO PRODUTO ----------
        $('#formNovoProduto').submit(function (e) {
            e.preventDefault();
            $.ajax({
                url: '../pages/entradas/entrada_produtos_actions.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    mostrarMensagemEntradaProdutos(`<div class="alert ${res.sucesso ? 'alert-success' : 'alert-danger'}">
                        ${res.mensagem}</div>`
                    );

                    if (res.sucesso) {
                        $('#tabelaEntradaProdutos tbody').html(res.tabela);
                        $('#modalNovoProduto').modal('hide');
                        $('#formNovoProduto')[0].reset();
                    }
                }
            });
            atualizarBotoesAcoes();
        });

        // ---------- ABRIR EDITAR PRODUTO ----------
        $('#btnEditarProduto').on('click', function () {

            if (!produtoSelecionado) return;

            $('#editarProdutoId').val(produtoSelecionado.id);
            $('#editarProdutoSelect').val(produtoSelecionado.produto);

            carregarCalibres(
                produtoSelecionado.produto,
                '#editarCalibreSelect',
                produtoSelecionado.calibre
            );

            $('#modalEditarProduto').modal('show');
            atualizarBotoesAcoes();
        });

        // ---------- SALVAR ALTERAÇÃO PRODUTO ----------
        $('#formEditarProduto').submit(function (e) {
            e.preventDefault();
            $.ajax({
                url: '../pages/entradas/entrada_produtos_actions.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    mostrarMensagemEntradaProdutos(`<div class="alert ${res.sucesso ? 'alert-success' : 'alert-danger'}">
                        ${res.mensagem}</div>`
                    );

                    if (res.sucesso) {
                        $('#tabelaEntradaProdutos tbody').html(res.tabela);
                        $('#modalEditarProduto').modal('hide');
                    }
                }
            });
            atualizarBotoesAcoes();
        });

        // ---------- EXCLUIR PRODUTO ----------
        $('#btnExcluirProduto').on('click', function () {

            if (!produtoSelecionado) return;

            Swal.fire({
                title: 'Excluir produto?',
                text: 'Esta ação não poderá ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.ajax({
                    url: '../pages/entradas/entrada_produtos_actions.php',
                    method: 'POST',
                    data: {
                        action: 'delete',
                        id: produtoSelecionado.id
                    },
                    dataType: 'json',
                    success: function (res) {

                        if (res.sucesso) {
                            Swal.fire('Excluído', res.mensagem, 'success');
                            $(`tr[data-id="${produtoSelecionado.id}"]`).remove();
                            produtoSelecionado = null;
                            $('#btnEditarProduto, #btnExcluirProduto, #btnConferencia').prop('disabled', true);
                        } else {
                            Swal.fire('Erro', res.mensagem, 'error');
                        }
                    }
                });

            });
            atualizarBotoesAcoes();
        });

        // ---------- ABRIR CONFERÊNCIA NO MESMO PAINEL ----------
        $('#btnConferencia').on('click', function () {
            if (!produtoSelecionado) return;

            const url = `../pages/entradas/conferencia_content.php?entrada_produtos_id=${produtoSelecionado.id}`;

            const abaAtiva = $('#dynamicTabContent .tab-pane.active');
            abaAtiva.load(url, function () {
                const abaId = abaAtiva.attr('id');
                $(`#dynamicTabs a[href="#${abaId}"]`)
                    .contents().first()[0]
                    .textContent = produtoSelecionado.parceiro_nome + ' ';
            });
        });

        // ---------- SELECIONAR LINHA ----------
        $(document).on('click', '.linha-produto', function () {

            $('.linha-produto').removeClass('table-primary selecionada'); // remove classe antiga
            $(this).addClass('table-primary selecionada'); // adiciona classe selecionada

            produtoSelecionado = $(this).data();

            atualizarBotoesAcoes(); // agora a função cuida de habilitar/desabilitar
        });

        // ---------- TEMPORIZADO DE MENSAGEM ----------
        function mostrarMensagemEntradaProdutos(html, tempo = 3000) {
            const $box = $('#mensagemEntradaProdutos');

            $box.stop(true, true).html(html).fadeIn();

            setTimeout(() => {
                $box.fadeOut(() => $box.html(''));
            }, tempo);
        }

        function atualizarBotoesAcoes() {
            const trSelecionada = $('#tabelaEntradaProdutos tbody tr.selecionada');

            if (!trSelecionada.length) {
                $('#acoesProdutos button').prop('disabled', true);
                return;
            }

            const status = trSelecionada.data('status')?.toUpperCase() || 'PENDENTE';

            $('#btnConferencia').prop('disabled', false);
            $('#btnConcluirProduto').prop('disabled', status === 'CONCLUIDO' || entradaConcluida);
            $('#btnEditarProduto').prop('disabled', status === 'CONCLUIDO' || entradaConcluida);
            $('#btnExcluirProduto').prop('disabled', status === 'CONCLUIDO' || entradaConcluida);
        }

    });

</script>