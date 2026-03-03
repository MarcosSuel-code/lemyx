<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

if (!pode($_SESSION['usuario_id'], 'chaves', 'editar')) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acesso negado.</div>';
    exit;
}

// Dados existentes
$chaves = $pdo->query("SELECT * FROM chave ORDER BY chave")->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="mensagemChaves"></div>

<h4 class="mb-3">Gerenciar Chaves</h4>

<div class="mb-3">
    <button class="btn btn-primary" id="btnNovaChave">Nova Chave</button>
</div>

<form id="formChave" style="display:none;" class="mb-4">
    <input type="hidden" name="chave_id">
    <div class="mb-3">
        <label class="form-label">Chave (identificador)</label>
        <input type="text" name="chave" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Salvar Chave</button>
    <button type="button" class="btn btn-secondary" id="btnCancelarChave">Cancelar</button>
</form>

<div class="table-responsive" style="max-height:50vh;">
    <table class="table table-bordered align-middle">
        <thead >
            <tr>
                <th>Chave</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaChaves">
            <?php foreach ($chaves as $c): ?>
                <tr data-id="<?= $c['chave_id'] ?>">
                    <td><?= htmlspecialchars($c['chave']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning btn-editar-chave">Editar</button>
                        <button class="btn btn-sm btn-danger btn-excluir-chave">Excluir</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    (() => {
        const msg = $('#mensagemChaves');
        const formChave = $('#formChave');

        function mostrarMensagem(texto, tipo = 'info') {
            msg.html(`<div class="alert alert-${tipo}">${texto}</div>`).fadeIn();
            setTimeout(() => msg.fadeOut(), 3000);
        }

        $('#btnNovaChave').click(() => formChave.show());
        $('#btnCancelarChave').click(() => formChave.hide());

        formChave.on('submit', function (e) {
            e.preventDefault();
            $.post('../pages/gerenciamento/chaves_actions.php', formChave.serialize(), function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) {
                    $('#tabelaChaves').html(res.tabelaChaves);
                    formChave[0].reset();
                    formChave.hide();
                }
            }, 'json');
        });

        $('#tabelaChaves').on('click', '.btn-editar-chave', function () {
            const tr = $(this).closest('tr');
            const id = tr.data('id');
            const chave = tr.find('td:eq(0)').text();

            formChave.show();
            formChave.find('[name=chave_id]').val(id);
            formChave.find('[name=chave]').val(chave);
        });

        $('#tabelaChaves').on('click', '.btn-excluir-chave', function () {
            const id = $(this).closest('tr').data('id');
            if (!confirm('Excluir esta chave?')) return;
            $.post('../pages/gerenciamento/chaves_actions.php', { action: 'delete', chave_id: id }, function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) $('#tabelaChaves').html(res.tabelaChaves);
            }, 'json');
        });
    })();
</script>