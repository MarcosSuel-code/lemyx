<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

if (!pode($_SESSION['usuario_id'], 'telas', 'editar')) {
    http_response_code(403);
    echo '<div class="alert alert-danger">Acesso negado.</div>';
    exit;
}

// Dados existentes
$telas = $pdo->query("SELECT * FROM telas ORDER BY ordem")->fetchAll(PDO::FETCH_ASSOC);
$chaves = $pdo->query("SELECT * FROM chave ORDER BY chave")->fetchAll(PDO::FETCH_ASSOC);
$permissoes = $pdo->query("
    SELECT p.*, t.nome AS tela_nome, c.chave AS chave_nome
    FROM permissoes p
    JOIN telas t ON t.tela_id = p.tela_id
    JOIN chave c ON c.chave_id = p.chave_id
    ORDER BY t.nome, c.chave
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="mensagemTelas"></div>

<h4 class="mb-3">Gerenciar Telas</h4>
<div class="mb-4">
    <button class="btn btn-primary" id="btnNovaTela">Nova Tela</button>
</div>

<form id="formTela" style="display:none;" class="mb-4">
    <input type="hidden" name="tela_id">
    <div class="mb-3">
        <label class="form-label">Nome da Tela</label>
        <input type="text" name="nome" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Chave (identificador)</label>
        <input type="text" name="chave" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Ordem</label>
        <input type="number" name="ordem" class="form-control" value="0">
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" name="ativo" id="ativoTela" checked>
        <label class="form-check-label" for="ativoTela">Ativo</label>
    </div>
    <button type="submit" class="btn btn-success">Salvar Tela</button>
    <button type="button" class="btn btn-secondary" id="btnCancelarTela">Cancelar</button>
</form>

<div class="table-responsive mb-5" style="max-height:50vh;">
    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Nome</th>
                <th>Chave</th>
                <th>Ordem</th>
                <th>Ativo</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaTelas">
            <?php foreach ($telas as $t): ?>
                <tr data-id="<?= $t['tela_id'] ?>">
                    <td><?= htmlspecialchars($t['nome']) ?></td>
                    <td><?= htmlspecialchars($t['chave']) ?></td>
                    <td><?= $t['ordem'] ?></td>
                    <td><?= $t['ativo'] ? 'Sim' : 'Não' ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-warning btn-editar-tela">Editar</button>
                        <button class="btn btn-sm btn-danger btn-excluir-tela">Excluir</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h4 class="mb-3">Gerenciar Permissões</h4>
<div class="mb-4">
    <button class="btn btn-primary" id="btnNovaPermissao">Nova Permissão</button>
</div>

<form id="formPermissao" style="display:none;" class="mb-4">
    <input type="hidden" name="permissao_id">
    <div class="mb-3">
        <label class="form-label">Tela</label>
        <select name="tela_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($telas as $t): ?>
                <option value="<?= $t['tela_id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Chave</label>
        <select name="chave_id" class="form-select" required>
            <option value="">Selecione</option>
            <?php foreach ($chaves as $c): ?>
                <option value="<?= $c['chave_id'] ?>"><?= htmlspecialchars($c['chave']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Descrição</label>
        <input type="text" name="descricao" class="form-control">
        <small class="text-muted">Se vazio, será preenchido automaticamente</small>
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" name="ativo" id="ativoPermissao" checked>
        <label class="form-check-label" for="ativoPermissao">Ativo</label>
    </div>
    <button type="submit" class="btn btn-success">Salvar Permissão</button>
    <button type="button" class="btn btn-secondary" id="btnCancelarPermissao">Cancelar</button>
</form>

<div class="table-responsive" style="max-height:50vh;">
    <table class="table table-bordered align-middle">
        <thead>
            <tr>
                <th>Tela</th>
                <th>Chave</th>
                <th>Descrição</th>
                <th>Ativo</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaPermissoes">
            <?php foreach ($permissoes as $p): ?>
                <tr data-id="<?= $p['permissao_id'] ?>">
                    <td><?= htmlspecialchars($p['tela_nome']) ?></td>
                    <td><?= htmlspecialchars($p['chave_nome']) ?></td>
                    <td><?= htmlspecialchars($p['descricao']) ?></td>
                    <td><?= $p['ativo'] ? 'Sim' : 'Não' ?></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-warning btn-editar-permissao">Editar</button>
                        <button class="btn btn-sm btn-danger btn-excluir-permissao">Excluir</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    (() => {
        const msg = $('#mensagemTelas');

        function mostrarMensagem(texto, tipo = 'info') {
            msg.html(`<div class="alert alert-${tipo}">${texto}</div>`).fadeIn();
            setTimeout(() => msg.fadeOut(), 3000);
        }

        // ---------- TELAS ----------
        const formTela = $('#formTela');
        $('#btnNovaTela').click(() => formTela.show());
        $('#btnCancelarTela').click(() => {
            formTela[0].reset();   // limpa todos os campos
            formTela.find('[name=tela_id]').val(''); // limpa o ID oculto
            formTela.hide();
        });

        formTela.on('submit', function (e) {
            e.preventDefault();
            let data = formTela.serialize() + '&action=salvar_tela';
            $.post('../pages/gerenciamento/mapeamento_actions.php', data, function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) {
                    $('#tabelaTelas').html(res.tabelaTelas);
                    formTela[0].reset();
                    formTela.hide();
                }
            }, 'json').fail(function (xhr) {
                let msg = 'Erro no servidor';
                try { msg = JSON.parse(xhr.responseText).mensagem; } catch (e) { }
                mostrarMensagem(msg, 'danger');
            });
        });

        $('#tabelaTelas').on('click', '.btn-editar-tela', function () {
            const tr = $(this).closest('tr');
            formTela.show();
            formTela.find('[name=tela_id]').val(tr.data('id'));
            formTela.find('[name=nome]').val(tr.find('td:eq(0)').text());
            formTela.find('[name=chave]').val(tr.find('td:eq(1)').text());
            formTela.find('[name=ordem]').val(tr.find('td:eq(2)').text());
            formTela.find('[name=ativo]').prop('checked', tr.find('td:eq(3)').text() === 'Sim');
        });

        $('#tabelaTelas').on('click', '.btn-excluir-tela', function () {
            const id = $(this).closest('tr').data('id');
            if (!confirm('Excluir esta tela?')) return;
            $.post('../pages/gerenciamento/mapeamento_actions.php', { action: 'delete_tela', tela_id: id }, function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) $('#tabelaTelas').html(res.tabelaTelas);
            }, 'json');
        });

        // ---------- PERMISSÕES ----------
        const formPermissao = $('#formPermissao');
        $('#btnNovaPermissao').click(() => formPermissao.show());
        $('#btnCancelarPermissao').click(() => {
            formPermissao[0].reset(); // limpa todos os campos
            formPermissao.find('[name=permissao_id]').val(''); // limpa o ID oculto
            formPermissao.hide();
        });

        formPermissao.on('submit', function (e) {
            e.preventDefault();
            let data = formPermissao.serialize() + '&action=salvar_permissao';
            $.post('../pages/gerenciamento/mapeamento_actions.php', data, function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) {
                    $('#tabelaPermissoes').html(res.tabelaPermissoes);
                    formPermissao[0].reset();
                    formPermissao.hide();
                }
            }, 'json').fail(function (xhr) {
                let msg = 'Erro no servidor';
                try { msg = JSON.parse(xhr.responseText).mensagem; } catch (e) { }
                mostrarMensagem(msg, 'danger');
            });
        });

        $('#tabelaPermissoes').on('click', '.btn-editar-permissao', function () {
            const tr = $(this).closest('tr');

            formPermissao.show();

            formPermissao.find('[name=permissao_id]').val(tr.data('id'));
            formPermissao.find('[name=descricao]').val(tr.find('td:eq(2)').text().trim());
            formPermissao.find('[name=ativo]').prop(
                'checked',
                tr.find('td:eq(3)').text().trim() === 'Sim'
            );

            const telaNome = tr.find('td:eq(0)').text().trim();
            const chaveNome = tr.find('td:eq(1)').text().trim();

            formPermissao.find('[name=tela_id] option').each(function () {
                this.selected = $(this).text().trim() === telaNome;
            });

            formPermissao.find('[name=chave_id] option').each(function () {
                this.selected = $(this).text().trim() === chaveNome;
            });
        });

        $('#tabelaPermissoes').on('click', '.btn-excluir-permissao', function () {
            const id = $(this).closest('tr').data('id');
            if (!confirm('Excluir esta permissão?')) return;
            $.post('../pages/gerenciamento/mapeamento_actions.php', { action: 'delete_permissao', permissao_id: id }, function (res) {
                mostrarMensagem(res.mensagem, res.sucesso ? 'success' : 'danger');
                if (res.sucesso) $('#tabelaPermissoes').html(res.tabelaPermissoes);
            }, 'json');
        });
    })();
</script>