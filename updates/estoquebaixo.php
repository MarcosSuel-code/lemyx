$produtosBaixoEstoque = $pdo->query("SELECT * FROM produtos WHERE estoque <= 5")->fetchAll();
