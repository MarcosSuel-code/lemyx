<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

if (!pode($_SESSION['usuario_id'], 'calibre_produto', 'visualizar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

$usuarioId = $_SESSION['usuario_id'];
$podeCriar = pode($usuarioId, 'calibre_produto', 'criar');
$podeExcluir = pode($usuarioId, 'calibre_produto', 'excluir');

/*
|--------------------------------------------------------------------------
| LISTAGEM
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT 
        cp.produto_id,
        cp.calibre_id,
        p.descricao AS produto,
        c.calibre AS calibre
    FROM calibre_produto cp
    JOIN produtos p ON p.produto_id = cp.produto_id
    JOIN calibre c  ON c.calibre_id  = cp.calibre_id
    ORDER BY p.descricao, c.calibre
");
$lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| COMBOS
|--------------------------------------------------------------------------
*/
$produtos = $pdo->query("
    SELECT produto_id, descricao 
    FROM produtos 
    ORDER BY descricao
")->fetchAll(PDO::FETCH_ASSOC);

$calibres = $pdo->query("
    SELECT calibre_id, calibre 
    FROM calibre
    ORDER BY calibre
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-4">
    <h2>Calibre × Produto</h2>
    <small class="text-muted">Gerencie os vínculos</small>
</div>

<?php if ($podeCriar): ?>
    <button class="btn btn-primary mb-3" id="btnNovoCalibreProduto">
        Novo vínculo
    </button>
<?php endif; ?>

<?php if ($podeCriar): ?>
    <form id="formCalibreProduto" class="mb-4 d-none">
        <input type="hidden" name="action" value="add">

        <div class="mb-3">
            <label class="form-label">Produto</label>
            <select name="produto_id" class="form-control" required>
                <option value="">Selecione</option>
                <?php foreach ($produtos as $p): ?>
                    <option value="<?= (int) $p['produto_id'] ?>">
                        <?= htmlspecialchars($p['descricao']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Calibre</label>
            <select name="calibre_id" class="form-control" required>
                <option value="">Selecione</option>
                <?php foreach ($calibres as $c): ?>
                    <option value="<?= (int) $c['calibre_id'] ?>">
                        <?= htmlspecialchars($c['calibre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn btn-success">Salvar</button>
        <button type="button" class="btn btn-secondary" id="btnCancelarCalibreProduto">
            Cancelar
        </button>
    </form>
<?php endif; ?>

<div class="table-responsive" style="max-height:60vh">
    <table class="table table-striped" id="tabelaCalibreProduto">
        <thead class="sticky-top bg-light">
            <tr>
                <th>Produto</th>
                <th>Calibre</th>
                <th width="120">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$lista): ?>
                <tr>
                    <td colspan="3">Nenhum registro encontrado</td>
                </tr>
            <?php else: ?>
                <?php foreach ($lista as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['produto']) ?></td>
                        <td><?= htmlspecialchars($l['calibre']) ?></td>
                        <td>
                            <?php if ($podeExcluir): ?>
                                <button class="btn btn-sm btn-danger btn-excluir-calibre-produto"
                                    data-produto="<?= (int) $l['produto_id'] ?>" data-calibre="<?= (int) $l['calibre_id'] ?>">
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
    // ---------- NOVO ----------
    $(document).on('click', '#btnNovoCalibreProduto', function () {
        $('#formCalibreProduto').removeClass('d-none').hide().slideDown();
    });

    // ---------- CANCELAR ----------
    $(document).on('click', '#btnCancelarCalibreProduto', function () {
        $('#formCalibreProduto').slideUp();
    });

    // ---------- SALVAR ----------
    $(document).on('submit', '#formCalibreProduto', function (e) {
        e.preventDefault();

        const form = $(this);
        const tbody = $('#tabelaCalibreProduto tbody');

        $.post('../pages/calibre/calibre_produto_actions.php', form.serialize(), function (res) {

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
    $(document).on('click', '.btn-excluir-calibre-produto', function () {

        const produto_id = $(this).data('produto');
        const calibre_id = $(this).data('calibre');
        const tbody = $('#tabelaCalibreProduto tbody');

        Swal.fire({
            title: 'Confirmar exclusão?',
            text: 'Este vínculo será removido.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then(result => {

            if (!result.isConfirmed) return;

            $.post('../pages/calibre/calibre_produto_actions.php', {
                action: 'delete',
                produto_id,
                calibre_id
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
</script>