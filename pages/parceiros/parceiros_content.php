<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/helpers/permissoes.php';

requirePermissao('fornecedores', 'visualizar');

$usuarioId = $_SESSION['usuario_id'];

$campo = $_GET['campo'] ?? '';
$valor = trim($_GET['valor'] ?? '');

$camposPermitidos = [
    'nome',
    'razao_social',
    'cpf_cnpj',
    'tipo_parceiro',
    'classificacao',
    'cidade',
    'estado',
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
        parceiro_id,
        nome,
        razao_social,
        tipo_pessoa,
        cpf_cnpj,
        inscricao_estadual,
        tipo_parceiro,
        classificacao,
        selecao,
        endereco,
        numero,
        complemento,
        bairro,
        cidade,
        estado,
        pais,
        cep,
        contato_nome,
        telefone,
        email,
        observacoes,
        ativo
    FROM parceiros
    " . ($where ? "WHERE " . implode(' AND ', $where) : "") . "
    ORDER BY nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$parceiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

$permissoes = [
    'criar' => pode($usuarioId, 'fornecedores', 'criar'),
    'editar' => pode($usuarioId, 'fornecedores', 'editar'),
    'excluir' => pode($usuarioId, 'fornecedores', 'excluir'),
];
?>
<div data-tela="parceiros">

    <div id="mensagemParceiro"></div>

    <h2 class="mb-3">Cadastro de Parceiros</h2>

    <div id="FiltroParceiros" class="row g-2 mb-3">

        <div class="col-md-3">
            <select name="campo" id="filtroCampo" class="form-select">
                <option value="">Filtrar por...</option>
                <option value="nome">Nome</option>
                <option value="razao_social">Razão Social</option>
                <option value="cpf_cnpj">CPF / CNPJ</option>
                <option value="tipo_parceiro">Tipo</option>
                <option value="classificacao">Classificação</option>
                <option value="cidade">Cidade</option>
                <option value="estado">Estado</option>
                <option value="ativo">Status</option>
            </select>
        </div>

        <div class="col-md-4">
            <input type="text" name="valor" id="filtroValor" class="form-control">
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                Filtrar
            </button>
        </div>

    </div>

    <div class="mb-3">
        <?php if ($permissoes['criar']): ?>
            <button id="btnNovoParceiro" class="btn btn-primary">➕ Novo</button>
        <?php endif; ?>

        <?php if ($permissoes['editar']): ?>
            <button id="btnSalvarParceiro" class="btn btn-success" disabled>✔ Salvar</button>
        <?php endif; ?>

        <button id="btnCancelarParceiro" class="btn btn-secondary" disabled>❌ Cancelar</button>

        <?php if ($permissoes['excluir']): ?>
            <button id="btnExcluirParceiro" class="btn btn-danger">
                🗑️ Excluir
            </button>
        <?php endif; ?>

    </div>

    <!-- ================= FORM OCULTO (SUBMIT) ================= -->
    <form id="formParceiro" class="d-none">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="parceiro_id">
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
                        <label class="form-label">ID</label>
                        <input type="text" class="form-control" data-field="parceiro_id" disabled>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" data-field="nome" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Razão Social</label>
                        <input type="text" class="form-control" data-field="razao_social">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo Pessoa</label>
                        <select class="form-select" data-field="tipo_pessoa">
                            <option value="F">Física</option>
                            <option value="J">Jurídica</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">CPF / CNPJ</label>
                        <input type="text" class="form-control" data-field="cpf_cnpj">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Inscrição Estadual</label>
                        <input type="text" class="form-control" data-field="inscricao_estadual">
                    </div>

                </div>
            </div>
        </div>

        <!-- ABAS -->
        <div class="card-header fw-bold">Abas</div>

        <ul class="nav nav-tabs">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                    data-bs-target="#tabClass">Classificação</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                    data-bs-target="#tabEndereco">Endereço</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                    data-bs-target="#tabContato">Contato</button>
            </li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                    data-bs-target="#tabControle">Controle</button></li>
        </ul>

        <div class="tab-content border border-top-0 p-3">

            <!-- CLASSIFICAÇÃO -->
            <div class="tab-pane fade show active" id="tabClass">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" data-field="tipo_parceiro">
                            <option>---------</option>
                            <option value="fornecedor">Fornecedor</option>
                            <option value="cliente">Cliente</option>
                            <option value="motorista">Motorista</option>
                            <option value="transportadora">Transportadora</option>
                            <option value="colaborador">Colaborador</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Classificação ICMS</label>
                        <select class="form-select" data-field="classificacao">
                            <option>---------</option>
                            <option value="empresa">Empresa</option>
                            <option value="produtor_rural">Produtor Rural</option>
                            <option value="não contribuente">Revendedor</option>
                            <option value="não contribuente">Consumidor Final Não Contribuinte</option>
                            <option value="não contribuente">Consumidor Final Contribuinte</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Seleção</label>
                        <select class="form-select" data-field="selecao">
                            <option>---------</option>
                            <option value="Terceiros">Terceiros</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ENDEREÇO -->
            <div class="tab-pane fade" id="tabEndereco">
                <div class="row g-3">

                    <div class="col-md-1">
                        <label class="form-label">CEP</label>
                        <input type="text" class="form-control" data-field="cep">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Endereço</label>
                        <input type="text" class="form-control" data-field="endereco">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Numero</label>
                        <input type="text" class="form-control" data-field="numero">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Complemento</label>
                        <input type="text" class="form-control" data-field="complemento">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Bairro</label>
                        <input type="text" class="form-control" data-field="bairro">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" class="form-control" data-field="cidade">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">UF</label>
                        <input type="text" class="form-control" data-field="estado">
                    </div>

                    <div class="col-md-1">
                        <label class="form-label">Pais</label>
                        <input type="text" class="form-control" data-field="pais">
                    </div>

                </div>
            </div>

            <!-- CONTATO -->
            <div class="tab-pane fade" id="tabContato">
                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">Nome do Contato</label>
                        <input type="text" class="form-control" data-field="contato_nome">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control" data-field="telefone">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">E-mail</label>
                        <input type="email" class="form-control" data-field="email">
                    </div>

                </div>
            </div>

            <!-- CONTROLE -->
            <div class="tab-pane fade" id="tabControle">
                <div class="row g-3 align-items-center">
                    <div class="col-md-2">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" data-field="ativo" checked>
                            <label class="form-check-label">Ativo</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" rows="3" data-field="observacoes"></textarea>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ================= VIEW LISTA ================= -->
    <div id="viewList" class="d-none">
        <div class="card-header fw-bold">Lista de parceiros</div>
        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
            <table class="table table-sm table-bordered table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Razão Social</th>
                        <th>Tipo de pessoa</th>
                        <th>CPF / CNPJ</th>
                        <th>Inscrição Est.</th>
                        <th>Tipo de parceiro</th>
                        <th>Classificação ICMS</th>
                        <th>Seleção</th>
                        <th>CEP</th>
                        <th>Endereço</th>
                        <th>Nº</th>
                        <th>Compl.</th>
                        <th>Bairro</th>
                        <th>Cidade</th>
                        <th>UF</th>
                        <th>País</th>
                        <th>Contato</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="listaParceiros">
                    <?php foreach ($parceiros as $p): ?>
                        <tr class="linha-parceiro"
                            data-parceiro='<?= htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8") ?>'>

                            <td><?= $p['parceiro_id'] ?></td>
                            <td><?= $p['nome'] ?></td>
                            <td><?= $p['razao_social'] ?: '-' ?></td>
                            <td><?= $p['tipo_pessoa'] ?: '-' ?></td>
                            <td><?= $p['cpf_cnpj'] ?: '-' ?></td>
                            <td><?= $p['inscricao_estadual'] ?: '-' ?></td>
                            <td><?= $p['tipo_parceiro'] ?: '-' ?></td>
                            <td><?= $p['classificacao'] ?: '-' ?></td>
                            <td><?= $p['selecao'] ?: '-' ?></td>
                            <td><?= $p['cep'] ?: '-' ?></td>
                            <td><?= $p['endereco'] ?: '-' ?></td>
                            <td><?= $p['numero'] ?: '-' ?></td>
                            <td><?= $p['complemento'] ?: '-' ?></td>
                            <td><?= $p['bairro'] ?: '-' ?></td>
                            <td><?= $p['cidade'] ?: '-' ?></td>
                            <td><?= $p['estado'] ?: '-' ?></td>
                            <td><?= $p['pais'] ?: '-' ?></td>
                            <td><?= $p['contato_nome'] ?: '-' ?></td>
                            <td><?= $p['telefone'] ?: '-' ?></td>
                            <td><?= $p['email'] ?: '-' ?></td>
                            <td>
                                <span class="badge <?= $p['ativo'] ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= $p['ativo'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
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
        const tela = document.querySelector('[data-tela="parceiros"]');
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
        const formHidden = tela.querySelector('#formParceiro');

        const btnSalvar = tela.querySelector('#btnSalvarParceiro');
        const btnNovo = tela.querySelector('#btnNovoParceiro');
        const btnCancelar = tela.querySelector('#btnCancelarParceiro');
        const btnExcluir = tela.querySelector('#btnExcluirParceiro');

        const campoCpfCnpj = tela.querySelector('[data-field="cpf_cnpj"]');
        const campoIE = tela.querySelector('[data-field="inscricao_estadual"]');
        const campoTelefone = tela.querySelector('[data-field="telefone"]');
        const campoCep = tela.querySelector('[data-field="cep"]');

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
            carregarPrimeiroParceiroNoForm();
        }

        /* ================= EVENTOS ================= */
        tela.addEventListener('click', e => {

            const linha = e.target.closest('.linha-parceiro');

            if (e.target === btnNovo) novoParceiro();
            if (e.target === btnCancelar) cancelarEdicao();
            if (e.target === btnSalvar) salvarParceiro(e);
            if (e.target === btnExcluir) excluirParceiro();
            if (e.target === btnToggle) toggleView();

            if (linha && !modoNovo) {
                selecionarLinha(linha);
                abrirParceiro(JSON.parse(linha.dataset.parceiro), 'click');
            }
        });

        tela.addEventListener('dblclick', e => {
            const linha = e.target.closest('.linha-parceiro');
            if (!linha || modoNovo) return;
            selecionarLinha(linha);
            abrirParceiro(JSON.parse(linha.dataset.parceiro), 'dblclick');
        });

        tela.addEventListener('input', e => {

            const el = e.target;
            if (!el.matches('[data-field]')) return;
            if (el.dataset.field === 'parceiro_id') return;

            if (!modoNovo) {
                formAlterado = true;
                btnSalvar.disabled = false;
                btnCancelar.disabled = false;
                btnExcluir.disabled = true;
            }
        });

        tela.addEventListener('keydown', e => {

            const linhas = [...tela.querySelectorAll('.linha-parceiro')];
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
        function novoParceiro() {
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

            carregarPrimeiroParceiroNoForm();

            setTimeout(() => ignorarValidacaoCpfCnpj = false, 0);
        }

        function salvarParceiro(e) {
            e.preventDefault();
            if (!modoNovo && !formAlterado) return;

            sincronizarFormParceiro();
            formHidden.querySelector('[name="action"]').value = modoNovo ? 'add' : 'edit';

            $.post(
                '../pages/parceiros/parceiros_actions.php',
                $('#formParceiro').serialize(),
                res => {

                    $('#mensagemParceiro').html(
                        `<div class="alert alert-${res.sucesso ? 'success' : 'danger'}">${res.mensagem}</div>`
                    );

                    if (!res.sucesso) return;

                    modoNovo = false;
                    formAlterado = false;

                    btnSalvar.disabled = true;
                    btnCancelar.disabled = true;
                    btnExcluir.disabled = false;

                    carregarListaParceiros(res.id);
                    setTimeout(carregarPrimeiroParceiroNoForm, 200);
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
                carregarPrimeiroParceiroNoForm();
            }
        }

        /* ================= UTIL ================= */
        function selecionarLinha(linha) {
            tela.querySelectorAll('.linha-parceiro')
                .forEach(l => l.classList.remove('linha-ativa'));
            linha.classList.add('linha-ativa');
            linhaSelecionadaIndex = [...linha.parentElement.children].indexOf(linha);
        }

        function carregarPrimeiroParceiroNoForm() {
            if (modoNovo || origemFormulario === 'dblclick') return;
            const primeira = tela.querySelector('.linha-parceiro');
            if (!primeira) return;
            selecionarLinha(primeira);
            abrirParceiro(JSON.parse(primeira.dataset.parceiro), 'auto');
        }

        function mostrarView(modo) {
            viewList.classList.toggle('d-none', modo !== 'lista');
            viewForm.classList.toggle('d-none', modo !== 'form');
            btnToggle.innerHTML = modo === 'lista'
                ? '<i class="fa-solid fa-pen"></i> Modo formulário'
                : '<i class="fa-solid fa-list"></i> Modo lista';
        }

        function abrirParceiro(dados, origem) {
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

        function sincronizarFormParceiro() {
            formHidden.querySelectorAll('input:not([name="action"])').forEach(i => i.remove());
            tela.querySelectorAll('[data-field]').forEach(el => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = el.dataset.field;
                i.value = el.type === 'checkbox' ? (el.checked ? 1 : 0) : el.value;
                formHidden.appendChild(i);
            });
        }

        function limparFormulario() {
            tela.querySelectorAll('[data-field]').forEach(el => {
                el.type === 'checkbox' ? el.checked = true : el.value = '';
            });
            formHidden.querySelector('[name="action"]').value = 'add';
            formHidden.querySelector('[name="parceiro_id"]').value = '';
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
            formHidden.querySelector('[name="parceiro_id"]').value = dados.parceiro_id;
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

        function carregarListaParceiros(id = null) {
            $.get('../pages/parceiros/parceiros_content.php', res => {
                const html = $('<div>').html(res);
                tela.querySelector('#listaParceiros').innerHTML =
                    html.find('#listaParceiros').html();
                if (id) {
                    const linha = tela.querySelector(`.linha-parceiro[data-parceiro*='"parceiro_id":${id}']`);
                    if (linha) selecionarLinha(linha);
                }
            });
        }

        /* ================= START ================= */
        init();

    })();
</script>