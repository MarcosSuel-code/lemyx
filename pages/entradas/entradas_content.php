<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

// 🔒 Verifica permissão para visualizar a tela
if (!pode($_SESSION['usuario_id'], 'entradas', 'visualizar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

// Lista de entradas
$sql = "
    SELECT 
        e.entrada_id,
        e.parceiro_id,
        p.nome AS parceiro_nome,
        e.data_entrada,
        e.chegada,
        e.status
    FROM entradas e
    JOIN parceiros p ON p.parceiro_id = e.parceiro_id
    WHERE 1 = 1
";

$params = [];

if (!empty($_GET['data_inicio'])) {
    $sql .= " AND e.data_entrada >= :data_inicio";
    $params[':data_inicio'] = $_GET['data_inicio'];
}

if (!empty($_GET['data_fim'])) {
    $sql .= " AND e.data_entrada <= :data_fim";
    $params[':data_fim'] = $_GET['data_fim'];
}

if (!empty($_GET['status'])) {
    $sql .= " AND e.status = :status";
    $params[':status'] = $_GET['status'];
}

$sql .= " ORDER BY p.nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entradas = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Lista de parceiros
$listaParceiros = $pdo->query("SELECT * FROM parceiros ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Permissões do usuário
$podeVisualizar = pode($_SESSION['usuario_id'], 'entrada_produtos', 'visualizar');
$podeCriar = pode($_SESSION['usuario_id'], 'entradas', 'criar');
$podeEditar = pode($_SESSION['usuario_id'], 'entradas', 'editar');
$podeExcluir = pode($_SESSION['usuario_id'], 'entradas', 'excluir');
$podeConcluir = pode($_SESSION['usuario_id'], 'entradas', 'concluir');

?>

<div id="mensagemEntrada" class="mt-2"></div>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0">Entradas</h2>
        <small class="small">Registro e acompanhamento de entradas de parceiros</small>
    </div>

    <!-- Botão Nova Entrada -->

    <?php if ($podeCriar): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNovaEntrada">
            + Nova Entrada
        </button>
    <?php endif; ?>

</div>

<!-- Filtro para data e status -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>Filtros</strong>
        </div>

        <form id="formFiltroEntradas" class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data_inicio" class="form-control" value="<?= $_GET['data_inicio'] ?? '' ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Data final</label>
                <input type="date" name="data_fim" class="form-control" value="<?= $_GET['data_fim'] ?? '' ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="PENDENTE" <?= ($_GET['status'] ?? '') === 'PENDENTE' ? 'selected' : '' ?>>Pendente
                    </option>
                    <option value="CONCLUIDO" <?= ($_GET['status'] ?? '') === 'CONCLUIDO' ? 'selected' : '' ?>>Concluido
                    </option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de Entradas -->
<h4 class="text-align: justify">Entradas cadastradas</h4>
<div class="d-flex justify-content-end gap-2 mb-2" id="acoesTabela">

    <button class="btn btn-sm btn-info" id="acaoProdutos" disabled>
        📦 Produtos
    </button>

    <?php if ($podeConcluir): ?>
        <button class="btn btn-sm btn-success" id="acaoConcluir" disabled>✔ Concluir</button>
    <?php endif; ?>


    <?php if ($podeEditar): ?>
        <button class="btn btn-sm btn-warning" id="acaoEditar" disabled>✏️ Editar</button>
    <?php endif; ?>

    <?php if ($podeExcluir): ?>
        <button class="btn btn-sm btn-danger" id="acaoExcluir" disabled>🗑️ Excluir</button>
    <?php endif; ?>
</div>

<div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
    <table class="table table-striped" id="tabelaEntradas">
        <thead style="position: sticky; top: 0; z-index: 2;">
            <tr>
                <th>Nº Entrada</th>
                <th>Codigo</th>
                <th>Parceiro</th>
                <th>Data do pedido</th>
                <th>Chegada</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entradas as $e): ?>
                <tr data-id="<?= $e['entrada_id'] ?>" data-parceiro-id="<?= $e['parceiro_id'] ?>"
                    data-data="<?= $e['data_entrada'] ?>" data-chegada="<?= $e['chegada'] ?>"
                    data-status="<?= strtoupper($e['status'] ?? 'PENDENTE') ?>">
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
                        <span class="status <?= strtoupper($e['status']) ?>">
                            <?= strtoupper($e['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>

    </table>
</div>

<!-- Modal de Nova Entrada -->
<div class="modal fade" id="modalNovaEntrada" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formNovaEntrada">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Nova Entrada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Código do Parceiro</label>
                        <input type="text" id="codigoParceiro" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Parceiro</label>
                        <select name="parceiro_id" id="parceiroSelect" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($listaParceiros as $forn): ?>
                                <option value="<?= $forn['parceiro_id'] ?>" data-codigo="<?= $forn['parceiro_id'] ?>">
                                    <?= htmlspecialchars($forn['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Data da Entrada</label>
                        <input type="date" class="form-control" name="data_entrada" required
                            value="<?= date('Y-m-d') ?>">
                    </div>
                    <input type="hidden" name="status" value="PENDENTE">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar Entrada</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Entrada -->
<div class="modal fade" id="modalEditarEntrada" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarEntrada">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="entrada_id" id="editarEntradaId">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Entrada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label>Código do Parceiro</label>
                        <input type="text" id="editarCodigoParceiro" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Parceiro</label>
                        <select class="form-select" name="parceiro_id" id="editarParceiro" required>
                            <option value="">Selecione</option>
                            <?php
                            foreach ($listaParceiros as $f) {
                                echo '<option value="' . $f['parceiro_id'] . '" data-codigo="' . $f['parceiro_id'] . '">' . htmlspecialchars($f['nome']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Data da Entrada</label>
                        <input type="date" class="form-control" name="data_entrada" id="editarDataEntrada" required>
                    </div>
                    <div class="mb-3">
                        <label>Data/Hora da Chegada</label>
                        <input type="datetime-local" class="form-control" id="editarChegada" readonly>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>

    $(document).ready(function () {

        let entradaSelecionada = null;

        // ------------ Nova Entrada - sincroniza código e select --------------
        $('#codigoParceiro').on('input', function () {
            const codigo = $(this).val().trim();
            $('#parceiroSelect option').each(function () {
                $(this).prop('selected', $(this).data('codigo') == codigo);
            });
        });
        $('#parceiroSelect').on('change', function () {
            $('#codigoParceiro').val($(this).find('option:selected').data('codigo') || '');
        });

        // -------------- Atualização do from de filtros -------------
        $('#formFiltroEntradas').on('submit', function (e) {
            e.preventDefault();
            reaplicarFiltros();
        });

        // ----------- Editar Entrada - sincroniza código e select -------------
        $('#editarCodigoParceiro').on('input', function () {
            const codigo = $(this).val().trim();
            $('#editarParceiro option').each(function () {
                $(this).prop('selected', $(this).data('codigo') == codigo);
            });
        });
        $('#editarParceiro').on('change', function () {
            $('#editarCodigoParceiro').val($(this).find('option:selected').data('codigo') || '');
        });

        // ---------- Nova Entrada ----------
        $('#formNovaEntrada').submit(function (e) {
            e.preventDefault();

            $.post('../pages/entradas/entradas_actions.php', $(this).serialize(), function (res) {

                $('#mensagemEntrada').html(
                    `<div class="alert ${res.sucesso ? 'alert-success' : 'alert-danger'}">${res.mensagem}</div>`
                );

                if (res.sucesso) {
                    $('#modalNovaEntrada').modal('hide');
                    $('#formNovaEntrada')[0].reset();

                    reaplicarFiltros();
                    atualizarBotoesAcoes();
                }

            }, 'json');
        });

        // ---------- EDITAR ENTRADA ----------
        $('#acaoEditar').on('click', function () {

            if (!entradaSelecionada) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhuma entrada selecionada',
                    text: 'Selecione uma entrada para editar.'
                });
                return;
            }

            Swal.fire({
                title: 'Editar entrada?',
                text: 'Você realmente deseja alterar os dados desta entrada.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, editar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (!result.isConfirmed) return;

                const row = $('#tabelaEntradas tr.selecionada');

                const parceiroId = row.data('parceiro-id');
                const dataEntrada = row.data('data');
                const chegada = row.data('chegada');
                const status = row.data('status');

                $('#editarEntradaId').val(entradaSelecionada);
                $('#editarParceiro').val(parceiroId);
                $('#editarCodigoParceiro').val(parceiroId);
                $('#editarDataEntrada').val(dataEntrada);

                if (chegada) {
                    $('#editarChegada').val(
                        chegada.replace(' ', 'T').substring(0, 16)
                    );
                } else {
                    $('#editarChegada').val('');
                }

                $('#modalEditarEntrada').modal('show');

                reaplicarFiltros();
                atualizarBotoesAcoes();
            });

        });
        $('#formEditarEntrada').submit(function (e) {
            e.preventDefault();

            const data = $(this).serialize();

            $.post('../pages/entradas/entradas_actions.php', data, function (res) {
                $('#mensagem').html(`<div class="alert alert-info">${res.mensagem}</div>`);
                if (res.sucesso) {
                    $('#tabelaEntradas tbody').html(res.tabela);
                    $('#modalEditarEntrada').modal('hide');
                }
            }, 'json');
        });

        // ---------- EXCLUIR ENTRADA ----------
        $('#acaoExcluir').on('click', function () {

            if (!entradaSelecionada) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nenhuma entrada selecionada',
                    text: 'Selecione uma entrada para excluir.'
                });
                return;
            }

            Swal.fire({
                title: 'Confirmar exclusão?',
                text: 'Esta ação excluirá a entrada e todos os produtos vinculados.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.post('../pages/entradas/entradas_actions.php', {
                    action: 'delete',
                    entrada_id: entradaSelecionada
                }, function (res) {

                    if (res.sucesso) {

                        $('#tabelaEntradas tbody').html(res.tabela);

                        entradaSelecionada = null;
                        $('#acoesTabela button').prop('disabled', true);

                        Swal.fire({
                            icon: 'success',
                            title: 'Excluído!',
                            text: res.mensagem,
                            timer: 1800,
                            showConfirmButton: false
                        });
                        reaplicarFiltros();
                        atualizarBotoesAcoes();

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: res.mensagem
                        });
                    }

                }, 'json');
            });
        });

        // ---------- CONCLUIR ENTRADA ----------
        $('#acaoConcluir').on('click', function () {

            if (!entradaSelecionada) return;

            Swal.fire({
                title: 'Confirmar conclusão',
                text: 'Deseja marcar esta entrada como CONCLUÍDA?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, concluir',
                cancelButtonText: 'Cancelar'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.post('../pages/entradas/entradas_actions.php', {
                    action: 'concluir',
                    entrada_id: entradaSelecionada
                }, function (res) {

                    if (res.sucesso) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Concluída',
                            text: res.mensagem,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        $('#tabelaEntradas tbody').html(res.tabela);
                        entradaSelecionada = null;
                        $('#acoesTabela button').prop('disabled', true);

                        reaplicarFiltros();
                        atualizarBotoesAcoes();

                    } else {
                        Swal.fire('Erro', res.mensagem, 'error');
                    }

                }, 'json');
            });
        });

        // ---------- IR PARA PAGINA DE PRODUTOS ----------
        $('#acaoProdutos').on('click', function () {
            if (!entradaSelecionada) return;
            const entradaId = $(this).data('entrada');
            const produtoId = $(this).data('produto');
            const parceiroNome = $('#tabelaEntradas tr.selecionada td:nth-child(3)').text();
            const url = `../pages/entradas/entrada_produtos_content.php?entrada_id=${entradaSelecionada}`;

            abrirAba(url, parceiroNome);
        });

        // ---------- SELECIONAR LINHA ----------
        $(document).on('click', '#tabelaEntradas tbody tr', function () {

            $('#tabelaEntradas tbody tr').removeClass('selecionada');
            $(this).addClass('selecionada');

            entradaSelecionada = $(this).data('id');
            const status = $(this).data('status');

            $('#acoesTabela button').prop('disabled', false);

            $('#acaoConcluir').prop('disabled', status === 'CONCLUIDO');
            $('#acaoEditar').prop('disabled', status === 'CONCLUIDO');
            $('#acaoExcluir').prop('disabled', status === 'CONCLUIDO');

        });

        // ---------- ATUALIZAR BOTÕES ----------
        function atualizarBotoesAcoes() {

            const tr = $('#tabelaEntradas tbody tr.selecionada');

            if (!tr.length) {
                $('#acoesTabela button').prop('disabled', true);
                return;
            }

            const status = tr.data('status');

            $('#acaoProdutos').prop('disabled', false);

            const bloqueado = status === 'CONCLUIDO' || status === 'CANCELADO';

            $('#acaoEditar').prop('disabled', bloqueado);
            $('#acaoExcluir').prop('disabled', bloqueado);
            $('#acaoConcluir').prop('disabled', bloqueado);
        }

        function getFiltros() {
            const form = document.getElementById('formFiltroEntradas');
            const dados = new FormData(form);

            return {
                data_inicio: dados.get('data_inicio') || '',
                data_fim: dados.get('data_fim') || '',
                status: dados.get('status') || ''
            };
        }

        function reaplicarFiltros() {
            const params = $('#formFiltroEntradas').serialize();

            $('#tabelaEntradas tbody').load(
                '../pages/entradas/entradas_content.php?' + params + ' #tabelaEntradas tbody > *',
                function () {
                    entradaSelecionada = null;
                    $('#acoesTabela button').prop('disabled', true);
                }
            );
        }

        const EntradasUI = {

            resetAcoes() {
                entradaSelecionada = null;
                $('#acoesTabela button').prop('disabled', true);
            },

            atualizarTabela(html) {
                $('#tabelaEntradas tbody').html(html);
                this.resetAcoes();
            }

        };

    });

</script>