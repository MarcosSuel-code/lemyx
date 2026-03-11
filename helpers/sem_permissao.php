<?php
// sem_permissao.php

$tela_origem = $_GET['tela_origem'] ?? 'inicial';

// Detecta se é requisição AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
?>

<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Acesso Negado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html { height: 100%; margin: 0; background: #f8f9fa; display: flex; justify-content: center; align-items: center; }
        .card { padding: 2rem; text-align: center; max-width: 400px; width: 90%; }
    </style>
</head>
<body>
<?php endif; ?>

<div class="card shadow text-center">
    <h1 class="text-danger mb-3">Acesso Negado</h1>
    <p class="mb-3">Você não possui permissão para visualizar esta tela.</p>
    <button class="btn btn-primary" id="btnVoltar">Voltar</button>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function () {
        const telaOrigem = '<?= $tela_origem ?>';

        $('#btnVoltar').on('click', function () {
            // Se a função carregarTela estiver definida (AJAX)
            if (typeof carregarTela === 'function') {
                carregarTela(telaOrigem);
            } else {
                // fallback: redireciona
                window.location.href = '../pages/' + telaOrigem + '.php';
            }
        });
    });
</script>

<?php if (!$isAjax): ?>
</body>
</html>
<?php endif; ?>