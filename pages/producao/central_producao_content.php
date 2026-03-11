<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';

$producaoId = $_GET['producao_pa_id'] ?? null;

$producao = null;
$pa_salvo = [];
$mp_salvos = [];

if ($producaoId) {

    // DADOS DA PRODUÇÃO
    $stmt = $pdo->prepare("
    SELECT media_final, status
    FROM producao_pa
    WHERE producao_pa_id = ?
    ");

    $stmt->execute([$producaoId]);
    $producao = $stmt->fetch(PDO::FETCH_ASSOC);

    // PRODUTO ACABADO (PA)
    $stmt = $pdo->prepare("
        SELECT 
            pa.produto_id,
            pa.quantidade_caixa,
            pa.quantidade,
            pa.peso_medio,
            pr.descricao,
            pr.unidade
        FROM producao_pa pa
        JOIN produtos pr ON pr.produto_id = pa.produto_id
        WHERE pa.producao_pa_id = ?
    ");
    $stmt->execute([$producaoId]);
    $pa_salvo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // MATÉRIA-PRIMA (MP)
    $stmt = $pdo->prepare("
        SELECT 
            mp.produto_id,
            mp.volume_id,
            mp.quantidade_caixa,
            pr.descricao,
            pr.unidade,
            v.quantidade AS volume_quantidade
        FROM producao_mp mp
        JOIN produtos pr ON pr.produto_id = mp.produto_id
        JOIN volumes v ON v.volume_id = mp.volume_id
        WHERE mp.producao_pa_id = ?
    ");
    $stmt->execute([$producaoId]);
    $mp_salvos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* PRODUTOS */
$produtos = $pdo->query("
    SELECT produto_id, descricao, unidade
    FROM produtos
    ORDER BY descricao
")->fetchAll(PDO::FETCH_ASSOC);

/* VOLUMES */
$volumes = $pdo->query("
    SELECT 
        volume_id,
        quantidade,
        volume AS descricao
    FROM volumes
    ORDER BY volume
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- BOTÃO VOLTAR PARA TELA DE PRODUÇÕES -->
<div style="display:flex; gap:10px; margin-bottom:16px;">

    <button class="btn-primary btn-VoltarProducao">
        ← Voltar
    </button>

    <?php if ($producaoId): ?>
        <button class="btn-primary" onclick="novaProducao()">+ Nova Produção</button>
        <button class="btn-danger" onclick="excluirProducao(<?= (int) $producaoId ?>)">Excluir</button>
    <?php endif; ?>

</div>

<div class="producao-wrapper">

    <!-- HEADER -->
    <div class="producao-header-card">
        <div>
            <h2>Produção</h2>
            <span>Controle de Matéria-Prima e Produto Acabado</span>
        </div>

        <div class="header-fields">
            <div class="field">
                <label>Peso médio final (kg)</label>
                <input type="number" step="0.01" id="media_final" value="<?= $producao['media_final'] ?? '' ?>">
            </div>

            <div class="field">
                <label>Status</label>
                <select id="status">
                    <option value="PENDENTE" <?= ($producao['status'] ?? '') === 'PENDENTE' ? 'selected' : '' ?>>Pendente
                    </option>
                    <option value="FINALIZADA" <?= ($producao['status'] ?? '') === 'FINALIZADA' ? 'selected' : '' ?>>
                        Finalizada</option>
                </select>

            </div>
        </div>
    </div>

    <div class="grid">

        <!-- PRODUTO ACABADO -->
        <div class="card pa">
            <div class="card-header">
                <h3>Produto Acabado (PA)</h3>
                <button type="button" onclick="addPA()">Definir Produto</button>
            </div>
            <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                <table id="tabela-pa">
                    <thead  style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Unidade</th>
                            <th>Caixas</th>
                            <th>Quantidade</th>
                            <th>Peso Médio</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pa_salvo)): ?>
                            <?php foreach ($pa_salvo as $pa): ?>
                                <tr>
                                    <td class="produto-codigo"><?= $pa['produto_id'] ?></td>
                                    <td>
                                        <select onchange="preencherProduto(this)">
                                            <?php foreach ($produtos as $p): ?>
                                                <option value="<?= $p['produto_id'] ?>" data-unidade="<?= $p['unidade'] ?>"
                                                    <?= $p['produto_id'] == $pa['produto_id'] ? 'selected' : '' ?>>
                                                    <?= $p['descricao'] ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="produto-unidade"><?= $pa['unidade'] ?></td>
                                    <td><input type="number" class="caixas-pa" value="<?= $pa['quantidade_caixa'] ?>"></td>
                                    <td><input type="number" class="quantidade-pa" step="0.01" value="<?= $pa['quantidade'] ?>">
                                    </td>
                                    <td class="peso-medio-td">
                                        <?php if (strtolower($pa['unidade']) === 'kg'): ?>
                                            <input type="number" class="peso-medio-pa" readonly value="<?= $pa['peso_medio'] ?>">
                                        <?php endif; ?>
                                    </td>
                                    <td><button onclick="this.closest('tr').remove(); atualizarMediaFinal();">×</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- MATERIA PRIMA -->
        <div class="card mp">
            <div class="card-header">
                <h3>Matéria-Prima (MP)</h3>
                <button type="button" onclick="addMP()">+ Adicionar</button>
            </div>
            <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                <table id="tabela-mp">
                    <thead  style="position: sticky; top: 0; z-index: 2;">
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Unidade</th>
                            <th>Volume</th>
                            <th>Caixas</th>
                            <th>Peso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mp_salvos as $mp): ?>
                            <tr>
                                <td class="produto-codigo">
                                    <?= $mp['produto_id'] ?>
                                </td>
                                <td>
                                    <select onchange="preencherProduto(this)">
                                        <?php foreach ($produtos as $p): ?>
                                            <option value="<?= $p['produto_id'] ?>" data-unidade="<?= $p['unidade'] ?>"
                                                <?= $p['produto_id'] == $mp['produto_id'] ? 'selected' : '' ?>>
                                                <?= $p['descricao'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td class="produto-unidade">
                                    <?= $mp['unidade'] ?>
                                </td>
                                <td>
                                    <select data-volume onchange="calcularPesoMP(this.closest('tr'))">
                                        <?php foreach ($volumes as $v): ?>
                                            <option value="<?= $v['volume_id'] ?>" data-quantidade="<?= $v['quantidade'] ?>"
                                                <?= $v['volume_id'] == $mp['volume_id'] ? 'selected' : '' ?>>
                                                <?= $v['descricao'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="number" class="caixas" value="<?= $mp['quantidade_caixa'] ?>"
                                        oninput="calcularPesoMP(this.closest('tr'))"></td>
                                <td><input type="number" class="peso" readonly
                                        value="<?= number_format($mp['volume_quantidade'] * $mp['quantidade_caixa'], 2, '.', '') ?>">
                                </td>
                                <td><button onclick="this.closest('tr').remove(); atualizarMediaFinal();">×</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <div class="footer-actions">
            <button class="btn-primary" onclick="salvarProducao()">Salvar Produção</button>
        </div>

    </div>
</div>

<style>
    :root {
        --primary: #2563eb;
        --bg: #f4f6f9;
        --card: #ffffff;
        --border: #e5e7eb;
        --text: #1f2937;
        --muted: #6b7280;
    }

    .producao-wrapper {
        padding: 24px;
        background: var(--bg);
        color: var(--text);
    }

    .producao-header-card {
        background: var(--card);
        border-radius: 12px;
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 24px rgba(0, 0, 0, .04);
        margin-bottom: 24px;
    }

    .header-fields {
        display: flex;
        gap: 16px;
    }

    .field {
        display: flex;
        flex-direction: column;
    }

    .field label {
        font-size: 12px;
        color: var(--muted);
        margin-bottom: 4px;
    }

    input,
    select {
        padding: 8px 10px;
        border-radius: 8px;
        border: 1px solid var(--border);
    }

    .grid {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .card {
        background: var(--card);
        border-radius: 14px;
        padding: 16px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, .05);
    }

    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }

    .card-header button {
        background: transparent;
        border: 1px dashed var(--primary);
        color: var(--primary);
        padding: 6px 10px;
        border-radius: 8px;
        cursor: pointer;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        font-size: 12px;
        color: var(--muted);
        text-align: left;
        padding: 8px;
    }

    td {
        padding: 6px;
    }

    tbody tr:hover {
        background: #f9fafb;
    }

    td button {
        background: none;
        border: none;
        color: #dc2626;
        font-weight: bold;
        cursor: pointer;
    }

    .footer-actions {
        display: flex;
        justify-content: flex-end;
    }

    .btn-primary {
        background: var(--primary);
        color: #fff;
        border: none;
        padding: 12px 26px;
        font-size: 15px;
        border-radius: 10px;
        cursor: pointer;
    }

    /* Peso Médio input menor e centralizado */
    .peso-medio-td input.peso-medio-pa {
        width: 70px;
        padding: 4px 6px;
        font-size: 13px;
        text-align: center;
    }

    #tabela-pa td,
    #tabela-pa th {
        vertical-align: middle;
    }

    #tabela-pa td:last-child {
        width: 30px;
        text-align: center;
    }

    .btn-danger {
        background: #dc2626;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11">

</script>

<script>

    const producaoId = <?= $producaoId ? (int) $producaoId : 'null' ?>;
    const produtos = <?= json_encode($produtos) ?>;
    const volumes = <?= json_encode($volumes) ?>;

    // EXECUTA AO CARREGAR PARA LINHAS EXISTENTES
    document.addEventListener('DOMContentLoaded', () => {

        document.querySelectorAll('#tabela-mp tbody tr').forEach(tr => {
            calcularPesoMP(tr);
        });

        document.querySelectorAll('#tabela-pa tbody tr').forEach(tr => {
            atualizarPesoMedioPA(tr);
        });

        document.querySelector('#tabela-pa').addEventListener('input', e => {
            if (e.target.classList.contains('caixas-pa') || e.target.classList.contains('quantidade-pa')) {
                const tr = e.target.closest('tr');
                atualizarPesoMedioPA(tr);      // atualiza o peso médio da linha
                atualizarMediaFinal();          // atualiza o peso médio final
            }
        });

    });

    // Nova produção
    function novaProducao() {
        const url = `../pages/producao/central_producao_content.php`;
        const abaAtiva = $('#dynamicTabContent .tab-pane.active');

        abaAtiva.load(url, function () {
            const abaId = abaAtiva.attr('id');
            $(`#dynamicTabs a[href="#${abaId}"]`)
                .contents()
                .first()[0].textContent = `Nova Produção`;
        });

        atualizarPesoMedioPA(tr);
    }

    // SALVAR PRODUÇÃO
    function salvarProducao() {

        document.querySelectorAll('#tabela-pa tbody tr').forEach(tr => {
            const unidade = tr.querySelector('.produto-unidade').textContent.trim().toLowerCase();
            if (unidade === 'kg') {
                atualizarPesoMedioPA(tr);
            }
        });

        const mp = [...document.querySelectorAll('#tabela-mp tbody tr')].map(tr => ({
            produto_id: tr.querySelector('select').value,
            volume_id: tr.querySelector('[data-volume]').value,
            quantidade_caixa: tr.querySelector('.caixas').value,
            peso_liquido: tr.querySelector('.peso').value
        }));

        const pa = [...document.querySelectorAll('#tabela-pa tbody tr')].map(tr => {
            const trSelect = tr.querySelector('select');
            const unidade = tr.querySelector('.produto-unidade').textContent.trim();
            return {
                produto_id: trSelect.value,
                quantidade_caixa: tr.querySelector('.caixas-pa').value,
                quantidade: tr.querySelector('.quantidade-pa').value,
                peso_medio: tr.querySelector('.peso-medio-pa')?.value || 0,
                unidade: unidade
            };
        });

        if (!pa.length) {
            Swal.fire('Atenção', 'Defina o Produto Acabado.', 'warning');
            return;
        }

        if (!mp.length) {
            Swal.fire('Atenção', 'Adicione ao menos uma Matéria-Prima.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Confirmar salvamento',
            text: 'Deseja realmente salvar esta produção?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim',
            cancelButtonText: 'Não',
            confirmButtonColor: '#2e7d32',
            cancelButtonColor: '#9e9e9e',
            reverseButtons: true
        }).then(result => {

            if (!result.isConfirmed) return;

            fetch('../pages/producao/central_producao_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    producao_pa_id: <?= (int) ($producaoId ?? 0) ?> || null,
                    media_final: document.getElementById('media_final').value,
                    status: document.getElementById('status').value,
                    mp,
                    pa
                })
            })
                .then(r => r.json())
                .then(r => {
                    if (r.sucesso) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Salvo',
                            text: 'Produção salva com sucesso',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        const url = `../pages/producao/producao_content.php`;
                        const abaAtiva = $('#dynamicTabContent .tab-pane.active');

                        abaAtiva.load(url, function () {
                            const abaId = abaAtiva.attr('id');
                            $(`#dynamicTabs a[href="#${abaId}"]`)
                                .contents()
                                .first()[0].textContent = `Produções`;
                        });

                    } else {
                        Swal.fire(
                            'Erro',
                            r.mensagem || 'Erro ao salvar a produção',
                            'error'
                        );
                    }
                })
                .catch(err => {
                    console.error('Erro no fetch:', err);
                    Swal.fire(
                        'Erro',
                        'Erro ao salvar produção. Veja o console para detalhes.',
                        'error'
                    );
                });

        });
    }

    // EXCLUIR PRODUÇÃO
    function excluirProducao(id) {

        Swal.fire({
            title: 'Confirmar exclusão',
            text: 'Deseja realmente excluir esta produção?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim',
            cancelButtonText: 'Não',
            confirmButtonColor: '#d32f2f',
            cancelButtonColor: '#9e9e9e',
            reverseButtons: true
        }).then((result) => {

            if (!result.isConfirmed) return;

            fetch('../pages/producao/central_producao_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    acao: 'excluir',
                    producao_pa_id: id
                })
            })
                .then(r => r.json())
                .then(r => {
                    if (r.sucesso) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Excluída',
                            text: 'Produção excluída com sucesso',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        const url = `../pages/producao/producao_content.php`;
                        const abaAtiva = $('#dynamicTabContent .tab-pane.active');

                        abaAtiva.load(url, function () {
                            const abaId = abaAtiva.attr('id');
                            $(`#dynamicTabs a[href="#${abaId}"]`)
                                .contents()
                                .first()[0].textContent = `Produções`;
                        });

                    } else {
                        Swal.fire('Erro', r.mensagem || 'Erro ao excluir', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire(
                        'Erro',
                        'Erro ao excluir produção. Veja o console para detalhes.',
                        'error'
                    );
                });

        });
    }

    // PREENCHE PRODUTO
    function preencherProduto(select) {
        const tr = select.closest('tr');
        const opt = select.options[select.selectedIndex];

        tr.querySelector('.produto-codigo').textContent = opt.value || '-';
        tr.querySelector('.produto-unidade').textContent = opt.dataset.unidade || '-';

        if (tr.closest('#tabela-pa')) {
            let tdPesoMedio = tr.querySelector('.peso-medio-td');
            if (!tdPesoMedio) {
                tdPesoMedio = document.createElement('td');
                tdPesoMedio.classList.add('peso-medio-td');
                tr.insertBefore(tdPesoMedio, tr.querySelector('td:last-child'));
            }

            if (opt.dataset.unidade.toLowerCase() === 'kg') {
                if (!tdPesoMedio.querySelector('.peso-medio-pa')) {
                    const input = document.createElement('input');
                    input.type = 'number';
                    input.step = '0.01';
                    input.classList.add('peso-medio-pa');
                    input.readOnly = true;
                    tdPesoMedio.appendChild(input);
                }
            } else {
                tdPesoMedio.innerHTML = '';
            }


            atualizarPesoMedioPA(tr);
        }

        atualizarMediaFinal();
    }

    // ADD PA
    function addPA() {
        const tbody = document.querySelector('#tabela-pa tbody');
        if (tbody.children.length) {
            alert('A produção permite apenas um Produto Acabado.');
            return;
        }

        tbody.insertAdjacentHTML('beforeend', `
        <tr>
            <td class="produto-codigo">-</td>
            <td><select onchange="preencherProduto(this)">${produtoOptions()}</select></td>
            <td class="produto-unidade">-</td>
            <td><input type="number" class="caixas-pa"></td>
            <td><input type="number" class="quantidade-pa" step="0.01"></td>
            <td class="peso-medio-td"></td>
            <td><button onclick="this.closest('tr').remove(); atualizarMediaFinal();">×</button></td>
        </tr>
    `);

        // Adiciona listener para atualizar peso médio ao mudar caixas ou quantidade
        const tr = tbody.querySelector('tr:last-child');
        tr.querySelector('.caixas-pa').addEventListener('input', atualizarMediaFinal);
        tr.querySelector('.quantidade-pa').addEventListener('input', atualizarMediaFinal);

        // Adiciona listeners corretos
        tr.querySelector('.caixas-pa').addEventListener('input', () => {
            atualizarPesoMedioPA(tr);
            atualizarMediaFinal();
        });

        tr.querySelector('.quantidade-pa').addEventListener('input', () => {
            atualizarPesoMedioPA(tr);
            atualizarMediaFinal();
        });

    }

    // ADD MP
    function addMP() {
        const tbody = document.querySelector('#tabela-mp tbody');
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="produto-codigo">-</td>
                <td><select onchange="preencherProduto(this)">${produtoOptions()}</select></td>
                <td class="produto-unidade">-</td>
                <td><select data-volume onchange="calcularPesoMP(this.closest('tr'))">${volumeOptions()}</select></td>
                <td><input type="number" class="caixas" oninput="calcularPesoMP(this.closest('tr'))"></td>
                <td><input type="number" class="peso" readonly></td>
                <td><button onclick="this.closest('tr').remove(); atualizarMediaFinal();">×</button></td>
            </tr>
        `);

        const tr = tbody.querySelector('tr:last-child');
        tr.querySelector('.caixas-pa').addEventListener('input', () => {
            atualizarPesoMedioPA(tr);
            atualizarMediaFinal();
        });
        tr.querySelector('.quantidade-pa').addEventListener('input', () => {
            atualizarPesoMedioPA(tr);
            atualizarMediaFinal();
        });

    }

    // OPTIONS PRODUTOS
    function produtoOptions() {
        return `<option value="">Selecione</option>` +
            produtos.map(p => `<option value="${p.produto_id}" data-unidade="${p.unidade}">${p.descricao}</option>`).join('');
    }

    // OPTIONS VOLUMES
    function volumeOptions() {
        return `<option value="">Selecione</option>` +
            volumes.map(v => `<option value="${v.volume_id}" data-quantidade="${v.quantidade}">${v.descricao}</option>`).join('');
    }

    // CALCULAR PESO MP
    function calcularPesoMP(tr) {
        const selectVolume = tr.querySelector('[data-volume]');
        const caixas = parseFloat(tr.querySelector('.caixas').value) || 0;
        const peso = tr.querySelector('.peso');

        if (!selectVolume.value || caixas <= 0) {
            peso.value = '';
        } else {
            const quantidadeBase = parseFloat(
                selectVolume.options[selectVolume.selectedIndex].dataset.quantidade
            ) || 0;

            peso.value = (quantidadeBase * caixas).toFixed(2);
        }

        atualizarMediaFinal(); // ✔️ permitido
    }

    // CALCULAR PESO MÉDIO PA
    function atualizarPesoMedioPA(tr) {
        const pesoInput = tr.querySelector('.peso-medio-pa');
        if (!pesoInput) return;

        const quantidade = parseFloat(tr.querySelector('.quantidade-pa').value) || 0;
        const caixas = parseFloat(tr.querySelector('.caixas-pa').value) || 0;

        if (caixas > 0 && quantidade > 0) {
            pesoInput.value = (quantidade / caixas).toFixed(2);
        } else {
            pesoInput.value = '';
        }
    }

    // CALCULAR PESO MÉDIO FINAL
    function atualizarMediaFinal() {
        const paTr = document.querySelector('#tabela-pa tbody tr');
        if (!paTr) return;

        const unidade = paTr.querySelector('.produto-unidade').textContent.trim().toLowerCase();
        const caixas = parseFloat(paTr.querySelector('.caixas-pa').value) || 0;
        const quantidadePA = parseFloat(paTr.querySelector('.quantidade-pa').value) || 0;

        let totalMP = 0;
        document.querySelectorAll('#tabela-mp tbody tr').forEach(tr => {
            totalMP += parseFloat(tr.querySelector('.peso').value) || 0;
        });

        let mediaFinal = '';
        if (unidade === 'kg' && caixas > 0) {
            mediaFinal = (totalMP / caixas).toFixed(2);
        } else if (unidade === 'un' && quantidadePA > 0) {
            mediaFinal = (totalMP / quantidadePA).toFixed(2);
        }

        document.getElementById('media_final').value = mediaFinal;
    }

    // Ao alterar quantidade ou caixas do PA
    document.querySelector('#tabela-pa').addEventListener('input', e => {
        if (e.target.classList.contains('caixas-pa') || e.target.classList.contains('quantidade-pa')) {
            const tr = e.target.closest('tr');
            atualizarPesoMedioPA(tr);      // atualiza apenas a linha do PA
            atualizarMediaFinal();     // atualiza o campo Peso médio final
        }
    });

    // ---------- VOLTAR PARA TELA DE PRODUÇÕES NO MESMO PAINEL ----------
    $(document).on('click', '.btn-VoltarProducao', function () {
        const url = `../pages/producao/producao_content.php`;
        const abaAtiva = $('#dynamicTabContent .tab-pane.active');

        abaAtiva.load(url, function () {
            const abaId = abaAtiva.attr('id');
            $(`#dynamicTabs a[href="#${abaId}"]`)
                .contents()
                .first()[0].textContent = `Produções`;
        });
    });

</script>