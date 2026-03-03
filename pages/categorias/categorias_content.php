<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

requirePermissao('categorias', 'visualizar');

$permCriar = pode($_SESSION['usuario_id'], 'categorias', 'criar');
$permEditar = pode($_SESSION['usuario_id'], 'categorias', 'editar');
$permExcluir = pode($_SESSION['usuario_id'], 'categorias', 'excluir');

// Lista de categorias
$stmt = $pdo->query("SELECT * FROM categorias ORDER BY categoria_id ASC");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="mensagemCategoria"></div>

<div class="mb-4">
    <h2>Cadastro de Categorias</h2>
</div>

<?php if ($permCriar): ?>
    <div class="mb-4">
        <button class="btn btn-primary" id="btnNovaCategoria">Nova Categoria</button>
    </div>
<?php endif; ?>

<?php if ($permCriar): ?>
    <form id="formCategoria" class="mb-4" style="display:none;">
        <input type="hidden" name="action" value="add">

        <div class="mb-3">
            <label class="form-label">Nome da Categoria</label>
            <input type="text" class="form-control" name="nome" required>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Salvar</button>
            <button type="button" class="btn btn-secondary" id="btnCancelarCategoria">Cancelar</button>
        </div>
    </form>
<?php endif; ?>

<h4>Categorias cadastradas</h4>

<div class="table-responsive" style="max-height:60vh; overflow-y:auto;">
    <table class="table table-striped" id="tabelaCategorias">
        <thead class="table-light" style="position: sticky; top:0; z-index:2;">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categorias as $cat): ?>
                <tr>
                    <td><?= $cat['categoria_id'] ?></td>
                    <td><?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-center">

                        <?php if ($permEditar): ?>
                            <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $cat['categoria_id'] ?>"
                                data-nome="<?= htmlspecialchars($cat['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                Editar
                            </button>
                        <?php endif; ?>

                        <?php if ($permExcluir): ?>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $cat['categoria_id'] ?>">
                                Excluir
                            </button>
                        <?php endif; ?>

                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    (() => {

        const form = $('#formCategoria');
        const tabela = $('#tabelaCategorias tbody');
        const mensagem = $('#mensagemCategoria');

        function alertar(texto, tipo = 'info', tempo = 3000) {
            mensagem
                .html(`<div class="alert alert-${tipo}">${texto}</div>`)
                .fadeIn();

            setTimeout(() => mensagem.fadeOut(), tempo);
        }

        // abrir formulário
        $('#btnNovaCategoria').on('click', () => {
            form[0].reset();
            form.slideDown();
        });

        // cancelar
        $('#btnCancelarCategoria').on('click', () => {
            form.slideUp();
            form[0].reset();
        });

        // cadastrar
        form.on('submit', function (e) {
            e.preventDefault();

            $.post(
                '../pages/categorias/categorias_actions.php',
                form.serialize(),
                res => {
                    alertar(res.mensagem, res.sucesso ? 'success' : 'danger');
                    if (res.sucesso) {
                        tabela.html(res.tabela);
                        form.slideUp();
                        form[0].reset();
                    }
                },
                'json'
            );
        });

        // excluir (delegado)
        $('#tabelaCategorias').on('click', '.btn-delete', function () {
            const id = $(this).data('id');

            if (!confirm('Deseja excluir esta categoria?')) return;

            $.post(
                '../pages/categorias/categorias_actions.php',
                { action: 'delete', categoria_id: id },
                res => {
                    alertar(res.mensagem, res.sucesso ? 'success' : 'danger');
                    if (res.sucesso) tabela.html(res.tabela);
                },
                'json'
            );
        });

        // editar
        $('#tabelaCategorias').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            const nome = $(this).data('nome');

            const modalHtml = `
        <div class="modal fade" id="modalEditCategoria">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Categoria</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="text" class="form-control" id="editNomeCategoria" value="${nome}">
                        <div id="msgEditCategoria" class="mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" id="salvarEditCategoria">Salvar</button>
                    </div>
                </div>
            </div>
        </div>`;

            $('body').append(modalHtml);

            const modal = new bootstrap.Modal($('#modalEditCategoria')[0]);
            modal.show();

            $('#salvarEditCategoria').on('click', () => {
                const novoNome = $('#editNomeCategoria').val().trim();
                if (!novoNome) return;

                $.post(
                    '../pages/categorias/categorias_actions.php',
                    { action: 'edit', categoria_id: id, nome: novoNome },
                    res => {
                        $('#msgEditCategoria').html(
                            `<div class="alert alert-${res.sucesso ? 'success' : 'danger'}">${res.mensagem}</div>`
                        );
                        if (res.sucesso) {
                            tabela.html(res.tabela);
                            modal.hide();
                            $('#modalEditCategoria').remove();
                        }
                    },
                    'json'
                );
            });

            $('#modalEditCategoria').on('hidden.bs.modal', function () {
                $(this).remove();
            });
        });

    })();
</script>