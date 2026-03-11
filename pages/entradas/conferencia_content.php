<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

// 🔒 Verifica permissão para visualizar a tela
if (!pode($_SESSION['usuario_id'], 'conferencia', 'visualizar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

// RECEBE O ID CORRETO DO PRODUTO DA ENTRADA
$entrada_produtos_id = (int) ($_GET['entrada_produtos_id'] ?? 0);

if ($entrada_produtos_id <= 0) {
    die('Produto da entrada inválido.');
}

// BUSCA DADOS DO PRODUTO DA ENTRADA (CORRIGIDO)
$stmtProduto = $pdo->prepare("
    SELECT ep.*, 
           ep.entrada_produtos_id,
           p.produto_id AS produto_codigo,
           p.descricao AS produto_nome,
           c.calibre AS calibre_nome, 
           e.entrada_id,
           pa.nome AS parceiro_nome, 
           pa.parceiro_id AS parceiro_codigo
    FROM entrada_produtos ep
    JOIN produtos p ON p.produto_id = ep.produtos_id
    LEFT JOIN calibre c ON c.calibre_id = ep.calibre_id
    JOIN entradas e ON e.entrada_id = ep.entradas_id
    JOIN parceiros pa ON pa.parceiro_id = e.parceiro_id
    WHERE ep.entrada_produtos_id = ?
");
$stmtProduto->execute([$entrada_produtos_id]);
$produto = $stmtProduto->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    die('Produto da entrada inválido.');
}

// Lista de conferências com todos os campos necessários
$stmtConferencias = $pdo->prepare("
    SELECT conf.*,
           u.nome AS usuario_nome
    FROM conferencia conf
    JOIN usuarios u ON u.usuario_id = conf.usuario_id
    WHERE conf.entrada_produtos_id = ?
    ORDER BY conf.pallet DESC
");

$produtosDaEntrada = (int) ($produto['entrada_produtos_id'] ?? 0);
$stmtConferencias->execute([$produtosDaEntrada]);
$conferencias = $stmtConferencias->fetchAll(PDO::FETCH_ASSOC);

// Listas para selects
$listaUsuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Permissões do usuário
$podeEditar = pode($_SESSION['usuario_id'], 'conferencia', 'editar');
$podeExcluir = pode($_SESSION['usuario_id'], 'conferencia', 'excluir');
$podeAdicionar = pode($_SESSION['usuario_id'], 'conferencia', 'adicionar');

?>

<div id="mensagem"></div>

<!-- BOTÃO VOLTAR PARA PRODUTOS DA ENTRADA -->
<button class="mb-4 btn btn-sm btn-info btn-VoltarProdutos" data-entrada-id="<?= $produto['entrada_id'] ?>"
    data-parceiro_nome="<?= htmlspecialchars($produto['parceiro_nome']) ?>">
    ← Voltar
</button>

<div class="mb-2">
    <h2 class="fw-bold">Conferência</h2>
    <small class="text-muted">Conferencia de recebimento do produto</small>
</div>

<!-- Cabeçalho do Produto -->
<?php if ($produto): ?>
    <div class="entrada-summary shadow-sm card mb-3 p-3">
        <div class="row">
            <div class="col-md-2"><strong>Entrada:</strong> <?= $produto['entrada_id'] ?></div>
            <div class="col-md-2"><strong>Parceiro:</strong> <?= $produto['parceiro_codigo'] ?></div>
            <div class="col-md-8"><strong>Parceiro:</strong> <?= htmlspecialchars($produto['parceiro_nome']) ?></div>
            <div class="col-md-2"><strong>Código:</strong> <?= htmlspecialchars($produto['produto_codigo']) ?></div>
            <div class="col-md-5"><strong>Produto:</strong> <?= htmlspecialchars($produto['produto_nome']) ?></div>
            <div class="col-md-2"><strong>Calibre:</strong> <?= htmlspecialchars($produto['calibre_nome'] ?? '') ?></div>
            <div class="col-md-2"><strong>Status:</strong> <?= htmlspecialchars($produto['status']) ?></div>
        </div>
    </div>
<?php endif; ?>

<!-- Botões de ação fora da tabela -->
<div class="mb-3 d-flex gap-2">
    <?php if ($podeAdicionar): ?>
        <button id="novaConferencia" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNovaConferencia">
            + Nova Conferência
        </button>
    <?php endif; ?>

    <?php if ($podeEditar): ?>
        <button id="editarConferenciaBtn" class="btn btn-warning" disabled>
            ✏️ Editar
        </button>
    <?php endif; ?>

    <?php if ($podeExcluir): ?>
        <button id="excluirConferenciaBtn" class="btn btn-danger" disabled>
            🗑️ Excluir
        </button>
    <?php endif; ?>

</div>

<!-- Tabela de Conferências sem botões de ação na linha -->
<div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
    <table class="table table-striped text-center" id="tabelaConferencia">
        <thead style="position: sticky; top: 0; z-index: 2;">
            <tr class="text-center">
                <th>Usuário</th>
                <th>Nº Pallet</th>
                <th>Quant. de caixas</th>
                <th>Peso bruto</th>
                <th>Peso das caixas</th>
                <th>Peso operacional</th>
                <th>Peso liquido</th>
                <th>Peso Médio</th>
            </tr>
        </thead>
        <tbody class="text-center">
            <?php foreach ($conferencias as $co): ?>
                <tr data-id="<?= $co['conferencia_id'] ?>" data-status="<?= $co['status'] ?? '' ?>">
                    <td><?= htmlspecialchars($co['usuario_nome'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($co['pallet'] ?? $co['entrada_produtos_id']) ?></td>
                    <td><?= htmlspecialchars($co['quantidade_cx'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($co['peso_bruto'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($co['peso_caixa'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($co['peso_operacional'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($co['peso_liquido'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($co['peso_medio'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Nova Conferência -->
<div class="modal fade" id="modalNovaConferencia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formNovaConferencia">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="entrada_produtos_id" value="<?= $entrada_produtos_id ?>">
                <input type="hidden" name="usuario_id" value="<?= $_SESSION['usuario_id'] ?>">

                <div class="modal-header">
                    <h5 class="modal-title">Nova Conferência</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- USUÁRIO LOGADO (APENAS VISUAL) -->
                    <div class="mb-3">
                        <label>Usuário</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario']) ?>"
                            readonly disabled>
                    </div>

                    <div class="mb-3">
                        <label>Quantidade CX</label>
                        <input type="number" name="quantidade_cx" class="form-control" value="" required>
                    </div>

                    <div class="mb-3">
                        <label>Peso bruto</label>
                        <input type="number" step="0.01" name="peso_bruto" class="form-control" value="" required>
                    </div>

                    <div class="mb-3">
                        <label>Peso da Caixa</label>
                        <input type="number" step="0.01" name="peso_caixa" class="form-control" value="">
                    </div>

                    <div class="mb-3">
                        <label>Peso operacional</label>
                        <input type="number" step="0.01" name="peso_operacional" class="form-control" value="">
                    </div>

                    <div class="mb-3">
                        <label>Peso liquido</label>
                        <input type="number" step="0.01" name="peso_liquido" class="form-control" value="0" readonly
                            disabled>
                    </div>

                    <div class="mb-3">
                        <label>Peso Médio</label>
                        <input type="number" step="0.01" name="peso_medio" class="form-control" value="0" readonly
                            disabled>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Fechar</button>
                    <button class="btn btn-primary" type="submit">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Conferência -->
<div class="modal fade" id="modalEditarConferencia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarConferencia">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="conferencia_id" id="editarConferenciaId">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Conferência</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Usuário</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['usuario']) ?>"
                            readonly disabled>
                        <input type="hidden" name="usuario_id" value="<?= $_SESSION['usuario_id'] ?>">
                    </div>

                    <div class="mb-3">
                        <label>Quantidade CX</label>
                        <input type="number" name="quantidade_cx" id="editarQuantidadeCx" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Peso bruto</label>
                        <input type="number" step="0.01" name="peso_bruto" id="editarQuantidadeKg" class="form-control"
                            required>
                    </div>

                    <div class="mb-3">
                        <label>Peso por Caixa</label>
                        <input type="number" step="0.01" name="peso_caixa" id="editarCaixa" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Peso operacional</label>
                        <input type="number" step="0.01" name="peso_operacional" id="editarPesoOperacional"
                            class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Peso liquido</label>
                        <input type="number" step="0.01" name="peso_liquido" id="editarPesoLiquido" class="form-control"
                            readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label>Peso Médio</label>
                        <input type="number" step="0.01" name="peso_medio" id="editarPesoMedio" class="form-control"
                            readonly disabled>

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

<script>
    $(document).ready(function () {

        // ---------- DESABILITAR NOVA CONFERÊNCIA SE STATUS CONCLUIDO ----------
        const statusProduto = "<?= $produto['status'] ?? '' ?>";
        let conferenciaSelecionadaId = null;

        if (statusProduto.toUpperCase() === 'CONCLUIDO') {
            $('#novaConferencia, #editarConferenciaBtn, #excluirConferenciaBtn').prop('disabled', true);
        }

        // ---------- FUNÇÃO DE CÁLCULO DE PESOS ----------
        function calcularPesos(prefix = '') {
            const quantidadeCx = parseFloat($(`${prefix}input[name="quantidade_cx"]`).val()) || 0;
            const pesoBruto = parseFloat($(`${prefix}input[name="peso_bruto"]`).val()) || 0;
            const pesoCaixa = parseFloat($(`${prefix}input[name="peso_caixa"]`).val()) || 0;
            const pesoOperacional = parseFloat($(`${prefix}input[name="peso_operacional"]`).val()) || 0;

            const pesoLiquido = Math.max(pesoBruto - (pesoOperacional + (quantidadeCx * pesoCaixa)), 0);
            const pesoMedio = quantidadeCx > 0 ? (pesoLiquido / quantidadeCx) : 0;

            $(`${prefix}input[name="peso_liquido"]`).val(pesoLiquido.toFixed(2));
            $(`${prefix}input[name="peso_medio"]`).val(pesoMedio.toFixed(2));
        }

        // ---------- CALCULO AUTOMÁTICO NOS MODAIS ----------
        $('#modalNovaConferencia, #modalEditarConferencia').on('input', 'input', function () {
            const prefix = $(this).closest('form').attr('id') === 'formNovaConferencia' ? '#modalNovaConferencia ' : '#modalEditarConferencia ';
            calcularPesos(prefix);
        });

        // ---------- SELEÇÃO DE LINHA ----------
        $('#tabelaConferencia tbody').on('click', 'tr', function () {
            $('#tabelaConferencia tbody tr').removeClass('table-primary');
            $(this).addClass('table-primary');
            conferenciaSelecionadaId = $(this).data('id');
            atualizarBotoesConferencia();
        });

        // ---------- EDITAR CONFERÊNCIA ----------
        $('#editarConferenciaBtn').click(function () {
            if (!conferenciaSelecionadaId) return;

            const tr = $(`tr[data-id="${conferenciaSelecionadaId}"]`);

            // Preencher modal com os dados da linha selecionada
            $('#editarConferenciaId').val(conferenciaSelecionadaId);
            $('#editarUsuario').val(tr.data('usuario') || '');
            $('#editarQuantidadeCx').val(tr.find('td:eq(2)').text());
            $('#editarQuantidadeKg').val(tr.find('td:eq(3)').text());
            $('#editarCaixa').val(tr.find('td:eq(4)').text());
            $('#editarPesoOperacional').val(tr.find('td:eq(5)').text());
            $('#editarPesoLiquido').val(tr.find('td:eq(6)').text());
            $('#editarPesoMedio').val(tr.find('td:eq(7)').text());

            $('#modalEditarConferencia').modal('show');
        });

        $('#formEditarConferencia').submit(function (e) {
            e.preventDefault();
            $.post('../pages/entradas/conferencia_actions.php', $(this).serialize(), function (res) {
                if (res.sucesso) {
                    mostrarMensagem(res.mensagem, 'success', 4000);
                } else {
                    mostrarMensagem(res.mensagem, 'danger', 4000); // 'danger' para erros
                }
                if (res.sucesso) {
                    $('#tabelaConferencia tbody').html(res.tabela);
                    $('#modalEditarConferencia').modal('hide');
                    conferenciaSelecionadaId = null;
                    $('#editarConferenciaBtn, #excluirConferenciaBtn').prop('disabled', true);
                }
            }, 'json');
            atualizarBotoesConferencia();
        });

        // ---------- NOVA CONFERÊNCIA ----------
        $('#formNovaConferencia').submit(function (e) {
            if (statusProduto.toUpperCase() === 'CONCLUIDO') {
                e.preventDefault();
                mostrarMensagem('Não é possível adicionar conferências a produtos concluídos.', 'warning', 4000);
            }
            e.preventDefault();
            $.post('../pages/entradas/conferencia_actions.php', $(this).serialize(), function (res) {
                if (res.sucesso) {
                    mostrarMensagem(res.mensagem, 'success', 4000);
                } else {
                    mostrarMensagem(res.mensagem, 'danger', 4000); // 'danger' para erros
                }
                if (res.sucesso) {
                    $('#tabelaConferencia tbody').html(res.tabela);
                    $('#modalNovaConferencia').modal('hide');
                    $('#formNovaConferencia')[0].reset();
                }
            }, 'json');
            atualizarBotoesConferencia();
        });

        // ---------- EXCLUIR COM SWEETALERT ----------
        $('#excluirConferenciaBtn').click(function () {
            if (!conferenciaSelecionadaId) return;

            Swal.fire({
                title: 'Deseja realmente excluir?',
                text: "Esta conferência será removida permanentemente!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../pages/entradas/conferencia_actions.php', { action: 'delete', conferencia_id: conferenciaSelecionadaId }, function (res) {
                        if (res.sucesso) {
                            mostrarMensagem(res.mensagem, 'success', 4000);
                        } else {
                            mostrarMensagem(res.mensagem, 'danger', 4000); // 'danger' para erros
                        }
                        if (res.sucesso) {
                            $(`tr[data-id="${conferenciaSelecionadaId}"]`).remove();
                            conferenciaSelecionadaId = null;
                            $('#editarConferenciaBtn, #excluirConferenciaBtn').prop('disabled', true);
                            Swal.fire('Excluído!', res.mensagem, 'success');
                        } else {
                            Swal.fire('Erro!', res.mensagem, 'error');
                        }
                    }, 'json');
                }
            });
            atualizarBotoesConferencia();
        });

        // ---------- VOLTAR PARA PRODUTOS NO MESMO PAINEL ----------
        $(document).on('click', '.btn-VoltarProdutos', function () {

            const entradaId = $(this).data('entradaId');
            const parceiroNome = $(this).data('parceiro_nome');

            if (!entradaId) {
                alert('Entrada não encontrada.');
                return;
            }

            const url = `../pages/entradas/entrada_produtos_content.php?entrada_id=${entradaId}`;
            const abaAtiva = $('#dynamicTabContent .tab-pane.active');

            abaAtiva.load(url, function () {
                const abaId = abaAtiva.attr('id');
                $(`#dynamicTabs a[href="#${abaId}"]`)
                    .contents()
                    .first()[0].textContent = `${parceiroNome} `;
            });
        });

        // ---------- TEMPORIZADOR DE MENSAGEM ----------
        function mostrarMensagem(texto, tipo = 'info', tempo = 3000) {
            // tipo pode ser: 'info', 'success', 'warning', 'error'
            const alertDiv = `<div class="alert alert-${tipo}">${texto}</div>`;
            $('#mensagem').html(alertDiv);

            // Remove a mensagem após X milissegundos
            setTimeout(() => {
                $('#mensagem').fadeOut(500, function () {
                    $(this).html('').show(); // limpa e reseta display
                });
            }, tempo);
        }

        function atualizarBotoesConferencia() {
            const trSelecionada = $('#tabelaConferencia tbody tr.table-primary');
            const produtoConcluido = statusProduto.toUpperCase() === 'CONCLUIDO';

            // Se não houver linha selecionada, desabilita tudo se produto concluído
            if (!trSelecionada.length) {
                $('#novaConferencia, #editarConferenciaBtn, #excluirConferenciaBtn').prop('disabled', produtoConcluido);
                return;
            }

            // Status da conferência selecionada
            const statusConferencia = (trSelecionada.data('status') || '').toUpperCase();
            const conferenciaConcluida = statusConferencia === 'CONCLUIDO';

            // Desabilita botões se o produto ou a conferência estiver concluído
            const desabilitar = produtoConcluido || conferenciaConcluida;
            $('#novaConferencia, #editarConferenciaBtn, #excluirConferenciaBtn').prop('disabled', desabilitar);
        }

    });

</script>