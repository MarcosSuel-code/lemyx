<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

if (!pode($_SESSION['usuario_id'], 'calibre', 'visualizar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

$usuarioId = $_SESSION['usuario_id'];
$podeCriar = pode($usuarioId, 'calibre', 'criar');
$podeEditar = pode($usuarioId, 'calibre', 'editar');
$podeExcluir = pode($usuarioId, 'calibre', 'excluir');

$stmt = $pdo->query("SELECT calibre_id, calibre FROM calibre ORDER BY calibre ASC");
$calibres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-4">
    <h2>Cadastro de Calibres</h2>
    <small class="text-muted">Gerencie calibres de produtos</small>
</div>

<?php if ($podeCriar): ?>
    <button class="btn btn-primary mb-3" id="btnNovoCalibre">Novo Calibre</button>
<?php endif; ?>

<?php if ($podeCriar): ?>
    <form id="formCalibreContainer" class="mb-4 d-none">
        <input type="hidden" name="action" value="add">

        <div class="mb-3">
            <label class="form-label">Calibre</label>
            <input type="text" name="calibre" class="form-control" required>
        </div>

        <button class="btn btn-primary">Salvar</button>
        <button type="button" class="btn btn-secondary" id="btnCancelarCalibre">Cancelar</button>
    </form>
<?php endif; ?>

<div class="table-responsive" style="max-height:60vh">
    <table class="table table-striped" id="tabelaCalibre">
        <thead class="sticky-top bg-light">
            <tr>
                <th width="80">ID</th>
                <th>Calibre</th>
                <th width="160">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($calibres as $c): ?>
                <tr>
                    <td><?= (int) $c['calibre_id'] ?></td>
                    <td><?= htmlspecialchars($c['calibre']) ?></td>
                    <td>
                        <?php if ($podeEditar): ?>
                            <button class="btn btn-sm btn-warning btn-edit-calibre" data-id="<?= $c['calibre_id'] ?>"
                                data-calibre="<?= htmlspecialchars($c['calibre']) ?>">
                                Editar
                            </button>
                        <?php endif; ?>
                        <?php if ($podeExcluir): ?>
                            <button class="btn btn-sm btn-danger btn-delete-calibre" data-id="<?= $c['calibre_id'] ?>">
                                Excluir
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).on('click', '#btnNovoCalibre', function () {
        const form = $('#formCalibreContainer');
        form.removeClass('d-none').hide().slideDown();
    });

    $(document).on('click', '#btnCancelarCalibre', function () {
        $('#formCalibreContainer').slideUp();
    });

    // ---------- SALVAR ----------
    $(document).on('submit', '#formCalibreContainer', function (e) {
        e.preventDefault();

        const form = $(this);
        const tbody = $('#tabelaCalibre tbody');

        $.post('../pages/calibre/calibre_actions.php', form.serialize(), function (res) {

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

    // ---------- EXCLUIR ----------
    $(document).on('click', '.btn-delete-calibre', function () {

        const id = $(this).data('id');
        const tbody = $('#tabelaCalibre tbody');

        Swal.fire({
            title: 'Confirmar exclusão?',
            text: 'Esta ação não poderá ser desfeita.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then(result => {

            if (!result.isConfirmed) return;

            $.post('../pages/calibre/calibre_actions.php', {
                action: 'delete',
                calibre_id: id
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

    // ---------- EDITAR ----------
    $(document).on('click', '.btn-edit-calibre', function () {

        const id = $(this).data('id');
        const calibre = $(this).data('calibre');
        const tbody = $('#tabelaCalibre tbody');

        Swal.fire({
            title: 'Editar calibre',
            input: 'text',
            inputValue: calibre,
            showCancelButton: true,
            confirmButtonText: 'Salvar',
            cancelButtonText: 'Cancelar',
            inputValidator: value => !value && 'Informe o calibre'
        }).then(result => {

            if (!result.isConfirmed) return;

            $.post('../pages/calibre/calibre_actions.php', {
                action: 'edit',
                calibre_id: id,
                calibre: result.value
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