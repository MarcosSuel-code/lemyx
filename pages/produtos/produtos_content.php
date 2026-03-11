<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/helpers/permissoes.php';

requirePermissao('produtos', 'visualizar');

$usuarioId = $_SESSION['usuario_id'];

$campo = $_GET['campo'] ?? '';
$valor = trim($_GET['valor'] ?? '');

$camposPermitidos = [
    'descricao',
    'sku',
    'codigo_barras',
    'marca',
    'modelo',
    'unidade',
    'ativo'
];

$where = [];
$params = [];

if ($campo && $valor && in_array($campo, $camposPermitidos, true)) {

    if ($campo === 'ativo') {
        $where[] = "ativo = :valor";
        $params[':valor'] = (int) $valor;
    } else {
        $where[] = "$campo LIKE :valor";
        $params[':valor'] = "%$valor%";
    }
}

$sql = "
SELECT
    produto_id,
    descricao,
    sku,
    codigo_barras,
    unidade,
    marca,
    modelo,
    comprador,
    conferente,
    preco_custo,
    preco_venda,
    estoque_minimo,
    estoque_maximo,
    localizacao,
    controla_estoque,
    categoria_id,
    observacoes,
    ativo,
    data_criacao,
    data_atualizacao,
    usuario_criacao,
    usuario_atualizacao
FROM produtos
" . ($where ? "WHERE " . implode(' AND ', $where) : "") . "
ORDER BY descricao ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$calibres = $pdo->query("
    SELECT calibre_id, calibre
    FROM calibre
    ORDER BY calibre
")->fetchAll(PDO::FETCH_ASSOC);

$volumes = $pdo->query("
    SELECT volume_id, volume
    FROM volumes
    ORDER BY volume
")->fetchAll(PDO::FETCH_ASSOC);

$permissoes = [
    'criar' => pode($usuarioId, 'produtos', 'criar'),
    'editar' => pode($usuarioId, 'produtos', 'editar'),
    'excluir' => pode($usuarioId, 'produtos', 'excluir'),
];
?>

<div data-tela="produtos">

    <div id="mensagemProduto"></div>

    <h2 class="mb-3">Cadastro de Produtos</h2>

    <div class="row g-2 mb-3">

        <div class="col-md-3">
            <select id="filtroCampo" class="form-select">
                <option value="">Filtrar por...</option>
                <option value="descricao">Descrição</option>
                <option value="sku">SKU</option>
                <option value="codigo_barras">Código de Barras</option>
                <option value="marca">Marca</option>
                <option value="modelo">Modelo</option>
                <option value="unidade">Unidade</option>
                <option value="ativo">Status</option>
            </select>
        </div>

        <div class="col-md-4">
            <input type="text" id="filtroValor" class="form-control">
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>

    </div>

    <div class="mb-3">

        <?php if ($permissoes['criar']): ?>
            <button id="btnNovoProduto" class="btn btn-primary">➕ Novo</button>
        <?php endif; ?>

        <?php if ($permissoes['editar']): ?>
            <button id="btnSalvarProduto" class="btn btn-success" disabled>✔ Salvar</button>
        <?php endif; ?>

        <button id="btnCancelarProduto" class="btn btn-secondary" disabled>❌ Cancelar</button>

        <?php if ($permissoes['excluir']): ?>
            <button id="btnExcluirProduto" class="btn btn-danger">🗑️ Excluir</button>
        <?php endif; ?>

    </div>

    <!-- ================= FORM OCULTO (SUBMIT) ================= -->
    <form id="formProduto" class="d-none">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="produto_id">
    </form>

    <button id="btnToggleView" class=" justify-end btn btn-outline-secondary btn-sm mb-3">
        <i class="fa-solid fa-list"></i> Modo lista
    </button>

    <!-- ================= VIEW FORM ================= -->
    <div id="viewForm">

        <!-- IDENTIFICAÇÃO -->
        <div class="card mb-3">

            <div class="card-header fw-bold">Identificação</div>

            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-2">
                        <label>ID</label>
                        <input class="form-control" data-field="produto_id" disabled>
                    </div>

                    <div class="col-md-6">
                        <label>Descrição</label>
                        <input class="form-control" data-field="descricao">
                    </div>

                    <div class="col-md-2">
                        <label>SKU</label>
                        <input class="form-control" data-field="sku">
                    </div>

                    <div class="col-md-2">
                        <label>Unidade</label>
                        <input class="form-control" data-field="unidade">
                    </div>

                    <div class="col-md-3">
                        <label>Código de Barras</label>
                        <input class="form-control" data-field="codigo_barras">
                    </div>

                    <div class="col-md-3">
                        <label>Marca</label>
                        <input class="form-control" data-field="marca">
                    </div>

                    <div class="col-md-3">
                        <label>Modelo</label>
                        <input class="form-control" data-field="modelo">
                    </div>

                    <div class="col-md-3">
                        <label>Categoria</label>
                        <input class="form-control" data-field="categoria_id">
                    </div>

                    <div class="col-md-3">
                        <label>Comprador</label>
                        <input class="form-control" data-field="comprador">
                    </div>

                    <div class="col-md-3">
                        <label>Conferente</label>
                        <input class="form-control" data-field="conferente">
                    </div>

                </div>
            </div>
        </div>

        <!-- ABAS -->
        <div class="card-header fw-bold">Abas</div>

        <ul class="nav nav-tabs">

            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCalibres">
                    Calibres
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabVolumes">
                    Volumes
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabFinanceiro">
                    Financeiro
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEstoque">
                    Estoque
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabControle">
                    Controle
                </button>
            </li>


        </ul>

        <div class="tab-content border border-top-0 p-3">

            <!-- Calibres -->
            <div class="tab-pane fade show active" id="tabCalibres">

                <div class="d-flex justify-content-end mb-2">
                    <button type="button" id="addCalibre" class="btn btn-sm btn-primary">
                        + Adicionar Calibre
                    </button>
                </div>

                <div class="table-responsive">

                    <table class="table table-sm table-bordered" id="tabelaCalibres">

                        <thead class="table-light">
                            <tr>
                                <th style="width:90%">Calibre</th>
                                <th style="width:10%">Ação</th>
                            </tr>
                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

            <!-- Volumes -->
            <div class="tab-pane fade" id="tabVolumes">

                <div class="d-flex justify-content-end mb-2">
                    <button type="button" id="addVolume" class="btn btn-sm btn-primary">
                        + Adicionar Volume
                    </button>
                </div>

                <div class="table-responsive">

                    <table class="table table-sm table-bordered" id="tabelaVolumes">

                        <thead class="table-light">
                            <tr>
                                <th style="width:90%">Volume</th>
                                <th style="width:10%">Ação</th>
                            </tr>
                        </thead>

                        <tbody></tbody>

                    </table>

                </div>

            </div>

            <!-- FINANCEIRO -->
            <div class="tab-pane fade" id="tabFinanceiro">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label>Preço Custo</label>
                        <input class="form-control" data-field="preco_custo">
                    </div>

                    <div class="col-md-3">
                        <label>Preço Venda</label>
                        <input class="form-control" data-field="preco_venda">
                    </div>

                </div>

            </div>

            <!-- ESTOQUE -->
            <div class="tab-pane fade" id="tabEstoque">

                <div class="row g-3">

                    <div class="col-md-3">
                        <label>Estoque Mínimo</label>
                        <input class="form-control" data-field="estoque_minimo">
                    </div>

                    <div class="col-md-3">
                        <label>Estoque Máximo</label>
                        <input class="form-control" data-field="estoque_maximo">
                    </div>

                    <div class="col-md-3">
                        <label>Localização</label>
                        <input class="form-control" data-field="localizacao">
                    </div>

                    <div class="col-md-3">

                        <div class="form-check form-switch mt-4">

                            <input class="form-check-input" type="checkbox" data-field="controla_estoque" checked>

                            <label class="form-check-label">
                                Controla Estoque
                            </label>

                        </div>

                    </div>

                </div>

            </div>

            <!-- CONTROLE -->
            <div class="tab-pane fade" id="tabControle">

                <div class="row g-3">

                    <div class="col-md-2">

                        <div class="form-check form-switch">

                            <input class="form-check-input" type="checkbox" data-field="ativo" checked>

                            <label class="form-check-label">
                                Ativo
                            </label>

                        </div>

                    </div>

                    <div class="col-md-6">

                        <label>Observações</label>

                        <textarea class="form-control" rows="3" data-field="observacoes"></textarea>

                    </div>

                </div>

                <hr>

                <!-- AUDITORIA -->
                <div class="row g-3">

                    <div class="col-md-3">
                        <label>Data Criação</label>
                        <input class="form-control" data-field="data_criacao" disabled>
                    </div>

                    <div class="col-md-3">
                        <label>Data Atualização</label>
                        <input class="form-control" data-field="data_atualizacao" disabled>
                    </div>

                    <div class="col-md-3">
                        <label>Usuário Criação</label>
                        <input class="form-control" data-field="usuario_criacao" disabled>
                    </div>

                    <div class="col-md-3">
                        <label>Usuário Alteração</label>
                        <input class="form-control" data-field="usuario_atualizacao" disabled>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- ================= VIEW LISTA ================= -->
    <div id="viewList" class="d-none">
        <div class="table-responsive" style="max-height:60vh; overflow-y:auto;">

            <table class="table table-sm table-bordered table-hover align-middle">

                <thead class="table-light">

                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>SKU</th>
                        <th>Cód. Barras</th>
                        <th>Unidade</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Comprador</th>
                        <th>Conferente</th>
                        <th>Categoria</th>
                        <th>Preço Custo</th>
                        <th>Preço Venda</th>
                        <th>Controla Estoque</th>
                        <th>Estoque Mín.</th>
                        <th>Estoque Máx.</th>
                        <th>Localização</th>
                        <th>Observações</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Atualizado em</th>
                        <th>Usuário Criação</th>
                        <th>Usuário Alteração</th>
                    </tr>

                </thead>

                <tbody id="listaProdutos">

                    <?php foreach ($produtos as $p): ?>

                        <tr class="linha-produto"
                            data-produto='<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>'>

                            <td><?= $p['produto_id'] ?></td>
                            <td><?= $p['descricao'] ?></td>
                            <td><?= $p['sku'] ?: '-' ?></td>
                            <td><?= $p['codigo_barras'] ?: '-' ?></td>
                            <td><?= $p['unidade'] ?: '-' ?></td>
                            <td><?= $p['marca'] ?: '-' ?></td>
                            <td><?= $p['modelo'] ?: '-' ?></td>
                            <td><?= $p['comprador'] ?: '-' ?></td>
                            <td><?= $p['conferente'] ?: '-' ?></td>
                            <td><?= $p['categoria_id'] ?: '-' ?></td>
                            <td><?= $p['preco_custo'] ?: '0.00' ?></td>
                            <td><?= $p['preco_venda'] ?: '0.00' ?></td>

                            <td>
                                <span class="badge <?= $p['controla_estoque'] ? 'bg-info' : 'bg-secondary' ?>">
                                    <?= $p['controla_estoque'] ? 'Sim' : 'Não' ?>
                                </span>
                            </td>

                            <td><?= $p['estoque_minimo'] ?: '0' ?></td>
                            <td><?= $p['estoque_maximo'] ?: '-' ?></td>
                            <td><?= $p['localizacao'] ?: '-' ?></td>
                            <td><?= $p['observacoes'] ?: '-' ?></td>

                            <td>
                                <span class="badge <?= $p['ativo'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $p['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>

                            <td><?= $p['data_criacao'] ?: '-' ?></td>
                            <td><?= $p['data_atualizacao'] ?: '-' ?></td>
                            <td><?= $p['usuario_criacao'] ?: '-' ?></td>
                            <td><?= $p['usuario_atualizacao'] ?: '-' ?></td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>
    (function () {

        /* ================= BOOTSTRAP ================= */
        const tela = document.querySelector('[data-tela="produtos"]');
        if (!tela) return;

        /* ================= ESTADO ================= */
        let modoNovo = false;
        let origemFormulario = null;
        let formAlterado = false;
        let linhaSelecionadaIndex = 0;
        let ignorarValidacaoCpfCnpj = false;

        /* ================= ELEMENTOS ================= */
        const btnToggle = tela.querySelector('#btnToggleView');
        const viewForm = tela.querySelector('#viewForm');
        const viewList = tela.querySelector('#viewList');
        const formHidden = tela.querySelector('#formProduto');

        const btnSalvar = tela.querySelector('#btnSalvarProduto');
        const btnNovo = tela.querySelector('#btnNovoProduto');
        const btnCancelar = tela.querySelector('#btnCancelarProduto');
        const btnExcluir = tela.querySelector('#btnExcluirProduto');

        /* ================= INIT ================= */
        function init() {
            modoNovo = false;
            origemFormulario = null;
            formAlterado = false;
            linhaSelecionadaIndex = 0;

            btnSalvar.disabled = true;
            btnCancelar.disabled = true;
            btnExcluir.disabled = false;

            mostrarView('lista');
            carregarPrimeiroProdutoNoForm();
        }

        // calibres
        document.querySelectorAll('[data-calibre]:checked').forEach(el => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'calibres[]';
            i.value = el.value;
            formHidden.appendChild(i);
        });

        // volumes
        document.querySelectorAll('[data-volume]:checked').forEach(el => {
            const i = document.createElement('input');
            i.type = 'hidden';
            i.name = 'volumes[]';
            i.value = el.value;
            formHidden.appendChild(i);
        });

        const calibres = <?= json_encode($calibres) ?>;
        const volumes = <?= json_encode($volumes) ?>;

        function novaLinhaCalibre(valor = '') {

            let options = '<option value="">Selecione</option>';

            calibres.forEach(c => {
                options += `<option value="${c.calibre_id}" ${valor == c.calibre_id ? 'selected' : ''}>
                        ${c.calibre}
                    </option>`;
            });

            const linha = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm calibre-select">
                                ${options}
                            </select>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger removerLinha">-</button>
                        </td>
                    </tr>
                `;

            document.querySelector('#tabelaCalibres tbody')
                .insertAdjacentHTML('beforeend', linha);
        }

        function novaLinhaVolume(valor = '') {

            let options = '<option value="">Selecione</option>';

            volumes.forEach(v => {
                options += `<option value="${v.volume_id}" ${valor == v.volume_id ? 'selected' : ''}>
                        ${v.volume}
                    </option>`;
            });

            const linha = `
                    <tr>
                        <td>
                            <select class="form-select form-select-sm volume-select">
                                ${options}
                            </select>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger removerLinha">-</button>
                        </td>
                    </tr>
                `;

            document.querySelector('#tabelaVolumes tbody')
                .insertAdjacentHTML('beforeend', linha);
        }

        document.getElementById('addCalibre')
            .addEventListener('click', () => novaLinhaCalibre());

        document.getElementById('addVolume')
            .addEventListener('click', () => novaLinhaVolume());

        document.addEventListener('click', e => {

            if (e.target.classList.contains('removerLinha')) {
                e.target.closest('tr').remove();
            }

        });

        /* ================= EVENTOS ================= */
        tela.addEventListener('click', e => {

            const linha = e.target.closest('.linha-produto');

            if (e.target === btnNovo) novoProduto();
            if (e.target === btnCancelar) cancelarEdicao();
            if (e.target === btnSalvar) salvarProduto(e);
            if (e.target === btnExcluir) excluirProduto();
            if (e.target === btnToggle) toggleView();

            if (linha && !modoNovo) {
                selecionarLinha(linha);
                abrirProduto(JSON.parse(linha.dataset.produto), 'click');
            }
        });

        tela.addEventListener('dblclick', e => {
            const linha = e.target.closest('.linha-produto');
            if (!linha || modoNovo) return;
            selecionarLinha(linha);
            abrirProduto(JSON.parse(linha.dataset.produto), 'dblclick');
        });

        tela.addEventListener('input', e => {

            const el = e.target;
            if (!el.matches('[data-field]')) return;
            if (el.dataset.field === 'produto_id') return;

            if (!modoNovo) {
                formAlterado = true;
                btnSalvar.disabled = false;
                btnCancelar.disabled = false;
                btnExcluir.disabled = true;
            }
        });

        tela.addEventListener('keydown', e => {

            const linhas = [...tela.querySelectorAll('.linha-produto')];
            if (!linhas.length) return;

            if (e.key === 'ArrowDown') {
                linhaSelecionadaIndex = Math.min(linhaSelecionadaIndex + 1, linhas.length - 1);
                selecionarLinha(linhas[linhaSelecionadaIndex]);
            }

            if (e.key === 'ArrowUp') {
                linhaSelecionadaIndex = Math.max(linhaSelecionadaIndex - 1, 0);
                selecionarLinha(linhas[linhaSelecionadaIndex]);
            }

            if (e.key === 'Enter') {
                linhas[linhaSelecionadaIndex]?.dispatchEvent(new Event('dblclick'));
            }

            if (e.key === 'Escape' && modoNovo) cancelarEdicao();
        });

        /* ================= AÇÕES ================= */
        function novoProduto() {
            modoNovo = true;
            origemFormulario = 'novo';
            formAlterado = true;

            limparFormulario();
            mostrarView('form');

            btnSalvar.disabled = false;
            btnCancelar.disabled = false;
            btnExcluir.disabled = true;
        }

        function cancelarEdicao() {

            if (formAlterado && !confirm('Descartar alterações?')) return;

            ignorarValidacaoCpfCnpj = true;

            modoNovo = false;
            origemFormulario = null;
            formAlterado = false;

            limparValidacoes();

            btnSalvar.disabled = true;
            btnCancelar.disabled = true;
            btnExcluir.disabled = false;

            carregarPrimeiroProdutoNoForm();

            setTimeout(() => ignorarValidacaoCpfCnpj = false, 0);
        }

        function salvarProduto(e) {
            e.preventDefault();
            if (!modoNovo && !formAlterado) return;

            sincronizarFormProduto();
            formHidden.querySelector('[name="action"]').value = modoNovo ? 'add' : 'edit';

            $.post(
                '../pages/produtos/produtos_actions.php',
                $('#formProduto').serialize(),
                res => {

                    $('#mensagemProduto').html(
                        `<div class="alert alert-${res.sucesso ? 'success' : 'danger'}">${res.mensagem}</div>`
                    );

                    if (!res.sucesso) return;

                    modoNovo = false;
                    formAlterado = false;

                    btnSalvar.disabled = true;
                    btnCancelar.disabled = true;
                    btnExcluir.disabled = false;

                    carregarListaProdutos(res.id);
                    setTimeout(carregarPrimeiroProdutoNoForm, 200);
                },
                'json'
            );
        }

        function toggleView() {

            if (formAlterado && !confirm('Existem alterações não salvas. Deseja continuar?')) return;

            const listaVisivel = !viewList.classList.contains('d-none');
            mostrarView(listaVisivel ? 'form' : 'lista');

            if (modoNovo || origemFormulario === 'dblclick') {
                modoNovo = false;
                origemFormulario = null;
                carregarPrimeiroProdutoNoForm();
            }
        }

        /* ================= UTIL ================= */
        function selecionarLinha(linha) {
            tela.querySelectorAll('.linha-produto')
                .forEach(l => l.classList.remove('linha-ativa'));
            linha.classList.add('linha-ativa');
            linhaSelecionadaIndex = [...linha.parentElement.children].indexOf(linha);
        }

        function carregarPrimeiroProdutoNoForm() {
            if (modoNovo || origemFormulario === 'dblclick') return;
            const primeira = tela.querySelector('.linha-produto');
            if (!primeira) return;
            selecionarLinha(primeira);
            abrirProduto(JSON.parse(primeira.dataset.produto), 'auto');
        }

        function mostrarView(modo) {
            viewList.classList.toggle('d-none', modo !== 'lista');
            viewForm.classList.toggle('d-none', modo !== 'form');
            btnToggle.innerHTML = modo === 'lista'
                ? '<i class="fa-solid fa-pen"></i> Modo formulário'
                : '<i class="fa-solid fa-list"></i> Modo lista';
        }

        function abrirProduto(dados, origem) {
            origemFormulario = origem;
            modoNovo = false;
            formAlterado = false;

            mostrarView('form');
            preencherFormulario(dados);

            btnSalvar.disabled = true;
            btnCancelar.disabled = true;
            btnExcluir.disabled = false;

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function sincronizarFormProduto() {
            formHidden.querySelectorAll('input:not([name="action"])').forEach(i => i.remove());
            tela.querySelectorAll('[data-field]').forEach(el => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = el.dataset.field;
                i.value = el.type === 'checkbox' ? (el.checked ? 1 : 0) : el.value;
                formHidden.appendChild(i);
            });

            // calibres
            document.querySelectorAll('.calibre-select').forEach(el => {

                if (!el.value) return;

                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'calibres[]';
                i.value = el.value;

                formHidden.appendChild(i);
            });

            // volumes
            document.querySelectorAll('.volume-select').forEach(el => {

                if (!el.value) return;

                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = 'volumes[]';
                i.value = el.value;

                formHidden.appendChild(i);
            });
        }

        function limparFormulario() {
            tela.querySelectorAll('[data-field]').forEach(el => {
                el.type === 'checkbox' ? el.checked = true : el.value = '';
            });
            formHidden.querySelector('[name="action"]').value = 'add';
            formHidden.querySelector('[name="produto_id"]').value = '';
        }

        function preencherFormulario(dados) {
            tela.querySelectorAll('[data-field]').forEach(el => {
                const campo = el.dataset.field;
                if (!(campo in dados)) return;
                el.type === 'checkbox'
                    ? el.checked = dados[campo] == 1
                    : el.value = dados[campo] ?? '';
            });
            formHidden.querySelector('[name="action"]').value = 'edit';
            formHidden.querySelector('[name="produto_id"]').value = dados.produto_id;
        }

        function limparValidacoes() {
            tela.querySelectorAll('.is-invalid, .is-valid')
                .forEach(el => el.classList.remove('is-invalid', 'is-valid'));
        }

        /* ================= VALIDAÇÕES / MÁSCARAS ================= */
        const apenasNumeros = v => v.replace(/\D/g, '');

        const validarCPF = cpf => {
            cpf = apenasNumeros(cpf);
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
            let s = 0;
            for (let i = 0; i < 9; i++) s += cpf[i] * (10 - i);
            let d1 = (s * 10) % 11; if (d1 === 10) d1 = 0;
            if (d1 != cpf[9]) return false;
            s = 0;
            for (let i = 0; i < 10; i++) s += cpf[i] * (11 - i);
            let d2 = (s * 10) % 11; if (d2 === 10) d2 = 0;
            return d2 == cpf[10];
        };

        const validarCNPJ = cnpj => {
            cnpj = apenasNumeros(cnpj);
            if (cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) return false;
            const calc = l => {
                let s = 0, p = l - 7;
                for (let i = l; i >= 1; i--) {
                    s += cnpj[l - i] * p--;
                    if (p < 2) p = 9;
                }
                let r = s % 11;
                return r < 2 ? 0 : 11 - r;
            };
            return calc(12) == cnpj[12] && calc(13) == cnpj[13];
        };

        campoCpfCnpj.addEventListener('blur', function () {
            if (ignorarValidacaoCpfCnpj) {
                this.classList.remove('is-invalid');
                return;
            }
            const v = apenasNumeros(this.value);
            const ok = v.length === 11 ? validarCPF(v) : v.length === 14 && validarCNPJ(v);
            if (!ok && v.length) {
                this.classList.add('is-invalid');
                btnSalvar.disabled = true;
                alert('CPF ou CNPJ inválido');
            } else {
                this.classList.remove('is-invalid');
                btnSalvar.disabled = false;
            }
        });

        campoCpfCnpj.addEventListener('input', function () {
            const c = this.selectionStart;
            const v = apenasNumeros(this.value);
            this.value = v.length <= 11
                ? v.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2')
                : v.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            this.setSelectionRange(c, c);
        });

        campoIE.addEventListener('input', function () {
            this.value = apenasNumeros(this.value);
        });

        campoTelefone?.addEventListener('input', function () {
            let v = apenasNumeros(this.value);
            this.value = v.length <= 10
                ? v.replace(/(\d{2})(\d{4})(\d+)/, '($1) $2-$3')
                : v.replace(/(\d{2})(\d{5})(\d+)/, '($1) $2-$3');
        });

        campoCep?.addEventListener('blur', function () {
            const cep = apenasNumeros(this.value);
            if (cep.length !== 8) return;
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(r => r.json())
                .then(d => {
                    if (d.erro) return;
                    tela.querySelector('[data-field="endereco"]').value = d.logradouro || '';
                    tela.querySelector('[data-field="bairro"]').value = d.bairro || '';
                    tela.querySelector('[data-field="cidade"]').value = d.localidade || '';
                    tela.querySelector('[data-field="estado"]').value = d.uf || '';
                });
        });

        function carregarListaProdutos(id = null) {
            $.get('../pages/produtos/produtos_content.php', res => {
                const html = $('<div>').html(res);
                tela.querySelector('#listaProdutos').innerHTML =
                    html.find('#listaProdutos').html();
                if (id) {
                    const linha = tela.querySelector(`.linha-produto[data-produto*='"produto_id":${id}']`);
                    if (linha) selecionarLinha(linha);
                }
            });
        }

        /* ================= START ================= */
        init();

    })();
</script>