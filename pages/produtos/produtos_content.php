<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

// 🔒 Permissão para acessar a tela
requirePermissao('produtos', 'visualizar');

// Permissões
$permCriar = pode($_SESSION['usuario_id'], 'produtos', 'criar');
$permEditar = pode($_SESSION['usuario_id'], 'produtos', 'editar');
$permExcluir = pode($_SESSION['usuario_id'], 'produtos', 'excluir');

// Categorias
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

// Produtos
$stmt = $pdo->query("
    SELECT 
        p.produto_id,
        p.descricao,
        p.categoria_id,
        c.nome AS categoria_nome
    FROM produtos p
    LEFT JOIN categorias c ON c.categoria_id = p.categoria_id
    ORDER BY p.descricao ASC
");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="mensagemProduto"></div>

<h2 class="mb-4">Cadastro de Produtos</h2>

<?php if ($permCriar): ?>
    <div class="mb-4">
        <button class="btn btn-primary" id="btnNovoProduto">Novo Produto</button>
    </div>
<?php endif; ?>

<?php if ($permCriar): ?>
    <form id="formProdutoContainer" class="mb-4" style="display:none;">
        <input type="hidden" name="action" value="add">

        <div class="mb-3">
            <label class="form-label">Descrição</label>
            <input type="text" class="form-control" name="descricao" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoria</label>
            <select class="form-control" name="categoria_id">
                <option value="">Sem categoria</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['categoria_id'] ?>">
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Salvar</button>
            <button type="button" class="btn btn-secondary" id="btnCancelarProduto">Cancelar</button>
        </div>
    </form>
<?php endif; ?>

<h4>Produtos cadastrados</h4>

<div class="table-responsive" style="max-height:60vh; overflow-y:auto;">
    <table class="table table-striped" id="tabelaProdutos">
        <thead class="table-light" style="position: sticky; top:0; z-index:2;">
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= $p['produto_id'] ?></td>
                    <td><?= htmlspecialchars($p['descricao']) ?></td>
                    <td><?= htmlspecialchars($p['categoria_nome'] ?? 'Sem categoria') ?></td>
                    <td class="text-center">
                        <?php if ($permEditar): ?>
                            <button class="btn btn-sm btn-warning btn-edit" data-id="<?= $p['produto_id'] ?>"
                                data-descricao="<?= htmlspecialchars($p['descricao']) ?>"
                                data-categoria="<?= $p['categoria_id'] ?>">
                                Editar
                            </button>
                        <?php endif; ?>

                        <?php if ($permExcluir): ?>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $p['produto_id'] ?>">
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
    $(function () {
        const form = $('#formProdutoContainer');
        const mensagem = $('#mensagemProduto');
        const tabela = $('#tabelaProdutos tbody');

        function mostrarMensagem(texto, tipo = 'info', tempo = 3000) {
            mensagem.html(`
            <div class="alert alert-${tipo} alert-dismissible fade show">
                ${texto}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);

            setTimeout(() => {
                mensagem.find('.alert').alert('close');
            }, tempo);
        }

        // ---------- ABRIR FORMULÁRIO ----------
        $('#btnNovoProduto').click(() => {
            form[0].reset();
            form.slideDown();
        });

        // ---------- CANCELAR FORMULÁRIO ----------
        $('#btnCancelarProduto').click(() => {
            form.slideUp();
            form[0].reset();
        });

        // ---------- SALVAR NOVO PRODUTO ----------
        form.on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: '../pages/produtos/produtos_actions.php',
                method: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success(res) {
                    mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');

                    if (res.sucesso) {
                        tabela.html(res.tabela);
                        form.slideUp();
                        form[0].reset();
                    }
                }
            });
        });

        // ---------- EXCLUIR PRODUTO ----------
        $(document).on('click', '.btn-delete', function () {
            const id = $(this).data('id');
            if (!confirm('Deseja excluir este produto?')) return;

            $.post('../pages/produtos/produtos_actions.php', {
                action: 'delete',
                produto_id: id
            }, function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) tabela.html(res.tabela);
            }, 'json');
        });

        // ---------- EDITAR COM MODAL ----------
        $('#tabelaProdutos').on('click', '.btn-edit', function () {
            const id = $(this).data('id');
            const descricaoAtual = $(this).data('descricao');
            const categoriaAtual = $(this).data('categoria');

            let modalHtml = `
        <div class="modal fade" id="editProdutoModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Produto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Descrição</label>
                            <input type="text" id="editProdutoDescricao" class="form-control" value="${descricaoAtual}">
                        </div>
                        <div class="mb-3">
                            <label>Categoria</label>
                            <select id="editProdutoCategoria" class="form-control">
                                <option value="">Sem categoria</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['categoria_id'] ?>" ${categoriaAtual == <?= $c['categoria_id'] ?> ? 'selected' : ''}>
                                        <?= htmlspecialchars($c['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="mensagemEditProduto" class="mb-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="saveEditProduto">Salvar</button>
                    </div>
                </div>
            </div>
        </div>
        `;

            $('body').append(modalHtml);
            let modalEl = document.getElementById('editProdutoModal');
            let editModal = new bootstrap.Modal(modalEl);
            editModal.show();

            $('#saveEditProduto').click(function () {
                const novaDescricao = $('#editProdutoDescricao').val().trim();
                const novaCategoria = $('#editProdutoCategoria').val();

                if (!novaDescricao) {
                    $('#mensagemEditProduto').html('<div class="alert alert-danger">Preencha a descrição do produto</div>').fadeIn();
                    setTimeout(() => $('#mensagemEditProduto').fadeOut(), 3000);
                    return;
                }

                $.post('../pages/produtos/produtos_actions.php',
                    { action: 'edit', produto_id: id, descricao: novaDescricao, categoria_id: novaCategoria },
                    function (res) {
                        $('#mensagemEditProduto').html(`<div class="alert alert-${res.sucesso ? 'success' : 'danger'}">${res.mensagem}</div>`).fadeIn();
                        setTimeout(() => $('#mensagemEditProduto').fadeOut(), 3000);

                        if (res.sucesso) {
                            $('#tabelaProdutos tbody').html(res.tabela);
                            editModal.hide();
                            $('#editProdutoModal').remove();
                        }
                    }, 'json');
            });

            $(modalEl).on('hidden.bs.modal', function () {
                $(this).remove();
            });
        });

    });
</script>