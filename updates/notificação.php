<?php if (count($produtosBaixoEstoque) > 0): ?>
    <div class="alert">
        <h3>⚠️ Atenção! Produtos com estoque baixo:</h3>
        <ul>
            <?php foreach($produtosBaixoEstoque as $p): ?>
                <li><?= htmlspecialchars($p['nome']) ?> — Estoque: <?= $p['estoque'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
