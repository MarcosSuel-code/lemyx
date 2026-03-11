<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

/*
|--------------------------------------------------------------------------
| PERMISSÕES
|--------------------------------------------------------------------------
*/
if (!pode($_SESSION['usuario_id'], 'volumes', 'visualizar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

$usuarioId = $_SESSION['usuario_id'];
$podeCriar = pode($usuarioId, 'volumes', 'criar');
$podeEditar = pode($usuarioId, 'volumes', 'editar');
$podeExcluir = pode($usuarioId, 'volumes', 'excluir');

/*
|--------------------------------------------------------------------------
| LISTAGEM
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT volume_id, volume, tipo, quantidade
    FROM volumes
    ORDER BY volume_id ASC
");
$volumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-4">
    <h2>Cadastro de Volumes</h2>
</div>

<?php if ($podeCriar): ?>
    <button class="btn btn-primary mb-3" id="btnNovoVolume">Novo Volume</button>
<?php endif; ?>

<?php if ($podeCriar): ?>
    <form id="formVolumeContainer" class="mb-4 d-none">
        <input type="hidden" name="action" value="add">

        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <input type="text" name="tipo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Quantidade</label>
            <input type="number" name="quantidade" class="form-control" step="0.01" min="0.01" required>
        </div>

        <button class="btn btn-primary">Salvar</button>
        <button type="button" class="btn btn-secondary" id="btnCancelarVolume">Cancelar</button>
    </form>
<?php endif; ?>

<div class="table-responsive" style="max-height:60vh">
    <table class="table table-striped" id="tabelaVolumes">
        <thead class="sticky-top bg-light">
            <tr>
                <th>ID</th>
                <th>Volume</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th width="180">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$volumes): ?>
                <tr>
                    <td colspan="5">Nenhum registro encontrado</td>
                </tr>
            <?php else: ?>
                <?php foreach ($volumes as $v): ?>
                    <tr>
                        <td><?= (int) $v['volume_id'] ?></td>
                        <td><?= htmlspecialchars($v['volume']) ?></td>
                        <td><?= htmlspecialchars($v['tipo']) ?></td>
                        <td><?= htmlspecialchars($v['quantidade']) ?></td>
                        <td>
                            <?php if ($podeEditar): ?>
                                <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $v['volume_id'] ?>"
                                    data-tipo="<?= htmlspecialchars($v['tipo']) ?>" data-quantidade="<?= $v['quantidade'] ?>">
                                    Editar
                                </button>
                            <?php endif; ?>

                            <?php if ($podeExcluir): ?>
                                <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $v['volume_id'] ?>">
                                    Excluir
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /* ---------- ABRIR / FECHAR FORM ---------- */
    $(document).on('click', '#btnNovoVolume', function () {
        $('#formVolumeContainer').removeClass('d-none').hide().slideDown();
    });

    $(document).on('click', '#btnCancelarVolume', function () {
        $('#formVolumeContainer').slideUp();
    });

    /* ---------- SALVAR ---------- */
    $(document).on('submit', '#formVolumeContainer', function (e) {
        e.preventDefault();

        const form = $(this);
        const tbody = $('#tabelaVolumes tbody');

        $.post('../pages/volumes/volumes_actions.php', form.serialize(), function (res) {

            if (!res.sucesso) {
                Swal.fire('Erro', res.mensagem, 'error');
                return;
            }

            Swal.fire('Sucesso', res.mensagem, 'success');
            tbody.html(res.tabela);
            form[0].reset();
            form.slideUp();

        }, 'json');
    });

    /* ---------- EXCLUIR ---------- */
    $(document).on('click', '.btn-delete', function () {

        const id = $(this).data('id');
        const tbody = $('#tabelaVolumes tbody');

        Swal.fire({
            title: 'Confirmar exclusão?',
            text: 'Esta ação não poderá ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then(result => {

            if (!result.isConfirmed) return;

            $.post('../pages/volumes/volumes_actions.php', {
                action: 'delete',
                volume_id: id
            }, function (res) {

                if (!res.sucesso) {
                    Swal.fire('Erro', res.mensagem, 'error');
                    return;
                }

                Swal.fire('Excluído', res.mensagem, 'success');
                tbody.html(res.tabela);

            }, 'json');
        });
    });

    /* ---------- EDITAR ---------- */
    $(document).on('click', '.btn-edit', function () {

        const id = $(this).data('id');
        const tipo = $(this).data('tipo');
        const quantidade = $(this).data('quantidade');
        const tbody = $('#tabelaVolumes tbody');

        Swal.fire({
            title: 'Editar Volume',
            html: `
            <input id="swalTipo" class="swal2-input" value="${tipo}">
            <input id="swalQuantidade" type="number" step="0.01" min="0.01"
                   class="swal2-input" value="${quantidade}">
        `,
            focusConfirm: false,
            showCancelButton: true,
            preConfirm: () => {
                const novoTipo = $('#swalTipo').val();
                const novaQuantidade = $('#swalQuantidade').val();
                if (!novoTipo || novaQuantidade <= 0) {
                    Swal.showValidationMessage('Preencha os campos corretamente');
                    return false;
                }
                return { novoTipo, novaQuantidade };
            }
        }).then(result => {

            if (!result.isConfirmed) return;

            $.post('../pages/volumes/volumes_actions.php', {
                action: 'edit',
                volume_id: id,
                tipo: result.value.novoTipo,
                quantidade: result.value.novaQuantidade,
                volume: result.value.novoTipo + ' ' + result.value.novaQuantidade
            }, function (res) {

                if (!res.sucesso) {
                    Swal.fire('Erro', res.mensagem, 'error');
                    return;
                }

                Swal.fire('Atualizado', res.mensagem, 'success');
                tbody.html(res.tabela);

            }, 'json');
        });
    });
</script>