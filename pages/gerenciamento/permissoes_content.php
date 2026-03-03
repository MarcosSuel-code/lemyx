<?php
declare(strict_types=1);

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

/* ==================================================
   AUTORIZAÇÃO
================================================== */
if (empty($_SESSION['usuario_id']) || !pode($_SESSION['usuario_id'], 'usuarios', 'editar')) {
    http_response_code(403);
    exit('<div class="alert alert-danger">Acesso negado.</div>');
}

/* ==================================================
   HELPERS
================================================== */
function e(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

/* ==================================================
   DADOS BASE
================================================== */
$usuarios = $pdo->query(
    'SELECT usuario_id, nome 
     FROM usuarios 
     WHERE ativo = 1 
     ORDER BY nome'
)->fetchAll(PDO::FETCH_ASSOC);

$telas = $pdo->query(
    'SELECT tela_id, nome 
     FROM telas 
     WHERE ativo = 1 
     ORDER BY nome'
)->fetchAll(PDO::FETCH_ASSOC);

/* ==================================================
   PERMISSÕES (QUERY ÚNICA – SEM N+1)
================================================== */
$stmt = $pdo->query(
    'SELECT 
    p.permissao_id,
    p.tela_id,
    c.chave AS chave
FROM permissoes p
JOIN chave c ON c.chave_id = p.chave_id
WHERE p.ativo = 1
ORDER BY c.chave'
);

$permissoesPorTela = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
    $permissoesPorTela[$p['tela_id']][] = $p;
}
?>

<div class="container-fluid">

    <div id="mensagemPermissoes" class="mb-3"></div>

    <h4 class="mb-4">Gerenciar Permissões</h4>

    <form id="formPermissoes" autocomplete="off">

        <div class="row mb-4">
            <div class="col-md-6 col-lg-4">
                <label class="form-label fw-semibold">Usuário</label>
                <select name="usuario_id" id="usuarioPermissao" class="form-select" required>
                    <option value="">Selecione um usuário</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int) $u['usuario_id'] ?>">
                            <?= e($u['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 30%">Tela</th>
                        <th>Permissões</th>
                    </tr>
                </thead>
                <tbody>

                    <?php foreach ($telas as $t): ?>
                        <tr>
                            <td class="fw-semibold">
                                <?= e($t['nome']) ?>
                            </td>
                            <td>
                                <?php if (!empty($permissoesPorTela[$t['tela_id']])): ?>
                                    <?php foreach ($permissoesPorTela[$t['tela_id']] as $p): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input chkPermissao" type="checkbox" name="permissoes[]"
                                                value="<?= (int) $p['permissao_id'] ?>" id="perm_<?= (int) $p['permissao_id'] ?>">
                                            <label class="form-check-label" for="perm_<?= (int) $p['permissao_id'] ?>">
                                                <?= e($p['chave']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Nenhuma permissão</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-success px-4">
                Salvar permissões
            </button>
        </div>

    </form>
</div>
<script>
    (() => {

        const form = $('#formPermissoes');
        const usuarioSelect = $('#usuarioPermissao');
        const msg = $('#mensagemPermissoes');
        const checkboxes = $('.chkPermissao');

        const alertar = (texto, tipo = 'info') => {
            msg.html(`<div class="alert alert-${tipo}">${texto}</div>`);
            setTimeout(() => msg.empty(), 4000);
        };

        const limpar = () => checkboxes.prop('checked', false);

        /* =========================
           Carregar permissões
        ========================= */
        usuarioSelect.on('change', function () {
            const usuarioId = this.value;
            limpar();

            if (!usuarioId) return;

            $.getJSON(
                '../pages/gerenciamento/permissoes_usuario.php',
                { usuario_id: usuarioId }
            )
                .done(res => {
                    if (!res.sucesso) {
                        alertar(res.mensagem, 'danger');
                        return;
                    }

                    res.permissoes.forEach(id => {
                        $(`#perm_${id}`).prop('checked', true);
                    });
                })
                .fail(() => {
                    alertar('Erro ao carregar permissões', 'danger');
                });
        });

        /* =========================
           Salvar permissões
        ========================= */
        form.on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: '../pages/gerenciamento/permissoes_actions.php',
                type: 'POST',
                data: form.serialize(),
                dataType: 'json'
            })
                .done(res => {
                    alertar(res.mensagem, res.sucesso ? 'success' : 'danger');
                })
                .fail(xhr => {
                    console.error(xhr.responseText);
                    alertar('Erro interno ao salvar permissões', 'danger');
                });
        });

    })();
</script>