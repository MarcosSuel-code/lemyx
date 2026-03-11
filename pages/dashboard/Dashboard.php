<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    exit('Acesso negado');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/lemyx/config/database.php';

/*
|--------------------------------------------------------------------------
| KPIs PRINCIPAIS
|--------------------------------------------------------------------------
*/
$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
$totalCategorias = $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
$totalParceiros = $pdo->query("SELECT COUNT(*) FROM parceiros")->fetchColumn();

$totalProdutosAtivos = $pdo->query("
    SELECT COUNT(*) FROM produtos WHERE ativo = 1
")->fetchColumn();

$totalProdutosInativos = $pdo->query("
    SELECT COUNT(*) FROM produtos WHERE ativo = 0
")->fetchColumn();

$totalVinculos = $pdo->query("
    SELECT COUNT(*) FROM calibre_produto
")->fetchColumn();

$entradasAbertas = $pdo->query("
    SELECT COUNT(*) FROM entradas WHERE status = 'aberta'
")->fetchColumn();

$entradasConcluidas = $pdo->query("
    SELECT COUNT(*) FROM entradas WHERE status = 'concluida'
")->fetchColumn();

/*
|--------------------------------------------------------------------------
| PRODUTOS POR CATEGORIA
|--------------------------------------------------------------------------
*/
$stmt = $pdo->query("
    SELECT 
        c.nome AS categoria,
        COUNT(p.produto_id) AS total
    FROM categorias c
    LEFT JOIN produtos p ON p.categoria_id = c.categoria_id
    GROUP BY c.categoria_id, c.nome
    ORDER BY total DESC
");
$produtosPorCategoria = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| ÚLTIMAS ENTRADAS
|--------------------------------------------------------------------------
*/
$ultimasEntradas = $pdo->query("
    SELECT 
        e.entrada_id,
        p.nome AS parceiros,
        e.data_entrada,
        e.status
    FROM entradas e
    JOIN parceiros p ON p.parceiro_id = e.parceiro_id
    ORDER BY e.data_entrada DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

/* Dados para gráfico */
$labelsCategorias = array_column($produtosPorCategoria, 'categoria');
$valoresCategorias = array_column($produtosPorCategoria, 'total');
?>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/pages/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<div class="container-fluid">

    <!-- HEADER -->
    <div class="row mb-4">
        <div class="col">
            <h4 class="section-title mb-1">Visão Geral</h4>
            <small class="text-muted">Resumo operacional do sistema</small>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-4 mb-5">

        <!-- Produtos ativos -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Produtos ativos</div>
                        <div class="kpi-value"><?= $totalProdutosAtivos ?></div>
                    </div>
                    <i class="fa-solid fa-box kpi-icon text-primary"></i>
                </div>
            </div>
        </div>

        <!-- Produtos inativos -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Produtos inativos</div>
                        <div class="kpi-value"><?= $totalProdutosInativos ?></div>
                    </div>
                    <i class="fa-solid fa-box-open kpi-icon text-danger"></i>
                </div>
            </div>
        </div>

        <!-- Categorias -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Categorias</div>
                        <div class="kpi-value"><?= $totalCategorias ?></div>
                    </div>
                    <i class="fa-solid fa-tags kpi-icon text-success"></i>
                </div>
            </div>
        </div>

        <!-- Parceiros -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Parceiros</div>
                        <div class="kpi-value"><?= $totalParceiros ?></div>
                    </div>
                    <i class="fa-solid fa-truck kpi-icon text-warning"></i>
                </div>
            </div>
        </div>

        <!-- Vínculos calibre × produto -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Vínculos calibre × produto</div>
                        <div class="kpi-value"><?= $totalVinculos ?></div>
                    </div>
                    <i class="fa-solid fa-link kpi-icon text-info"></i>
                </div>
            </div>
        </div>

        <!-- Entradas abertas -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Entradas abertas</div>
                        <div class="kpi-value"><?= $entradasAbertas ?></div>
                    </div>
                    <i class="fa-solid fa-folder-open kpi-icon text-primary"></i>
                </div>
            </div>
        </div>

        <!-- Entradas concluídas -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Entradas concluídas</div>
                        <div class="kpi-value"><?= $entradasConcluidas ?></div>
                    </div>
                    <i class="fa-solid fa-check-circle kpi-icon text-success"></i>
                </div>
            </div>
        </div>

        <!-- Sessão -->
        <div class="col-md-3 col-sm-6">
            <div class="card kpi-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="kpi-label">Sessão</div>
                        <div class="kpi-value text-success">Ativa</div>
                    </div>
                    <i class="fa-solid fa-user-shield kpi-icon text-warning"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- GRÁFICO + TABELA -->
    <div class="row g-4 mb-5">

        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="section-title mb-3">Produtos por categoria</h5>
                <canvas id="graficoCategorias" height="140"></canvas>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="section-title mb-3">Distribuição detalhada</h5>

                <div class="table-responsive" style="max-height: 45vh;">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Categoria</th>
                                <th class="text-end">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtosPorCategoria as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['categoria']) ?></td>
                                    <td class="text-end fw-semibold"><?= $row['total'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$produtosPorCategoria): ?>
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-4">
                                        Nenhum dado disponível
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>

    <!-- ÚLTIMAS ENTRADAS -->
    <div class="row">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="section-title mb-3">Últimas entradas</h5>

                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Parceiros</th>
                            <th>Data</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimasEntradas as $e): ?>
                            <tr>
                                <td><?= htmlspecialchars($e['parceiros']) ?></td>
                                <td><?= date('d/m/Y', strtotime($e['data_entrada'])) ?></td>
                                <td>
                                    <?php
                                    $badgeClass = match ($e['status']) {
                                        'aberta' => 'bg-warning',
                                        'concluida' => 'bg-success',
                                        default => 'bg-secondary',
                                    };
                                    ?>

                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($e['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$ultimasEntradas): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">
                                    Nenhuma entrada registrada
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            </div>
        </div>
    </div>

</div>

<script>
    new Chart(document.getElementById('graficoCategorias'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsCategorias) ?>,
            datasets: [{
                label: 'Produtos',
                data: <?= json_encode($valoresCategorias) ?>
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });
</script>