<?php
require_once 'config/database.php';

$usuario = 'novo_usuario';
$senha = 'senha123';

// Gera o hash da senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Insere no banco
$sql = "INSERT INTO usuarios (usuario, senha, perfil) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$usuario, $senhaHash, 'perfil_exemplo']);

echo "Usuário criado com sucesso!";
?>
