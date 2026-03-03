<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

// 🔒 Permissão para acessar a TELA
requirePermissao('usuarios', 'visualizar');

// Permissões de ação
$permCriar = pode($_SESSION['usuario_id'], 'usuarios', 'criar');
$permEditar = pode($_SESSION['usuario_id'], 'usuarios', 'editar');
$permExcluir = pode($_SESSION['usuario_id'], 'usuarios', 'excluir');

// Lista usuários
$usuarios = $pdo->query("
    SELECT usuario_id, nome, usuario, email, perfil, ativo
    FROM usuarios
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="mensagemUsuarios"></div>

<h4 class="mb-3">Cadastro de Usuários</h4>

<?php if ($permCriar): ?>
    <button class="btn btn-primary mb-3" id="btnNovoUsuario">
        Novo Usuário
    </button>
<?php endif; ?>

<!-- FORM -->
<?php if ($permCriar || $permEditar): ?>
    <form id="formUsuario" class="card p-3 mb-4" style="display:none">
        <input type="hidden" name="action" id="usuarioAction" value="add">
        <input type="hidden" name="usuario_id" id="usuarioId">

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Nome</label>
                <input type="text" name="nome" id="nome" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Usuário</label>
                <input type="text" name="usuario" id="usuario" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" id="email" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label">Perfil</label>
                <select name="perfil" id="perfil" class="form-select">
                    <option value="usuario">Usuário</option>
                    <option value="admin">Admin</option>
                    <option value="outro">Outro</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Senha</label>
                <input type="password" name="senha" id="senha" class="form-control">
                <small class="text-muted">Deixe em branco para não alterar</small>
            </div>

            <div class="col-md-2">
                <label class="form-label">Ativo</label>
                <select name="ativo" id="ativo" class="form-select">
                    <option value="1">Sim</option>
                    <option value="0">Não</option>
                </select>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-success">Salvar</button>
            <button type="button" class="btn btn-secondary" id="btnCancelarUsuario">Cancelar</button>
        </div>
    </form>
<?php endif; ?>

<!-- TABELA -->
<div class="table-responsive">
    <table class="table table-striped align-middle" id="tabelaUsuarios">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>Usuário</th>
                <th>Email</th>
                <th>Perfil</th>
                <th>Status</th>
                <th width="160">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome']) ?></td>
                    <td><?= htmlspecialchars($u['usuario']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= ucfirst($u['perfil']) ?></td>
                    <td><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                    <td>
                        <?php if ($permEditar): ?>
                            <button class="btn btn-sm btn-warning btn-edit" data-json='<?= json_encode($u) ?>'>
                                Editar
                            </button>
                        <?php endif; ?>

                        <?php if ($permExcluir): ?>
                            <button class="btn btn-sm btn-danger btn-delete" data-id="<?= $u['usuario_id'] ?>">
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

        const form = $('#formUsuario');
        const msg = $('#mensagemUsuarios');

        function mensagem(texto, tipo = 'info') {
            msg.html(`<div class="alert alert-${tipo}">${texto}</div>`).fadeIn();
            setTimeout(() => msg.fadeOut(), 3000);
        }

        $('#btnNovoUsuario').on('click', () => {
            form[0].reset();
            $('#usuarioAction').val('add');
            $('#usuarioId').val('');
            $('#senha').prop('required', true);
            form.show();
        });

        $('#btnCancelarUsuario').on('click', () => {
            form.hide();
        });

        // EDITAR
        $('.btn-edit').on('click', function () {
            const u = $(this).data('json');

            $('#usuarioAction').val('edit');
            $('#usuarioId').val(u.usuario_id);
            $('#nome').val(u.nome);
            $('#usuario').val(u.usuario);
            $('#email').val(u.email);
            $('#perfil').val(u.perfil);
            $('#ativo').val(u.ativo);
            $('#senha').val('').prop('required', false);

            form.show();
        });

        // SALVAR
        form.on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: '../pages/gerenciamento/usuarios_actions.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success(res) {
                    mensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                    if (res.sucesso) location.reload();
                },
                error(xhr) {
                    console.error(xhr.responseText);
                    mensagem('Erro ao salvar usuário', 'danger');
                }
            });
        });

        // EXCLUIR
        $('.btn-delete').on('click', function () {
            if (!confirm('Deseja excluir este usuário?')) return;

            $.post('../pages/gerenciamento/usuarios_actions.php', {
                action: 'delete',
                usuario_id: $(this).data('id')
            }, res => {
                mensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) location.reload();
            }, 'json');
        });

    })();
</script>