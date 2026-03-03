<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/Controller/config/database.php';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario']);
    $senha = $_POST['senha'];

    if (!empty($usuario) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['perfil'] = $user['perfil'];
            $_SESSION['usuario_id'] = $user['usuario_id'];
            $_SESSION['email'] = $user['email'];
            header("Location: pages/inicial.php");
            exit;
        }
    }

    $erro = "Usuário ou senha inválidos";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - Controller</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body, html {
      height: 100%;
    }

    .login-container {
      min-height: 100vh;
    }

    .left-side {
      background: url('assets/img/login.jpg') no-repeat center center;
      background-size: cover;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem;
    }

    .right-side {
      background-color: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-form {
      width: 100%;
      max-width: 320px;
    }
  </style>
</head>
<body>

<!-- Navbar com botão de configuração -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Controller</a>
    <div class="ms-auto">
      <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#configModal">⚙️ Configurações</button>
    </div>
  </div>
</nav>

<!-- Conteúdo Principal -->
<div class="container-fluid login-container d-flex">
  <div class="row w-100">
    <!-- Lado Esquerdo (Imagem ou Apresentação) -->
    <div class="col-md-8 left-side">
      <div>
        <h1>Bem-vindo ao Controller</h1>
        <p>Gerencie sua empresa com eficiência e segurança.</p>
      </div>
    </div>

    <!-- Lado Direito (Formulário de Login) -->
    <div class="col-md-4 right-side">
      <form class="login-form" method="POST" action="index.php">
        <h2 class="mb-4">Login</h2>
        <div class="mb-3">
          <input type="text" name="usuario" class="form-control" placeholder="Usuário" required>
        </div>
        <div class="mb-3">
          <input type="password" name="senha" class="form-control" placeholder="Senha" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar</button>
        <?php if (isset($erro)) echo "<p class='text-danger mt-2'>$erro</p>"; ?>
      </form>
    </div>
  </div>
</div>

<!-- Modal de Configuração -->
<div class="modal fade" id="configModal" tabindex="-1" aria-labelledby="configModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="configModalLabel">⚙️ Configurações do Servidor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <label for="serverURL" class="form-label">Endereço do Servidor:</label>
        <input type="text" class="form-control" id="serverURL" placeholder="http://localhost:8000">
        <small class="text-muted">Salve e recarregue para aplicar.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary">Salvar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

