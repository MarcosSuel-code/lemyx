<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';

/*
|--------------------------------------------------------------------------
| MODO AJAX – RETORNA DADOS DO RELATÓRIO
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $where = [];
    $params = [];

    if (!empty($_POST['data_inicio'])) {
        $where[] = 'e.data_entrada >= :data_inicio';
        $params[':data_inicio'] = $_POST['data_inicio'];
    }

    if (!empty($_POST['data_fim'])) {
        $where[] = 'e.data_entrada <= :data_fim';
        $params[':data_fim'] = $_POST['data_fim'];
    }

    if (!empty($_POST['fornecedor_id'])) {
        $where[] = 'e.fornecedor_id = :fornecedor_id';
        $params[':fornecedor_id'] = $_POST['fornecedor_id'];
    }

    if (!empty($_POST['produto_id'])) {
        $where[] = 'ep.produtos_id = :produto_id';
        $params[':produto_id'] = $_POST['produto_id'];
    }

    if (!empty($_POST['status'])) {
        $where[] = 'ep.status = :status';
        $params[':status'] = $_POST['status'];
    }

    $sql = "
    SELECT
        e.entrada_id,
        DATE_FORMAT(e.data_entrada, '%d/%m/%Y') AS data_entrada,
        f.nome AS fornecedor,
        p.produto_id AS codigo_produto,
        p.descricao AS produto,

        COALESCE(SUM(c.quantidade_cx), 0) AS quantidade,
        COALESCE(SUM(c.peso_liquido), 0) AS peso_liquido,
        COALESCE(AVG(c.peso_medio), 0) AS peso_medio,

        ep.status
    FROM entradas e
    JOIN fornecedores f ON f.fornecedor_id = e.fornecedor_id
    JOIN entrada_produtos ep ON ep.entradas_id = e.entrada_id
    JOIN produtos p ON p.produto_id = ep.produtos_id
    LEFT JOIN conferencia c ON c.entrada_produtos_id = ep.entrada_produtos_id
";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= "
        GROUP BY e.entrada_id, p.produto_id, ep.status
        ORDER BY e.data_entrada DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    header('Content-Type: application/json');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/*
|--------------------------------------------------------------------------
| MODO TELA – CARREGADO VIA AJAX NA TELA PRINCIPAL
|--------------------------------------------------------------------------
*/
$fornecedores = $pdo->query("
    SELECT fornecedor_id, nome 
    FROM fornecedores 
    ORDER BY nome
")->fetchAll(PDO::FETCH_ASSOC);

$produtos = $pdo->query("
    SELECT produto_id, descricao 
    FROM produtos 
    ORDER BY descricao
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-body">

        <h5 class="mb-4">Relatório de Entradas</h5>

        <form id="formRelatorioEntradas" class="row g-3 mb-4">

            <div class="col-md-3">
                <label class="form-label">Data inicial</label>
                <input type="date" name="data_inicio" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Data final</label>
                <input type="date" name="data_fim" class="form-control">
            </div>

            <div class="col-md-3">
                <label class="form-label">Fornecedor</label>
                <select name="fornecedor_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($fornecedores as $f): ?>
                        <option value="<?= $f['fornecedor_id'] ?>">
                            <?= htmlspecialchars($f['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Todos</option>
                    <option value="PENDENTE">PENDENTE</option>
                    <option value="CONFERIDO">CONFERIDO</option>
                    <option value="CANCELADO">CANCELADO</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Produto</label>
                <select name="produto_id" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($produtos as $p): ?>
                        <option value="<?= $p['produto_id'] ?>">
                            <?= htmlspecialchars($p['descricao']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-12 text-end">
                <button class="btn btn-primary me-2">
                    Gerar relatório
                </button>

                <button type="button" class="btn btn-success me-2" id="exportExcel">
                    Exportar Excel
                </button>

                <button type="button" class="btn btn-danger" id="exportPdf">
                    Exportar PDF
                </button>
            </div>


        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Entrada</th>
                        <th>Data</th>
                        <th>Fornecedor</th>
                        <th>Cód. Produto</th>
                        <th>Produto</th>
                        <th>Qtd CX</th>
                        <th>Peso Líquido</th>
                        <th>Peso Médio</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="resultadoRelatorio"></tbody>
            </table>
        </div>

    </div>
</div>

<script>
    $('#formRelatorioEntradas').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: '../pages/relatorios/relatorio_entradas.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (dados) {

                const tbody = $('#resultadoRelatorio');
                tbody.empty();

                if (!dados.length) {
                    tbody.append(`
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Nenhum registro encontrado
                        </td>
                    </tr>
                `);
                    return;
                }

                dados.forEach(r => {
                    tbody.append(`
                     <tr>
                    <td>${r.entrada_id}</td>
                    <td>${r.data_entrada}</td>
                    <td>${r.fornecedor}</td>
                    <td>${r.codigo_produto}</td>
                    <td>${r.produto}</td>
                    <td>${r.quantidade}</td>
                    <td>${parseFloat(r.peso_liquido).toFixed(2)}</td>
                    <td>${parseFloat(r.peso_medio).toFixed(2)}</td>
                    <td>${r.status}</td>
                    </tr>
                    `);
                });

            }
        });
    });

</script>